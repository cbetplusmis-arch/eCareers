<?php
require_once __DIR__ . "/../includes/portal.php";
require_once __DIR__ . "/../includes/layout.php";
career_require_headteacher();
$application_id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);
if (!$application_id || $application_id < 1) {
	http_response_code(400);
	exit("A valid application is required.");
}
$database = careers_db();
$statuses = array("Under review", "Shortlisted", "Interviewed", "Hired");
$error = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
	career_verify_csrf();
	$new_status = isset($_POST["status"]) ? trim((string) $_POST["status"]) : "";
	$feedback = trim(isset($_POST["feedback"]) ? (string) $_POST["feedback"] : "");
	if (!in_array($new_status, $statuses, true)) {
		$error = "Choose a valid application status.";
	} elseif (strlen($feedback) > 10000) {
		$error = "Feedback must be no more than 10,000 characters.";
	} else {
		$current_statement = $database->prepare("SELECT status FROM career_applications WHERE id = ? LIMIT 1");
		$current_statement->bind_param("i", $application_id);
		if (!$current_statement->execute()) {
			http_response_code(500);
			die("Could not load the current application status.");
		}
		$current = $current_statement->get_result()->fetch_assoc();
		$current_statement->close();
		if (!$current) {
			http_response_code(404);
			exit("The application was not found.");
		}
		$old_status = $current["status"];
		if (!$database->begin_transaction()) {
			$error = "Could not start the application update. Please try again.";
		} else {
			$update = $database->prepare("UPDATE career_applications SET status = ?, feedback = ? WHERE id = ?");
			if (!$update) {
				$database->rollback();
				http_response_code(500);
				die("Could not prepare the application update.");
			}
			$update->bind_param("ssi", $new_status, $feedback, $application_id);
			$updated = $update->execute();
			$update->close();
			$headteacher_id = (int) $_SESSION["career_headteacher_id"];
			$event = $database->prepare(
				"INSERT INTO career_application_events (application_id, actor_hmd_id, event_type, old_status, new_status, feedback)
				 VALUES (?, ?, 'Application reviewed', ?, ?, ?)"
			);
			if ($updated && $event) {
				$event->bind_param("iisss", $application_id, $headteacher_id, $old_status, $new_status, $feedback);
				$updated = $event->execute();
			} else {
				$updated = false;
			}
			if ($event) {
				$event->close();
			}
			if (!$updated || !$database->commit()) {
				$database->rollback();
				$error = "Could not save the decision and feedback. No update was applied.";
			} else {
				career_flash("Application status and feedback saved. The applicant can now see the update in their account.");
				career_redirect("admin/review.php?id=" . (int) $application_id);
			}
		}
	}
}
$detail = $database->prepare(
	"SELECT a.*, p.full_name, p.email, j.title, j.department, j.location
	 FROM career_applications a
	 JOIN career_applicant_accounts p ON p.id = a.applicant_id
	 JOIN career_jobs j ON j.id = a.job_id
	 WHERE a.id = ? LIMIT 1"
);
$detail->bind_param("i", $application_id);
if (!$detail->execute()) {
	http_response_code(500);
	die("Could not load the application details.");
}
$application = $detail->get_result()->fetch_assoc();
$detail->close();
if (!$application) {
	http_response_code(404);
	exit("The application was not found.");
}
$status_value = $_SERVER["REQUEST_METHOD"] === "POST" && isset($new_status)
	? (in_array($new_status, $statuses, true) ? $new_status : "")
	: (in_array($application["status"], $statuses, true) ? $application["status"] : "");
$feedback_value = $_SERVER["REQUEST_METHOD"] === "POST" && isset($feedback) ? $feedback : $application["feedback"];
careers_page_start("Review application", "admin");
?>
<div class="dashboard-intro"><div><p class="eyebrow">APPLICATION REVIEW</p><h1><?php echo career_h($application["full_name"]); ?></h1><p>Submitted <?php echo career_h(date("j F Y, H:i", strtotime($application["created_at"]))); ?></p></div><a class="button button-outline" href="<?php echo career_h(career_url("admin/applicants.php")); ?>">← All applications</a></div>
<div class="detail-layout">
	<section class="panel">
		<h2><?php echo career_h($application["title"]); ?></h2>
		<p class="muted"><?php echo career_h($application["department"]); ?> · <?php echo career_h($application["location"]); ?></p>
		<div class="job-meta"><span class="tag">Applicant details</span><span class="tag"><?php echo career_h($application["email"]); ?></span><span class="tag"><?php echo career_h($application["phone"]); ?></span></div>
		<h3>Cover letter</h3><div class="prose"><?php echo career_h($application["cover_letter"]); ?></div>
		<div class="document-links">
			<?php if ($application["resume_mime_type"] === "application/pdf") { ?><a class="button button-outline" target="_blank" rel="noopener" href="<?php echo career_h(career_url("admin/resume.php?id=" . (int) $application["id"] . "&view=1")); ?>">View CV PDF</a><?php } ?>
			<a class="button button-outline" href="<?php echo career_h(career_url("admin/resume.php?id=" . (int) $application["id"])); ?>">Download CV · <?php echo career_h($application["resume_original_name"]); ?></a>
		</div>
		<?php if (!empty($application["qualifications_storage_name"])) { ?>
			<h3>Academic qualifications</h3>
			<div class="document-links">
				<a class="button button-outline" target="_blank" rel="noopener" href="<?php echo career_h(career_url("admin/document.php?id=" . (int) $application["id"] . "&type=qualifications&view=1")); ?>">View qualifications PDF</a>
				<a class="button button-outline" href="<?php echo career_h(career_url("admin/document.php?id=" . (int) $application["id"] . "&type=qualifications")); ?>">Download qualifications · <?php echo career_h($application["qualifications_original_name"]); ?></a>
			</div>
		<?php } ?>
		<?php if (trim((string) $application["feedback"]) !== "") { ?><div class="feedback-box"><strong>Previously shared feedback</strong><?php echo career_h($application["feedback"]); ?></div><?php } ?>
	</section>
	<aside class="panel">
		<h2>Decision and feedback</h2>
		<p class="muted">The applicant will see the saved status and feedback in their Careers account.</p>
		<?php if ($error !== "") { ?><div class="notice notice-error" role="alert"><?php echo career_h($error); ?></div><?php } ?>
		<form method="post" action="<?php echo career_h(career_url("admin/review.php?id=" . (int) $application_id)); ?>" onsubmit="return confirm('Save this application status and feedback? The applicant will be able to see the update.');">
			<?php career_csrf_input(); ?>
			<div class="field"><label for="status">Application status</label><select id="status" name="status" required><option value="" disabled <?php echo $status_value === "" ? "selected" : ""; ?>>Select a status</option><?php foreach ($statuses as $status) { ?><option value="<?php echo career_h($status); ?>" <?php echo $status_value === $status ? "selected" : ""; ?>><?php echo career_h($status); ?></option><?php } ?></select></div>
			<div class="field"><label for="feedback">Feedback for applicant</label><textarea id="feedback" name="feedback" maxlength="10000" placeholder="Share next steps or constructive feedback..."><?php echo career_h($feedback_value); ?></textarea></div>
			<button class="button" type="submit">Save update</button>
		</form>
	</aside>
</div>
<?php careers_page_end(); ?>
