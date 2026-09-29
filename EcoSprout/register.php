<?php

require_once "db_connection.php";

// Allow this file to process only registration form submissions.
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: index.php");
    exit;
}

// Read the values submitted by the registration form.
$firstName = trim($_POST["first_name"] ?? "");
$lastName = trim($_POST["last_name"] ?? "");
$email = trim($_POST["email"] ?? "");
$phone = trim($_POST["phone"] ?? "");
$homeAddress = trim($_POST["home_address"] ?? "");
$password = $_POST["password"] ?? "";
$confirmPassword = $_POST["confirm_password"] ?? "";

// Make sure that every required field has been completed.
if (
    $firstName === "" ||
    $lastName === "" ||
    $email === "" ||
    $phone === "" ||
    $homeAddress === "" ||
    $password === "" ||
    $confirmPassword === ""
) {
    exit("Please complete all registration fields.");
}

// Check that the submitted email address is valid.
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    exit("Please enter a valid email address.");
}

// Require a password containing at least eight characters.
if (strlen($password) < 8) {
    exit("Password must contain at least 8 characters.");
}

// Both password fields must contain the same value.
if ($password !== $confirmPassword) {
    exit("Passwords do not match.");
}

// Check whether another account already uses the submitted email address.
$checkSql = "SELECT user_id FROM users WHERE email = ? LIMIT 1";
$checkStatement = $conn->prepare($checkSql);

if (!$checkStatement) {
    exit("Unable to prepare email check: " . $conn->error);
}

$checkStatement->bind_param("s", $email);
$checkStatement->execute();
$checkStatement->store_result();

if ($checkStatement->num_rows > 0) {
    $checkStatement->close();
    $conn->close();
    exit("This email address is already registered.");
}

$checkStatement->close();

// Never store the customer's plain-text password.
$passwordHash = password_hash($password, PASSWORD_DEFAULT);

// CUSTOMER, ACTIVE and timestamps use the defaults defined in the users table.
$insertSql = "
    INSERT INTO users (
        first_name,
        last_name,
        email,
        phone,
        address_line_1,
        password_hash
    ) VALUES (?, ?, ?, ?, ?, ?)
";

$insertStatement = $conn->prepare($insertSql);

if (!$insertStatement) {
    exit("Unable to prepare registration: " . $conn->error);
}

$insertStatement->bind_param(
    "ssssss",
    $firstName,
    $lastName,
    $email,
    $phone,
    $homeAddress,
    $passwordHash
);

if (!$insertStatement->execute()) {
    exit("Registration failed: " . $insertStatement->error);
}

$insertStatement->close();
$conn->close();

header("Location: index.php?registered=success");
exit;
