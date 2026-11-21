USE e_careers;

ALTER TABLE career_applications
	ADD COLUMN qualifications_storage_name CHAR(68) NULL AFTER resume_size,
	ADD COLUMN qualifications_original_name VARCHAR(255) NULL AFTER qualifications_storage_name,
	ADD COLUMN qualifications_mime_type VARCHAR(120) NULL AFTER qualifications_original_name,
	ADD COLUMN qualifications_size INT UNSIGNED NULL AFTER qualifications_mime_type;
