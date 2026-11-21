<?php
require_once __DIR__ . "/../includes/portal.php";
career_require_headteacher();
$application_id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);
if (!$application_id || $application_id < 1) {
	http_response_code(400);
	exit("A valid application is required.");
}
$statement = careers_db()->prepare(
	"SELECT resume_storage_name, resume_original_name, resume_mime_type FROM career_applications WHERE id = ? LIMIT 1"
);
$statement->bind_param("i", $application_id);
if (!$statement->execute()) {
	http_response_code(500);
	die("Could not retrieve the CV details.");
}
$resume = $statement->get_result()->fetch_assoc();
$statement->close();
if (!$resume) {
	http_response_code(404);
	exit("The requested CV was not found.");
}
if (!preg_match('/^[a-f0-9]{64}\.(pdf|doc|docx)$/', $resume["resume_storage_name"])
	|| !in_array($resume["resume_mime_type"], array("application/pdf", "application/msword", "application/vnd.openxmlformats-officedocument.wordprocessingml.document"), true)) {
	http_response_code(404);
	exit("The requested CV is unavailable.");
}
$path = dirname(__DIR__) . DIRECTORY_SEPARATOR . "storage" . DIRECTORY_SEPARATOR . $resume["resume_storage_name"];
if (!is_file($path)) {
	http_response_code(404);
	exit("The stored CV is no longer available.");
}
$download_name = preg_replace('/[^A-Za-z0-9._-]/', "_", basename($resume["resume_original_name"]));
$view_inline = isset($_GET["view"]) && $_GET["view"] === "1" && $resume["resume_mime_type"] === "application/pdf";
header("X-Content-Type-Options: nosniff");
header("Content-Type: " . $resume["resume_mime_type"]);
header("Content-Length: " . filesize($path));
header("Content-Disposition: " . ($view_inline ? "inline" : "attachment") . "; filename=\"" . ($download_name ?: "candidate-cv") . "\"");
readfile($path);
