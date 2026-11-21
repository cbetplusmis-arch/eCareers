<?php

function career_send_password_reset_email($email, $full_name, $reset_url, &$error) {
	$error = "";
	$autoload = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . "eAdmissions_cbetplus"
		. DIRECTORY_SEPARATOR . "vendor" . DIRECTORY_SEPARATOR . "autoload.php";
	if (!is_file($autoload)) {
		$error = "PHPMailer is not installed in the admissions project.";
		return false;
	}
	require_once $autoload;
	if (!class_exists("PHPMailer\\PHPMailer\\PHPMailer")) {
		$error = "PHPMailer could not be loaded.";
		return false;
	}

	$shared_smtp = array();
	$shared_config_path = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . "CBETPlus"
		. DIRECTORY_SEPARATOR . "Authetication" . DIRECTORY_SEPARATOR . "smtp_config.php";
	$shared_local_config_path = dirname($shared_config_path) . DIRECTORY_SEPARATOR . "smtp_config.local.php";
	$mail_password = getenv("AD_MAIL_PASSWORD");
	if (($mail_password === false || $mail_password === "")
		&& is_file($shared_config_path) && is_file($shared_local_config_path)) {
		require_once $shared_config_path;
		if (function_exists("cbetplusSmtpConfig")) {
			try {
				$shared_smtp = cbetplusSmtpConfig();
			} catch (\RuntimeException $exception) {
				$error = "The shared SMTP configuration is invalid.";
				return false;
			}
		}
	}

	$setting = function ($environment_name, $shared_name, $default) use ($shared_smtp) {
		$value = getenv($environment_name);
		if ($value !== false && $value !== "") {
			return $value;
		}
		return isset($shared_smtp[$shared_name]) ? $shared_smtp[$shared_name] : $default;
	};
	$host = trim((string) $setting("AD_MAIL_HOST", "host", "smtp.gmail.com"));
	$username = trim((string) $setting("AD_MAIL_USERNAME", "username", "cbetplusmis@gmail.com"));
	$password = (string) $setting("AD_MAIL_PASSWORD", "password", "");
	$from_email = trim((string) $setting("AD_MAIL_FROM", "from_email", "cbetplusmis@gmail.com"));
	$from_name = trim((string) $setting("AD_MAIL_FROM_NAME", "from_name", "Lawrence College Careers"));
	$port = filter_var($setting("AD_MAIL_PORT", "port", "587"), FILTER_VALIDATE_INT);
	$encryption = strtolower(trim((string) $setting("AD_MAIL_ENCRYPTION", "secure", "tls")));
	if ($host === "" || !filter_var($from_email, FILTER_VALIDATE_EMAIL)
		|| ($username !== "" && $password === "")
		|| $port === false || $port < 1 || $port > 65535
		|| !in_array($encryption, array("tls", "ssl", "none"), true)) {
		$error = "SMTP settings are missing or invalid.";
		return false;
	}
	if (!filter_var($email, FILTER_VALIDATE_EMAIL) || filter_var($reset_url, FILTER_VALIDATE_URL) === false) {
		$error = "The recipient email or reset URL is invalid.";
		return false;
	}

	$escaped_name = htmlspecialchars($full_name, ENT_QUOTES, "UTF-8");
	$escaped_url = htmlspecialchars($reset_url, ENT_QUOTES, "UTF-8");
	$mailer = new \PHPMailer\PHPMailer\PHPMailer(true);
	try {
		$mailer->isSMTP();
		$mailer->Host = $host;
		$mailer->Port = $port;
		$mailer->SMTPAuth = $username !== "";
		$mailer->Username = $username;
		$mailer->Password = $password;
		$mailer->Timeout = 15;
		$mailer->CharSet = "UTF-8";
		$mailer->SMTPDebug = 0;
		$mailer->SMTPAutoTLS = $encryption === "tls";
		$mailer->SMTPSecure = $encryption === "tls"
			? \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS
			: ($encryption === "ssl" ? \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS : "");
		$mailer->setFrom($from_email, $from_name);
		$mailer->addAddress($email, $full_name);
		$mailer->Subject = "Reset your Careers Portal password";
		$mailer->isHTML(true);
		$mailer->Body = "<p>Hello " . $escaped_name . ",</p>"
			. "<p>Use the secure link below to choose a new Careers Portal password. "
			. "The link expires in 30 minutes and can only be used once.</p>"
			. '<p><a href="' . $escaped_url . '">Reset your password</a></p>'
			. "<p>If you did not request this, you can ignore this email.</p>";
		$mailer->AltBody = "Hello " . $full_name . ",\n\n"
			. "Reset your Careers Portal password using this one-time link (expires in 30 minutes):\n"
			. $reset_url . "\n\nIf you did not request this, ignore this email.";
		$mailer->send();
		return true;
	} catch (\PHPMailer\PHPMailer\Exception $exception) {
		$error = $mailer->ErrorInfo !== "" ? $mailer->ErrorInfo : $exception->getMessage();
		return false;
	}
}
