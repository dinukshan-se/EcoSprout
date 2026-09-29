<?php

require_once "admin_auth.php";
require_once "db_connection.php";

/*
 * admin_auth.php should already:
 * - start the session
 * - check whether the user is logged in
 * - allow ADMIN and STAFF users
 */

/* Safely display database values */
function orderAdminEscape($value)
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        "UTF-8"
    );
}

/* Create security token for order update form */
if (empty($_SESSION["order_admin_token"])) {
    $_SESSION["order_admin_token"] =
        bin2hex(random_bytes(32));
}

/* Current logged-in user information */
$currentRole = $_SESSION["role"] ?? "STAFF";

$firstName = $_SESSION["first_name"] ?? "";
$lastName = $_SESSION["last_name"] ?? "";

$adminName = trim($firstName . " " . $lastName);

if ($adminName === "") {
    $adminName = "EcoSprout User";
}

$roleLabel = $currentRole === "ADMIN"
    ? "Administrator"
    : "Staff";

/* Allowed values from the database */
$orderStatuses = [
    "PENDING",
    "PROCESSING",
    "OUT_FOR_DELIVERY",
    "COMPLETED",
    "CANCELLED"
];

$paymentStatuses = [
    "PENDING",
    "PAID",
    "FAILED",
    "REFUNDED"
];

/* Read filter values */
$orderStatusFilter =
    strtoupper(trim($_GET["order_status"] ?? ""));

$paymentStatusFilter =
    strtoupper(trim($_GET["payment_status"] ?? ""));

/* Reject invalid filter values */
if (
    $orderStatusFilter !== "" &&
    !in_array(
        $orderStatusFilter,
        $orderStatuses,
        true
    )
) {
    $orderStatusFilter = "";
}

if (
    $paymentStatusFilter !== "" &&
    !in_array(
        $paymentStatusFilter,
        $paymentStatuses,
        true
    )
) {
    $paymentStatusFilter = "";
}

/* Build the filter query */
$whereConditions = [];

if ($orderStatusFilter !== "") {
    $safeOrderStatus =
        $conn->real_escape_string($orderStatusFilter);

    $whereConditions[] =
        "order_status = '$safeOrderStatus'";
}

if ($paymentStatusFilter !== "") {
    $safePaymentStatus =
        $conn->real_escape_string($paymentStatusFilter);

    $whereConditions[] =
        "payment_status = '$safePaymentStatus'";
}

$whereSql = "";

if (!empty($whereConditions)) {
    $whereSql =
        " WHERE " . implode(" AND ", $whereConditions);
}

/* Load orders */
$orderSql = "
    SELECT
        order_id,
        order_code,
        first_name,
        last_name,
        phone,
        email,
        order_date,
        total_amount,
        payment_status,
        order_status
    FROM orders
    $whereSql
    ORDER BY order_date DESC, order_id DESC
";

$orderResult = $conn->query($orderSql);

if (!$orderResult) {
    exit(
        "Unable to load orders: " .
        orderAdminEscape($conn->error)
    );
}

/* Dashboard summary values */
$summarySql = "
    SELECT
        COUNT(*) AS total_orders,

        SUM(
            CASE
                WHEN order_status = 'PENDING'
                THEN 1
                ELSE 0
            END
        ) AS pending_orders,

        SUM(
            CASE
                WHEN order_status = 'COMPLETED'
                THEN 1
                ELSE 0
            END
        ) AS completed_orders,

        COALESCE(
            SUM(
                CASE
                    WHEN payment_status = 'PAID'
                    THEN total_amount
                    ELSE 0
                END
            ),
            0
        ) AS paid_revenue
    FROM orders
";

$summaryResult = $conn->query($summarySql);

$summary = [
    "total_orders" => 0,
    "pending_orders" => 0,
    "completed_orders" => 0,
    "paid_revenue" => 0
];

if ($summaryResult) {
    $summaryRow = $summaryResult->fetch_assoc();

    if ($summaryRow) {
        $summary = $summaryRow;
    }
}

/* Selected order details */
$selectedOrder = null;
$selectedItems = [];

$selectedOrderId =
    filter_input(
        INPUT_GET,
        "view",
        FILTER_VALIDATE_INT
    );

if ($selectedOrderId) {

    /* Load selected order */
    $detailSql = "
        SELECT
            order_id,
            order_code,
            first_name,
            last_name,
            phone,
            email,
            address_line_1,
            address_line_2,
            city,
            postal_code,
            payment_method,
            payment_status,
            order_status,
            subtotal,
            delivery_fee,
            total_amount,
            remark,
            order_date,
            updated_at
        FROM orders
        WHERE order_id = ?
        LIMIT 1
    ";

    $detailStatement = $conn->prepare($detailSql);

    if ($detailStatement) {
        $detailStatement->bind_param(
            "i",
            $selectedOrderId
        );

        $detailStatement->execute();

        $detailResult =
            $detailStatement->get_result();

        $selectedOrder =
            $detailResult->fetch_assoc();

        $detailStatement->close();
    }

    /* Load products belonging to the order */
    if ($selectedOrder) {

        $itemSql = "
            SELECT
                order_item_id,
                plant_id,
                tool_id,
                item_name,
                quantity,
                unit_price,
                line_total
            FROM order_items
            WHERE order_id = ?
            ORDER BY order_item_id ASC
        ";

        $itemStatement = $conn->prepare($itemSql);

        if ($itemStatement) {
            $itemStatement->bind_param(
                "i",
                $selectedOrderId
            );

            $itemStatement->execute();

            $itemResult =
                $itemStatement->get_result();

            while ($item = $itemResult->fetch_assoc()) {
                $selectedItems[] = $item;
            }

            $itemStatement->close();
        }
    }
}

/* Result messages from order_action.php */
$successMessage = "";
$errorMessage = "";

if (isset($_GET["updated"])) {
    $successMessage =
        "The order was updated successfully.";
}

if (isset($_GET["error"])) {
    $errorMessage =
        "The order could not be updated.";
}

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Manage Orders | EcoSprout</title>

    <link rel="stylesheet"
          href="stylesheet.css">

</head>

<body class="admin-body">

    <!-- Header -->
    <header class="admin-header">

        <a href="index.php"
           class="admin-logo">

            <img src="Images/Home/Logo.png"
                 alt="EcoSprout Nursery">

        </a>

        <div class="admin-user">

            <div class="admin-user-details">

                <strong>
                    <?= orderAdminEscape($adminName) ?>
                </strong>

                <span>
                    <?= orderAdminEscape($roleLabel) ?>
                </span>

            </div>

            <a href="logout.php"
               class="admin-logout-button">
                Logout
            </a>

        </div>

    </header>


    <!-- Admin Navigation -->
    <nav class="admin-navigation">
        <a href="admindashboard.php" class="admin-nav-link">Dashboard</a>
        <a href="admin_orders.php" class="admin-nav-link">Orders</a>
        <a href="admin_plants.php" class="admin-nav-link">Plants</a>
        <a href="admin_tools.php" class="admin-nav-link">Tools</a>
        <a href="admin_services.php" class="admin-nav-link">Services</a>
        <a href="admin_inquiries.php" class="admin-nav-link">Inquiries</a>
        <?php if ($isAdministrator): ?> <a href="admin_staff.php" class="admin-nav-link">Manage Staff</a><?php endif; ?>
    </nav>


    <!-- Main Content -->
    <main class="order-admin-main">

        <div class="order-admin-title">

            <p>EcoSprout Order Management</p>

            <h1>Manage Orders</h1>

            <span>
                View customer orders and update their status.
            </span>

        </div>


        <!-- Success or Error Messages -->
        <?php if ($successMessage !== ""): ?>

            <div class="order-notice success">
                <?= orderAdminEscape($successMessage) ?>
            </div>

        <?php endif; ?>


        <?php if ($errorMessage !== ""): ?>

            <div class="order-notice error">
                <?= orderAdminEscape($errorMessage) ?>
            </div>

        <?php endif; ?>


        <!-- Order Summary -->
        <section class="order-admin-summary">

            <div class="order-admin-card">

                <span>Total Orders</span>

                <strong>
                    <?= (int) $summary["total_orders"] ?>
                </strong>

            </div>

            <div class="order-admin-card">

                <span>Pending Orders</span>

                <strong>
                    <?= (int) $summary["pending_orders"] ?>
                </strong>

            </div>

            <div class="order-admin-card">

                <span>Completed Orders</span>

                <strong>
                    <?= (int) $summary["completed_orders"] ?>
                </strong>

            </div>

            <div class="order-admin-card">

                <span>Paid Revenue</span>

                <strong>
                    LKR
                    <?= number_format(
                        (float) $summary["paid_revenue"],
                        2
                    ) ?>
                </strong>

            </div>

        </section>


        <!-- Filters -->
        <section class="order-admin-panel">

            <div class="order-admin-panel-heading">

                <div>
                    <h2>Customer Orders</h2>

                    <p>
                        Filter orders using their current status.
                    </p>
                </div>

            </div>

            <form method="get"
                  action="admin_orders.php"
                  class="order-admin-filters">

                <div>

                    <label for="orderStatus">
                        Order Status
                    </label>

                    <select id="orderStatus"
                            name="order_status">

                        <option value="">
                            All order statuses
                        </option>

                        <?php foreach ($orderStatuses as $status): ?>

                            <option
                                value="<?= orderAdminEscape($status) ?>"
                                <?= $orderStatusFilter === $status
                                    ? "selected"
                                    : "" ?>>

                                <?= orderAdminEscape(
                                    ucwords(
                                        strtolower(
                                            str_replace(
                                                "_",
                                                " ",
                                                $status
                                            )
                                        )
                                    )
                                ) ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <div>

                    <label for="paymentStatus">
                        Payment Status
                    </label>

                    <select id="paymentStatus"
                            name="payment_status">

                        <option value="">
                            All payment statuses
                        </option>

                        <?php foreach ($paymentStatuses as $status): ?>

                            <option
                                value="<?= orderAdminEscape($status) ?>"
                                <?= $paymentStatusFilter === $status
                                    ? "selected"
                                    : "" ?>>

                                <?= orderAdminEscape(
                                    ucwords(
                                        strtolower($status)
                                    )
                                ) ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>

                <button type="submit"
                        class="order-admin-button">
                    Apply Filters
                </button>

                <a href="admin_orders.php"
                   class="order-admin-button secondary">
                    Clear
                </a>

            </form>


            <!-- Orders Table -->
            <div class="order-admin-table-wrapper">

                <table class="order-admin-table">

                    <thead>

                        <tr>
                            <th>Order Code</th>
                            <th>Customer</th>
                            <th>Contact</th>
                            <th>Order Date</th>
                            <th>Total</th>
                            <th>Payment</th>
                            <th>Order Status</th>
                            <th>Action</th>
                        </tr>

                    </thead>

                    <tbody>

                        <?php if ($orderResult->num_rows > 0): ?>

                            <?php while (
                                $order = $orderResult->fetch_assoc()
                            ): ?>

                                <tr>

                                    <td>
                                        <strong>
                                            <?= orderAdminEscape(
                                                $order["order_code"]
                                            ) ?>
                                        </strong>
                                    </td>

                                    <td>
                                        <?= orderAdminEscape(
                                            trim(
                                                $order["first_name"] .
                                                " " .
                                                $order["last_name"]
                                            )
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= orderAdminEscape(
                                            $order["phone"]
                                        ) ?>

                                        <br>

                                        <small>
                                            <?= orderAdminEscape(
                                                $order["email"]
                                            ) ?>
                                        </small>
                                    </td>

                                    <td>
                                        <?= date(
                                            "d M Y, h:i A",
                                            strtotime(
                                                $order["order_date"]
                                            )
                                        ) ?>
                                    </td>

                                    <td>
                                        LKR
                                        <?= number_format(
                                            (float)
                                            $order["total_amount"],
                                            2
                                        ) ?>
                                    </td>

                                    <td>

                                        <span class="order-status-label
                                            payment-<?=
                                            strtolower(
                                                $order["payment_status"]
                                            )
                                            ?>">

                                            <?= orderAdminEscape(
                                                ucwords(
                                                    strtolower(
                                                        $order[
                                                            "payment_status"
                                                        ]
                                                    )
                                                )
                                            ) ?>

                                        </span>

                                    </td>

                                    <td>

                                        <span class="order-status-label
                                            status-<?=
                                            strtolower(
                                                str_replace(
                                                    "_",
                                                    "-",
                                                    $order["order_status"]
                                                )
                                            )
                                            ?>">

                                            <?= orderAdminEscape(
                                                ucwords(
                                                    strtolower(
                                                        str_replace(
                                                            "_",
                                                            " ",
                                                            $order[
                                                                "order_status"
                                                            ]
                                                        )
                                                    )
                                                )
                                            ) ?>

                                        </span>

                                    </td>

                                    <td>

                                        <a href="admin_orders.php?view=<?=
                                            (int) $order["order_id"]
                                            ?>"
                                           class="order-admin-button small">

                                            View

                                        </a>

                                    </td>

                                </tr>

                            <?php endwhile; ?>

                        <?php else: ?>

                            <tr>

                                <td colspan="8"
                                    class="order-empty-message">

                                    No orders were found.

                                </td>

                            </tr>

                        <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </section>


        <!-- Selected Order Details -->
        <?php if ($selectedOrder): ?>

            <section class="order-admin-panel"
                     id="order-details">

                <div class="order-admin-panel-heading">

                    <div>

                        <h2>
                            Order
                            <?= orderAdminEscape(
                                $selectedOrder["order_code"]
                            ) ?>
                        </h2>

                        <p>
                            Complete customer and order information.
                        </p>

                    </div>

                    <a href="admin_orders.php"
                       class="order-admin-button secondary">
                        Close Details
                    </a>

                </div>


                <div class="order-details-grid">

                    <div class="order-detail-block">

                        <h3>Customer Details</h3>

                        <p>
                            <strong>Name:</strong>

                            <?= orderAdminEscape(
                                trim(
                                    $selectedOrder["first_name"] .
                                    " " .
                                    $selectedOrder["last_name"]
                                )
                            ) ?>
                        </p>

                        <p>
                            <strong>Email:</strong>

                            <?= orderAdminEscape(
                                $selectedOrder["email"]
                            ) ?>
                        </p>

                        <p>
                            <strong>Phone:</strong>

                            <?= orderAdminEscape(
                                $selectedOrder["phone"]
                            ) ?>
                        </p>

                    </div>


                    <div class="order-detail-block">

                        <h3>Delivery Address</h3>

                        <p>
                            <?= orderAdminEscape(
                                $selectedOrder["address_line_1"]
                            ) ?>
                        </p>

                        <?php if (
                            !empty(
                                $selectedOrder["address_line_2"]
                            )
                        ): ?>

                            <p>
                                <?= orderAdminEscape(
                                    $selectedOrder["address_line_2"]
                                ) ?>
                            </p>

                        <?php endif; ?>

                        <p>
                            <?= orderAdminEscape(
                                $selectedOrder["city"]
                            ) ?>

                            <?php if (
                                !empty(
                                    $selectedOrder["postal_code"]
                                )
                            ): ?>

                                ,
                                <?= orderAdminEscape(
                                    $selectedOrder["postal_code"]
                                ) ?>

                            <?php endif; ?>
                        </p>

                    </div>


                    <div class="order-detail-block">

                        <h3>Payment Details</h3>

                        <p>
                            <strong>Method:</strong>

                            <?= orderAdminEscape(
                                ucwords(
                                    strtolower(
                                        str_replace(
                                            "_",
                                            " ",
                                            $selectedOrder[
                                                "payment_method"
                                            ]
                                        )
                                    )
                                )
                            ) ?>
                        </p>

                        <p>
                            <strong>Subtotal:</strong>

                            LKR
                            <?= number_format(
                                (float)
                                $selectedOrder["subtotal"],
                                2
                            ) ?>
                        </p>

                        <p>
                            <strong>Delivery:</strong>

                            LKR
                            <?= number_format(
                                (float)
                                $selectedOrder["delivery_fee"],
                                2
                            ) ?>
                        </p>

                        <p>
                            <strong>Total:</strong>

                            LKR
                            <?= number_format(
                                (float)
                                $selectedOrder["total_amount"],
                                2
                            ) ?>
                        </p>

                    </div>

                </div>


                <!-- Ordered Items -->
                <div class="order-items-section">

                    <h3>Ordered Items</h3>

                    <div class="order-admin-table-wrapper">

                        <table class="order-items-table">

                            <thead>

                                <tr>
                                    <th>Item</th>
                                    <th>Type</th>
                                    <th>Unit Price</th>
                                    <th>Quantity</th>
                                    <th>Total</th>
                                </tr>

                            </thead>

                            <tbody>

                                <?php if (!empty($selectedItems)): ?>

                                    <?php foreach (
                                        $selectedItems as $item
                                    ): ?>

                                        <?php

                                        if (!empty($item["plant_id"])) {
                                            $itemType = "Plant";
                                        } elseif (
                                            !empty($item["tool_id"])
                                        ) {
                                            $itemType = "Tool";
                                        } else {
                                            $itemType = "Product";
                                        }

                                        ?>

                                        <tr>

                                            <td>
                                                <?= orderAdminEscape(
                                                    $item["item_name"]
                                                ) ?>
                                            </td>

                                            <td>
                                                <?= orderAdminEscape(
                                                    $itemType
                                                ) ?>
                                            </td>

                                            <td>
                                                LKR
                                                <?= number_format(
                                                    (float)
                                                    $item["unit_price"],
                                                    2
                                                ) ?>
                                            </td>

                                            <td>
                                                <?= (int)
                                                    $item["quantity"] ?>
                                            </td>

                                            <td>
                                                LKR
                                                <?= number_format(
                                                    (float)
                                                    $item["line_total"],
                                                    2
                                                ) ?>
                                            </td>

                                        </tr>

                                    <?php endforeach; ?>

                                <?php else: ?>

                                    <tr>

                                        <td colspan="5"
                                            class="order-empty-message">

                                            No order items were found.

                                        </td>

                                    </tr>

                                <?php endif; ?>

                            </tbody>

                        </table>

                    </div>

                </div>


                <!-- Update Order -->
                <div class="order-update-section">

                    <h3>Update Order</h3>

                    <form action="order_action.php"
                          method="post"
                          class="order-update-form">

                        <input type="hidden"
                               name="order_admin_token"
                               value="<?= orderAdminEscape(
                                   $_SESSION[
                                       "order_admin_token"
                                   ]
                               ) ?>">

                        <input type="hidden"
                               name="order_id"
                               value="<?= (int)
                                   $selectedOrder["order_id"] ?>">


                        <div>

                            <label for="newOrderStatus">
                                Order Status
                            </label>

                            <select id="newOrderStatus"
                                    name="order_status"
                                    required>

                                <?php foreach (
                                    $orderStatuses as $status
                                ): ?>

                                    <option
                                        value="<?=
                                        orderAdminEscape($status)
                                        ?>"
                                        <?= $selectedOrder[
                                            "order_status"
                                        ] === $status
                                            ? "selected"
                                            : "" ?>>

                                        <?= orderAdminEscape(
                                            ucwords(
                                                strtolower(
                                                    str_replace(
                                                        "_",
                                                        " ",
                                                        $status
                                                    )
                                                )
                                            )
                                        ) ?>

                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>


                        <div>

                            <label for="newPaymentStatus">
                                Payment Status
                            </label>

                            <select id="newPaymentStatus"
                                    name="payment_status"
                                    required>

                                <?php foreach (
                                    $paymentStatuses as $status
                                ): ?>

                                    <option
                                        value="<?=
                                        orderAdminEscape($status)
                                        ?>"
                                        <?= $selectedOrder[
                                            "payment_status"
                                        ] === $status
                                            ? "selected"
                                            : "" ?>>

                                        <?= orderAdminEscape(
                                            ucwords(
                                                strtolower($status)
                                            )
                                        ) ?>

                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>

                        <button type="submit"
                                class="order-admin-button">
                            Update Order
                        </button>

                    </form>

                </div>


                <?php if (
                    !empty($selectedOrder["remark"])
                ): ?>

                    <div class="order-detail-block order-remark">

                        <h3>Customer Remark</h3>

                        <p>
                            <?= nl2br(
                                orderAdminEscape(
                                    $selectedOrder["remark"]
                                )
                            ) ?>
                        </p>

                    </div>

                <?php endif; ?>

            </section>

        <?php elseif ($selectedOrderId): ?>

            <div class="order-notice error">
                The selected order could not be found.
            </div>

        <?php endif; ?>

    </main>

</body>

</html>

<?php

$conn->close();

?>