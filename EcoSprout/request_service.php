<?php

session_start();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: services.php");
    exit;
}

$submittedToken = $_POST["service_request_token"] ?? "";
$savedToken = $_SESSION["service_request_token"] ?? "";

if ($savedToken === "" || !hash_equals($savedToken, $submittedToken)) {
    header("Location: services.php?request=failed#request-service");
    exit;
}

$serviceId = filter_input(INPUT_POST, "service_id", FILTER_VALIDATE_INT);
$customerName = trim($_POST["customer_name"] ?? "");
$address = trim($_POST["address"] ?? "");
$email = trim($_POST["email"] ?? "");
$phone = trim($_POST["phone"] ?? "");
$preferredDate = trim($_POST["preferred_date"] ?? "");
$customerMessage = trim($_POST["customer_message"] ?? "");

if (
    $serviceId === false ||
    $serviceId === null ||
    $serviceId < 1 ||
    $customerName === "" ||
    $address === "" ||
    $email === "" ||
    $phone === "" ||
    !filter_var($email, FILTER_VALIDATE_EMAIL)
) {
    header("Location: services.php?request=missing#request-service");
    exit;
}

if ($preferredDate !== "") {
    $dateObject = DateTime::createFromFormat("Y-m-d", $preferredDate);
    $validDate = $dateObject && $dateObject->format("Y-m-d") === $preferredDate;

    if (!$validDate || $preferredDate < date("Y-m-d")) {
        header("Location: services.php?request=invalid#request-service");
        exit;
    }
} else {
    $preferredDate = null;
}

require_once "db_connection.php";

$serviceCheck = $conn->prepare("
    SELECT service_id
    FROM services
    WHERE service_id = ? AND status = 'ACTIVE'
    LIMIT 1
");
$serviceCheck->bind_param("i", $serviceId);
$serviceCheck->execute();
$serviceCheck->store_result();

if ($serviceCheck->num_rows !== 1) {
    $serviceCheck->close();
    $conn->close();
    header("Location: services.php?request=invalid#request-service");
    exit;
}
$serviceCheck->close();

$userId = isset($_SESSION["user_id"]) ? (int) $_SESSION["user_id"] : 0;

$insertStatement = $conn->prepare("
    INSERT INTO service_requests (
        service_id,
        user_id,
        customer_name,
        address,
        email,
        phone,
        preferred_date,
        customer_message
    ) VALUES (?, NULLIF(?, 0), ?, ?, ?, ?, ?, ?)
");

if (!$insertStatement) {
    $conn->close();
    header("Location: services.php?request=failed#request-service");
    exit;
}

$insertStatement->bind_param(
    "iissssss",
    $serviceId,
    $userId,
    $customerName,
    $address,
    $email,
    $phone,
    $preferredDate,
    $customerMessage
);

if (!$insertStatement->execute()) {
    $insertStatement->close();
    $conn->close();
    header("Location: services.php?request=failed#request-service");
    exit;
}

$insertStatement->close();
$conn->close();

// Replace the token to prevent accidental duplicate submissions.
$_SESSION["service_request_token"] = bin2hex(random_bytes(32));

header("Location: services.php?request=success#request-service");
exit;
