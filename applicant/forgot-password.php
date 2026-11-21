<?php
require_once __DIR__ . "/../includes/portal.php";
require_once __DIR__ . "/../includes/layout.php";
require_once __DIR__ . "/../includes/mailer.php";
$submitted = false;
if ($_SERVER["REQUEST_METHOD"] === "POST") {
	career_verify_csrf();
	$email = strtolower(trim(isset($_POST["email"]) ? (string) $_POST["email"] : ""));
	if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
		$database = careers_db();
		$account_statement = $database->prepare(
			"SELECT id, full_name, email FROM career_applicant_accounts WHERE email = ? LIMIT 1"
		);
		if (!$account_statement) {
			http_response_code(500);
			die("Could not prepare the password recovery request.");
		}
		$account_statement->bind_param("s", $email);
		if (!$account_statement->execute()) {
			http_response_code(500);
			die("Could not check the applicant account.");
		}
		$account = $account_statement->get_result()->fetch_assoc();
		$account_statement->close();
		if ($account) {
			$applicant_id = (int) $account["id"];
			$rate_check = $database->prepare(
				"SELECT COUNT(*) AS request_count FROM career_password_resets
				 WHERE applicant_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 10 MINUTE)"
			);
			if (!$rate_check) {
				http_response_code(500);
				die("Could not check password recovery limits.");
			}
			$rate_check->bind_param("i", $applicant_id);
			if (!$rate_check->execute()) {
				http_response_code(500);
				die("Could not check password recovery limits.");
			}
			$request_count = (int) $rate_check->get_result()->fetch_assoc()["request_count"];
			$rate_check->close();
			if ($request_count < 3) {
				$raw_token = bin2hex(random_bytes(32));
				$token_hash = hash("sha256", $raw_token);
				$expire_tokens = $database->prepare(
					"UPDATE career_password_resets SET used_at = NOW()
					 WHERE applicant_id = ? AND used_at IS NULL"
				);
				if (!$expire_tokens) {
					http_response_code(500);
					die("Could not prepare password recovery.");
				}
				$expire_tokens->bind_param("i", $applicant_id);
				if (!$expire_tokens->execute()) {
					http_response_code(500);
					die("Could not invalidate previous password recovery links.");
				}
				$expire_tokens->close();
				$save_token = $database->prepare(
					"INSERT INTO career_password_resets (applicant_id, token_hash, expires_at)
					 VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 30 MINUTE))"
				);
				if (!$save_token) {
					http_response_code(500);
					die("Could not prepare a password recovery link.");
				}
				$save_token->bind_param("is", $applicant_id, $token_hash);
				if (!$save_token->execute()) {
					http_response_code(500);
					die("Could not save the password recovery link.");
				}
				$save_token->close();
				$reset_url = career_absolute_url("applicant/reset-password.php?token=" . rawurlencode($raw_token));
				$mail_error = "";
				if ($reset_url === false
					|| !career_send_password_reset_email($account["email"], $account["full_name"], $reset_url, $mail_error)) {
					error_log("Careers Portal password reset email could not be sent: "
						. ($reset_url === false ? "Configure CAREER_PORTAL_BASE_URL or use a valid Host header." : $mail_error));
				}
			}
		}
	}
	$submitted = true;
}
careers_page_start("Applicant password recovery", "applicant");
?>
<div class="auth-wrap"><section class="auth-card">
	<p class="auth-kicker">APPLICANT ACCOUNT</p><h1>Reset your password</h1>
	<p>Enter the email address on your applicant account. We will email a one-time reset link if the account can be found.</p>
	<?php if ($submitted) { ?><div class="notice notice-success" role="status">If an applicant account matches that email, a password reset link will arrive shortly. Check your inbox and spam folder.</div><?php } ?>
	<form method="post" action="<?php echo career_h(career_url("applicant/forgot-password.php")); ?>">
		<?php career_csrf_input(); ?>
		<div class="field"><label for="email">Applicant email address</label><input id="email" name="email" type="email" autocomplete="email" maxlength="190" required></div>
		<button class="button" type="submit">Email reset link</button>
	</form>
	<p class="auth-switch"><a class="text-link" href="<?php echo career_h(career_url("login.php")); ?>">Return to sign in</a></p>
</section></div>
<?php careers_page_end(); ?>
