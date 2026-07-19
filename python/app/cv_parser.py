"""
CV text extraction + NLP skill/title extraction.
"""

from __future__ import annotations

import re
from pathlib import Path
from typing import Any

from .skills_lexicon import SKILL_LEXICON, TITLE_HINTS

_nlp = None


def get_nlp():
    """Load spaCy English model once (lazy). Falls back to None if unavailable."""
    global _nlp
    if _nlp is not False and _nlp is not None:
        return _nlp
    if _nlp is False:
        return None
    try:
        import spacy

        try:
            _nlp = spacy.load("en_core_web_sm")
        except OSError:
            from spacy.cli import download

            download("en_core_web_sm")
            _nlp = spacy.load("en_core_web_sm")
        return _nlp
    except Exception:
        _nlp = False
        return None


def extract_text_from_file(path: str) -> str:
    file_path = Path(path)
    if not file_path.is_file():
        raise FileNotFoundError(f"File not found: {path}")

    suffix = file_path.suffix.lower()
    if suffix == ".pdf":
        return _extract_pdf(file_path)
    if suffix in {".docx"}:
        return _extract_docx(file_path)
    if suffix == ".doc":
        raise ValueError("Legacy .doc is not supported. Please upload PDF or DOCX.")
    raise ValueError(f"Unsupported file type: {suffix}")


def _extract_pdf(path: Path) -> str:
    import pdfplumber

    chunks: list[str] = []
    with pdfplumber.open(path) as pdf:
        for page in pdf.pages:
            text = page.extract_text() or ""
            if text.strip():
                chunks.append(text)
    return "\n".join(chunks).strip()


def _extract_docx(path: Path) -> str:
    from docx import Document

    doc = Document(str(path))
    parts = [p.text.strip() for p in doc.paragraphs if p.text and p.text.strip()]
    return "\n".join(parts).strip()


def normalize_text(text: str) -> str:
    text = text.replace("\x00", " ")
    text = re.sub(r"[ \t]+", " ", text)
    text = re.sub(r"\n{3,}", "\n\n", text)
    return text.strip()


def extract_skills_and_titles(text: str) -> dict[str, Any]:
    cleaned = normalize_text(text)
    lower = cleaned.lower()

    nlp = get_nlp()
    lemma_blob = lower
    if nlp is not None:
        doc = nlp(cleaned[:100000])  # safety cap
        lemma_blob = " ".join(tok.lemma_.lower() for tok in doc if not tok.is_space)

    search_space = f"{lower}\n{lemma_blob}"

    found_skills: list[str] = []
    for skill in sorted(SKILL_LEXICON, key=len, reverse=True):
        pattern = r"(?<![a-z0-9])" + re.escape(skill.lower()) + r"(?![a-z0-9])"
        if re.search(pattern, search_space, flags=re.IGNORECASE):
            # Prefer canonical casing from lexicon
            label = skill
            if label not in found_skills and label.lower() not in {s.lower() for s in found_skills}:
                found_skills.append(label)

    found_titles: list[str] = []
    for title in TITLE_HINTS:
        pattern = r"(?<![a-z0-9])" + re.escape(title.lower()) + r"(?![a-z0-9])"
        if re.search(pattern, lower, flags=re.IGNORECASE):
            found_titles.append(title)

    # Deduplicate titles preserving order
    seen = set()
    titles: list[str] = []
    for t in found_titles:
        key = t.lower()
        if key not in seen:
            seen.add(key)
            titles.append(t.title() if " " in t or "/" in t else t)

    return {
        "skills": found_skills[:40],
        "titles": titles[:8],
        "engine": "spacy+lexicon" if nlp is not None else "lexicon",
        "char_count": len(cleaned),
    }


def parse_cv_file(path: str) -> dict[str, Any]:
    raw = extract_text_from_file(path)
    cleaned = normalize_text(raw)
    if not cleaned:
        return {
            "success": False,
            "error": "Could not extract text from this CV. Try a text-based PDF or DOCX.",
            "text": "",
            "skills": [],
            "titles": [],
        }

    extracted = extract_skills_and_titles(cleaned)
    preview = cleaned if len(cleaned) <= 1200 else cleaned[:1200] + "…"

    return {
        "success": True,
        "text": cleaned,
        "text_preview": preview,
        "skills": extracted["skills"],
        "titles": extracted["titles"],
        "engine": extracted["engine"],
        "char_count": extracted["char_count"],
    }
