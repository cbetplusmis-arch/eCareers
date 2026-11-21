<?php
require_once __DIR__ . "/../includes/portal.php";
require_once __DIR__ . "/../includes/layout.php";
career_require_headteacher();
$database = careers_db();
$filter = trim(isset($_GET["status"]) ? (string) $_GET["status"] : "");
$statuses = array("Under review", "Shortlisted", "Interviewed", "Hired");
if ($filter !== "" && !in_array($filter, $statuses, true)) {
	$filter = "";
}
if ($filter !== "") {
	$statement = $database->prepare(
		"SELECT a.id, a.status, a.created_at, a.feedback, p.full_name, p.email, j.title
		 FROM career_applications a
		 JOIN career_applicant_accounts p ON p.id = a.applicant_id
		 JOIN career_jobs j ON j.id = a.job_id
		 WHERE a.status = ? ORDER BY a.created_at DESC"
	);
	$statement->bind_param("s", $filter);
	if (!$statement->execute()) {
		http_response_code(500);
		die("Could not filter career applications.");
	}
	$applications = $statement->get_result();
} else {
	$applications = $database->query(
		"SELECT a.id, a.status, a.created_at, a.feedback, p.full_name, p.email, j.title
		 FROM career_applications a
		 JOIN career_applicant_accounts p ON p.id = a.applicant_id
		 JOIN career_jobs j ON j.id = a.job_id
		 ORDER BY a.created_at DESC"
	);
	if (!$applications) {
		http_response_code(500);
		die("Could not load career applications.");
	}
}
careers_page_start("Career applications", "admin");
?>
<div class="dashboard-intro"><div><p class="eyebrow">CANDIDATE PIPELINE</p><h1>Applications</h1><p>Review application materials, record a decision and leave feedback for the applicant.</p></div></div>
<section class="panel">
	<div class="panel-heading"><h2><?php echo $filter ? career_h($filter) : "All applicants"; ?></h2><form method="get" action="<?php echo career_h(career_url("admin/applicants.php")); ?>"><label class="field-help" for="status-filter">Filter by status</label><select id="status-filter" name="status" onchange="this.form.submit()"><option value="">All statuses</option><?php foreach ($statuses as $status) { ?><option value="<?php echo career_h($status); ?>" <?php echo $filter === $status ? "selected" : ""; ?>><?php echo career_h($status); ?></option><?php } ?></select></form></div>
	<?php if ($applications->num_rows === 0) { ?><div class="empty-state"><p>No applications match this view.</p></div><?php } else { ?>
		<div class="table-wrap"><table><thead><tr><th>Applicant</th><th>Applied role</th><th>Date received</th><th>Status</th><th>Feedback</th><th></th></tr></thead><tbody>
		<?php while ($application = $applications->fetch_assoc()) { ?>
			<tr><td><strong><?php echo career_h($application["full_name"]); ?></strong><div class="muted"><?php echo career_h($application["email"]); ?></div></td><td><?php echo career_h($application["title"]); ?></td><td><?php echo career_h(date("j M Y", strtotime($application["created_at"]))); ?></td><td><span class="status <?php echo career_h(career_status_class($application["status"])); ?>"><?php echo career_h($application["status"]); ?></span></td><td><?php echo trim((string) $application["feedback"]) === "" ? "—" : career_h(mb_strimwidth($application["feedback"], 0, 55, "…")); ?></td><td><a class="button button-outline button-small" href="<?php echo career_h(career_url("admin/review.php?id=" . (int) $application["id"])); ?>">Review</a></td></tr>
		<?php } ?>
		</tbody></table></div>
	<?php } ?>
</section>
<?php careers_page_end(); ?>
