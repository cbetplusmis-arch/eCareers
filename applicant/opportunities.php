<?php
require_once __DIR__ . "/../includes/portal.php";
require_once __DIR__ . "/../includes/layout.php";
career_require_applicant();
$result = careers_db()->query(
	"SELECT id, title, department, employment_type, location, vacancies, description, closing_date
	 FROM career_jobs
	 WHERE status = 'Open' AND (closing_date IS NULL OR closing_date >= CURRENT_DATE())
	 ORDER BY created_at DESC"
);
if (!$result) {
	http_response_code(500);
	die("Could not load current career opportunities.");
}
careers_page_start("Available opportunities", "applicant");
?>
<section class="applicant-opportunities">
	<div class="section-heading">
		<div><p class="eyebrow">CAREERS AT <?php echo career_h(strtoupper(career_school_name())); ?></p><h1>Available opportunities</h1></div>
		<span class="tag role-count"><?php echo (int) $result->num_rows; ?> open <?php echo $result->num_rows === 1 ? "opportunity" : "opportunities"; ?></span>
	</div>
	<?php if ($result->num_rows === 0) { ?>
		<div class="panel empty-state"><h2>No open roles right now</h2><p>There are no current opportunities. Please check back soon.</p></div>
	<?php } else { ?>
		<div class="job-grid">
			<?php while ($job = $result->fetch_assoc()) { ?>
				<article class="job-card">
					<div class="job-meta"><span class="tag tag-open">Accepting applications</span><span class="tag"><?php echo career_h($job["employment_type"]); ?></span><span class="tag"><?php echo (int) $job["vacancies"]; ?> <?php echo (int) $job["vacancies"] === 1 ? "vacancy" : "vacancies"; ?></span></div>
					<h2><a href="<?php echo career_h(career_url("job.php?id=" . (int) $job["id"])); ?>"><?php echo career_h($job["title"]); ?></a></h2>
					<p><?php echo career_h($job["department"]); ?> · <?php echo career_h($job["location"]); ?></p>
					<p><?php echo career_h(mb_strimwidth(trim(strip_tags($job["description"])), 0, 170, "…")); ?></p>
					<div class="job-footer"><small><?php echo $job["closing_date"] ? "Apply by " . career_h(date("j M Y", strtotime($job["closing_date"]))) : "Applications currently open"; ?></small><a class="text-link" href="<?php echo career_h(career_url("job.php?id=" . (int) $job["id"])); ?>">View role <span aria-hidden="true">→</span></a></div>
				</article>
			<?php } ?>
		</div>
	<?php } ?>
</section>
<?php careers_page_end(); ?>
