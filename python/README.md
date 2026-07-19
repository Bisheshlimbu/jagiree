# Jagiree NLP service (Python)

CV parsing with **spaCy + skill lexicon**, PDF text via **pdfplumber**, job ranking with **scikit-learn TF-IDF**.

## Setup (once)

```bash
cd python
python3 -m venv .venv
source .venv/bin/activate
pip install -r requirements.txt
python -m spacy download en_core_web_sm
```

## Run

```bash
cd python
source .venv/bin/activate
uvicorn app.main:app --host 127.0.0.1 --port 8001
```

Health check: http://127.0.0.1:8001/health

Jagiree PHP calls this service at `NLP_SERVICE_URL` (default `http://127.0.0.1:8001`).
