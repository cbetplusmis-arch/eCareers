<?php
require_once __DIR__ . "/../includes/portal.php";
$job_id = filter_input(INPUT_GET, "job", FILTER_VALIDATE_INT);
career_redirect("login.php" . ($job_id && $job_id > 0 ? "?job=" . (int) $job_id : ""));
