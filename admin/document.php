<?php
require_once __DIR__ . "/../includes/portal.php";
career_require_headteacher();
$application_id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);
if (!$application_id || $application_id < 1 || !isset($_GET["type"]) || $_GET["type"] !== "qualifications") {
	http_response_code(400);
	exit("A valid application document is required.");
}
$statement = careers_db()->prepare(
	"SELECT qualifications_storage_name, qualifications_original_name, qualifications_mime_type
	 FROM career_applications WHERE id = ? LIMIT 1"
);
if (!$statement) {
	http_response_code(500);
	die("Could not prepare the document request.");
}
$statement->bind_param("i", $application_id);
if (!$statement->execute()) {
	http_response_code(500);
	die("Could not retrieve the document details.");
}
$document = $statement->get_result()->fetch_assoc();
$statement->close();
if (!$document || !$document["qualifications_storage_name"]) {
	http_response_code(404);
	exit("The requested qualifications PDF was not found.");
}
if (!preg_match('/^[a-f0-9]{64}\.pdf$/', $document["qualifications_storage_name"])
	|| $document["qualifications_mime_type"] !== "application/pdf") {
	http_response_code(404);
	exit("The requested qualifications PDF is unavailable.");
}
$path = dirname(__DIR__) . DIRECTORY_SEPARATOR . "storage" . DIRECTORY_SEPARATOR . $document["qualifications_storage_name"];
if (!is_file($path)) {
	http_response_code(404);
	exit("The stored qualifications PDF is no longer available.");
}
$download_name = preg_replace('/[^A-Za-z0-9._-]/', "_", basename($document["qualifications_original_name"]));
$view_inline = isset($_GET["view"]) && $_GET["view"] === "1";
header("X-Content-Type-Options: nosniff");
header("Content-Type: application/pdf");
header("Content-Length: " . filesize($path));
header("Content-Disposition: " . ($view_inline ? "inline" : "attachment") . "; filename=\"" . ($download_name ?: "academic-qualifications.pdf") . "\"");
readfile($path);
