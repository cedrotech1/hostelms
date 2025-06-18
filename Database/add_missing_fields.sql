-- Add missing fields to info table
ALTER TABLE info ADD COLUMN intake varchar(100) DEFAULT NULL AFTER yearofstudy;
ALTER TABLE info ADD COLUMN disability varchar(100) DEFAULT NULL AFTER intake;

-- Update existing records with default values
UPDATE info SET intake = '1' WHERE intake IS NULL;
UPDATE info SET disability = 'None' WHERE disability IS NULL; 