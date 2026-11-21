USE e_careers;

SET @career_contact_email_exists = (
	SELECT COUNT(*)
	FROM information_schema.COLUMNS
	WHERE TABLE_SCHEMA = DATABASE()
		AND TABLE_NAME = 'career_jobs'
		AND COLUMN_NAME = 'contact_email'
);
SET @career_contact_email_sql = IF(
	@career_contact_email_exists = 0,
	'ALTER TABLE career_jobs ADD COLUMN contact_email VARCHAR(190) NOT NULL DEFAULT '''' AFTER location',
	'SELECT 1'
);
PREPARE career_contact_email_stmt FROM @career_contact_email_sql;
EXECUTE career_contact_email_stmt;
DEALLOCATE PREPARE career_contact_email_stmt;

CREATE TABLE IF NOT EXISTS career_password_resets (
	id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	applicant_id BIGINT UNSIGNED NOT NULL,
	token_hash CHAR(64) NOT NULL,
	expires_at DATETIME NOT NULL,
	used_at DATETIME NULL,
	created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (id),
	UNIQUE KEY uq_career_password_reset_token (token_hash),
	KEY idx_career_password_reset_applicant (applicant_id, created_at),
	CONSTRAINT fk_career_password_reset_applicant FOREIGN KEY (applicant_id)
		REFERENCES career_applicant_accounts (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
