<?php
function careers_page_start($title, $area = "public") {
	$school = career_school_name();
	$is_admin = $area === "admin";
	$is_applicant_signed_in = !empty($_SESSION["career_applicant_id"]) && (isset($_SESSION["career_role"]) && $_SESSION["career_role"] === "Applicant");
	$is_headteacher_signed_in = !empty($_SESSION["career_headteacher_id"]) && (isset($_SESSION["career_role"]) && $_SESSION["career_role"] === "Headteacher");
	$is_login = $title === "Sign in";
	$brand_target = $is_headteacher_signed_in
		? "admin/dashboard.php"
		: ($is_applicant_signed_in ? "applicant/dashboard.php" : "index.php");
	$flash = career_take_flash();
	?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="theme-color" content="#292443">
	<title><?php echo career_h($title); ?> | Careers at <?php echo career_h($school); ?></title>
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="<?php echo career_h(career_url("assets/careers.css") . "?v=" . filemtime(__DIR__ . "/../assets/careers.css")); ?>">
</head>
<body class="<?php echo ($is_admin ? "portal-admin " : "") . ($is_login ? "login-page" : ""); ?>">
<header class="site-header">
	<div class="header-inner">
		<a class="brand" href="<?php echo career_h(career_url($brand_target)); ?>">
			<img class="brand-logo" src="<?php echo career_h(career_url("assets/lawrence-college-crest.jpg")); ?>" alt="<?php echo career_h($school); ?> crest">
			<span><strong><?php echo career_h($school); ?></strong><small>CAREERS &amp; OPPORTUNITIES</small></span>
		</a>
		<nav class="main-nav" aria-label="Main navigation">
			<?php if ($is_headteacher_signed_in) { ?>
				<a href="<?php echo career_h(career_url("admin/dashboard.php")); ?>">Dashboard</a>
				<a href="<?php echo career_h(career_url("admin/jobs.php")); ?>">Manage jobs</a>
				<a href="<?php echo career_h(career_url("admin/applicants.php")); ?>">Applications</a>
				<a class="nav-button" href="<?php echo career_h(career_url("admin/logout.php")); ?>" onclick="return confirm('Are you sure you want to sign out?');">Sign out</a>
			<?php } elseif ($is_applicant_signed_in) { ?>
				<a href="<?php echo career_h(career_url("applicant/dashboard.php")); ?>">My applications</a>
				<a class="nav-button" href="<?php echo career_h(career_url("applicant/logout.php")); ?>" onclick="return confirm('Are you sure you want to sign out?');">Sign out</a>
			<?php } else { ?>
				<a href="<?php echo career_h(career_url("index.php")); ?>">Home</a>
				<a class="nav-button" href="<?php echo career_h(career_url("login.php")); ?>">Login</a>
			<?php } ?>
		</nav>
		<button class="mobile-menu-toggle" type="button" aria-expanded="false" aria-controls="mobile-menu" aria-label="Open navigation menu">
			<span class="menu-icon" aria-hidden="true"><span></span><span></span><span></span></span>
		</button>
	</div>
</header>
<div class="mobile-menu" id="mobile-menu" role="dialog" aria-modal="true" aria-label="Careers Portal navigation" hidden>
	<div class="mobile-menu-heading">
		<a class="mobile-menu-brand" href="<?php echo career_h(career_url($brand_target)); ?>">
			<img src="<?php echo career_h(career_url("assets/lawrence-college-crest.jpg")); ?>" alt="">
			<strong><?php echo career_h($school); ?></strong>
		</a>
		<button class="mobile-menu-close" type="button" aria-label="Close navigation menu"><span aria-hidden="true">&times;</span></button>
	</div>
	<nav class="mobile-menu-links" aria-label="Careers Portal">
		<?php if ($is_headteacher_signed_in) { ?>
			<a href="<?php echo career_h(career_url("admin/dashboard.php")); ?>">Dashboard</a>
			<a href="<?php echo career_h(career_url("admin/jobs.php")); ?>">Manage jobs</a>
			<a href="<?php echo career_h(career_url("admin/applicants.php")); ?>">Applications</a>
			<a class="mobile-menu-signout" href="<?php echo career_h(career_url("admin/logout.php")); ?>" onclick="return confirm('Are you sure you want to sign out?');">Sign out</a>
		<?php } elseif ($is_applicant_signed_in) { ?>
			<a href="<?php echo career_h(career_url("applicant/dashboard.php")); ?>">My applications</a>
			<a class="mobile-menu-signout" href="<?php echo career_h(career_url("applicant/logout.php")); ?>" onclick="return confirm('Are you sure you want to sign out?');">Sign out</a>
		<?php } else { ?>
			<a href="<?php echo career_h(career_url("index.php")); ?>">Home</a>
			<a class="mobile-menu-signout" href="<?php echo career_h(career_url("login.php")); ?>">Login</a>
		<?php } ?>
	</nav>
</div>
<main class="page-shell">
	<?php if ($flash) { ?>
		<div class="notice notice-<?php echo career_h($flash["type"]); ?>" role="<?php echo $flash["type"] === "error" ? "alert" : "status"; ?>"><?php echo career_h($flash["message"]); ?></div>
	<?php } ?>
	<?php
}

function careers_page_end() {
	?>
</main>
<footer class="site-footer">
	<span>Careers applications are managed by the Careers Office at <?php echo career_h(career_school_name()); ?>.</span>
</footer>
<script>
	(function () {
		var toggle = document.querySelector(".mobile-menu-toggle");
		var menu = document.getElementById("mobile-menu");
		var closeButton = menu ? menu.querySelector(".mobile-menu-close") : null;
		if (!toggle || !menu || !closeButton) {
			return;
		}
		function setMenuOpen(open) {
			menu.hidden = !open;
			toggle.setAttribute("aria-expanded", open ? "true" : "false");
			toggle.setAttribute("aria-label", open ? "Close navigation menu" : "Open navigation menu");
			document.body.classList.toggle("menu-open", open);
			if (open) {
				closeButton.focus();
			} else {
				toggle.focus();
			}
		}
		toggle.addEventListener("click", function () {
			setMenuOpen(menu.hidden);
		});
		closeButton.addEventListener("click", function () {
			setMenuOpen(false);
		});
		menu.addEventListener("keydown", function (event) {
			if (event.key === "Escape") {
				setMenuOpen(false);
				return;
			}
			if (event.key !== "Tab") {
				return;
			}
			var focusable = menu.querySelectorAll('a[href], button:not([disabled])');
			var first = focusable[0];
			var last = focusable[focusable.length - 1];
			if (event.shiftKey && document.activeElement === first) {
				event.preventDefault();
				last.focus();
			} else if (!event.shiftKey && document.activeElement === last) {
				event.preventDefault();
				first.focus();
			}
		});
	}());
</script>
</body>
</html>
	<?php
}
