<?php
require_once __DIR__ . "/includes/portal.php";
require_once __DIR__ . "/includes/layout.php";
$error = "";
$email = "";
$job_id = filter_input(INPUT_GET, "job", FILTER_VALIDATE_INT);
if (!empty($_SESSION["career_applicant_id"]) && ($_SESSION["career_role"] ?? "") === "Applicant") {
	career_redirect($job_id && $job_id > 0 ? "applicant/apply.php?job=" . (int) $job_id : "applicant/dashboard.php");
}
if (!empty($_SESSION["career_headteacher_id"]) && ($_SESSION["career_role"] ?? "") === "Headteacher") {
	career_redirect("admin/dashboard.php");
}
if ($_SERVER["REQUEST_METHOD"] === "POST") {
	career_verify_csrf();
	$email = strtolower(trim(isset($_POST["email"]) ? (string) $_POST["email"] : ""));
	$password = isset($_POST["password"]) ? (string) $_POST["password"] : "";
	$job_id = filter_input(INPUT_POST, "job_id", FILTER_VALIDATE_INT);
	if ($job_id === false || $job_id < 1) {
		$job_id = null;
	}
	if (filter_var($email, FILTER_VALIDATE_EMAIL) && $password !== "") {
		$applicant_statement = careers_db()->prepare(
			"SELECT id, full_name, email, password_hash
			 FROM career_applicant_accounts WHERE email = ? LIMIT 1"
		);
		if (!$applicant_statement) {
			http_response_code(500);
			die("Could not prepare sign-in.");
		}
		$applicant_statement->bind_param("s", $email);
		if (!$applicant_statement->execute()) {
			http_response_code(500);
			die("Could not check the applicant account.");
		}
		$applicant = $applicant_statement->get_result()->fetch_assoc();
		$applicant_statement->close();
		$applicant_matches = $applicant && password_verify($password, $applicant["password_hash"]);

		$school_database = school_db();
		$headteacher_statement = $school_database->prepare(
			"SELECT id, full_name, email, password, status
			 FROM hmd WHERE email = ? AND role = 'Headteacher' LIMIT 1"
		);
		if (!$headteacher_statement) {
			http_response_code(500);
			die("Could not prepare school sign-in.");
		}
		$headteacher_statement->bind_param("s", $email);
		if (!$headteacher_statement->execute()) {
			http_response_code(500);
			die("Could not check the school account.");
		}
		$headteacher_statement->bind_result($headteacher_id, $headteacher_name, $headteacher_email, $stored_password, $account_status);
		$headteacher_found = $headteacher_statement->fetch();
		$headteacher_statement->close();
		$headteacher_matches = $headteacher_found
			&& strcasecmp((string) $account_status, "Active") === 0
			&& (password_verify($password, $stored_password) || hash_equals((string) $stored_password, $password));

		if ($applicant_matches && $headteacher_matches) {
			$error = "This sign-in matches more than one account. Contact the school office to resolve the account conflict.";
		} elseif ($applicant_matches) {
			session_regenerate_id(true);
			unset($_SESSION["career_headteacher_id"], $_SESSION["career_headteacher_name"], $_SESSION["career_headteacher_email"]);
			$_SESSION["career_role"] = "Applicant";
			$_SESSION["career_applicant_id"] = (int) $applicant["id"];
			$_SESSION["career_applicant_name"] = $applicant["full_name"];
			$_SESSION["career_applicant_email"] = $applicant["email"];
			career_redirect($job_id ? "applicant/apply.php?job=" . (int) $job_id : "applicant/dashboard.php");
		} elseif ($headteacher_matches) {
			session_regenerate_id(true);
			unset($_SESSION["career_applicant_id"], $_SESSION["career_applicant_name"], $_SESSION["career_applicant_email"]);
			$_SESSION["career_role"] = "Headteacher";
			$_SESSION["career_headteacher_id"] = (int) $headteacher_id;
			$_SESSION["career_headteacher_name"] = $headteacher_name;
			$_SESSION["career_headteacher_email"] = $headteacher_email;
			career_redirect("admin/dashboard.php");
		} else {
			$error = "The email address or password is not correct.";
		}
	} else {
		$error = "Enter a valid email address and password.";
	}
}
careers_page_start("Sign in");
?>
<section class="login-card">
	<div class="login-brand">
		<img src="<?php echo career_h(career_url("assets/lawrence-college-crest.jpg")); ?>" alt="">
		<strong><?php echo career_h(career_school_name()); ?></strong>
	</div>
	<h1>Welcome back</h1>
	<p class="login-intro">Sign in with your Careers Portal account to continue.</p>
		<?php if ($error !== "") { ?><div class="notice notice-error" role="alert"><?php echo career_h($error); ?></div><?php } ?>
		<form method="post" action="<?php echo career_h(career_url("login.php")); ?>">
			<?php career_csrf_input(); ?>
			<input type="hidden" name="job_id" value="<?php echo $job_id && $job_id > 0 ? (int) $job_id : ""; ?>">
			<div class="field"><label for="email">Email address</label><input id="email" name="email" type="email" autocomplete="username" required value="<?php echo career_h($email); ?>"></div>
			<div class="field"><label for="password">Password</label><div class="password-control"><input id="password" name="password" type="password" autocomplete="current-password" required><button class="password-toggle" type="button" aria-label="Show password" aria-pressed="false" title="Show password"><svg class="password-eye" viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"></path><circle cx="12" cy="12" r="3"></circle></svg><svg class="password-eye-off" viewBox="0 0 24 24" aria-hidden="true"><path d="m3 3 18 18M10.6 10.6a2 2 0 0 0 2.8 2.8"></path><path d="M9.9 5.2A10.7 10.7 0 0 1 12 5c6.4 0 10 7 10 7a16 16 0 0 1-3.1 3.8M6.2 6.2C3.5 8 2 12 2 12s3.6 7 10 7a10.7 10.7 0 0 0 3.3-.5"></path></svg></button></div></div>
			<button class="button" type="submit">Login</button>
		</form>
		<p class="forgot-link"><a class="text-link" href="<?php echo career_h(career_url("applicant/forgot-password.php")); ?>">Forgot your password?</a></p>
		<p class="auth-switch">New applicant? <a class="text-link" href="<?php echo career_h(career_url("applicant/register.php" . ($job_id ? "?job=" . (int) $job_id : ""))); ?>">Create an account</a></p>
	<a class="button button-outline login-return" href="<?php echo career_h(career_url("index.php")); ?>">Return to Careers Home</a>
</section>
<script>
	(function () {
		var password = document.getElementById("password");
		var toggle = document.querySelector(".password-toggle");
		if (!password || !toggle) {
			return;
		}
		toggle.addEventListener("click", function () {
			var showPassword = password.type === "password";
			password.type = showPassword ? "text" : "password";
			toggle.setAttribute("aria-pressed", showPassword ? "true" : "false");
			toggle.setAttribute("aria-label", showPassword ? "Hide password" : "Show password");
			toggle.setAttribute("title", showPassword ? "Hide password" : "Show password");
		});
	}());
</script>
<?php careers_page_end(); ?>
