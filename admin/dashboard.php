<?php
require_once __DIR__ . "/../includes/portal.php";
require_once __DIR__ . "/../includes/layout.php";
career_require_headteacher();
$database = careers_db();
$summary = $database->query(
	"SELECT COUNT(*) AS total,
		SUM(status = 'Under review') AS reviewing,
		SUM(status IN ('Shortlisted', 'Interviewed')) AS progressing
	 FROM career_applications"
);
if (!$summary) {
	http_response_code(500);
	die("Could not load career application statistics.");
}
$counts = $summary->fetch_assoc();
$jobs_result = $database->query("SELECT SUM(status = 'Open' AND (closing_date IS NULL OR closing_date >= CURRENT_DATE())) AS open_count FROM career_jobs");
if (!$jobs_result) {
	http_response_code(500);
	die("Could not load job statistics.");
}
$job_counts = $jobs_result->fetch_assoc();
$recent = $database->query(
	"SELECT a.id, a.status, a.created_at, p.full_name, p.email, j.title
	 FROM career_applications a
	 JOIN career_applicant_accounts p ON p.id = a.applicant_id
	 JOIN career_jobs j ON j.id = a.job_id
	 ORDER BY a.created_at DESC LIMIT 8"
);
if (!$recent) {
	http_response_code(500);
	die("Could not load recent career applications.");
}
careers_page_start("Careers overview", "admin");
?>
<div class="dashboard-intro"><div><p class="eyebrow">HEADTEACHER WORKSPACE</p><h1>Careers overview</h1><p>Welcome, <?php echo career_h(isset($_SESSION["career_headteacher_name"]) ? $_SESSION["career_headteacher_name"] : "Headteacher"); ?>. Manage roles and follow every application.</p></div><a class="button" href="<?php echo career_h(career_url("admin/jobs.php")); ?>">＋ Create a job</a></div>
<div class="stats">
	<div class="stat-card"><span>All applications</span><strong><?php echo (int) $counts["total"]; ?></strong></div>
	<div class="stat-card"><span>Awaiting review</span><strong><?php echo (int) $counts["reviewing"]; ?></strong></div>
	<div class="stat-card"><span>Shortlisted / interviewed</span><strong><?php echo (int) $counts["progressing"]; ?></strong></div>
	<div class="stat-card"><span>Open opportunities</span><strong><?php echo (int) $job_counts["open_count"]; ?></strong></div>
</div>
<section class="panel">
	<div class="panel-heading"><div><h2>Latest applications</h2><span class="muted">Review candidates and leave feedback visible in their account.</span></div></div>
	<?php if ($recent->num_rows === 0) { ?>
		<div class="empty-state"><p>No applications have been received yet.</p><a class="button" href="<?php echo career_h(career_url("admin/jobs.php")); ?>">Post the first job</a></div>
	<?php } else { ?>
		<div class="table-wrap"><table><thead><tr><th>Applicant</th><th>Role</th><th>Received</th><th>Status</th><th></th></tr></thead><tbody>
		<?php while ($application = $recent->fetch_assoc()) { ?>
			<tr><td><strong><?php echo career_h($application["full_name"]); ?></strong><div class="muted"><?php echo career_h($application["email"]); ?></div></td><td><?php echo career_h($application["title"]); ?></td><td><?php echo career_h(date("j M Y", strtotime($application["created_at"]))); ?></td><td><span class="status <?php echo career_h(career_status_class($application["status"])); ?>"><?php echo career_h($application["status"]); ?></span></td><td><a class="button button-outline button-small" href="<?php echo career_h(career_url("admin/review.php?id=" . (int) $application["id"])); ?>">Review</a></td></tr>
		<?php } ?>
		</tbody></table></div>
	<?php } ?>
</section>
<?php careers_page_end(); ?>
