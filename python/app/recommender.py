"""
Job recommendation ranking with TF-IDF + cosine similarity (NLP).
"""

from __future__ import annotations

from typing import Any

from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.metrics.pairwise import cosine_similarity


def _job_document(job: dict[str, Any]) -> str:
    parts = [
        str(job.get("title") or ""),
        str(job.get("company") or ""),
        str(job.get("location") or ""),
        str(job.get("skills") or ""),
        str(job.get("description") or "")[:2000],
    ]
    return " ".join(p for p in parts if p).strip().lower()


def _profile_document(skills: list[str], titles: list[str], query: str, cv_text: str) -> str:
    chunks = [
        " ".join(skills),
        " ".join(titles),
        query or "",
        (cv_text or "")[:3000],
    ]
    return " ".join(c for c in chunks if c).strip().lower()


def recommend_jobs(
    jobs: list[dict[str, Any]],
    skills: list[str] | None = None,
    titles: list[str] | None = None,
    query: str = "",
    cv_text: str = "",
    limit: int = 5,
) -> list[dict[str, Any]]:
    if not jobs:
        return []

    skills = skills or []
    titles = titles or []
    profile_doc = _profile_document(skills, titles, query, cv_text)
    if not profile_doc:
        profile_doc = "job seeker"

    docs = [profile_doc] + [_job_document(job) for job in jobs]

    vectorizer = TfidfVectorizer(
        stop_words="english",
        ngram_range=(1, 2),
        min_df=1,
        max_features=5000,
    )
    matrix = vectorizer.fit_transform(docs)
    scores = cosine_similarity(matrix[0:1], matrix[1:]).flatten()

    ranked: list[dict[str, Any]] = []
    for idx, job in enumerate(jobs):
        score = float(scores[idx]) if idx < len(scores) else 0.0
        # Map cosine [0,1] roughly to percentage for UI
        match_pct = int(min(99, max(0, round(score * 100))))
        item = dict(job)
        item["nlp_score"] = round(score, 4)
        item["match"] = match_pct
        ranked.append(item)

    ranked.sort(key=lambda j: (j.get("nlp_score", 0), j.get("match", 0)), reverse=True)
    return ranked[: max(1, min(limit, 20))]
