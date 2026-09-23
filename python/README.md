# Jagiree NLP service (Python)

CV parsing with **spaCy + skill lexicon**, PDF text via **pdfplumber**, job ranking with **scikit-learn TF-IDF + cosine similarity**.

Jagiree PHP (chat CV upload + “Recommend jobs for me”) calls this service at `NLP_SERVICE_URL` (default `http://127.0.0.1:8001`).

## Setup (once)

### Install Python first

You need **Python 3.10–3.12** (recommended) or **3.13**.

- **Best on Windows:** install **Python 3.12** from [python.org/downloads](https://www.python.org/downloads/)  
  (check **“Add python.exe to PATH”**)
- Avoid the Microsoft Store stub. If you see *“Python was not found… Microsoft Store”*, disable App execution aliases for `python.exe` / `python3.exe`.
- After installing, close and reopen PowerShell, then verify: `py --version`

### macOS / Linux

```bash
cd python
python3 -m venv .venv
source .venv/bin/activate
pip install -r requirements.txt
python -m spacy download en_core_web_sm
```

### Windows (PowerShell)

Prefer creating the venv with **Python 3.12** if you have several versions:

```powershell
cd python
py -3.12 -m venv .venv
.\.venv\Scripts\Activate.ps1
python -m pip install --upgrade pip
python -m pip install -r requirements.txt
python -m spacy download en_core_web_sm
```

If you only have 3.13, the same commands work with the updated `requirements.txt` (use `py -3.13` or just `py`).

If an old broken `.venv` exists, delete it first:

```powershell
Remove-Item -Recurse -Force .venv
```

Then recreate with the steps above.

If `Activate.ps1` is blocked:

```powershell
Set-ExecutionPolicy -Scope CurrentUser RemoteSigned
```

Then run `.\.venv\Scripts\Activate.ps1` again.

## Run

Keep this terminal open while using AI Chat.

### macOS / Linux

```bash
cd python
source .venv/bin/activate
uvicorn app.main:app --host 127.0.0.1 --port 8001
```

### Windows

```powershell
cd python
.\.venv\Scripts\Activate.ps1
python -m uvicorn app.main:app --host 127.0.0.1 --port 8001
```

Without activating the venv:

```powershell
cd python
.\.venv\Scripts\python.exe -m uvicorn app.main:app --host 127.0.0.1 --port 8001
```

### One-liner (from project root)

**macOS / Linux**

```bash
cd python && source .venv/bin/activate && uvicorn app.main:app --host 127.0.0.1 --port 8001
```

**Windows**

```powershell
cd python
.\.venv\Scripts\python.exe -m uvicorn app.main:app --host 127.0.0.1 --port 8001
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
