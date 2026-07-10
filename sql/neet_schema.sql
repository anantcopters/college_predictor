CREATE TABLE IF NOT EXISTS neet_round_uploads (
    id BIGSERIAL PRIMARY KEY,
    year INT NOT NULL,
    round_no INT NOT NULL,
    file_name TEXT,
    uploaded_at TIMESTAMP DEFAULT NOW(),
    UNIQUE(year, round_no)
);

CREATE TABLE IF NOT EXISTS neet_allotments (
    id BIGSERIAL PRIMARY KEY,
    upload_id BIGINT REFERENCES neet_round_uploads(id) ON DELETE CASCADE,

    year INT NOT NULL,
    round_no INT NOT NULL,
    sno INT,
    rank INT NOT NULL,

    previous_quota TEXT,
    previous_institute TEXT,
    previous_course TEXT,
    previous_remarks TEXT,

    current_quota TEXT,
    current_institute TEXT,
    current_course TEXT,
    allotted_category TEXT,
    candidate_category TEXT,
    option_no INT,
    remarks TEXT,

    final_quota TEXT,
    final_institute TEXT,
    final_course TEXT,
    final_category TEXT,

    status VARCHAR(60),
    is_upgraded BOOLEAN DEFAULT FALSE,
    is_fresh_allotted BOOLEAN DEFAULT FALSE,
    is_active_seat BOOLEAN DEFAULT TRUE,

    raw_json JSONB,
    row_hash TEXT NOT NULL,

    created_at TIMESTAMP DEFAULT NOW(),
    UNIQUE(upload_id, rank, row_hash)
);

CREATE INDEX IF NOT EXISTS idx_neet_year_rank
ON neet_allotments(year, rank);

CREATE INDEX IF NOT EXISTS idx_neet_year_quota
ON neet_allotments(year, final_quota);

CREATE INDEX IF NOT EXISTS idx_neet_latest
ON neet_allotments(year, round_no, rank, is_active_seat);