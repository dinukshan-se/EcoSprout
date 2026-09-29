<?php

require_once "admin_auth.php";
require_once "db_connection.php";

function adminEscape($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

function adminStatusLabel($status)
{
    $labels = [
        "PENDING" => "Pending",
        "PROCESSING" => "Processing",
        "OUT_FOR_DELIVERY" => "On the Way",
        "COMPLETED" => "Completed",
        "CANCELLED" => "Cancelled"
    ];
    return $labels[$status] ?? $status;
}

function adminStatusClass($status)
{
    if ($status === "COMPLETED") {
        return "completed";
    }
    if ($status === "OUT_FOR_DELIVERY") {
        return "delivery";
    }
    if ($status === "PROCESSING") {
        return "processing";
    }
    return "pending";
}

$totalSales = 0.00;
$totalOrders = 0;
$todaySales = 0.00;
$todayOrders = 0;

$summaryResult = $conn->query("
    SELECT
        COALESCE(SUM(CASE WHEN order_status <> 'CANCELLED' THEN total_amount ELSE 0 END), 0),
        COUNT(*),
        COALESCE(SUM(CASE
            WHEN DATE(order_date) = CURDATE() AND order_status <> 'CANCELLED'
            THEN total_amount ELSE 0 END), 0),
        COALESCE(SUM(DATE(order_date) = CURDATE()), 0)
    FROM orders
");
$summaryRow = $summaryResult->fetch_row();
$totalSales = $summaryRow[0];
$totalOrders = $summaryRow[1];
$todaySales = $summaryRow[2];
$todayOrders = $summaryRow[3];
$summaryResult->close();

$pendingServices = 0;
$serviceResult = $conn->query("
    SELECT COUNT(*) FROM service_requests WHERE request_status = 'PENDING'
");
$pendingServices = $serviceResult->fetch_row()[0];
$serviceResult->close();

$newInquiries = 0;
$inquiryResult = $conn->query("
    SELECT COUNT(*) FROM contact_inquiries WHERE inquiry_status = 'NEW'
");
$newInquiries = $inquiryResult->fetch_row()[0];
$inquiryResult->close();

$lowStock = 0;
$stockResult = $conn->query("
    SELECT
        (SELECT COUNT(*) FROM plants
         WHERE status = 'ACTIVE' AND quantity <= 5)
        +
        (SELECT COUNT(*) FROM tools
         WHERE status = 'ACTIVE' AND quantity <= 5)
");
$lowStock = $stockResult->fetch_row()[0];
$stockResult->close();

$recentOrders = [];
$orderStatement = $conn->prepare("
    SELECT order_code, CONCAT_WS(' ', first_name, last_name), order_date,
           total_amount, payment_status, order_status
    FROM orders
    ORDER BY order_date DESC
    LIMIT 10
");
$orderStatement->execute();
$orderStatement->bind_result(
    $orderCode,
    $customerName,
    $orderDate,
    $totalAmount,
    $paymentStatus,
    $orderStatus
);

while ($orderStatement->fetch()) {
    $recentOrders[] = [
        "order_code" => $orderCode,
        "customer_name" => $customerName,
        "order_date" => $orderDate,
        "total_amount" => $totalAmount,
        "payment_status" => $paymentStatus,
        "order_status" => $orderStatus
    ];
}

$orderStatement->close();
$conn->close();
$adminName = trim(($_SESSION["first_name"] ?? "") . " " . ($_SESSION["last_name"] ?? ""));
if ($adminName === "") {
    $adminName = $isAdministrator ? "Administrator" : "Staff Member";
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | EcoSprout</title>
    <link rel="stylesheet" href="stylesheet.css">
    <style>
        .admin-summary { grid-template-columns: repeat(3, 1fr); }
        .admin-empty-row { text-align:center !important; color:#777; padding:30px !important; }
        @media (max-width:850px) { .admin-summary { grid-template-columns:repeat(2,1fr); } }
        @media (max-width:520px) { .admin-summary { grid-template-columns:1fr; } }
    </style>
</head>
<body class="admin-body">
    <header class="admin-header">
        <a href="index.php" class="admin-logo">
            <img src="Images/Home/Logo.png" alt="EcoSprout Logo">
        </a>

        <div class="admin-user">
            <div class="admin-user-details">
                <strong><?= adminEscape($adminName) ?></strong>
                <span><?= $isAdministrator ? "Administrator" : "Staff" ?></span>
            </div>
            <a href="logout.php" class="admin-logout-button">Log Out</a>
        </div>
    </header>

    <nav class="admin-navigation">
        <a href="admindashboard.php" class="admin-nav-link">Dashboard</a>
        <a href="admin_orders.php" class="admin-nav-link">Orders</a>
        <a href="admin_plants.php" class="admin-nav-link">Plants</a>
        <a href="admin_tools.php" class="admin-nav-link">Tools</a>
        <a href="admin_services.php" class="admin-nav-link">Services</a>
        <a href="admin_inquiries.php" class="admin-nav-link">Inquiries</a>
        <?php if ($isAdministrator): ?> <a href="admin_staff.php" class="admin-nav-link">Manage Staff</a><?php endif; ?>
    </nav>

    <main class="admin-main">
        <section class="admin-title">
            <p>EcoSprout Management</p>
            <h1>Admin Dashboard</h1>
            <span>View sales information and manage nursery operations.</span>
        </section>

        <section class="admin-summary">
            <article class="admin-summary-card"><div><span>Total Sales</span><strong>Rs. <?= number_format((float) $totalSales, 2) ?></strong></div></article>
            <article class="admin-summary-card"><div><span>Total Orders</span><strong><?= (int) $totalOrders ?></strong></div></article>
            <article class="admin-summary-card"><div><span>Today's Sales</span><strong>Rs. <?= number_format((float) $todaySales, 2) ?></strong></div></article>
            <article class="admin-summary-card"><div><span>Today's Orders</span><strong><?= (int) $todayOrders ?></strong></div></article>
            <article class="admin-summary-card"><div><span>Pending Services</span><strong><?= (int) $pendingServices ?></strong></div></article>
            <article class="admin-summary-card"><div><span>New Inquiries</span><strong><?= (int) $newInquiries ?></strong></div></article>
            <article class="admin-summary-card"><div><span>Low Stock Products</span><strong><?= (int) $lowStock ?></strong></div></article>
        </section>

        <section class="admin-orders-section">
            <div class="admin-section-heading">
                <div>
                    <h2>Customer Orders</h2>
                    <p>The ten most recent customer orders.</p>
                </div>
            </div>

            <div class="admin-table-container">
                <table class="admin-orders-table">
                    <thead>
                        <tr>
                            <th>Order ID</th><th>Customer Name</th><th>Date</th>
                            <th>Total</th><th>Payment</th><th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($recentOrders) === 0): ?>
                            <tr><td colspan="6" class="admin-empty-row">No customer orders have been received.</td></tr>
                        <?php endif; ?>

                        <?php foreach ($recentOrders as $order): ?>
                            <tr>
                                <td><strong>#<?= adminEscape($order["order_code"]) ?></strong></td>
                                <td><?= adminEscape($order["customer_name"]) ?></td>
                                <td><?= date("d M Y", strtotime($order["order_date"])) ?></td>
                                <td>Rs. <?= number_format((float) $order["total_amount"], 2) ?></td>
                                <td>
                                    <span class="<?= $order["payment_status"] === "PAID" ? "admin-payment-paid" : "admin-payment-pending" ?>">
                                        <?= adminEscape(ucwords(strtolower(str_replace("_", " ", $order["payment_status"])))) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="admin-status <?= adminStatusClass($order["order_status"]) ?>">
                                        <?= adminEscape(adminStatusLabel($order["order_status"])) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</body>
</html>
