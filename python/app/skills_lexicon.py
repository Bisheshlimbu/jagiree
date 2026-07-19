"""
Common tech / design / business skill phrases for CV NLP extraction.
Matched as whole phrases (case-insensitive) after spaCy tokenization cleanup.
"""

SKILL_LEXICON = [
    # Programming / tech
    "python", "php", "java", "javascript", "typescript", "react", "react.js", "reactjs",
    "node", "node.js", "nodejs", "laravel", "django", "flask", "fastapi",
    "html", "css", "sass", "tailwind", "bootstrap", "vue", "vue.js", "angular",
    "mysql", "postgresql", "mongodb", "sqlite", "sql", "redis",
    "git", "github", "docker", "kubernetes", "aws", "azure", "linux",
    "rest api", "graphql", "api", "ajax", "jquery",
    "c++", "c#", ".net", "golang", "go", "ruby", "rails", "swift", "kotlin",
    "machine learning", "deep learning", "nlp", "data science", "pandas", "numpy",
    "tensorflow", "pytorch", "opencv",
    # Design / product
    "figma", "adobe xd", "photoshop", "illustrator", "sketch", "invision",
    "ui", "ux", "ui/ux", "ui design", "ux design", "user research", "wireframing",
    "prototyping", "graphic design", "product design", "interaction design",
    "canva", "after effects", "premiere pro",
    # Soft / business
    "communication", "teamwork", "leadership", "project management", "agile", "scrum",
    "seo", "digital marketing", "content writing", "copywriting", "excel",
    "power bi", "tableau", "salesforce", "customer service", "problem solving",
]

TITLE_HINTS = [
    "ui/ux designer", "ui designer", "ux designer", "product designer", "graphic designer",
    "frontend developer", "backend developer", "full stack developer", "fullstack developer",
    "software engineer", "software developer", "web developer", "mobile developer",
    "data analyst", "data scientist", "devops engineer", "qa engineer", "intern",
]
