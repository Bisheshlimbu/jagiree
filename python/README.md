# Jagiree NLP service (Python)

CV parsing with **spaCy + skill lexicon**, PDF text via **pdfplumber**, job ranking with **scikit-learn TF-IDF + cosine similarity**.

Jagiree PHP (chat CV upload + “Recommend jobs for me”) calls this service at `NLP_SERVICE_URL` (default `http://127.0.0.1:8001`).

## Setup (once)

### macOS / Linux

```bash
cd python
python3 -m venv .venv
source .venv/bin/activate
pip install -r requirements.txt
python -m spacy download en_core_web_sm
```

### Windows

```bash
cd python
python -m venv .venv
.venv\Scripts\activate
pip install -r requirements.txt
python -m spacy download en_core_web_sm
```

## Run

Keep this terminal open while using AI Chat.

### macOS / Linux

```bash
cd python
source .venv/bin/activate
uvicorn app.main:app --host 127.0.0.1 --port 8001
```

### Windows

```bash
cd python
.venv\Scripts\activate
uvicorn app.main:app --host 127.0.0.1 --port 8001
```

### One-liner (from project root)

**macOS / Linux**

```bash
cd python && source .venv/bin/activate && uvicorn app.main:app --host 127.0.0.1 --port 8001
```

**Windows**

```bash
cd python && .venv\Scripts\activate && uvicorn app.main:app --host 127.0.0.1 --port 8001
```

## Health check

Open [http://127.0.0.1:8001/health](http://127.0.0.1:8001/health) — you should see a successful response.

If chat says **“NLP service is offline”**, this process is not running (or the URL in `.env` / Admin settings does not match).

## Endpoints

| Method | Path | Purpose |
|--------|------|---------|
| `GET` | `/health` | Service status |
| `POST` | `/parse-cv` | Extract text + skills from a CV file |
| `POST` | `/recommend` | Rank jobs with TF-IDF cosine similarity |
