<?php

/* Start or continue the PHP session */
require_once "session_config.php";

/* User must be logged in */
if (
    !isset($_SESSION["user_id"]) ||
    !isset($_SESSION["logged_in"]) ||
    $_SESSION["logged_in"] !== true
) {
    header("Location: index.php?login=required");
    exit;
}

/* Get the logged-in user's role */
$currentRole =
    strtoupper(trim($_SESSION["role"] ?? ""));

/* Only Admin and Staff can enter admin pages */
if (
    $currentRole !== "ADMIN" &&
    $currentRole !== "STAFF"
) {
    header("Location: userprofile.php");
    exit;
}

/* Variables used by admin pages */
$isAdministrator =
    $currentRole === "ADMIN";

$isStaff =
    $currentRole === "STAFF";