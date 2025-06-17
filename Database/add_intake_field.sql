-- Add intake field to hostels table
ALTER TABLE hostels ADD COLUMN intake varchar(100) DEFAULT NULL AFTER year;

-- Update existing hostels to have a default intake value
UPDATE hostels SET intake = '1' WHERE intake IS NULL; 