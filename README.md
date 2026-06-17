# RuteStrip V3

RuteStrip adalah aplikasi Laravel untuk manajemen, pencarian, dan rekomendasi jalur pendakian berbasis file GPX. Aplikasi memakai SBERT untuk embedding/search semantic, Google Gemini untuk chatbot RAG, serta modul provenance GPX dengan IPFS dan blockchain registry.

## Fitur

- Upload dan parsing file GPX.
- Ekstraksi jarak, elevasi, estimasi durasi Naismith, grade, dan koordinat rute.
- Search semantic berbasis SBERT `paraphrase-multilingual-MiniLM-L12-v2`.
- Rekomendasi rute menggunakan cosine similarity.
- Chatbot RAG berbasis Google Gemini.
- Login/register user dan dashboard admin.
- GPX provenance: versioning, hashing, verifikasi integritas, IPFS, dan blockchain registry.
- Python processor bisa berjalan lokal atau remote via Hugging Face Spaces/FastAPI.

## Arsitektur Deploy

```txt
User Browser
  -> Laravel App di cPanel/VPS
  -> MySQL/MariaDB
  -> Python Processor lokal atau Hugging Face Space
  -> Gemini API / Pinata IPFS / Blockchain RPC bila fitur terkait dipakai
```

Mode yang direkomendasikan untuk shared cPanel:

```txt
cPanel: Laravel, Blade UI, auth, upload, database
Hugging Face Space: Python, SBERT, GPX processing, embedding
MySQL: data aplikasi dan embedding hasil proses
```

## Tech Stack

- Laravel 12, PHP 8.2+
- Blade, Vite, Tailwind CSS
- MySQL/MariaDB
- Python, FastAPI, SBERT, scikit-learn, gpxpy
- Google Gemini API
- Pinata/IPFS
- Hardhat, ethers.js, Solidity

## Setup Lokal

```bash
git clone https://github.com/RamsNotes31/RuteStripV3.git
cd RuteStripV3
composer install
npm install
npm run build
cp .env.example .env
php artisan key:generate
php artisan migrate
```

Jalankan Laravel:

```bash
php artisan serve
```

Jalankan Python processor lokal:

```bash
cd python
pip install -r requirements.txt
uvicorn api:app --host 127.0.0.1 --port 7860
```

Set `.env` untuk memakai Python processor lokal:

```env
PYTHON_PROCESSOR_URL=http://127.0.0.1:7860
```

Kalau `PYTHON_PROCESSOR_URL` dikosongkan, Laravel fallback ke proses lokal `python/processor.py` lewat `PYTHON_PATH`.

## Environment Penting

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://domainmu.com

DB_CONNECTION=mysql
DB_HOST=localhost
DB_DATABASE=nama_database
DB_USERNAME=user_database
DB_PASSWORD=password_database

PYTHON_PATH=python
PYTHON_PROCESSOR_URL=

GEMINI_API_KEY=
PINATA_JWT=
PINATA_API_URL=https://api.pinata.cloud
IPFS_GATEWAY_URL=https://gateway.pinata.cloud/ipfs/

BLOCKCHAIN_RPC_URL=
BLOCKCHAIN_PRIVATE_KEY=
BLOCKCHAIN_CONTRACT_ADDRESS=
BLOCKCHAIN_NETWORK=
NODE_PATH=node
ETHERSCAN_API_KEY=
```

## Deploy Ke cPanel

1. Clone repository melalui cPanel Git Version Control.
2. Set document root domain/subdomain ke folder `public`.
3. Buat database MySQL dan update `.env`.
4. Jalankan command berikut melalui Terminal/SSH cPanel:

```bash
composer install --no-dev --optimize-autoloader
npm install
npm run build
php artisan key:generate
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Untuk shared cPanel, gunakan Hugging Face Space untuk Python processor dan isi:

```env
PYTHON_PROCESSOR_URL=https://username-rutestrip-processor.hf.space
```

## Deploy Python Processor Ke Hugging Face Space

Gunakan Hugging Face Space dengan SDK Docker atau Python/FastAPI. Minimal file yang dibutuhkan dari repo ini:

- `python/api.py`
- `python/processor.py`
- `python/requirements.txt`

Start command:

```bash
uvicorn api:app --host 0.0.0.0 --port 7860
```

Endpoint yang tersedia:

- `GET /` health check
- `POST /ingest` upload GPX multipart field `gpx_file`
- `POST /embed` JSON `{ "query": "..." }`
- `POST /search` JSON `{ "query": "...", "routes": [...] }`

## Akun Default

Admin seeder membuat akun admin untuk login `/admin/login`. Jalankan seeder terkait bila database masih kosong.

```bash
php artisan db:seed --class=AdminSeeder
```

## Catatan

- Jangan commit `.env` karena berisi secret.
- Shared cPanel biasanya tidak cocok menjalankan SBERT langsung karena RAM, timeout, dan `exec()` bisa dibatasi.
- Hugging Face free Space bisa sleep, jadi request pertama dapat lambat.
- Untuk production stabil, pakai VPS/Railway/Render atau Hugging Face Space berbayar untuk Python processor.
