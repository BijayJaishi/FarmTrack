-- Run ONCE on an existing farmtrack_db (keeps all data). New installs do not need this file.
USE farmtrack_db;
ALTER TABLE messages  ADD COLUMN read_at DATETIME NULL;
ALTER TABLE enquiries ADD COLUMN farmer_read TINYINT(1) NOT NULL DEFAULT 0;
-- Treat everything that already exists as read, so only NEW messages notify
UPDATE messages  SET read_at = sent_at WHERE read_at IS NULL;
UPDATE enquiries SET farmer_read = 1;
