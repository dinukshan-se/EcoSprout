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
$currentPassword = $_POST["current_password"] ?? "";
$newPassword = $_POST["new_password"] ?? "";
$confirmPassword = $_POST["confirm_password"] ?? "";

if (
    $currentPassword === "" ||
    $newPassword === "" ||
    $confirmPassword === ""
) {
    $conn->close();
    header("Location: userprofile.php?password=missing");
    exit;
}

if (strlen($newPassword) < 8) {
    $conn->close();
    header("Location: userprofile.php?password=short");
    exit;
}

if ($newPassword !== $confirmPassword) {
    $conn->close();
    header("Location: userprofile.php?password=mismatch");
    exit;
}

// Retrieve the customer's existing password hash.
$selectSql = "
    SELECT password_hash
    FROM users
    WHERE user_id = ?
      AND status = 'ACTIVE'
    LIMIT 1
";

$selectStatement = $conn->prepare($selectSql);

if (!$selectStatement) {
    exit("Unable to prepare password check: " . $conn->error);
}

$selectStatement->bind_param("i", $userId);
$selectStatement->execute();
$selectStatement->bind_result($savedPasswordHash);

if (!$selectStatement->fetch()) {
    $selectStatement->close();
    $conn->close();
    session_destroy();
    header("Location: index.php?login=invalid");
    exit;
}

$selectStatement->close();

if (!password_verify($currentPassword, $savedPasswordHash)) {
    $conn->close();
    header("Location: userprofile.php?password=incorrect");
    exit;
}

$newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);

$updateSql = "
    UPDATE users
    SET password_hash = ?
    WHERE user_id = ?
      AND status = 'ACTIVE'
";

$updateStatement = $conn->prepare($updateSql);

if (!$updateStatement) {
    exit("Unable to prepare password update: " . $conn->error);
}

$updateStatement->bind_param("si", $newPasswordHash, $userId);

if (!$updateStatement->execute()) {
    exit("Password update failed: " . $updateStatement->error);
}

$updateStatement->close();
$conn->close();

header("Location: userprofile.php?password=updated");
exit;

