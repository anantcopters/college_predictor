CREATE TABLE counselling_years (
    id SERIAL PRIMARY KEY,
    year INT NOT NULL UNIQUE
);

CREATE TABLE rounds (
    id SERIAL PRIMARY KEY,
    year_id INT NOT NULL REFERENCES counselling_years(id) ON DELETE CASCADE,
    round_no INT NOT NULL,
    round_name VARCHAR(20) NOT NULL,
    UNIQUE(year_id, round_no)
);

CREATE TABLE institute_types (
    id SERIAL PRIMARY KEY,
    code VARCHAR(20) NOT NULL UNIQUE
    -- IIT, NIT, IIIT, GFTI
);

CREATE TABLE institutes (
    id SERIAL PRIMARY KEY,
    name TEXT NOT NULL UNIQUE,
    institute_type_id INT REFERENCES institute_types(id)
);

CREATE TABLE programs (
    id SERIAL PRIMARY KEY,
    institute_id INT NOT NULL REFERENCES institutes(id) ON DELETE CASCADE,
    program_name TEXT NOT NULL,
    UNIQUE(institute_id, program_name)
);

CREATE TABLE quotas (
    id SERIAL PRIMARY KEY,
    code VARCHAR(20) NOT NULL UNIQUE
    -- AI, HS, OS, GO, JK, LA etc
);

CREATE TABLE seat_types (
    id SERIAL PRIMARY KEY,
    code VARCHAR(30) NOT NULL UNIQUE
    -- OPEN, EWS, OBC-NCL, SC, ST etc
);

CREATE TABLE genders (
    id SERIAL PRIMARY KEY,
    name VARCHAR(80) NOT NULL UNIQUE
);

CREATE TABLE cutoffs (
    id BIGSERIAL PRIMARY KEY,

    year_id INT NOT NULL REFERENCES counselling_years(id),
    round_id INT NOT NULL REFERENCES rounds(id),

    institute_id INT NOT NULL REFERENCES institutes(id),
    program_id INT NOT NULL REFERENCES programs(id),

    quota_id INT NOT NULL REFERENCES quotas(id),
    seat_type_id INT NOT NULL REFERENCES seat_types(id),
    gender_id INT NOT NULL REFERENCES genders(id),

    opening_rank INT NOT NULL,
    closing_rank INT NOT NULL,

    opening_rank_raw VARCHAR(30),
    closing_rank_raw VARCHAR(30),

    is_preparatory BOOLEAN DEFAULT FALSE,

    created_at TIMESTAMP DEFAULT NOW(),

    UNIQUE (
        year_id,
        round_id,
        institute_id,
        program_id,
        quota_id,
        seat_type_id,
        gender_id,
        is_preparatory
    )
);

CREATE INDEX idx_cutoffs_rank_search
ON cutoffs (
    year_id,
    round_id,
    seat_type_id,
    gender_id,
    quota_id,
    closing_rank
);

CREATE INDEX idx_cutoffs_institute
ON cutoffs (institute_id);

CREATE INDEX idx_cutoffs_program
ON cutoffs (program_id);

CREATE INDEX idx_institutes_type
ON institutes (institute_type_id);

