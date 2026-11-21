<?php
require_once __DIR__ . "/includes/portal.php";
require_once __DIR__ . "/includes/layout.php";
$job_id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);
if (!$job_id || $job_id < 1) {
	http_response_code(400);
	exit("Choose a valid job.");
}
$statement = careers_db()->prepare(
	"SELECT id, title, department, employment_type, location, vacancies, contact_email, description, requirements, closing_date
	 FROM career_jobs
	 WHERE id = ? AND status = 'Open' AND (closing_date IS NULL OR closing_date >= CURRENT_DATE())
	 LIMIT 1"
);
if (!$statement) {
	http_response_code(500);
	die("Could not prepare the job details.");
}
$statement->bind_param("i", $job_id);
if (!$statement->execute()) {
	http_response_code(500);
	die("Could not load the job details.");
}
$job = $statement->get_result()->fetch_assoc();
$statement->close();
if (!$job) {
	http_response_code(404);
	exit("This opportunity is no longer available.");
}
$is_applicant_signed_in = !empty($_SESSION["career_applicant_id"])
	&& ($_SESSION["career_role"] ?? "") === "Applicant";
$browse_url = $is_applicant_signed_in
	? career_url("applicant/opportunities.php")
	: career_url("index.php#opportunities");
careers_page_start($job["title"]);
?>
<p><a class="text-link" href="<?php echo career_h($browse_url); ?>">← Browse opportunities</a></p>
<section class="detail-head">
	<p class="eyebrow">CAREER OPPORTUNITY · <?php echo career_h($job["department"]); ?></p>
	<h1><?php echo career_h($job["title"]); ?></h1>
	<p><?php echo career_h($job["employment_type"]); ?> <span aria-hidden="true">·</span> <?php echo career_h($job["location"]); ?></p>
</section>
<div class="detail-layout">
	<div>
		<section class="panel"><h2>About the role</h2><div class="prose"><?php echo career_h($job["description"]); ?></div></section>
		<section class="panel" style="margin-top:16px"><h2>What we're looking for</h2><div class="prose"><?php echo career_h($job["requirements"]); ?></div></section>
	</div>
	<aside class="panel side-apply">
		<h2>Ready to apply?</h2>
		<?php if ($is_applicant_signed_in) { ?>
			<p class="muted">You are signed in. Review the role details, then start your application when you're ready.</p>
		<?php } else { ?>
			<p class="muted">Applications are submitted securely from an applicant account. Sign in or create an account to continue.</p>
		<?php } ?>
		<dl><dt>Department</dt><dd><?php echo career_h($job["department"]); ?></dd><dt>Work arrangement</dt><dd><?php echo career_h($job["employment_type"]); ?></dd><dt>Location</dt><dd><?php echo career_h($job["location"]); ?></dd><dt>Vacancies</dt><dd><?php echo (int) $job["vacancies"]; ?> <?php echo (int) $job["vacancies"] === 1 ? "position" : "positions"; ?></dd><dt>Closing date</dt><dd><?php echo $job["closing_date"] ? career_h(date("j F Y", strtotime($job["closing_date"]))) : "Open until filled"; ?></dd><?php if (filter_var($job["contact_email"], FILTER_VALIDATE_EMAIL)) { ?><dt>Role enquiries</dt><dd><a class="text-link" href="<?php echo career_h("mailto:" . $job["contact_email"]); ?>"><?php echo career_h($job["contact_email"]); ?></a></dd><?php } ?></dl>
		<?php if ($is_applicant_signed_in) { ?>
			<a class="button" href="<?php echo career_h(career_url("applicant/apply.php?job=" . (int) $job["id"])); ?>">Apply for this role <span aria-hidden="true">→</span></a>
		<?php } else { ?>
			<a class="button" href="<?php echo career_h(career_url("login.php?job=" . (int) $job["id"])); ?>">Continue to apply <span aria-hidden="true">→</span></a>
			<a class="option-secondary" href="<?php echo career_h(career_url("applicant/register.php?job=" . (int) $job["id"])); ?>">New here? Create an applicant account</a>
		<?php } ?>
	</aside>
</div>
<?php careers_page_end(); ?>
