USE e_careers;

ALTER TABLE career_applications
	MODIFY COLUMN status VARCHAR(32) NOT NULL DEFAULT 'Under review';
