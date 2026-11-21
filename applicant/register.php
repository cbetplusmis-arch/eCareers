<?php
require_once __DIR__ . "/../includes/portal.php";
require_once __DIR__ . "/../includes/layout.php";
if (!empty($_SESSION["career_applicant_id"])) {
	$registered_job_id = filter_input(INPUT_GET, "job", FILTER_VALIDATE_INT);
	career_redirect($registered_job_id && $registered_job_id > 0
		? "applicant/apply.php?job=" . (int) $registered_job_id
		: "applicant/dashboard.php");
}
$error = "";
$full_name = "";
$email = "";
$job_id = filter_input(INPUT_GET, "job", FILTER_VALIDATE_INT);
if ($_SERVER["REQUEST_METHOD"] === "POST") {
	career_verify_csrf();
	$job_id = filter_input(INPUT_POST, "job_id", FILTER_VALIDATE_INT);
	if ($job_id === false || $job_id < 1) {
		$job_id = null;
	}
	$full_name = trim(isset($_POST["full_name"]) ? (string) $_POST["full_name"] : "");
	$email = strtolower(trim(isset($_POST["email"]) ? (string) $_POST["email"] : ""));
	$password = isset($_POST["password"]) ? (string) $_POST["password"] : "";
	$confirm_password = isset($_POST["confirm_password"]) ? (string) $_POST["confirm_password"] : "";
	if ($full_name === "" || strlen($full_name) > 160 || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) {
		$error = "Enter your name and a valid email address.";
	} elseif (strlen($password) < 10) {
		$error = "Choose a password with at least 10 characters.";
	} elseif ($password !== $confirm_password) {
		$error = "The password confirmation does not match.";
	} else {
		$hash = password_hash($password, PASSWORD_DEFAULT);
		$statement = careers_db()->prepare("INSERT INTO career_applicant_accounts (full_name, email, password_hash) VALUES (?, ?, ?)");
		if (!$statement) {
			http_response_code(500);
			die("Could not prepare your applicant account.");
		}
		$statement->bind_param("sss", $full_name, $email, $hash);
		if ($statement->execute()) {
			$applicant_id = (int) careers_db()->insert_id;
			$statement->close();
			session_regenerate_id(true);
			unset($_SESSION["career_headteacher_id"], $_SESSION["career_headteacher_name"], $_SESSION["career_headteacher_email"]);
			$_SESSION["career_role"] = "Applicant";
			$_SESSION["career_applicant_id"] = $applicant_id;
			$_SESSION["career_applicant_name"] = $full_name;
			$_SESSION["career_applicant_email"] = $email;
			career_flash("Your applicant account is ready. You can now submit an application.");
			career_redirect($job_id ? "applicant/apply.php?job=" . (int) $job_id : "applicant/dashboard.php");
		}
		$error = $statement->errno === 1062
			? "An applicant account already exists for that email. Please sign in."
			: "Could not create your account. Please try again.";
		$statement->close();
	}
}
careers_page_start("Create applicant account", "applicant");
?>
<div class="auth-wrap"><section class="auth-card">
	<p class="auth-kicker">CANDIDATE ACCOUNT</p><h1>Create your profile</h1><p>One account keeps your applications and hiring updates together.</p>
	<?php if ($error !== "") { ?><div class="notice notice-error" role="alert"><?php echo career_h($error); ?></div><?php } ?>
	<form method="post" action="<?php echo career_h(career_url("applicant/register.php")); ?>">
		<?php career_csrf_input(); ?><input type="hidden" name="job_id" value="<?php echo $job_id && $job_id > 0 ? (int) $job_id : ""; ?>">
		<div class="field"><label for="full_name">Full name</label><input id="full_name" name="full_name" maxlength="160" autocomplete="name" required value="<?php echo career_h($full_name); ?>"></div>
		<div class="field"><label for="email">Email address</label><input id="email" name="email" type="email" maxlength="190" autocomplete="email" required value="<?php echo career_h($email); ?>"><span class="field-help">Use an active inbox. We use this address for hiring updates and password recovery.</span></div>
		<div class="field"><label for="password">Password</label><input id="password" name="password" type="password" minlength="10" autocomplete="new-password" required><span class="field-help">Use at least 10 characters.</span></div>
		<div class="field"><label for="confirm_password">Confirm password</label><input id="confirm_password" name="confirm_password" type="password" minlength="10" autocomplete="new-password" required></div>
		<button class="button" type="submit">Create account</button>
	</form>
	<p class="auth-switch">Already registered? <a class="text-link" href="<?php echo career_h(career_url("login.php" . ($job_id ? "?job=" . (int) $job_id : ""))); ?>">Sign in</a></p>
</section></div>
<?php careers_page_end(); ?>
