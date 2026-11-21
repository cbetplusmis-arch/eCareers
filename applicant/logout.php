<?php
require_once __DIR__ . "/../includes/portal.php";
unset($_SESSION["career_role"], $_SESSION["career_applicant_id"], $_SESSION["career_applicant_name"], $_SESSION["career_applicant_email"], $_SESSION["career_headteacher_id"], $_SESSION["career_headteacher_name"], $_SESSION["career_headteacher_email"], $_SESSION["career_csrf_token"]);
career_flash("You have been signed out.");
career_redirect("index.php");
