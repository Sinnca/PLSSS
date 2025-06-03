-- Add meeting_link column to appointments table
ALTER TABLE appointments
ADD COLUMN IF NOT EXISTS meeting_link VARCHAR(255) DEFAULT NULL;