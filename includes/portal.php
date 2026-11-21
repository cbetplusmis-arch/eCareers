<?php
require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . "eAdmissions_cbetplus"
	. DIRECTORY_SEPARATOR . "includes" . DIRECTORY_SEPARATOR . "database.php";

if (session_status() !== PHP_SESSION_ACTIVE) {
	session_set_cookie_params(array(
		"httponly" => true,
		"samesite" => "Lax",
		"secure" => !empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off"
	));
	session_start();
}

function careers_db() {
	static $database = null;
	if ($database === null) {
		$database = new mysqli(AD_DB_HOST, AD_DB_USER, AD_DB_PASSWORD, "e_careers");
		if ($database->connect_error) {
			http_response_code(500);
			die("The Careers Portal database is unavailable. Import database/careers.sql and check the eAdmissions_cbetplus/includes/config.php database settings.");
		}
		if (!$database->set_charset("utf8mb4")) {
			http_response_code(500);
			die("Could not set the Careers Portal database character set.");
		}
	}
	return $database;
}

function career_h($value) {
	return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

function career_url($path = "") {
	$directory = str_replace("\\", "/", dirname($_SERVER["SCRIPT_NAME"]));
	$last_directory = basename($directory);
	if ($last_directory === "admin" || $last_directory === "applicant") {
		$directory = dirname($directory);
	}
	$base = rtrim($directory, "/");
	return $base . ($path === "" ? "/" : "/" . ltrim($path, "/"));
}

function career_redirect($path) {
	header("Location: " . career_url($path));
	exit;
}

function career_csrf_token() {
	if (empty($_SESSION["career_csrf_token"])) {
		$_SESSION["career_csrf_token"] = bin2hex(random_bytes(32));
	}
	return $_SESSION["career_csrf_token"];
}

function career_csrf_input() {
	echo '<input type="hidden" name="csrf_token" value="' . career_h(career_csrf_token()) . '">';
}

function career_verify_csrf() {
	$submitted = isset($_POST["csrf_token"]) ? (string) $_POST["csrf_token"] : "";
	if ($submitted === "" || !hash_equals(career_csrf_token(), $submitted)) {
		http_response_code(419);
		exit("This form expired. Return to the page and submit it again.");
	}
}

function career_flash($message, $type = "success") {
	$_SESSION["career_flash"] = array("message" => $message, "type" => $type);
}

function career_take_flash() {
	if (!isset($_SESSION["career_flash"])) {
		return null;
	}
	$message = $_SESSION["career_flash"];
	unset($_SESSION["career_flash"]);
	return $message;
}

function career_require_applicant() {
	if (empty($_SESSION["career_applicant_id"]) || ($_SESSION["career_role"] ?? "") !== "Applicant") {
		career_flash("Sign in to your applicant account to continue.", "error");
		career_redirect("login.php");
	}
}

function career_require_headteacher() {
	if (empty($_SESSION["career_headteacher_id"]) || ($_SESSION["career_role"] ?? "") !== "Headteacher") {
		career_redirect("login.php");
	}
}

function career_absolute_url($path) {
	$configured_base = getenv("CAREER_PORTAL_BASE_URL");
	if ($configured_base !== false && $configured_base !== "") {
		$parts = parse_url($configured_base);
		if ($parts === false || !isset($parts["scheme"], $parts["host"])
			|| !in_array(strtolower($parts["scheme"]), array("http", "https"), true)
			|| isset($parts["user"]) || isset($parts["pass"])) {
			return false;
		}
		return rtrim($configured_base, "/") . "/" . ltrim($path, "/");
	}
	$host = isset($_SERVER["HTTP_HOST"]) ? (string) $_SERVER["HTTP_HOST"] : "";
	if (!preg_match('/\A[A-Za-z0-9.-]+(?::[0-9]{1,5})?\z/', $host)) {
		return false;
	}
	$scheme = !empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off" ? "https" : "http";
	return $scheme . "://" . $host . career_url($path);
}

function career_school_name() {
	static $school_name = null;
	if ($school_name !== null) {
		return $school_name;
	}
	$result = school_db()->query("SELECT school_name FROM institutional_profile WHERE id = 1 LIMIT 1");
	if (!$result) {
		http_response_code(500);
		die("Could not load the school name from the SMS database.");
	}
	$profile = $result->fetch_assoc();
	if (!$profile || trim((string) $profile["school_name"]) === "") {
		http_response_code(500);
		die("The school profile is missing from the SMS database.");
	}
	$school_name = preg_replace('/\s+/', ' ', trim($profile["school_name"]));
	return $school_name;
}

function career_valid_date($value) {
	$date = DateTime::createFromFormat("!Y-m-d", $value);
	$errors = DateTime::getLastErrors();
	return $date !== false && $date->format("Y-m-d") === $value
		&& ($errors === false || ($errors["warning_count"] === 0 && $errors["error_count"] === 0));
}

function career_status_class($status) {
	return "status-" . strtolower(str_replace(" ", "-", (string) $status));
}
