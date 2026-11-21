<?php
require_once __DIR__ . "/../includes/portal.php";
require_once __DIR__ . "/../includes/layout.php";
career_require_headteacher();
$database = careers_db();
$types = array("Full-time", "Part-time", "Contract", "Temporary");
$statuses = array("Open", "Closed");
$error = "";
$job_id = filter_input(INPUT_GET, "edit", FILTER_VALIDATE_INT);
$values = array(
	"title" => "", "department" => "", "employment_type" => "Full-time",
	"location" => "", "vacancies" => "1", "contact_email" => "", "description" => "", "requirements" => "",
	"closing_date" => "", "status" => "Open"
);
if ($job_id && $job_id > 0) {
	$load = $database->prepare("SELECT title, department, employment_type, location, vacancies, contact_email, description, requirements, closing_date, status FROM career_jobs WHERE id = ? LIMIT 1");
	$load->bind_param("i", $job_id);
	if (!$load->execute()) {
		http_response_code(500);
		die("Could not load the job for editing.");
	}
	$loaded = $load->get_result()->fetch_assoc();
	$load->close();
	if (!$loaded) {
		http_response_code(404);
		exit("The selected job was not found.");
	}
	$values = $loaded;
}
if ($_SERVER["REQUEST_METHOD"] === "POST") {
	career_verify_csrf();
	$job_id = filter_input(INPUT_POST, "job_id", FILTER_VALIDATE_INT);
	foreach ($values as $key => $unused) {
		$values[$key] = trim(isset($_POST[$key]) ? (string) $_POST[$key] : "");
	}
	$vacancy_count = filter_var($values["vacancies"], FILTER_VALIDATE_INT, array(
		"options" => array("min_range" => 1, "max_range" => 4294967295)
	));
	if ($vacancy_count === false) {
		$error = "Enter a whole number of vacancies greater than zero.";
	} elseif ($values["title"] === "" || strlen($values["title"]) > 160
		|| $values["department"] === "" || strlen($values["department"]) > 120
		|| $values["location"] === "" || strlen($values["location"]) > 160) {
		$error = "Enter a role title, department and location within the stated length limits.";
	} elseif (!filter_var($values["contact_email"], FILTER_VALIDATE_EMAIL) || strlen($values["contact_email"]) > 190) {
		$error = "Enter a valid recruitment contact email address. Applicants may use it for role-related questions.";
	} elseif (!in_array($values["employment_type"], $types, true) || !in_array($values["status"], $statuses, true)) {
		$error = "Choose a valid employment type and listing status.";
	} elseif ($values["description"] === "" || strlen($values["description"]) > 20000
		|| $values["requirements"] === "" || strlen($values["requirements"]) > 20000) {
		$error = "Add a role description and requirements (each no more than 20,000 characters).";
	} elseif ($values["closing_date"] !== "" && !career_valid_date($values["closing_date"])) {
		$error = "Enter a valid application closing date.";
	} elseif (isset($_POST["save_job"]) && $_POST["save_job"] === "1") {
		$closing_date = $values["closing_date"] === "" ? null : $values["closing_date"];
		if ($job_id && $job_id > 0) {
			$save = $database->prepare(
				"UPDATE career_jobs SET title = ?, department = ?, employment_type = ?, location = ?, vacancies = ?, contact_email = ?, description = ?, requirements = ?, closing_date = ?, status = ? WHERE id = ?"
			);
			$message = "Job listing updated.";
		} else {
			$headteacher_id = (int) $_SESSION["career_headteacher_id"];
			$save = $database->prepare(
				"INSERT INTO career_jobs (title, department, employment_type, location, vacancies, contact_email, description, requirements, closing_date, status, created_by_hmd_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
			);
			$message = "Job listing created.";
		}
		if (!$save) {
			http_response_code(500);
			die("Could not prepare the job listing.");
		}
		if ($job_id && $job_id > 0) {
			$save->bind_param("ssssisssssi", $values["title"], $values["department"], $values["employment_type"], $values["location"], $vacancy_count, $values["contact_email"], $values["description"], $values["requirements"], $closing_date, $values["status"], $job_id);
		} else {
			$save->bind_param("ssssisssssi", $values["title"], $values["department"], $values["employment_type"], $values["location"], $vacancy_count, $values["contact_email"], $values["description"], $values["requirements"], $closing_date, $values["status"], $headteacher_id);
		}
		if (!$save->execute()) {
			$error = "Could not save this job listing. Please try again.";
			$save->close();
		} else {
			$save->close();
			career_flash($message);
			career_redirect("admin/jobs.php");
		}
	}
}
$jobs = $database->query(
	"SELECT j.id, j.title, j.department, j.employment_type, j.location, j.vacancies, j.contact_email, j.status, j.closing_date, j.created_at,
		(SELECT COUNT(*) FROM career_applications a WHERE a.job_id = j.id) AS applicant_count
	 FROM career_jobs j ORDER BY j.created_at DESC"
);
if (!$jobs) {
	http_response_code(500);
	die("Could not load job listings.");
}
careers_page_start("Manage job listings", "admin");
?>
<div class="dashboard-intro"><div><p class="eyebrow">HIRING MANAGEMENT</p><h1>Manage opportunities</h1><p>Create a clear role listing, then open or close applications when needed.</p></div></div>
<section class="panel">
	<div class="panel-heading"><h2><?php echo $job_id ? "Edit job listing" : "Create a job listing"; ?></h2><?php if ($job_id) { ?><a class="text-link" href="<?php echo career_h(career_url("admin/jobs.php")); ?>">Cancel edit</a><?php } ?></div>
	<?php if ($error !== "") { ?><div class="notice notice-error" role="alert"><?php echo career_h($error); ?></div><?php } ?>
	<form method="post" action="<?php echo career_h(career_url("admin/jobs.php" . ($job_id ? "?edit=" . (int) $job_id : ""))); ?>" onsubmit="return confirm('<?php echo $job_id ? "Save changes to this job listing?" : "Post this job listing?"; ?>');">
		<?php career_csrf_input(); ?><input type="hidden" name="job_id" value="<?php echo $job_id ? (int) $job_id : ""; ?>"><input type="hidden" name="save_job" value="1">
		<div class="form-grid">
			<div class="field"><label for="title">Job title</label><input id="title" name="title" maxlength="160" required value="<?php echo career_h($values["title"]); ?>"></div>
			<div class="field"><label for="department">Department</label><input id="department" name="department" maxlength="120" required value="<?php echo career_h($values["department"]); ?>"></div>
			<div class="field"><label for="employment_type">Employment type</label><select id="employment_type" name="employment_type"><?php foreach ($types as $type) { ?><option value="<?php echo career_h($type); ?>" <?php echo $values["employment_type"] === $type ? "selected" : ""; ?>><?php echo career_h($type); ?></option><?php } ?></select></div>
			<div class="field"><label for="location">Location</label><input id="location" name="location" maxlength="160" required value="<?php echo career_h($values["location"]); ?>"></div>
			<div class="field"><label for="vacancies">Number of vacancies</label><input id="vacancies" name="vacancies" type="number" min="1" step="1" required value="<?php echo career_h($values["vacancies"]); ?>"><span class="field-help">How many people are needed for this role?</span></div>
			<div class="field"><label for="contact_email">Recruitment contact email</label><input id="contact_email" name="contact_email" type="email" maxlength="190" autocomplete="email" required value="<?php echo career_h($values["contact_email"]); ?>"><span class="field-help">Use a real, monitored email address applicants can contact about this role.</span></div>
			<div class="field"><label for="closing_date">Closing date (optional)</label><input id="closing_date" name="closing_date" type="date" value="<?php echo career_h($values["closing_date"]); ?>"></div>
			<div class="field"><label for="status">Listing status</label><select id="status" name="status"><?php foreach ($statuses as $status) { ?><option value="<?php echo career_h($status); ?>" <?php echo $values["status"] === $status ? "selected" : ""; ?>><?php echo career_h($status); ?></option><?php } ?></select></div>
		</div>
		<div class="field"><label for="description">Role description</label><textarea id="description" name="description" maxlength="20000" required><?php echo career_h($values["description"]); ?></textarea></div>
		<div class="field"><label for="requirements">Requirements and qualifications</label><textarea id="requirements" name="requirements" maxlength="20000" required><?php echo career_h($values["requirements"]); ?></textarea></div>
		<button class="button" type="submit">Save listing</button>
	</form>
</section>
<section class="admin-section panel">
	<div class="panel-heading"><h2>All listings</h2><span class="muted"><?php echo (int) $jobs->num_rows; ?> total</span></div>
	<?php if ($jobs->num_rows === 0) { ?><div class="empty-state"><p>No listings yet. Create a role above to publish it.</p></div><?php } else { ?>
		<div class="table-wrap"><table><thead><tr><th>Role</th><th>Type / location</th><th>Vacancies</th><th>Contact email</th><th>Applicants</th><th>Closing date</th><th>Status</th><th></th></tr></thead><tbody>
		<?php while ($job = $jobs->fetch_assoc()) { ?>
			<tr><td><strong><?php echo career_h($job["title"]); ?></strong><div class="muted"><?php echo career_h($job["department"]); ?></div></td><td><?php echo career_h($job["employment_type"]); ?><div class="muted"><?php echo career_h($job["location"]); ?></div></td><td><?php echo (int) $job["vacancies"]; ?></td><td><?php echo career_h($job["contact_email"]); ?></td><td><?php echo (int) $job["applicant_count"]; ?></td><td><?php echo $job["closing_date"] ? career_h(date("j M Y", strtotime($job["closing_date"]))) : "No closing date"; ?></td><td><span class="status <?php echo $job["status"] === "Open" ? "status-shortlisted" : ""; ?>"><?php echo career_h($job["status"]); ?></span></td><td><a class="button button-outline button-small" href="<?php echo career_h(career_url("admin/jobs.php?edit=" . (int) $job["id"])); ?>">Edit</a></td></tr>
		<?php } ?>
		</tbody></table></div>
	<?php } ?>
</section>
<?php careers_page_end(); ?>
