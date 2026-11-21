USE e_careers;

SET @career_vacancies_exists = (
	SELECT COUNT(*)
	FROM information_schema.COLUMNS
	WHERE TABLE_SCHEMA = DATABASE()
		AND TABLE_NAME = 'career_jobs'
		AND COLUMN_NAME = 'vacancies'
);
SET @career_vacancies_sql = IF(
	@career_vacancies_exists = 0,
	'ALTER TABLE career_jobs ADD COLUMN vacancies INT UNSIGNED NOT NULL DEFAULT 1 AFTER location',
	'SELECT 1'
);
PREPARE career_vacancies_stmt FROM @career_vacancies_sql;
EXECUTE career_vacancies_stmt;
DEALLOCATE PREPARE career_vacancies_stmt;
