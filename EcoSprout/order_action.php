<?php

require_once "admin_auth.php";
require_once "db_connection.php";


/* =========================================================
   ONLY POST REQUESTS
========================================================= */

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    header("Location: admin_orders.php");

    exit;
}


/* =========================================================
   GET FORM VALUES
========================================================= */

$submittedToken =
    $_POST["order_admin_token"]
    ?? "";


$savedToken =
    $_SESSION["order_admin_token"]
    ?? "";


$orderId =
    isset($_POST["order_id"])
        ? (int) $_POST["order_id"]
        : 0;


$orderStatus =
    strtoupper(
        trim(
            $_POST["order_status"]
            ?? ""
        )
    );


$paymentStatus =
    strtoupper(
        trim(
            $_POST["payment_status"]
            ?? ""
        )
    );


/* =========================================================
   VALID VALUES
========================================================= */

$allowedOrderStatuses = [

    "PENDING",

    "PROCESSING",

    "OUT_FOR_DELIVERY",

    "COMPLETED",

    "CANCELLED"

];


$allowedPaymentStatuses = [

    "PENDING",

    "PAID",

    "FAILED",

    "REFUNDED"

];


/* =========================================================
   VALIDATE SECURITY TOKEN
========================================================= */

if (
    $savedToken === ""
    ||
    $submittedToken === ""
    ||
    !hash_equals(
        $savedToken,
        $submittedToken
    )
) {

    header(
        "Location: admin_orders.php?error=token"
    );

    exit;
}


/* =========================================================
   VALIDATE ORDER ID
========================================================= */

if ($orderId <= 0) {

    header(
        "Location: admin_orders.php?error=invalid"
    );

    exit;
}


/* =========================================================
   VALIDATE ORDER STATUS
========================================================= */

if (
    !in_array(
        $orderStatus,
        $allowedOrderStatuses,
        true
    )
) {

    header(
        "Location: admin_orders.php?view=" .
        $orderId .
        "&error=status#order-details"
    );

    exit;
}


/* =========================================================
   VALIDATE PAYMENT STATUS
========================================================= */

if (
    !in_array(
        $paymentStatus,
        $allowedPaymentStatuses,
        true
    )
) {

    header(
        "Location: admin_orders.php?view=" .
        $orderId .
        "&error=payment#order-details"
    );

    exit;
}


/* =========================================================
   CHECK ORDER EXISTS
========================================================= */

$checkStatement =
    $conn->prepare(
        "
        SELECT order_id
        FROM orders
        WHERE order_id = ?
        LIMIT 1
        "
    );


if (!$checkStatement) {

    header(
        "Location: admin_orders.php?error=database"
    );

    exit;
}


$checkStatement->bind_param(
    "i",
    $orderId
);


$checkStatement->execute();


$checkResult =
    $checkStatement->get_result();


if (
    $checkResult->num_rows === 0
) {

    $checkStatement->close();

    $conn->close();


    header(
        "Location: admin_orders.php?error=notfound"
    );

    exit;
}


$checkStatement->close();


/* =========================================================
   UPDATE ORDER
========================================================= */

$updateStatement =
    $conn->prepare(
        "
        UPDATE orders
        SET
            order_status = ?,
            payment_status = ?,
            updated_at = CURRENT_TIMESTAMP
        WHERE order_id = ?
        LIMIT 1
        "
    );


if (!$updateStatement) {

    $conn->close();


    header(
        "Location: admin_orders.php?view=" .
        $orderId .
        "&error=database#order-details"
    );

    exit;
}


$updateStatement->bind_param(

    "ssi",

    $orderStatus,

    $paymentStatus,

    $orderId

);


if (
    !$updateStatement->execute()
) {

    $updateStatement->close();

    $conn->close();


    header(
        "Location: admin_orders.php?view=" .
        $orderId .
        "&error=update#order-details"
    );

    exit;
}


$updateStatement->close();


/* =========================================================
   CLOSE DATABASE
========================================================= */

$conn->close();


/* =========================================================
   SUCCESS
========================================================= */

header(
    "Location: admin_orders.php?view=" .
    $orderId .
    "&updated=1#order-details"
);

exit;

?>