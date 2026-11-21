<?php
require_once __DIR__ . "/../includes/portal.php";
require_once __DIR__ . "/../includes/layout.php";
career_require_applicant();
$applicant_id = (int) $_SESSION["career_applicant_id"];
$statement = careers_db()->prepare(
	"SELECT a.id, a.status, a.feedback, a.created_at, a.updated_at, j.title, j.department, j.location
	 FROM career_applications a
	 JOIN career_jobs j ON j.id = a.job_id
	 WHERE a.applicant_id = ?
	 ORDER BY a.created_at DESC"
);
if (!$statement) {
	http_response_code(500);
	die("Could not prepare your application list.");
}
$statement->bind_param("i", $applicant_id);
if (!$statement->execute()) {
	http_response_code(500);
	die("Could not load your applications.");
}
$applications = $statement->get_result();
$count = $applications->num_rows;
careers_page_start("My applications", "applicant");
?>
<div class="dashboard-intro">
	<div>
		<p class="eyebrow">CANDIDATE SPACE</p>
		<h1>Hello,
			<?php echo career_h(isset($_SESSION["career_applicant_name"]) ? $_SESSION["career_applicant_name"] : "there"); ?>
		</h1>
		<p>Your applications and hiring updates are collected here.</p>
	</div><a class="button" href="<?php echo career_h(career_url("applicant/opportunities.php")); ?>">Browse available
		jobs <span aria-hidden="true">→</span></a>
</div>
<div class="stats">
	<div class="stat-card"><span>Applications submitted</span><strong><?php echo $count; ?></strong></div>
	<div class="stat-card"><span>Most recent update</span><strong
			style="font-size:15px"><?php echo $count ? career_h(date("j M Y", strtotime($applications->fetch_assoc()["updated_at"]))) : "—"; ?></strong>
	</div>
</div>
<?php
if ($count > 0) {
	$applications->data_seek(0);
}
?>
<div class="section-heading">
	<div>
		<p class="eyebrow">YOUR PROGRESS</p>
		<h2>My applications</h2>
		<p>Statuses and feedback are updated by the Headteacher.</p>
	</div>
</div>
<?php if ($count === 0) { ?>
	<div class="panel empty-state">
		<h3>Your next step starts with a role</h3>
		<p>You have not applied for any jobs yet. Browse current opportunities, open a role, and apply securely from your
			applicant portal.</p><a class="button"
			href="<?php echo career_h(career_url("applicant/opportunities.php")); ?>">Browse opportunities</a>
	</div>
<?php } else { ?>
	<div class="application-list">
		<?php while ($application = $applications->fetch_assoc()) { ?>
			<article class="application-card">
				<div class="application-top">
					<div>
						<h3><?php echo career_h($application["title"]); ?></h3>
						<div class="muted"><?php echo career_h($application["department"]); ?> ·
							<?php echo career_h($application["location"]); ?> · Applied
							<?php echo career_h(date("j M Y", strtotime($application["created_at"]))); ?>
						</div>
					</div><span
						class="status <?php echo career_h(career_status_class($application["status"])); ?>"><?php echo career_h($application["status"]); ?></span>
				</div>
				<?php if (trim((string) $application["feedback"]) !== "") { ?>
					<div class="feedback-box"><strong>Feedback from the Headteacher · Updated
							<?php echo career_h(date("j M Y", strtotime($application["updated_at"]))); ?></strong><?php echo career_h($application["feedback"]); ?>
					</div><?php } ?>
			</article>
		<?php } ?>
	</div>
<?php } ?>
<?php careers_page_end(); ?>