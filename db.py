from pathlib import Path
import sqlite3

BASE_DIR = Path(__file__).resolve().parent
DB_PATH = BASE_DIR / "wishtrain.db"

SCHEMA_SQL = """
CREATE TABLE IF NOT EXISTS members (
    member_name TEXT PRIMARY KEY,
    dance_name TEXT,
    generation INTEGER
);

CREATE TABLE IF NOT EXISTS numbers (
    number_id INTEGER PRIMARY KEY,
    number_name TEXT NOT NULL UNIQUE,
    team TEXT,
    number_type TEXT
);

CREATE TABLE IF NOT EXISTS member_numbers (
    member_name TEXT NOT NULL,
    number_id INTEGER NOT NULL,
    PRIMARY KEY (member_name, number_id)
);

CREATE TABLE IF NOT EXISTS rehearsals (
    rehearsal_id INTEGER PRIMARY KEY,
    rehearsal_type TEXT,
    rehearsal_date TEXT NOT NULL,
    weekday TEXT,
    studio TEXT,
    main_room TEXT,
    main_time TEXT,
    sub_room TEXT,
    sub_time TEXT,
    notes TEXT
);

CREATE TABLE IF NOT EXISTS rehearsal_numbers (
    rehearsal_id INTEGER NOT NULL,
    number_id INTEGER NOT NULL,
    status TEXT NOT NULL CHECK(status IN ('main', 'sub', 'all', 'absent')),
    main_seq INTEGER,
    remark TEXT,
    PRIMARY KEY (rehearsal_id, number_id)
);

CREATE TABLE IF NOT EXISTS musics (
    music_id INTEGER PRIMARY KEY,
    music_title TEXT NOT NULL,
    artist TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS number_musics (
    number_id INTEGER NOT NULL,
    music_id INTEGER NOT NULL,
    sequence_no INTEGER NOT NULL DEFAULT 1,
    PRIMARY KEY (number_id, music_id)
);
"""

def get_db() -> sqlite3.Connection:
    conn = sqlite3.connect(DB_PATH)
    conn.row_factory = sqlite3.Row
    return conn

def init_db() -> None:
    with get_db() as conn:
        conn.executescript(SCHEMA_SQL)
        conn.commit()
