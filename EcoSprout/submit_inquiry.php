<?php

session_start();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: contact.php");
    exit;
}

$submittedToken = $_POST["inquiry_token"] ?? "";
$savedToken = $_SESSION["inquiry_token"] ?? "";

if ($savedToken === "" || !hash_equals($savedToken, $submittedToken)) {
    header("Location: contact.php?inquiry=failed");
    exit;
}

$fullName = trim($_POST["full_name"] ?? "");
$email = trim($_POST["email"] ?? "");
$phone = trim($_POST["phone"] ?? "");
$subject = trim($_POST["subject"] ?? "");
$message = trim($_POST["message"] ?? "");

if (
    $fullName === "" ||
    $email === "" ||
    $subject === "" ||
    $message === "" ||
    !filter_var($email, FILTER_VALIDATE_EMAIL)
) {
    header("Location: contact.php?inquiry=missing");
    exit;
}

require_once "db_connection.php";

$userId = isset($_SESSION["user_id"]) ? (int) $_SESSION["user_id"] : 0;
$statement = $conn->prepare("
    INSERT INTO contact_inquiries (
        user_id,
        full_name,
        email,
        phone,
        subject,
        message
    ) VALUES (NULLIF(?, 0), ?, ?, NULLIF(?, ''), ?, ?)
");

if (!$statement) {
    $conn->close();
    header("Location: contact.php?inquiry=failed");
    exit;
}

$statement->bind_param(
    "isssss",
    $userId,
    $fullName,
    $email,
    $phone,
    $subject,
    $message
);

if (!$statement->execute()) {
    $statement->close();
    $conn->close();
    header("Location: contact.php?inquiry=failed");
    exit;
}

$statement->close();
$conn->close();

$_SESSION["inquiry_token"] = bin2hex(random_bytes(32));
header("Location: contact.php?inquiry=success");
exit;
