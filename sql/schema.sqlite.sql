CREATE TABLE IF NOT EXISTS users (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  username TEXT NOT NULL UNIQUE,
  email TEXT NOT NULL UNIQUE,
  password_hash TEXT NOT NULL,
  full_name TEXT NOT NULL,
  company_name TEXT NULL,
  industry TEXT NULL,
  company_about TEXT NULL,
  avatar_path TEXT NULL,
  location TEXT NULL,
  phone TEXT NULL,
  headline TEXT NULL,
  about TEXT NULL,
  skills TEXT NULL,
  open_to_work INTEGER NOT NULL DEFAULT 0,
  cv_path TEXT NULL,
  cv_updated_at TEXT NULL,
  cv_parsed_text TEXT NULL,
  cv_parsed_at TEXT NULL,
  cv_titles TEXT NULL,
  role TEXT NOT NULL CHECK (role IN ('admin', 'employer', 'seeker')),
  status TEXT NOT NULL DEFAULT 'verified' CHECK (status IN ('verified', 'pending')),
  created_at TEXT DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_users_role ON users (role);
CREATE INDEX IF NOT EXISTS idx_users_status ON users (status);

CREATE TABLE IF NOT EXISTS site_settings (
  setting_key TEXT PRIMARY KEY,
  setting_value TEXT NOT NULL DEFAULT ''
);

CREATE TABLE IF NOT EXISTS jobs (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  employer_id INTEGER NULL,
  company_name TEXT NOT NULL,
  title TEXT NOT NULL,
  location TEXT NULL,
  job_type TEXT NOT NULL DEFAULT 'full-time',
  salary TEXT NULL,
  skills TEXT NULL,
  description TEXT NOT NULL,
  status TEXT NOT NULL DEFAULT 'pending' CHECK (status IN ('pending', 'approved', 'rejected')),
  created_by TEXT NOT NULL DEFAULT 'admin' CHECK (created_by IN ('admin', 'employer')),
  source TEXT NOT NULL DEFAULT 'employer',
  external_id TEXT NULL,
  external_url TEXT NULL,
  synced_at TEXT NULL,
  created_at TEXT DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (employer_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE INDEX IF NOT EXISTS idx_jobs_status ON jobs (status);
CREATE INDEX IF NOT EXISTS idx_jobs_created_at ON jobs (created_at);

CREATE TABLE IF NOT EXISTS seeker_education (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL,
  school TEXT NOT NULL,
  degree TEXT NULL,
  field_of_study TEXT NULL,
  start_year TEXT NULL,
  end_year TEXT NULL,
  description TEXT NULL,
  created_at TEXT DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS seeker_experience (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL,
  title TEXT NOT NULL,
  company TEXT NULL,
  location TEXT NULL,
  start_year TEXT NULL,
  end_year TEXT NULL,
  is_current INTEGER NOT NULL DEFAULT 0,
  description TEXT NULL,
  created_at TEXT DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_seeker_education_user ON seeker_education (user_id);
CREATE INDEX IF NOT EXISTS idx_seeker_experience_user ON seeker_experience (user_id);

CREATE TABLE IF NOT EXISTS job_applications (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  job_id INTEGER NOT NULL,
  seeker_id INTEGER NOT NULL,
  status TEXT NOT NULL DEFAULT 'applied' CHECK (status IN ('applied', 'review', 'interview', 'rejected', 'hired', 'completed')),
  match_score INTEGER NOT NULL DEFAULT 0,
  cv_path TEXT NULL,
  cover_letter TEXT NULL,
  interview_reply TEXT NULL,
  interview_date TEXT NULL,
  created_at TEXT DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE,
  FOREIGN KEY (seeker_id) REFERENCES users(id) ON DELETE CASCADE,
  UNIQUE (job_id, seeker_id)
);

CREATE INDEX IF NOT EXISTS idx_job_applications_job ON job_applications (job_id);
CREATE INDEX IF NOT EXISTS idx_job_applications_seeker ON job_applications (seeker_id);
CREATE INDEX IF NOT EXISTS idx_job_applications_status ON job_applications (status);

CREATE TABLE IF NOT EXISTS notifications (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL,
  type TEXT NOT NULL,
  title TEXT NOT NULL,
  message TEXT NOT NULL,
  link TEXT NULL,
  is_read INTEGER NOT NULL DEFAULT 0,
  created_at TEXT DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_notifications_user ON notifications (user_id);
CREATE INDEX IF NOT EXISTS idx_notifications_user_read ON notifications (user_id, is_read);
CREATE INDEX IF NOT EXISTS idx_notifications_created ON notifications (created_at);
