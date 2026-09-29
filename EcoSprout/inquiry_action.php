<?php
require_once "admin_auth.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: admin_inquiries.php");
    exit;
}

$submittedToken = $_POST["inquiry_admin_token"] ?? "";
$sessionToken = $_SESSION["inquiry_admin_token"] ?? "";
if ($sessionToken === "" || !hash_equals($sessionToken, $submittedToken)) {
    header("Location: admin_inquiries.php?result=invalid");
    exit;
}

$inquiryId = filter_input(INPUT_POST, "inquiry_id", FILTER_VALIDATE_INT);
$status = $_POST["inquiry_status"] ?? "";
$allowedStatuses = ["NEW", "READ", "REPLIED", "CLOSED"];

if (!$inquiryId || !in_array($status, $allowedStatuses, true)) {
    header("Location: admin_inquiries.php?result=invalid");
    exit;
}

require_once "db_connection.php";
$statement = $conn->prepare(
    "UPDATE contact_inquiries SET inquiry_status = ? WHERE inquiry_id = ?"
);
$statement->bind_param("si", $status, $inquiryId);
$statement->execute();
$found = $statement->affected_rows > 0;
$statement->close();

if (!$found) {
    $checkStatement = $conn->prepare(
        "SELECT inquiry_id FROM contact_inquiries WHERE inquiry_id = ? LIMIT 1"
    );
    $checkStatement->bind_param("i", $inquiryId);
    $checkStatement->execute();
    $checkStatement->store_result();
    $found = $checkStatement->num_rows === 1;
    $checkStatement->close();
}

$conn->close();
$_SESSION["inquiry_admin_token"] = bin2hex(random_bytes(32));

header(
    "Location: admin_inquiries.php?result=" . ($found ? "updated" : "missing")
    . "&view=" . (int) $inquiryId
);
exit;
