<?php

/* Start or continue the PHP session */
require_once "session_config.php";

/* Connect to the database */
require_once "db_connection.php";

/*
 * Only allow POST requests from the login form.
 * Direct access to login.php returns to the homepage.
 */
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: index.php");
    exit;
}

/* Get the submitted login details */
$email = trim($_POST["email"] ?? "");
$password = $_POST["password"] ?? "";

/* Check empty fields */
if ($email === "" || $password === "") {
    header("Location: index.php?login=missing");
    exit;
}

/* Check email format */
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location: index.php?login=invalid");
    exit;
}

/* Find the user using their email address */
$sql = "
    SELECT
        user_id,
        first_name,
        last_name,
        email,
        password_hash,
        role,
        status
    FROM users
    WHERE email = ?
    LIMIT 1
";

$statement = $conn->prepare($sql);

if (!$statement) {
    header("Location: index.php?login=database");
    exit;
}

/* Add email to the prepared statement */
$statement->bind_param("s", $email);

/* Execute the query */
$statement->execute();

/* Connect the selected columns to PHP variables */
$statement->bind_result(
    $userId,
    $firstName,
    $lastName,
    $savedEmail,
    $passwordHash,
    $role,
    $status
);

/* Check whether the user exists */
if (!$statement->fetch()) {

    $statement->close();
    $conn->close();

    header("Location: index.php?login=invalid");
    exit;
}

/* Clean and standardize role and status values */
$role = strtoupper(trim($role));
$status = strtoupper(trim($status));

/* Check whether the account is active */
if ($status !== "ACTIVE") {

    $statement->close();
    $conn->close();

    header("Location: index.php?login=inactive");
    exit;
}

/* Check the submitted password */
if (!password_verify($password, $passwordHash)) {

    $statement->close();
    $conn->close();

    header("Location: index.php?login=invalid");
    exit;
}

/*
 * Login was successful.
 * Generate a new session ID for security.
 */
session_regenerate_id(true);

/* Save the user information in the session */
$_SESSION["user_id"] = (int) $userId;
$_SESSION["first_name"] = $firstName;
$_SESSION["last_name"] = $lastName;
$_SESSION["email"] = $savedEmail;
$_SESSION["role"] = strtoupper(trim($role));
$_SESSION["logged_in"] = true;

// Store the standardized role
$currentRole = $_SESSION["role"];

// Close database resources
$statement->close();
$conn->close();

// Admin and Staff go to Admin Dashboard
if (
    $currentRole === "ADMIN" ||
    $currentRole === "STAFF"
) {
    header("Location: admindashboard.php");
    exit;
}

// Customer goes to Customer Profile
header("Location: userprofile.php?login=success");
exit;
