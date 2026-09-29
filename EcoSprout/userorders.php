<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: index.php?login=required");
    exit;
}

require_once "db_connection.php";

$userId = (int) $_SESSION["user_id"];

function escapeOutput($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

function displayOrderStatus($status)
{
    $labels = [
        "PENDING" => "Pending",
        "PROCESSING" => "Processing",
        "OUT_FOR_DELIVERY" => "On the Way",
        "COMPLETED" => "Delivered",
        "CANCELLED" => "Cancelled"
    ];

    return $labels[$status] ?? $status;
}

function orderStatusClass($status)
{
    if ($status === "COMPLETED") {
        return "status-delivered";
    }

    if ($status === "PROCESSING" || $status === "OUT_FOR_DELIVERY") {
        return "status-processing";
    }

    return "status-pending";
}

// Load the three order summary values.
$totalOrders = 0;
$onTheWayOrders = 0;
$completedOrders = 0;

$summarySql = "
    SELECT
        COUNT(*),
        COALESCE(SUM(order_status IN ('PROCESSING', 'OUT_FOR_DELIVERY')), 0),
        COALESCE(SUM(order_status = 'COMPLETED'), 0)
    FROM orders
    WHERE user_id = ?
";

$summaryStatement = $conn->prepare($summarySql);

if (!$summaryStatement) {
    exit("Unable to load order summary: " . $conn->error);
}

$summaryStatement->bind_param("i", $userId);
$summaryStatement->execute();
$summaryStatement->bind_result(
    $totalOrders,
    $onTheWayOrders,
    $completedOrders
);
$summaryStatement->fetch();
$summaryStatement->close();

// Load orders belonging only to the logged-in customer.
$orders = [];

$ordersSql = "
    SELECT
        order_id,
        order_code,
        payment_status,
        order_status,
        total_amount,
        delivery_fee,
        order_date
    FROM orders
    WHERE user_id = ?
    ORDER BY order_date DESC
";

$ordersStatement = $conn->prepare($ordersSql);

if (!$ordersStatement) {
    exit("Unable to load orders: " . $conn->error);
}

$ordersStatement->bind_param("i", $userId);
$ordersStatement->execute();
$ordersStatement->bind_result(
    $orderId,
    $orderCode,
    $paymentStatus,
    $orderStatus,
    $totalAmount,
    $deliveryFee,
    $orderDate
);

while ($ordersStatement->fetch()) {
    $orders[] = [
        "order_id" => $orderId,
        "order_code" => $orderCode,
        "payment_status" => $paymentStatus,
        "order_status" => $orderStatus,
        "total_amount" => $totalAmount,
        "delivery_fee" => $deliveryFee,
        "order_date" => $orderDate
    ];
}

$ordersStatement->close();

// Prepare one reusable query for the items contained in each order.
$itemSql = "
    SELECT
        oi.item_name,
        oi.quantity,
        oi.unit_price,
        oi.line_total,
        CASE
            WHEN oi.plant_id IS NOT NULL THEN 'Plant'
            ELSE 'Gardening Tool'
        END,
        COALESCE(
            pi.image_path,
            ti.image_path,
            'Images/Home/Logo.png'
        )
    FROM order_items oi
    LEFT JOIN plant_images pi
        ON pi.plant_id = oi.plant_id AND pi.is_main = 1
    LEFT JOIN tool_images ti
        ON ti.tool_id = oi.tool_id AND ti.is_main = 1
    WHERE oi.order_id = ?
    ORDER BY oi.order_item_id
";

$itemStatement = $conn->prepare($itemSql);

if (!$itemStatement) {
    exit("Unable to load order items: " . $conn->error);
}

$currentOrderId = 0;
$itemStatement->bind_param("i", $currentOrderId);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders | EcoSprout Nursery</title>
    <link rel="stylesheet" href="stylesheet.css">
</head>
<body class="orders-body">

    <header class="profile-header">
        <div class="nav-logo">
            <a href="index.php">
                <img src="Images/Home/Logo.png" alt="EcoSprout Logo">
            </a>
        </div>

        <nav class="profile-navigation" aria-label="Account navigation">
            <a href="userprofile.php" class="profile-nav-link">Account</a>
            <a href="userorders.php" class="profile-nav-link active">Orders</a>
        </nav>

        <div class="profile-header-actions">
            <div class="nav-right">
                <a href="cart.php">
                    <img src="Images/Home/Cart.png" alt="Cart">
                </a>
            </div>

            <a href="logout.php" class="profile-logout-button">Log Out</a>
        </div>
    </header>

    <main class="orders-main">
        <div class="orders-page-title">
            <p>My EcoSprout Account</p>
            <h1>My Orders</h1>
            <span>View your orders and check their delivery status.</span>
        </div>

        <section class="orders-summary">
            <article class="orders-summary-card">
                <div>
                    <span>Total Orders</span>
                    <strong><?= (int) $totalOrders ?></strong>
                </div>
            </article>

            <article class="orders-summary-card">
                <div>
                    <span>On the Way</span>
                    <strong><?= (int) $onTheWayOrders ?></strong>
                </div>
            </article>

            <article class="orders-summary-card">
                <div>
                    <span>Completed</span>
                    <strong><?= (int) $completedOrders ?></strong>
                </div>
            </article>
        </section>

        <section class="orders-list">
            <?php if (count($orders) === 0): ?>
                <article class="order-card">
                    <div class="order-card-body">
                        <div class="order-information">
                            <h2>No orders yet</h2>
                            <p>Your completed checkouts will appear here.</p>
                        </div>
                    </div>
                </article>
            <?php endif; ?>

            <?php foreach ($orders as $order): ?>
                <?php
                    $currentOrderId = (int) $order["order_id"];
                    $itemStatement->execute();
                    $itemStatement->bind_result(
                        $itemName,
                        $itemQuantity,
                        $unitPrice,
                        $lineTotal,
                        $itemCategory,
                        $imagePath
                    );

                    $items = [];

                    while ($itemStatement->fetch()) {
                        $items[] = [
                            "item_name" => $itemName,
                            "quantity" => $itemQuantity,
                            "unit_price" => $unitPrice,
                            "line_total" => $lineTotal,
                            "category" => $itemCategory,
                            "image_path" => $imagePath
                        ];
                    }

                    $itemStatement->free_result();
                    $firstImage = $items[0]["image_path"]
                        ?? "Images/Home/Logo.png";
                ?>

                <article class="order-card">
                    <header class="order-card-header">
                        <div>
                            <span>Order ID</span>
                            <strong>#<?= escapeOutput($order["order_code"]) ?></strong>
                        </div>

                        <div>
                            <span>Order Date</span>
                            <strong>
                                <?= escapeOutput(date("d F Y", strtotime($order["order_date"]))) ?>
                            </strong>
                        </div>

                        <div>
                            <span>Payment</span>
                            <strong class="<?= $order["payment_status"] === "PAID" ? "payment-paid" : "payment-pending" ?>">
                                <?= escapeOutput(ucwords(strtolower($order["payment_status"]))) ?>
                            </strong>
                        </div>

                        <div>
                            <span>Delivery Status</span>
                            <strong class="<?= escapeOutput(orderStatusClass($order["order_status"])) ?>">
                                <?= escapeOutput(displayOrderStatus($order["order_status"])) ?>
                            </strong>
                        </div>
                    </header>

                    <div class="order-card-body">
                        <div class="order-image">
                            <img src="<?= escapeOutput($firstImage) ?>" alt="Order item">
                        </div>

                        <div class="order-information">
                            <?php foreach ($items as $item): ?>
                                <h2><?= escapeOutput($item["item_name"]) ?></h2>
                                <p class="order-category">
                                    <?= escapeOutput($item["category"]) ?>
                                </p>

                                <div class="order-details">
                                    <p>
                                        Quantity:
                                        <strong><?= (int) $item["quantity"] ?></strong>
                                    </p>

                                    <p>
                                        Price:
                                        <strong>Rs. <?= number_format((float) $item["unit_price"], 2) ?></strong>
                                    </p>

                                    <p>
                                        Item Total:
                                        <strong>Rs. <?= number_format((float) $item["line_total"], 2) ?></strong>
                                    </p>
                                </div>
                            <?php endforeach; ?>

                            <p class="order-total">
                                Delivery:
                                <strong>
                                    <?= (float) $order["delivery_fee"] === 0.0
                                        ? "Free"
                                        : "Rs. " . number_format((float) $order["delivery_fee"], 2) ?>
                                </strong>
                            </p>

                            <p class="order-total">
                                Total:
                                <strong>Rs. <?= number_format((float) $order["total_amount"], 2) ?></strong>
                            </p>
                        </div>

                        <div class="order-actions">
                            <span class="order-completed-message">
                                <?= escapeOutput(displayOrderStatus($order["order_status"])) ?>
                            </span>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </section>
    </main>

    <footer class="footer">
        <div class="footer-container">
            <div class="footer-column footer-brand">
                <img src="Images/Home/Logo-white.png" alt="EcoSprout Logo">
                <p>
                    Quality plants, gardening tools and professional services
                    to help you create healthier and greener spaces.
                </p>
                <div class="footer_social">
                    <img src="Images/Home/white-wp.png" alt="WhatsApp">
                    <img src="Images/Home/white-fb.png" alt="Facebook">
                    <img src="Images/Home/white-inster.png" alt="Instagram">
                </div>
            </div>

            <div class="footer-column">
                <h3>Explore</h3>
                <a href="plants.php">Plants</a>
                <a href="tools.php">Tools</a>
                <a href="services.php">Services</a>
                <a href="events.php">Events</a>
                <a href="aboutus.php">About Us</a>
                <a href="contact.php">Contact Us</a>
            </div>

            <div class="footer-column">
                <h3>Contact</h3>
                <p>info@ecosprout.lk</p>
                <p>+94 77 123 4567</p>
                <p>Kegalle, Sri Lanka</p>
            </div>

            <div class="footer-column">
                <h3>Opening Hours</h3>
                <p>Mon - Sat: 9:00 AM - 7:00 PM</p>
                <p>Sunday: 10:00 AM - 4:00 PM</p>
            </div>
        </div>

        <div class="footer-bottom">
            <p>© 2026 EcoSprout. All rights reserved.</p>
        </div>
    </footer>

<?php
$itemStatement->close();
$conn->close();
?>
</body>
</html>