from __future__ import annotations

import os
import tempfile

from fastapi import FastAPI, File, HTTPException, UploadFile
from pydantic import BaseModel

from processor import mode_embed, mode_ingest, mode_search


app = FastAPI(title="RuteStrip Python Processor")


class EmbedRequest(BaseModel):
    query: str


class SearchRequest(BaseModel):
    query: str
    routes: list[dict]


@app.get("/")
def health() -> dict:
    return {"success": True, "service": "rutestrip-python-processor"}


@app.post("/ingest")
async def ingest(gpx_file: UploadFile = File(...)) -> dict:
    suffix = os.path.splitext(gpx_file.filename or "route.gpx")[1] or ".gpx"

    with tempfile.NamedTemporaryFile(delete=False, suffix=suffix) as tmp:
        tmp.write(await gpx_file.read())
        tmp_path = tmp.name

    try:
        result = mode_ingest(tmp_path)
    finally:
        os.unlink(tmp_path)

    if not result.get("success"):
        raise HTTPException(status_code=422, detail=result.get("error", "GPX ingest failed"))

    return result


@app.post("/search")
def search(payload: SearchRequest) -> dict:
    result = mode_search(payload.query, payload.routes)
    if not result.get("success"):
        raise HTTPException(status_code=422, detail=result.get("error", "Search failed"))

    return result


@app.post("/embed")
def embed(payload: EmbedRequest) -> dict:
    result = mode_embed(payload.query)
    if not result.get("success"):
        raise HTTPException(status_code=422, detail=result.get("error", "Embedding failed"))

    return result
