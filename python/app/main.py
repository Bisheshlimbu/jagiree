"""
Jagiree NLP service — CV parsing + TF-IDF job recommendations.
Run: uvicorn app.main:app --reload --port 8001
"""

from __future__ import annotations

from typing import Any

from fastapi import FastAPI, HTTPException
from pydantic import BaseModel, Field

from .cv_parser import parse_cv_file
from .recommender import recommend_jobs

app = FastAPI(
    title="Jagiree NLP Service",
    description="CV text extraction (spaCy + lexicon) and TF-IDF job ranking",
    version="1.0.0",
)


class ParseCvRequest(BaseModel):
    path: str = Field(..., description="Absolute path to PDF/DOCX on the same machine")


class RecommendRequest(BaseModel):
    skills: list[str] = Field(default_factory=list)
    titles: list[str] = Field(default_factory=list)
    query: str = ""
    cv_text: str = ""
    jobs: list[dict[str, Any]] = Field(default_factory=list)
    limit: int = 5


@app.get("/health")
def health() -> dict[str, Any]:
    from .cv_parser import get_nlp

    nlp = get_nlp()
    return {
        "ok": True,
        "service": "jagiree-nlp",
        "spacy_loaded": nlp is not None,
    }


@app.post("/parse-cv")
def parse_cv(body: ParseCvRequest) -> dict[str, Any]:
    try:
        result = parse_cv_file(body.path)
    except FileNotFoundError as exc:
        raise HTTPException(status_code=404, detail=str(exc)) from exc
    except ValueError as exc:
        raise HTTPException(status_code=422, detail=str(exc)) from exc
    except Exception as exc:  # noqa: BLE001
        raise HTTPException(status_code=500, detail=f"CV parse failed: {exc}") from exc

    if not result.get("success"):
        raise HTTPException(status_code=422, detail=result.get("error") or "Parse failed")

    return result


@app.post("/recommend")
def recommend(body: RecommendRequest) -> dict[str, Any]:
    ranked = recommend_jobs(
        jobs=body.jobs,
        skills=body.skills,
        titles=body.titles,
        query=body.query,
        cv_text=body.cv_text,
        limit=body.limit,
    )
    return {
        "success": True,
        "engine": "tfidf-cosine",
        "count": len(ranked),
        "jobs": ranked,
    }
