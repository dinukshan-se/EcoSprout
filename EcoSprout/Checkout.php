<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: index.php?login=required");
    exit;
}

if (empty($_SESSION["cart"])) {
    header("Location: cart.php?cart=empty");
    exit;
}

require_once "db_connection.php";

function checkoutEscape($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

$userId = (int) $_SESSION["user_id"];
$userSql = "
    SELECT first_name, last_name, phone, email,
           address_line_1, address_line_2, city, postal_code
    FROM users
    WHERE user_id = ? AND status = 'ACTIVE'
    LIMIT 1
";

$userStatement = $conn->prepare($userSql);
$userStatement->bind_param("i", $userId);
$userStatement->execute();
$userStatement->bind_result(
    $firstName,
    $lastName,
    $phone,
    $email,
    $addressLine1,
    $addressLine2,
    $city,
    $postalCode
);

if (!$userStatement->fetch()) {
    $userStatement->close();
    $conn->close();
    session_destroy();
    header("Location: index.php?login=required");
    exit;
}

$userStatement->close();

$plantSql = "
    SELECT plant_name, price, quantity
    FROM plants
    WHERE plant_id = ? AND status = 'ACTIVE'
    LIMIT 1
";

$toolSql = "
    SELECT tool_name, price, quantity
    FROM tools
    WHERE tool_id = ? AND status = 'ACTIVE'
    LIMIT 1
";

$plantStatement = $conn->prepare($plantSql);
$toolStatement = $conn->prepare($toolSql);
$checkoutItems = [];
$subtotal = 0.00;

foreach ($_SESSION["cart"] as $itemKey => $sessionItem) {
    $productType = $sessionItem["product_type"] ?? "";
    $productId = (int) ($sessionItem["product_id"] ?? 0);
    $requestedQuantity = (int) ($sessionItem["quantity"] ?? 1);

    if ($productType === "plant") {
        $statement = $plantStatement;
    } elseif ($productType === "tool") {
        $statement = $toolStatement;
    } else {
        continue;
    }

    $statement->bind_param("i", $productId);
    $statement->execute();
    $statement->bind_result($productName, $productPrice, $availableStock);

    if (!$statement->fetch() || (int) $availableStock < 1) {
        unset($_SESSION["cart"][$itemKey]);
        $statement->free_result();
        continue;
    }

    $statement->free_result();
    $quantity = min(max($requestedQuantity, 1), (int) $availableStock);
    $_SESSION["cart"][$itemKey]["quantity"] = $quantity;
    $lineTotal = (float) $productPrice * $quantity;
    $subtotal += $lineTotal;

    $checkoutItems[] = [
        "name" => $productName,
        "quantity" => $quantity,
        "line_total" => $lineTotal
    ];
}

$plantStatement->close();
$toolStatement->close();
$conn->close();

if (count($checkoutItems) === 0) {
    header("Location: cart.php?cart=empty");
    exit;
}

$deliveryFee = 0.00;
$total = $subtotal + $deliveryFee;

if (empty($_SESSION["checkout_token"])) {
    $_SESSION["checkout_token"] = bin2hex(random_bytes(32));
}

$errorMessage = "";
if (($_GET["error"] ?? "") === "missing") {
    $errorMessage = "Please complete all required checkout fields.";
} elseif (($_GET["error"] ?? "") === "stock") {
    $errorMessage = "One or more products no longer have enough stock. Please review your cart.";
} elseif (($_GET["error"] ?? "") === "order") {
    $errorMessage = "The order could not be placed. Please try again.";
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout | EcoSprout</title>
    <link rel="stylesheet" href="stylesheet.css">
    <style>
        .checkout-popup { opacity: 1; visibility: visible; }
        .checkout-summary { background: #f5f7f2; padding: 18px; margin-bottom: 20px; }
        .checkout-summary-row { display: flex; justify-content: space-between; gap: 20px; margin: 8px 0; }
        .checkout-summary-total { border-top: 1px solid #ccc; padding-top: 10px; font-weight: bold; }
        .checkout-error { color: #a40000; background: #ffecec; padding: 12px; margin-bottom: 15px; }
        .checkout-page .payment-method {
            width: 100%;
            box-sizing: border-box;
            text-align: left;
        }
        .checkout-page .payment-method h3 {
            margin: 0 0 14px;
            text-align: left;
        }
        .checkout-page .payment-method label {
            display: flex;
            align-items: center;
            gap: 10px;
            width: 100%;
            margin: 0 0 12px;
            line-height: 1.4;
            text-align: left;
        }
        .checkout-page .payment-method label:last-child { margin-bottom: 0; }
        .checkout-page .payment-method input[type="radio"] {
            flex: 0 0 auto;
            width: 16px;
            height: 16px;
            margin: 0;
        }
    </style>
</head>
<body class="checkout-page">

    <div class="checkout-popup show">
        <div class="checkout-popup-box">
            <a href="cart.php" class="checkout-close" aria-label="Return to cart">&times;</a>

            <div class="checkout-header">
                <img src="Images/Home/Logo.png" alt="EcoSprout Logo">
                <h2>Checkout</h2>
            </div>

            <?php if ($errorMessage !== ""): ?>
                <div class="checkout-error"><?= checkoutEscape($errorMessage) ?></div>
            <?php endif; ?>

            <div class="checkout-summary">
                <?php foreach ($checkoutItems as $item): ?>
                    <div class="checkout-summary-row">
                        <span><?= checkoutEscape($item["name"]) ?> × <?= (int) $item["quantity"] ?></span>
                        <span>Rs. <?= number_format($item["line_total"], 2) ?></span>
                    </div>
                <?php endforeach; ?>

                <div class="checkout-summary-row">
                    <span>Delivery</span>
                    <span>Free</span>
                </div>

                <div class="checkout-summary-row checkout-summary-total">
                    <span>Total</span>
                    <span>Rs. <?= number_format($total, 2) ?></span>
                </div>
            </div>

            <form action="place_order.php" method="post">
                <input type="hidden" name="checkout_token"
                       value="<?= checkoutEscape($_SESSION["checkout_token"]) ?>">

                <div class="checkout-details">
                    <div class="checkout-row">
                        <input type="text" name="first_name" placeholder="First Name"
                               value="<?= checkoutEscape($firstName) ?>" required>
                        <input type="text" name="last_name" placeholder="Last Name"
                               value="<?= checkoutEscape($lastName) ?>" required>
                    </div>

                    <div class="checkout-row">
                        <input type="tel" name="phone" placeholder="Phone Number"
                               value="<?= checkoutEscape($phone) ?>" required>
                        <input type="email" name="email" placeholder="Email Address"
                               value="<?= checkoutEscape($email) ?>" required>
                    </div>

                    <div class="checkout-row">
                        <input type="text" name="address_line_1" placeholder="Address Line 1"
                               value="<?= checkoutEscape($addressLine1) ?>" required>
                        <input type="text" name="address_line_2" placeholder="Address Line 2"
                               value="<?= checkoutEscape($addressLine2) ?>">
                    </div>

                    <div class="checkout-row">
                        <input type="text" name="city" placeholder="City"
                               value="<?= checkoutEscape($city) ?>" required>
                        <input type="text" name="postal_code" placeholder="Postal Code"
                               value="<?= checkoutEscape($postalCode) ?>" required>
                    </div>
                </div>

                <div class="payment-method">
                    <h3>Payment Method</h3>

                    <label>
                        <input type="radio" name="payment_method" value="CARD" required>
                        Card Payment
                    </label>

                    <label>
                        <input type="radio" name="payment_method" value="BANK_TRANSFER">
                        Bank Transfer
                    </label>

                    <label>
                        <input type="radio" name="payment_method" value="CASH_ON_DELIVERY">
                        Cash on Delivery
                    </label>
                </div>

                <textarea class="checkout-remark" name="remark"
                          placeholder="Add any remark here"></textarea>

                <button type="submit" class="place-order-btn">Place Order</button>
            </form>

            <div class="checkout-footer-links">
                <a href="aboutus.php">About EcoSprout</a>
                <span>|</span>
                <a href="contact.php">Contact Us</a>
            </div>
        </div>
    </div>

</body>
</html>
