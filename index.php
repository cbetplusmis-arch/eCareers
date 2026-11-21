<?php
require_once __DIR__ . "/includes/portal.php";
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
require_once __DIR__ . "/includes/layout.php";
careers_page_start("Careers home");
?>
<section class="hero">
	<div class="hero-content">
		<div class="hero-school"><img src="<?php echo career_h(career_url("assets/lawrence-college-crest.jpg")); ?>" alt=""><span><?php echo career_h(career_school_name()); ?><small>CAREERS &amp; OPPORTUNITIES</small></span></div>
		<p class="eyebrow">A PURPOSEFUL CAREER STARTS HERE</p>
		<h1>Make a difference.<br><span>Grow with us.</span></h1>
		<p class="hero-copy">Build your future at <?php echo career_h(career_school_name()); ?>. Explore meaningful roles, share what you bring, and find your place in a community committed to helping young people thrive.</p>
		<div class="hero-actions">
			<a class="button button-light" href="#opportunities">Explore opportunities <span aria-hidden="true">→</span></a>
			<a class="button button-outline" href="<?php echo career_h(career_url("login.php")); ?>">Track your application</a>
		</div>
		<div class="hero-proof"><span><b>01</b> Find a role</span><span><b>02</b> Apply online</span><span><b>03</b> Follow your progress</span></div>
	</div>
	<aside class="hero-visual">
		<img class="campus-image" src="<?php echo career_h(career_url("assets/school-campus.jpg")); ?>" alt="The school campus, home to a community of learners and educators">
		<div class="campus-caption"><span class="campus-caption-icon" aria-hidden="true">✦</span><span><strong>A community with purpose</strong><small>Bring your talent. Help shape what's next.</small></span></div>
		<div class="welcome-stamp"><img src="<?php echo career_h(career_url("assets/lawrence-college-crest.jpg")); ?>" alt=""><span>YOUR NEXT<br>CHAPTER</span></div>
	</aside>
</section>
<section class="purpose-strip" aria-label="What makes careers at our school meaningful">
	<div><span class="purpose-icon">✳</span><span><strong>Purpose in every role</strong><small>Help learners build a brighter future.</small></span></div>
	<div><span class="purpose-icon">⌂</span><span><strong>People make the difference</strong><small>Join a caring, collaborative community.</small></span></div>
	<div><span class="purpose-icon">↗</span><span><strong>Room to grow</strong><small>Bring your strengths and keep learning.</small></span></div>
</section>
<section id="opportunities">
	<div class="section-heading">
		<div><p class="eyebrow">TAKE YOUR NEXT STEP</p><h2>Opportunities to make an impact</h2><p>Find a role that fits your skills, experience and the difference you want to make.</p></div>
		<span class="tag role-count"><?php echo (int) $result->num_rows; ?> open <?php echo $result->num_rows === 1 ? "opportunity" : "opportunities"; ?></span>
	</div>
	<?php if ($result->num_rows === 0) { ?>
		<div class="panel empty-state"><h3>No open roles right now</h3><p>There are no current opportunities. Please check back soon.</p></div>
	<?php } else { ?>
		<div class="job-grid">
			<?php while ($job = $result->fetch_assoc()) { ?>
				<article class="job-card">
					<div class="job-meta"><span class="tag tag-open">Accepting applications</span><span class="tag"><?php echo career_h($job["employment_type"]); ?></span><span class="tag"><?php echo (int) $job["vacancies"]; ?> <?php echo (int) $job["vacancies"] === 1 ? "vacancy" : "vacancies"; ?></span></div>
					<h3><a href="<?php echo career_h(career_url("job.php?id=" . (int) $job["id"])); ?>"><?php echo career_h($job["title"]); ?></a></h3>
					<p><?php echo career_h($job["department"]); ?> · <?php echo career_h($job["location"]); ?></p>
					<p><?php echo career_h(mb_strimwidth(trim(strip_tags($job["description"])), 0, 170, "…")); ?></p>
					<div class="job-footer"><small><?php echo $job["closing_date"] ? "Apply by " . career_h(date("j M Y", strtotime($job["closing_date"]))) : "Applications currently open"; ?></small><a class="text-link" href="<?php echo career_h(career_url("job.php?id=" . (int) $job["id"])); ?>">View role <span aria-hidden="true">→</span></a></div>
				</article>
			<?php } ?>
		</div>
	<?php } ?>
</section>
<section class="journey-section">
	<div class="section-heading"><div><p class="eyebrow">A CLEAR, SUPPORTIVE PROCESS</p><h2>Your application, step by step</h2><p>From exploring roles to receiving feedback, your next step stays easy to follow.</p></div></div>
	<div class="journey-grid">
		<article class="journey-card"><span>01</span><h3>Explore the role</h3><p>Read the responsibilities, requirements and closing date to see if the opportunity is right for you.</p></article>
		<article class="journey-card"><span>02</span><h3>Apply with confidence</h3><p>Create your applicant profile and securely send your CV, one PDF containing all academic qualifications, and a short cover letter.</p></article>
		<article class="journey-card"><span>03</span><h3>Stay in the know</h3><p>Sign in any time to see your application status and feedback from the Headteacher.</p></article>
	</div>
</section>
<section class="closing-banner"><div><p class="eyebrow">YOUR EXPERIENCE CAN MAKE A DIFFERENCE</p><h2>Find work with purpose.</h2><p>Take a look at our open opportunities and imagine what you could build with us.</p></div><a class="button button-light" href="#opportunities">See open opportunities <span aria-hidden="true">↑</span></a></section>
<?php careers_page_end(); ?>
