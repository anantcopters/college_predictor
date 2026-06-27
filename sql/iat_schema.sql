CREATE TABLE IF NOT EXISTS iat_years (
    id SERIAL PRIMARY KEY,
    year INT NOT NULL UNIQUE
);

CREATE TABLE IF NOT EXISTS iat_rounds (
    id SERIAL PRIMARY KEY,
    year_id INT NOT NULL REFERENCES iat_years(id) ON DELETE CASCADE,
    round_no INT NOT NULL,
    round_name VARCHAR(30) NOT NULL,
    UNIQUE(year_id, round_no)
);

CREATE TABLE iat_institutes (
    id SERIAL PRIMARY KEY,
    name TEXT NOT NULL UNIQUE,
    display_order INT NOT NULL DEFAULT 0
);

CREATE TABLE IF NOT EXISTS iat_categories (
    id SERIAL PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    display_name VARCHAR(100) NOT NULL,
    display_order INT NOT NULL DEFAULT 0
);

CREATE TABLE IF NOT EXISTS iat_cutoffs (
    id BIGSERIAL PRIMARY KEY,
    year_id INT NOT NULL REFERENCES iat_years(id) ON DELETE CASCADE,
    round_id INT NOT NULL REFERENCES iat_rounds(id) ON DELETE CASCADE,
    institute_id INT NOT NULL REFERENCES iat_institutes(id) ON DELETE CASCADE,
    category_id INT NOT NULL REFERENCES iat_categories(id) ON DELETE CASCADE,
    closing_rank INT,
    remarks TEXT,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW(),

    UNIQUE(year_id, round_id, institute_id, category_id)
);

CREATE INDEX IF NOT EXISTS idx_iat_cutoffs_search
ON iat_cutoffs(year_id, round_id, category_id, closing_rank);

CREATE INDEX IF NOT EXISTS idx_iat_cutoffs_institute
ON iat_cutoffs(institute_id);

INSERT INTO iat_institutes (name, display_order) VALUES
('IISER Berhampur (BS-MS)', 1),
('IISER Bhopal (BS-MS)', 2),
('IISER Kolkata (BS-MS)', 3),
('IISER Mohali (BS-MS)', 4),
('IISER Pune (BS-MS)', 5),
('IISER Thiruvananthapuram (BS-MS)', 6),
('IISER Tirupati (BS-MS)', 7),
('IISER Bhopal (BTech.)', 8),
('IISER Bhopal (BS Economic Sciences)', 9),
('IISER Kolkata (BS-MS in Computational and Data Sciences)', 10),
('IISER Tirupati (BS in Economic and Statistical Sciences)', 11)
ON CONFLICT (name) DO UPDATE SET
display_order = EXCLUDED.display_order;

INSERT INTO iat_categories (code, display_name, display_order) VALUES
('GEN', 'Unreserved / General', 1),
('OBC-NCL', 'OBC-NCL', 2),
('SC', 'SC', 3),
('EWS', 'EWS', 4),
('ST', 'ST', 5),
('GEN-PWD', 'General PwD', 6),
('OBC-NCL-PWD', 'OBC-NCL PwD', 7),
('SC-PWD', 'SC PwD', 8),
('EWS-PWD', 'EWS PwD', 9),
('ST-PWD', 'ST PwD', 10)
ON CONFLICT (code) DO UPDATE SET
    display_name = EXCLUDED.display_name,
    display_order = EXCLUDED.display_order;

INSERT INTO iat_years(year)
VALUES (2025)
ON CONFLICT(year) DO NOTHING;

INSERT INTO iat_rounds(year_id, round_no, round_name)
SELECT id, 1, 'R1'
FROM iat_years
WHERE year = 2025
ON CONFLICT(year_id, round_no) DO NOTHING;

WITH data(category_code, institute_name, closing_rank) AS (
VALUES
('GEN','IISER Berhampur (BS-MS)',3299),
('GEN','IISER Bhopal (BS-MS)',2015),
('GEN','IISER Kolkata (BS-MS)',935),
('GEN','IISER Mohali (BS-MS)',1778),
('GEN','IISER Pune (BS-MS)',355),
('GEN','IISER Thiruvananthapuram (BS-MS)',2381),
('GEN','IISER Tirupati (BS-MS)',2990),
('GEN','IISER Bhopal (BTech.)',974),
('GEN','IISER Bhopal (BS Economic Sciences)',1636),
('GEN','IISER Kolkata (BS-MS in Computational and Data Sciences)',498),
('GEN','IISER Tirupati (BS in Economic and Statistical Sciences)',2324),

('OBC-NCL','IISER Berhampur (BS-MS)',2080),
('OBC-NCL','IISER Bhopal (BS-MS)',1325),
('OBC-NCL','IISER Kolkata (BS-MS)',891),
('OBC-NCL','IISER Mohali (BS-MS)',1358),
('OBC-NCL','IISER Pune (BS-MS)',404),
('OBC-NCL','IISER Thiruvananthapuram (BS-MS)',1563),
('OBC-NCL','IISER Tirupati (BS-MS)',1880),
('OBC-NCL','IISER Bhopal (BTech.)',604),
('OBC-NCL','IISER Bhopal (BS Economic Sciences)',851),
('OBC-NCL','IISER Kolkata (BS-MS in Computational and Data Sciences)',417),
('OBC-NCL','IISER Tirupati (BS in Economic and Statistical Sciences)',1529),

('SC','IISER Berhampur (BS-MS)',856),
('SC','IISER Bhopal (BS-MS)',555),
('SC','IISER Kolkata (BS-MS)',292),
('SC','IISER Mohali (BS-MS)',520),
('SC','IISER Pune (BS-MS)',167),
('SC','IISER Thiruvananthapuram (BS-MS)',665),
('SC','IISER Tirupati (BS-MS)',812),
('SC','IISER Bhopal (BTech.)',355),
('SC','IISER Bhopal (BS Economic Sciences)',609),
('SC','IISER Kolkata (BS-MS in Computational and Data Sciences)',169),
('SC','IISER Tirupati (BS in Economic and Statistical Sciences)',800),

('EWS','IISER Berhampur (BS-MS)',767),
('EWS','IISER Bhopal (BS-MS)',465),
('EWS','IISER Kolkata (BS-MS)',341),
('EWS','IISER Mohali (BS-MS)',480),
('EWS','IISER Pune (BS-MS)',143),
('EWS','IISER Thiruvananthapuram (BS-MS)',576),
('EWS','IISER Tirupati (BS-MS)',718),
('EWS','IISER Bhopal (BTech.)',158),
('EWS','IISER Bhopal (BS Economic Sciences)',246),
('EWS','IISER Kolkata (BS-MS in Computational and Data Sciences)',106),
('EWS','IISER Tirupati (BS in Economic and Statistical Sciences)',446),

('ST','IISER Berhampur (BS-MS)',409),
('ST','IISER Bhopal (BS-MS)',264),
('ST','IISER Kolkata (BS-MS)',191),
('ST','IISER Mohali (BS-MS)',263),
('ST','IISER Pune (BS-MS)',65),
('ST','IISER Thiruvananthapuram (BS-MS)',338),
('ST','IISER Tirupati (BS-MS)',393),
('ST','IISER Bhopal (BTech.)',115),
('ST','IISER Bhopal (BS Economic Sciences)',189),
('ST','IISER Kolkata (BS-MS in Computational and Data Sciences)',124),
('ST','IISER Tirupati (BS in Economic and Statistical Sciences)',313),

('GEN-PWD','IISER Berhampur (BS-MS)',58118),
('GEN-PWD','IISER Bhopal (BS-MS)',31547),
('GEN-PWD','IISER Kolkata (BS-MS)',44127),
('GEN-PWD','IISER Mohali (BS-MS)',50112),
('GEN-PWD','IISER Pune (BS-MS)',10526),
('GEN-PWD','IISER Thiruvananthapuram (BS-MS)',53859),
('GEN-PWD','IISER Tirupati (BS-MS)',60050),
('GEN-PWD','IISER Bhopal (BTech.)',35747),
('GEN-PWD','IISER Bhopal (BS Economic Sciences)',50106),
('GEN-PWD','IISER Kolkata (BS-MS in Computational and Data Sciences)',967),
('GEN-PWD','IISER Tirupati (BS in Economic and Statistical Sciences)',27004),

('OBC-NCL-PWD','IISER Berhampur (BS-MS)',29183),
('OBC-NCL-PWD','IISER Bhopal (BS-MS)',23669),
('OBC-NCL-PWD','IISER Kolkata (BS-MS)',20784),
('OBC-NCL-PWD','IISER Mohali (BS-MS)',23209),
('OBC-NCL-PWD','IISER Pune (BS-MS)',8735),
('OBC-NCL-PWD','IISER Thiruvananthapuram (BS-MS)',24716),
('OBC-NCL-PWD','IISER Tirupati (BS-MS)',27439),
('OBC-NCL-PWD','IISER Bhopal (BTech.)',3132),
('OBC-NCL-PWD','IISER Bhopal (BS Economic Sciences)',29841),
('OBC-NCL-PWD','IISER Kolkata (BS-MS in Computational and Data Sciences)',23780),
('OBC-NCL-PWD','IISER Tirupati (BS in Economic and Statistical Sciences)',26865),

('SC-PWD','IISER Berhampur (BS-MS)',9247),
('SC-PWD','IISER Bhopal (BS-MS)',10976),
('SC-PWD','IISER Kolkata (BS-MS)',8626),
('SC-PWD','IISER Mohali (BS-MS)',12900),
('SC-PWD','IISER Pune (BS-MS)',3775),
('SC-PWD','IISER Thiruvananthapuram (BS-MS)',13788),
('SC-PWD','IISER Tirupati (BS-MS)',13814),
('SC-PWD','IISER Bhopal (BTech.)',2116),
('SC-PWD','IISER Bhopal (BS Economic Sciences)',3594),
('SC-PWD','IISER Kolkata (BS-MS in Computational and Data Sciences)',8432),
('SC-PWD','IISER Tirupati (BS in Economic and Statistical Sciences)',8502),

('EWS-PWD','IISER Berhampur (BS-MS)',NULL),
('EWS-PWD','IISER Bhopal (BS-MS)',NULL),
('EWS-PWD','IISER Kolkata (BS-MS)',9505),
('EWS-PWD','IISER Mohali (BS-MS)',NULL),
('EWS-PWD','IISER Pune (BS-MS)',7606),
('EWS-PWD','IISER Thiruvananthapuram (BS-MS)',NULL),
('EWS-PWD','IISER Tirupati (BS-MS)',3563),
('EWS-PWD','IISER Bhopal (BTech.)',4854),
('EWS-PWD','IISER Bhopal (BS Economic Sciences)',NULL),
('EWS-PWD','IISER Kolkata (BS-MS in Computational and Data Sciences)',243),
('EWS-PWD','IISER Tirupati (BS in Economic and Statistical Sciences)',7674),

('ST-PWD','IISER Berhampur (BS-MS)',NULL),
('ST-PWD','IISER Bhopal (BS-MS)',NULL),
('ST-PWD','IISER Kolkata (BS-MS)',447),
('ST-PWD','IISER Mohali (BS-MS)',NULL),
('ST-PWD','IISER Pune (BS-MS)',NULL),
('ST-PWD','IISER Thiruvananthapuram (BS-MS)',NULL),
('ST-PWD','IISER Tirupati (BS-MS)',NULL),
('ST-PWD','IISER Bhopal (BTech.)',NULL),
('ST-PWD','IISER Bhopal (BS Economic Sciences)',NULL),
('ST-PWD','IISER Kolkata (BS-MS in Computational and Data Sciences)',NULL),
('ST-PWD','IISER Tirupati (BS in Economic and Statistical Sciences)',NULL)
)

INSERT INTO iat_cutoffs (
    year_id,
    round_id,
    institute_id,
    category_id,
    closing_rank
)
SELECT
    y.id,
    r.id,
    i.id,
    c.id,
    d.closing_rank
FROM data d
JOIN iat_years y
    ON y.year = 2025
JOIN iat_rounds r
    ON r.year_id = y.id
   AND r.round_no = 1
JOIN iat_institutes i
    ON i.name = d.institute_name
JOIN iat_categories c
    ON c.code = d.category_code
ON CONFLICT (
    year_id,
    round_id,
    institute_id,
    category_id
)
DO UPDATE SET
    closing_rank = EXCLUDED.closing_rank,
    updated_at = NOW();