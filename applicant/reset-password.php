<?php
require_once __DIR__ . "/../includes/portal.php";
require_once __DIR__ . "/../includes/layout.php";
$token = trim(isset($_POST["token"]) ? (string) $_POST["token"] : (isset($_GET["token"]) ? (string) $_GET["token"] : ""));
$token_valid = preg_match('/\A[a-f0-9]{64}\z/', $token) === 1;
$error = "";
$reset_complete = false;
$password = "";
$confirm_password = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
	career_verify_csrf();
	$password = isset($_POST["password"]) ? (string) $_POST["password"] : "";
	$confirm_password = isset($_POST["confirm_password"]) ? (string) $_POST["confirm_password"] : "";
	if (!$token_valid) {
		$error = "This reset link is invalid or has expired.";
	} elseif (strlen($password) < 10) {
		$error = "Choose a password with at least 10 characters.";
	} elseif ($password !== $confirm_password) {
		$error = "The password confirmation does not match.";
	} else {
		$database = careers_db();
		$token_hash = hash("sha256", $token);
		if (!$database->begin_transaction()) {
			$error = "Could not start the password update. Please try again.";
		} else {
			$lookup = $database->prepare(
				"SELECT id, applicant_id FROM career_password_resets
				 WHERE token_hash = ? AND used_at IS NULL AND expires_at > NOW()
				 LIMIT 1 FOR UPDATE"
			);
			if (!$lookup) {
				$database->rollback();
				http_response_code(500);
				die("Could not prepare password recovery.");
			}
			$lookup->bind_param("s", $token_hash);
			if (!$lookup->execute()) {
				$lookup->close();
				$database->rollback();
				http_response_code(500);
				die("Could not verify the reset link.");
			}
			$reset = $lookup->get_result()->fetch_assoc();
			$lookup->close();
			if (!$reset) {
				$database->rollback();
				$error = "This reset link is invalid, expired or has already been used.";
			} else {
				$applicant_id = (int) $reset["applicant_id"];
				$password_hash = password_hash($password, PASSWORD_DEFAULT);
				$update_account = $database->prepare(
					"UPDATE career_applicant_accounts SET password_hash = ? WHERE id = ?"
				);
				if (!$update_account) {
					$database->rollback();
					http_response_code(500);
					die("Could not prepare the password update.");
				}
				$update_account->bind_param("si", $password_hash, $applicant_id);
				$account_saved = $update_account->execute();
				$update_account->close();
				$consume_tokens = $database->prepare(
					"UPDATE career_password_resets SET used_at = NOW()
					 WHERE applicant_id = ? AND used_at IS NULL"
				);
				if (!$consume_tokens) {
					$database->rollback();
					http_response_code(500);
					die("Could not prepare reset-link invalidation.");
				}
				$consume_tokens->bind_param("i", $applicant_id);
				$tokens_saved = $consume_tokens->execute();
				$consume_tokens->close();
				if (!$account_saved || !$tokens_saved || !$database->commit()) {
					$database->rollback();
					$error = "Could not save the new password. Please try again.";
				} else {
					$reset_complete = true;
				}
			}
		}
	}
}

$token_row = null;
if (!$reset_complete && $token_valid && $_SERVER["REQUEST_METHOD"] !== "POST") {
	$token_hash = hash("sha256", $token);
	$check = careers_db()->prepare(
		"SELECT id FROM career_password_resets
		 WHERE token_hash = ? AND used_at IS NULL AND expires_at > NOW() LIMIT 1"
	);
	if (!$check) {
		http_response_code(500);
		die("Could not check the reset link.");
	}
	$check->bind_param("s", $token_hash);
	if (!$check->execute()) {
		http_response_code(500);
		die("Could not check the reset link.");
	}
	$token_row = $check->get_result()->fetch_assoc();
	$check->close();
}
careers_page_start("Choose a new applicant password", "applicant");
?>
<div class="auth-wrap"><section class="auth-card">
	<p class="auth-kicker">APPLICANT ACCOUNT</p><h1>Choose a new password</h1>
	<?php if ($reset_complete) { ?>
		<div class="notice notice-success" role="status">Your password has been reset. You can now sign in with your new password.</div>
		<a class="button" href="<?php echo career_h(career_url("login.php")); ?>">Return to sign in</a>
	<?php } elseif (!$token_row) { ?>
		<div class="notice notice-error" role="alert"><?php echo career_h($error !== "" ? $error : "This reset link is invalid, expired or has already been used."); ?></div>
		<a class="button" href="<?php echo career_h(career_url("applicant/forgot-password.php")); ?>">Request another reset link</a>
	<?php } else { ?>
		<p>Choose a password you have not used here before. Reset links expire after 30 minutes and work once.</p>
		<?php if ($error !== "") { ?><div class="notice notice-error" role="alert"><?php echo career_h($error); ?></div><?php } ?>
		<form method="post" action="<?php echo career_h(career_url("applicant/reset-password.php")); ?>">
			<?php career_csrf_input(); ?><input type="hidden" name="token" value="<?php echo career_h($token); ?>">
			<div class="field"><label for="password">New password</label><input id="password" name="password" type="password" minlength="10" autocomplete="new-password" required></div>
			<div class="field"><label for="confirm_password">Confirm new password</label><input id="confirm_password" name="confirm_password" type="password" minlength="10" autocomplete="new-password" required></div>
			<button class="button" type="submit">Save new password</button>
		</form>
	<?php } ?>
</section></div>
<?php careers_page_end(); ?>
