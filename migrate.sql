-- ZenMind Premium Upgrade Migration
-- Run this script on your live MySQL server via phpMyAdmin if the database already exists.

ALTER TABLE users
    ADD COLUMN IF NOT EXISTS theme VARCHAR(20) DEFAULT 'sage';

ALTER TABLE habits
    ADD COLUMN IF NOT EXISTS streak INT DEFAULT 0,
    ADD COLUMN IF NOT EXISTS best_streak INT DEFAULT 0;
