CREATE DATABASE IF NOT EXISTS e_careers
	CHARACTER SET utf8mb4
	COLLATE utf8mb4_unicode_ci;
USE e_careers;

CREATE TABLE IF NOT EXISTS career_applicant_accounts (
	id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	full_name VARCHAR(160) NOT NULL,
	email VARCHAR(190) NOT NULL,
	password_hash VARCHAR(255) NOT NULL,
	created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (id),
	UNIQUE KEY uq_career_applicant_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

CREATE TABLE IF NOT EXISTS career_jobs (
	id INT UNSIGNED NOT NULL AUTO_INCREMENT,
	title VARCHAR(160) NOT NULL,
	department VARCHAR(120) NOT NULL,
	employment_type ENUM('Full-time', 'Part-time', 'Contract', 'Temporary') NOT NULL,
	location VARCHAR(160) NOT NULL,
	vacancies INT UNSIGNED NOT NULL DEFAULT 1,
	contact_email VARCHAR(190) NOT NULL DEFAULT '',
	description TEXT NOT NULL,
	requirements TEXT NOT NULL,
	closing_date DATE NULL,
	status ENUM('Open', 'Closed') NOT NULL DEFAULT 'Open',
	created_by_hmd_id INT UNSIGNED NULL,
	created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	PRIMARY KEY (id),
	KEY idx_career_job_status (status, closing_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS career_applications (
	id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	applicant_id BIGINT UNSIGNED NOT NULL,
	job_id INT UNSIGNED NOT NULL,
	phone VARCHAR(32) NOT NULL,
	cover_letter TEXT NOT NULL,
	resume_storage_name CHAR(69) NOT NULL,
	resume_original_name VARCHAR(255) NOT NULL,
	resume_mime_type VARCHAR(120) NOT NULL,
	resume_size INT UNSIGNED NOT NULL,
	qualifications_storage_name CHAR(68) NULL,
	qualifications_original_name VARCHAR(255) NULL,
	qualifications_mime_type VARCHAR(120) NULL,
	qualifications_size INT UNSIGNED NULL,
	status VARCHAR(32) NOT NULL DEFAULT 'Under review',
	feedback TEXT NULL,
	created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	PRIMARY KEY (id),
	UNIQUE KEY uq_career_application_applicant_job (applicant_id, job_id),
	KEY idx_career_application_job_status (job_id, status),
	KEY idx_career_application_applicant (applicant_id, created_at),
	CONSTRAINT fk_career_application_applicant FOREIGN KEY (applicant_id)
		REFERENCES career_applicant_accounts (id) ON DELETE CASCADE,
	CONSTRAINT fk_career_application_job FOREIGN KEY (job_id)
		REFERENCES career_jobs (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS career_application_events (
	id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	application_id BIGINT UNSIGNED NOT NULL,
	actor_hmd_id INT UNSIGNED NULL,
	event_type VARCHAR(40) NOT NULL,
	old_status VARCHAR(30) NULL,
	new_status VARCHAR(30) NULL,
	feedback TEXT NULL,
	created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (id),
	KEY idx_career_application_event (application_id, created_at),
	CONSTRAINT fk_career_application_event_application FOREIGN KEY (application_id)
		REFERENCES career_applications (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
