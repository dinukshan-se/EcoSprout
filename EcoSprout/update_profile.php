<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: index.php?login=required");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: userprofile.php");
    exit;
}

require_once "db_connection.php";

$userId = (int) $_SESSION["user_id"];
$firstName = trim($_POST["first_name"] ?? "");
$lastName = trim($_POST["last_name"] ?? "");
$phone = trim($_POST["phone_number"] ?? "");
$alternativePhone = trim($_POST["alternative_phone"] ?? "");
$addressLine1 = trim($_POST["address_line_1"] ?? "");
$addressLine2 = trim($_POST["address_line_2"] ?? "");
$city = trim($_POST["city"] ?? "");
$postalCode = trim($_POST["zipcode"] ?? "");
$email = trim($_POST["email"] ?? "");

if (
    $firstName === "" ||
    $lastName === "" ||
    $phone === "" ||
    $addressLine1 === "" ||
    $city === "" ||
    $email === ""
) {
    $conn->close();
    header("Location: userprofile.php?profile=missing");
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $conn->close();
    header("Location: userprofile.php?profile=invalid_email");
    exit;
}

// The email may not belong to another EcoSprout account.
$checkSql = "
    SELECT user_id
    FROM users
    WHERE email = ? AND user_id <> ?
    LIMIT 1
";

$checkStatement = $conn->prepare($checkSql);

if (!$checkStatement) {
    exit("Unable to prepare email check: " . $conn->error);
}

$checkStatement->bind_param("si", $email, $userId);
$checkStatement->execute();
$checkStatement->store_result();

if ($checkStatement->num_rows > 0) {
    $checkStatement->close();
    $conn->close();
    header("Location: userprofile.php?profile=email_exists");
    exit;
}

$checkStatement->close();

$updateSql = "
    UPDATE users
    SET
        first_name = ?,
        last_name = ?,
        phone = ?,
        alternative_phone = ?,
        address_line_1 = ?,
        address_line_2 = ?,
        city = ?,
        postal_code = ?,
        email = ?
    WHERE user_id = ?
      AND status = 'ACTIVE'
";

$updateStatement = $conn->prepare($updateSql);

if (!$updateStatement) {
    exit("Unable to prepare profile update: " . $conn->error);
}

$updateStatement->bind_param(
    "sssssssssi",
    $firstName,
    $lastName,
    $phone,
    $alternativePhone,
    $addressLine1,
    $addressLine2,
    $city,
    $postalCode,
    $email,
    $userId
);

if (!$updateStatement->execute()) {
    exit("Profile update failed: " . $updateStatement->error);
}

// Keep the session details consistent with the updated database record.
$_SESSION["first_name"] = $firstName;
$_SESSION["last_name"] = $lastName;
$_SESSION["email"] = $email;

$updateStatement->close();
$conn->close();

header("Location: userprofile.php?profile=updated");
exit;