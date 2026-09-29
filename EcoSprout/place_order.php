<?php

session_start();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: cart.php");
    exit;
}

if (!isset($_SESSION["user_id"])) {
    header("Location: index.php?login=required");
    exit;
}

if (empty($_SESSION["cart"])) {
    header("Location: cart.php?cart=empty");
    exit;
}

$submittedToken = $_POST["checkout_token"] ?? "";
$savedToken = $_SESSION["checkout_token"] ?? "";

if ($savedToken === "" || !hash_equals($savedToken, $submittedToken)) {
    header("Location: checkout.php?error=order");
    exit;
}

$firstName = trim($_POST["first_name"] ?? "");
$lastName = trim($_POST["last_name"] ?? "");
$phone = trim($_POST["phone"] ?? "");
$email = trim($_POST["email"] ?? "");
$addressLine1 = trim($_POST["address_line_1"] ?? "");
$addressLine2 = trim($_POST["address_line_2"] ?? "");
$city = trim($_POST["city"] ?? "");
$postalCode = trim($_POST["postal_code"] ?? "");
$paymentMethod = $_POST["payment_method"] ?? "";
$remark = trim($_POST["remark"] ?? "");

if (
    $firstName === "" ||
    $lastName === "" ||
    $phone === "" ||
    $email === "" ||
    $addressLine1 === "" ||
    $city === "" ||
    $postalCode === "" ||
    !filter_var($email, FILTER_VALIDATE_EMAIL) ||
    !in_array($paymentMethod, ["CARD", "BANK_TRANSFER", "CASH_ON_DELIVERY"], true)
) {
    header("Location: checkout.php?error=missing");
    exit;
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
require_once "db_connection.php";

$userId = (int) $_SESSION["user_id"];
$orderItems = [];
$subtotal = 0.00;
$deliveryFee = 0.00;

try {
    $conn->begin_transaction();

    $plantSelect = $conn->prepare("
        SELECT plant_name, price, quantity
        FROM plants
        WHERE plant_id = ? AND status = 'ACTIVE'
        FOR UPDATE
    ");

    $toolSelect = $conn->prepare("
        SELECT tool_name, price, quantity
        FROM tools
        WHERE tool_id = ? AND status = 'ACTIVE'
        FOR UPDATE
    ");

    foreach ($_SESSION["cart"] as $sessionItem) {
        $productType = $sessionItem["product_type"] ?? "";
        $productId = (int) ($sessionItem["product_id"] ?? 0);
        $quantity = (int) ($sessionItem["quantity"] ?? 0);

        if ($productId < 1 || $quantity < 1) {
            throw new RuntimeException("INVALID_CART");
        }

        if ($productType === "plant") {
            $statement = $plantSelect;
        } elseif ($productType === "tool") {
            $statement = $toolSelect;
        } else {
            throw new RuntimeException("INVALID_CART");
        }

        $statement->bind_param("i", $productId);
        $statement->execute();
        $statement->bind_result($productName, $unitPrice, $availableStock);

        if (!$statement->fetch() || (int) $availableStock < $quantity) {
            $statement->free_result();
            throw new RuntimeException("INSUFFICIENT_STOCK");
        }

        $statement->free_result();
        $lineTotal = (float) $unitPrice * $quantity;
        $subtotal += $lineTotal;

        $orderItems[] = [
            "product_type" => $productType,
            "product_id" => $productId,
            "name" => $productName,
            "quantity" => $quantity,
            "unit_price" => (float) $unitPrice,
            "line_total" => $lineTotal
        ];
    }

    if (count($orderItems) === 0) {
        throw new RuntimeException("INVALID_CART");
    }

    $totalAmount = $subtotal + $deliveryFee;
    $orderCode = "ECO" . date("YmdHis") . random_int(100, 999);

    $orderInsert = $conn->prepare("
        INSERT INTO orders (
            order_code, user_id, first_name, last_name, phone, email,
            address_line_1, address_line_2, city, postal_code,
            payment_method, subtotal, delivery_fee, total_amount, remark
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $orderInsert->bind_param(
        "sisssssssssddds",
        $orderCode,
        $userId,
        $firstName,
        $lastName,
        $phone,
        $email,
        $addressLine1,
        $addressLine2,
        $city,
        $postalCode,
        $paymentMethod,
        $subtotal,
        $deliveryFee,
        $totalAmount,
        $remark
    );
    $orderInsert->execute();
    $orderId = $conn->insert_id;

    $itemInsert = $conn->prepare("
        INSERT INTO order_items (
            order_id, plant_id, tool_id, item_name,
            quantity, unit_price, line_total
        ) VALUES (?, NULLIF(?, 0), NULLIF(?, 0), ?, ?, ?, ?)
    ");

    $plantUpdate = $conn->prepare("
        UPDATE plants
        SET quantity = quantity - ?
        WHERE plant_id = ? AND quantity >= ?
    ");

    $toolUpdate = $conn->prepare("
        UPDATE tools
        SET quantity = quantity - ?
        WHERE tool_id = ? AND quantity >= ?
    ");

    foreach ($orderItems as $item) {
        $plantId = $item["product_type"] === "plant" ? $item["product_id"] : 0;
        $toolId = $item["product_type"] === "tool" ? $item["product_id"] : 0;
        $itemName = $item["name"];
        $itemQuantity = $item["quantity"];
        $unitPrice = $item["unit_price"];
        $lineTotal = $item["line_total"];

        $itemInsert->bind_param(
            "iiisidd",
            $orderId,
            $plantId,
            $toolId,
            $itemName,
            $itemQuantity,
            $unitPrice,
            $lineTotal
        );
        $itemInsert->execute();

        if ($item["product_type"] === "plant") {
            $stockUpdate = $plantUpdate;
        } else {
            $stockUpdate = $toolUpdate;
        }

        $stockProductId = (int) $item["product_id"];

        $stockUpdate->bind_param(
            "iii",
            $itemQuantity,
            $stockProductId,
            $itemQuantity
        );
        $stockUpdate->execute();

        if ($stockUpdate->affected_rows !== 1) {
            throw new RuntimeException("INSUFFICIENT_STOCK");
        }
    }

    $conn->commit();

    $_SESSION["cart"] = [];
    unset($_SESSION["checkout_token"]);

    header("Location: userorders.php?order=success&code=" . urlencode($orderCode));
    exit;
} catch (Throwable $error) {
    $conn->rollback();
    error_log("EcoSprout order error: " . $error->getMessage());

    if ($error->getMessage() === "INSUFFICIENT_STOCK") {
        header("Location: checkout.php?error=stock");
    } else {
        header("Location: checkout.php?error=order");
    }
    exit;
}
