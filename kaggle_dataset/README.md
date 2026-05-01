# RuteStrip Indonesian Hiking Routes GPX Features and SBERT Embeddings

RuteStrip Indonesian Hiking Routes GPX Features and SBERT Embeddings is a processed dataset of Indonesian hiking routes extracted from GPX track data. The dataset contains route-level hiking statistics, curated Indonesian route narratives, and 384-dimensional SBERT embeddings for semantic search and recommendation system experiments.

## Dataset Overview

- Number of routes: 39
- Country coverage: Indonesia
- Data source type: processed GPX hiking tracks
- Text language: Indonesian
- Embedding model: `sentence-transformers/paraphrase-multilingual-MiniLM-L12-v2`
- Embedding dimensions: 384
- Recommended license: CC BY 4.0

## Files

| File | Description |
| --- | --- |
| `rutestrip_hiking_routes.csv` | Main tabular dataset with one row per hiking route. |
| `rutestrip_hiking_route_embeddings.csv` | SBERT embedding vectors with 384 dimensions per route. |
| `rutestrip_hiking_routes_full.json` | Full JSON records including route metadata and embedding arrays. |
| `data_dictionary.csv` | Column descriptions and explanations. |
| `dataset-metadata.json` | Optional Kaggle CLI metadata. |
| `kaggle_upload_copy.txt` | Copy-ready Kaggle title, subtitle, description, tags, and license. |

## Main Columns

| Column | Description |
| --- | --- |
| `route_id` | Unique route identifier from the processed dataset. |
| `route_name` | Cleaned hiking route name. |
| `original_name` | Original processed file-derived route name. |
| `mountain` | Inferred mountain name. |
| `province` | Inferred Indonesian province or cross-province area where identifiable. |
| `distance_km` | 3D GPX track distance in kilometers. |
| `elevation_gain_m` | Cumulative positive elevation gain in meters. |
| `naismith_duration_hour` | Estimated hiking duration using Naismith's rule. |
| `average_grade_pct` | Average route grade percentage. |
| `min_elevation_m` | Minimum elevation found in GPX track points. |
| `max_elevation_m` | Maximum elevation found in GPX track points. |
| `difficulty` | Route difficulty label: `mudah`, `sedang`, `sulit`, or `sangat sulit`. |
| `total_trackpoints` | Number of GPX track points used in processing. |
| `manual_description` | Human-authored or curated route description where available. |
| `narrative_text` | Combined Indonesian text used for semantic embedding. |
| `embedding_model` | SentenceTransformer model used to encode the narrative text. |
| `embedding_dimension` | Number of dimensions in the embedding vector. |

## Feature Extraction

The dataset was generated from processed GPX tracks. The extraction pipeline computes route features from track points:

- Distance: computed from GPX 3D track length and converted to kilometers.
- Elevation gain: cumulative sum of positive elevation differences.
- Estimated duration: Naismith's rule, `distance_km / 5 + elevation_gain_m / 600`.
- Average grade: `elevation_gain_m / (distance_km * 1000) * 100`.
- Difficulty label: derived from average grade.
- Narrative text: Indonesian description combining route characteristics and curated descriptions.
- Embedding: SBERT vector generated from the route narrative text.

## Suggested Use Cases

- Semantic hiking route search
- Hiking route recommendation systems
- GPX feature analysis
- Indonesian NLP retrieval experiments
- Route similarity and clustering
- Difficulty classification experiments
- Geospatial and outdoor activity data exploration

## Example Usage

```python
import pandas as pd

routes = pd.read_csv('/kaggle/input/rutestrip-indonesian-hiking-routes-gpx-sbert/rutestrip_hiking_routes.csv')
embeddings = pd.read_csv('/kaggle/input/rutestrip-indonesian-hiking-routes-gpx-sbert/rutestrip_hiking_route_embeddings.csv')

print(routes.head())
print(routes[['route_name', 'distance_km', 'elevation_gain_m', 'difficulty']].head())
```

## Tags

`hiking`, `gpx`, `indonesia`, `mountain`, `recommendation-system`, `sbert`, `nlp`, `semantic-search`

## Citation

If you use this dataset, please cite or credit the RuteStrip project and the dataset author according to the selected Kaggle license.

## Notes

- Province and mountain fields are inferred from route names where possible.
- The original processed route name is retained in `original_name` for traceability.
- The dataset contains processed features and embeddings, not the raw GPX files.
