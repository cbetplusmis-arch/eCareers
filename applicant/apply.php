<?php
require_once __DIR__ . "/../includes/portal.php";
require_once __DIR__ . "/../includes/layout.php";
career_require_applicant();
$job_id = filter_input(INPUT_GET, "job", FILTER_VALIDATE_INT);
if ($_SERVER["REQUEST_METHOD"] === "POST") {
	$job_id = filter_input(INPUT_POST, "job_id", FILTER_VALIDATE_INT);
}
if (!$job_id || $job_id < 1) {
	http_response_code(400);
	exit("Choose a valid job before applying.");
}
$database = careers_db();
$job_statement = $database->prepare(
	"SELECT id, title, closing_date FROM career_jobs
	 WHERE id = ? AND status = 'Open' AND (closing_date IS NULL OR closing_date >= CURRENT_DATE()) LIMIT 1"
);
if (!$job_statement) {
	http_response_code(500);
	die("Could not prepare the job application.");
}
$job_statement->bind_param("i", $job_id);
if (!$job_statement->execute()) {
	http_response_code(500);
	die("Could not check whether this job is open.");
}
$job = $job_statement->get_result()->fetch_assoc();
$job_statement->close();
if (!$job) {
	http_response_code(404);
	exit("This job is no longer accepting applications.");
}
$applicant_id = (int) $_SESSION["career_applicant_id"];
$existing = $database->prepare("SELECT id FROM career_applications WHERE applicant_id = ? AND job_id = ? LIMIT 1");
$existing->bind_param("ii", $applicant_id, $job_id);
if (!$existing->execute()) {
	http_response_code(500);
	die("Could not check for an existing application.");
}
$already_applied = $existing->get_result()->num_rows > 0;
$existing->close();
$errors = array();
$phone = "";
$cover_letter = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
	career_verify_csrf();
	$phone = trim(isset($_POST["phone"]) ? (string) $_POST["phone"] : "");
	$cover_letter = trim(isset($_POST["cover_letter"]) ? (string) $_POST["cover_letter"] : "");
	if ($already_applied) {
		$errors[] = "You have already applied for this role.";
	}
	if (!preg_match('/^[0-9+() -]{7,32}$/', $phone)) {
		$errors[] = "Enter a valid telephone number.";
	}
	if ($cover_letter === "" || strlen($cover_letter) > 10000) {
		$errors[] = "Write a short cover letter of no more than 10,000 characters.";
	}
	if (!isset($_POST["declaration"]) || $_POST["declaration"] !== "1") {
		$errors[] = "Confirm the application declaration before submitting.";
	}
	$documents = array(
		"resume" => array(
			"label" => "CV or resume",
			"format_error" => "Your CV must be a PDF, DOC or DOCX file no larger than 5 MB.",
			"allowed" => array(
				"application/pdf" => "pdf",
				"application/msword" => "doc",
				"application/vnd.openxmlformats-officedocument.wordprocessingml.document" => "docx"
			)
		),
		"qualifications" => array(
			"label" => "academic qualifications PDF",
			"format_error" => "Your academic qualifications must be a PDF no larger than 5 MB.",
			"allowed" => array("application/pdf" => "pdf")
		)
	);
	$stored_documents = array();
	foreach ($documents as $field_name => $document) {
		if (!isset($_FILES[$field_name]) || $_FILES[$field_name]["error"] !== UPLOAD_ERR_OK || !is_uploaded_file($_FILES[$field_name]["tmp_name"])) {
			$errors[] = "Upload your " . $document["label"] . ".";
		}
	}
	if (!$errors) {
		$storage_directory = dirname(__DIR__) . DIRECTORY_SEPARATOR . "storage";
		if (!is_dir($storage_directory) && !mkdir($storage_directory, 0700, true)) {
			$errors[] = "Secure application document storage is unavailable. Please contact the Careers Office.";
		} else {
			foreach ($documents as $field_name => $document) {
				$file = $_FILES[$field_name];
				$size = filesize($file["tmp_name"]);
				$finfo = new finfo(FILEINFO_MIME_TYPE);
				$mime = $finfo->file($file["tmp_name"]);
				if ($size === false || $size < 1 || $size > 5 * 1024 * 1024 || !isset($document["allowed"][$mime])) {
					$errors[] = $document["format_error"];
					break;
				}
				$storage_name = bin2hex(random_bytes(32)) . "." . $document["allowed"][$mime];
				$stored_path = $storage_directory . DIRECTORY_SEPARATOR . $storage_name;
				if (!move_uploaded_file($file["tmp_name"], $stored_path)) {
					$errors[] = "Could not securely store your " . $document["label"] . ". Please try again.";
					break;
				}
				$stored_documents[$field_name] = array(
					"path" => $stored_path,
					"storage_name" => $storage_name,
					"original_name" => substr(basename($file["name"]), 0, 255),
					"mime" => $mime,
					"size" => (int) $size
				);
			}
		}
	}
	if ($errors) {
		foreach ($stored_documents as $document) {
			@unlink($document["path"]);
		}
	}
	if (!$errors) {
		$resume = $stored_documents["resume"];
		$qualifications = $stored_documents["qualifications"];
		$resume_storage_name = $resume["storage_name"];
		$resume_original_name = $resume["original_name"];
		$resume_mime = $resume["mime"];
		$resume_size = $resume["size"];
		$qualifications_storage_name = $qualifications["storage_name"];
		$qualifications_original_name = $qualifications["original_name"];
		$qualifications_mime = $qualifications["mime"];
		$qualifications_size = $qualifications["size"];
		$insert = $database->prepare(
			"INSERT INTO career_applications
			 (applicant_id, job_id, phone, cover_letter, resume_storage_name, resume_original_name, resume_mime_type, resume_size,
			  qualifications_storage_name, qualifications_original_name, qualifications_mime_type, qualifications_size)
			 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
		);
		if (!$insert) {
			@unlink($resume["path"]);
			@unlink($qualifications["path"]);
			http_response_code(500);
			die("Could not prepare your application.");
		}
		$insert->bind_param(
			"iisssssisssi",
			$applicant_id,
			$job_id,
			$phone,
			$cover_letter,
			$resume_storage_name,
			$resume_original_name,
			$resume_mime,
			$resume_size,
			$qualifications_storage_name,
			$qualifications_original_name,
			$qualifications_mime,
			$qualifications_size
		);
		if (!$database->begin_transaction()) {
			$insert->close();
			@unlink($resume["path"]);
			@unlink($qualifications["path"]);
			$errors[] = "Could not start your application submission. Please try again.";
		} elseif (!$insert->execute()) {
			$error_code = $insert->errno;
			$database->rollback();
			$insert->close();
			@unlink($resume["path"]);
			@unlink($qualifications["path"]);
			$errors[] = $error_code === 1062
				? "You have already applied for this role."
				: "Your application could not be submitted. Please try again.";
		} else {
			$application_id = (int) $database->insert_id;
			$insert->close();
			$event = $database->prepare(
				"INSERT INTO career_application_events (application_id, event_type, new_status) VALUES (?, 'Application submitted', 'Under review')"
			);
			if (!$event) {
				$database->rollback();
				@unlink($resume["path"]);
				@unlink($qualifications["path"]);
				http_response_code(500);
				die("Could not prepare the application activity record. Please try again.");
			}
			$event->bind_param("i", $application_id);
			$event_saved = $event->execute();
			$event->close();
			if (!$event_saved || !$database->commit()) {
				$database->rollback();
				@unlink($resume["path"]);
				@unlink($qualifications["path"]);
				http_response_code(500);
				die("Could not save your application activity record. Please try again.");
			}
			career_flash("Your application for " . $job["title"] . " has been submitted. You can follow updates from your account.");
			career_redirect("applicant/dashboard.php");
		}
	}
}
careers_page_start("Apply for " . $job["title"], "applicant");
?>
<div class="auth-wrap"><section class="auth-card">
	<p class="auth-kicker">APPLICATION</p><h1><?php echo career_h($job["title"]); ?></h1>
	<p>Applying as <strong><?php echo career_h(isset($_SESSION["career_applicant_name"]) ? $_SESSION["career_applicant_name"] : ""); ?></strong>. Your application will be visible in your account.</p>
	<?php if ($already_applied && $_SERVER["REQUEST_METHOD"] !== "POST") { ?>
		<div class="notice notice-error">You have already applied for this role. Check your application status in your account.</div><a class="button" href="<?php echo career_h(career_url("applicant/dashboard.php")); ?>">View my applications</a>
	<?php } else { ?>
		<?php if ($errors) { ?><ul class="error-list"><?php foreach ($errors as $error) { ?><li><?php echo career_h($error); ?></li><?php } ?></ul><?php } ?>
		<form method="post" enctype="multipart/form-data" action="<?php echo career_h(career_url("applicant/apply.php?job=" . (int) $job_id)); ?>" onsubmit="return confirm('Submit this application now?');">
			<?php career_csrf_input(); ?><input type="hidden" name="job_id" value="<?php echo (int) $job_id; ?>">
			<div class="field"><label for="phone">Telephone number</label><input id="phone" name="phone" type="tel" maxlength="32" autocomplete="tel" required value="<?php echo career_h($phone); ?>"></div>
			<div class="field"><label for="cover_letter">Cover letter</label><textarea id="cover_letter" name="cover_letter" maxlength="10000" required><?php echo career_h($cover_letter); ?></textarea><span class="field-help">Tell us why this role interests you and what you would bring.</span></div>
			<div class="field"><label for="resume">CV / resume</label><input id="resume" name="resume" type="file" accept=".pdf,.doc,.docx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document" required><span class="field-help">PDF, DOC or DOCX · maximum 5 MB.</span></div>
			<div class="field"><label for="qualifications">Academic qualifications</label><input id="qualifications" name="qualifications" type="file" accept=".pdf,application/pdf" required><span class="field-help">Combine all academic certificates into one PDF · maximum 5 MB.</span></div>
			<label class="check-row"><input type="checkbox" name="declaration" value="1" required><span>I confirm that the information in this application is accurate and may be used for recruitment purposes.</span></label>
			<div class="field"><button class="button" type="submit" <?php echo $already_applied ? "disabled" : ""; ?>>Submit application</button></div>
		</form>
	<?php } ?>
</section></div>
<?php careers_page_end(); ?>
