<?php

session_start();

require_once "db_connection.php";


/* =========================================================
   HELPER: DETECT AJAX REQUEST
========================================================= */

function isAjaxRequest()
{
    $requestedWith =
        strtolower(
            $_SERVER["HTTP_X_REQUESTED_WITH"]
            ?? ""
        );

    $accept =
        strtolower(
            $_SERVER["HTTP_ACCEPT"]
            ?? ""
        );

    return
        $requestedWith === "xmlhttprequest"
        ||
        strpos(
            $accept,
            "application/json"
        ) !== false;
}


/* =========================================================
   HELPER: SAFE RETURN URL
========================================================= */

function getReturnUrl()
{
    $referer =
        $_SERVER["HTTP_REFERER"]
        ?? "";

    if ($referer !== "") {

        $parts =
            parse_url($referer);

        $host =
            $parts["host"]
            ?? "";

        $currentHost =
            $_SERVER["HTTP_HOST"]
            ?? "";


        /*
         * Only return to pages from
         * the same website.
         */
        if (
            $host === "" ||
            $host === $currentHost
        ) {

            return $referer;
        }
    }


    return "index.php";
}


/* =========================================================
   HELPER: SEND RESPONSE
========================================================= */

function sendCartResponse(
    $response,
    $redirectResult = ""
) {

    if (isAjaxRequest()) {

        header(
            "Content-Type: application/json; charset=UTF-8"
        );

        echo json_encode(
            $response
        );

        exit;
    }


    $returnUrl =
        getReturnUrl();


    /*
     * Add simple result parameter
     * for normal form submission.
     */

    if ($redirectResult !== "") {

        $separator =
            strpos(
                $returnUrl,
                "?"
            ) !== false
                ? "&"
                : "?";


        $returnUrl .=
            $separator .
            "cart=" .
            urlencode(
                $redirectResult
            );
    }


    header(
        "Location: " . $returnUrl
    );

    exit;
}


/* =========================================================
   DEFAULT RESPONSE
========================================================= */

$response = [

    "success" => false,

    "message" =>
        "Invalid request."
];


/* =========================================================
   ONLY POST REQUESTS
========================================================= */

if (
    $_SERVER["REQUEST_METHOD"]
    !== "POST"
) {

    sendCartResponse(
        $response,
        "invalid"
    );
}


/* =========================================================
   GET ACTION
========================================================= */

$action =
    $_POST["action"]
    ?? "";


/* =========================================================
   ADD TO CART
========================================================= */

if ($action === "add") {


    /* -----------------------------------------------------
       GET PRODUCT TYPE
    ----------------------------------------------------- */

    $productType =
        $_POST["product_type"]
        ?? "";


    /* -----------------------------------------------------
       GET PRODUCT ID
    ----------------------------------------------------- */

    $productId =
        isset(
            $_POST["product_id"]
        )
            ? (int)
                $_POST["product_id"]
            : 0;


    /* -----------------------------------------------------
       GET QUANTITY
    ----------------------------------------------------- */

    $quantity =
        isset(
            $_POST["quantity"]
        )
            ? (int)
                $_POST["quantity"]
            : 1;


    /* =====================================================
       VALIDATE PRODUCT TYPE
    ===================================================== */

    if (
        !in_array(
            $productType,
            [
                "plant",
                "tool"
            ],
            true
        )
    ) {

        $response["message"] =
            "Invalid product type.";


        sendCartResponse(
            $response,
            "invalid"
        );
    }


    /* =====================================================
       VALIDATE PRODUCT ID
    ===================================================== */

    if ($productId <= 0) {

        $response["message"] =
            "Invalid product.";


        sendCartResponse(
            $response,
            "invalid"
        );
    }


    /* =====================================================
       VALIDATE QUANTITY
    ===================================================== */

    if ($quantity <= 0) {

        $quantity = 1;
    }


    /* =====================================================
       GET PRODUCT FROM DATABASE
    ===================================================== */

    if ($productType === "plant") {


        $sql = "

            SELECT

                plant_id,

                plant_name,

                price,

                quantity,

                status

            FROM plants

            WHERE plant_id = ?

            LIMIT 1

        ";


    } else {


        $sql = "

            SELECT

                tool_id,

                tool_name,

                price,

                quantity,

                status

            FROM tools

            WHERE tool_id = ?

            LIMIT 1

        ";
    }


    $stmt =
        $conn->prepare(
            $sql
        );


    if (!$stmt) {

        $response["message"] =
            "Database error.";


        sendCartResponse(
            $response,
            "error"
        );
    }


    $stmt->bind_param(
        "i",
        $productId
    );


    $stmt->execute();


    $result =
        $stmt->get_result();


    /* =====================================================
       PRODUCT NOT FOUND
    ===================================================== */

    if (
        $result->num_rows
        === 0
    ) {

        $response["message"] =
            "Product not found.";


        $stmt->close();

        $conn->close();


        sendCartResponse(
            $response,
            "not_found"
        );
    }


    $product =
        $result->fetch_assoc();


    $stmt->close();


    /* =====================================================
       CHECK PRODUCT STATUS
    ===================================================== */

    if (
        isset(
            $product["status"]
        )
        &&
        strtoupper(
            $product["status"]
        ) !== "ACTIVE"
    ) {

        $response["message"] =
            "This product is currently unavailable.";


        $conn->close();


        sendCartResponse(
            $response,
            "unavailable"
        );
    }


    /* =====================================================
       CHECK STOCK
    ===================================================== */

    $stock =
        (int)
        $product["quantity"];


    if ($stock <= 0) {

        $response["message"] =
            "This product is out of stock.";


        $conn->close();


        sendCartResponse(
            $response,
            "out_of_stock"
        );
    }


    /* =====================================================
       PRODUCT NAME
    ===================================================== */

    if (
        $productType
        === "plant"
    ) {

        $productName =
            $product[
                "plant_name"
            ];

    } else {

        $productName =
            $product[
                "tool_name"
            ];
    }


    /* =====================================================
       PRODUCT PRICE
    ===================================================== */

    $productPrice =
        (float)
        $product["price"];


    /* =====================================================
       CREATE SESSION CART
    ===================================================== */

    if (
        !isset(
            $_SESSION["cart"]
        )
        ||
        !is_array(
            $_SESSION["cart"]
        )
    ) {

        $_SESSION["cart"] =
            [];
    }


    /* =====================================================
       CREATE CART KEY
    ===================================================== */

    $cartKey =
        $productType .
        "_" .
        $productId;


    /* =====================================================
       PRODUCT ALREADY IN CART
    ===================================================== */

    if (
        isset(
            $_SESSION["cart"][
                $cartKey
            ]
        )
    ) {


        $currentQuantity =
            (int)
            $_SESSION["cart"][
                $cartKey
            ]["quantity"];


        $newQuantity =
            $currentQuantity +
            $quantity;


        /*
         * Prevent quantity from
         * becoming greater than stock.
         */

        if (
            $newQuantity >
            $stock
        ) {

            $newQuantity =
                $stock;
        }


        $_SESSION["cart"][
            $cartKey
        ]["quantity"] =
            $newQuantity;


        $_SESSION["cart"][
            $cartKey
        ]["name"] =
            $productName;


        $_SESSION["cart"][
            $cartKey
        ]["price"] =
            $productPrice;


        $_SESSION["cart"][
            $cartKey
        ]["product_type"] =
            $productType;


        $_SESSION["cart"][
            $cartKey
        ]["product_id"] =
            $productId;


    } else {


        /* =================================================
           NEW CART ITEM
        ================================================= */

        $quantity =
            min(
                $quantity,
                $stock
            );


        $_SESSION["cart"][
            $cartKey
        ] = [

            "product_type" =>
                $productType,

            "product_id" =>
                $productId,

            "name" =>
                $productName,

            "price" =>
                $productPrice,

            "quantity" =>
                $quantity

        ];
    }


    /* =====================================================
       CALCULATE CART COUNT + TOTAL
    ===================================================== */

    $cartCount = 0;

    $cartTotal = 0.00;


    foreach (
        $_SESSION["cart"]
        as $item
    ) {


        $itemQuantity =
            (int)
            (
                $item["quantity"]
                ?? 0
            );


        $itemPrice =
            (float)
            (
                $item["price"]
                ?? 0
            );


        $cartCount +=
            $itemQuantity;


        $cartTotal +=
            $itemPrice *
            $itemQuantity;
    }


    /* =====================================================
       CLOSE DATABASE
    ===================================================== */

    $conn->close();


    /* =====================================================
       SUCCESS RESPONSE
    ===================================================== */

    $response = [

        "success" =>
            true,

        "message" =>
            "Product added to cart.",

        "cart_count" =>
            $cartCount,

        "cart_total" =>
            number_format(
                $cartTotal,
                2,
                ".",
                ""
            )

    ];


    sendCartResponse(
        $response,
        "added"
    );
}


/* =========================================================
   REMOVE FROM CART
========================================================= */

if ($action === "remove") {


    $cartKey =
        trim(
            $_POST[
                "cart_key"
            ]
            ?? ""
        );


    if (
        $cartKey !== ""
        &&
        isset(
            $_SESSION[
                "cart"
            ][$cartKey]
        )
    ) {


        unset(
            $_SESSION[
                "cart"
            ][$cartKey]
        );


        /* ---------------------------------------------
           RECALCULATE COUNT + TOTAL
        --------------------------------------------- */

        $cartCount = 0;

        $cartTotal = 0.00;


        foreach (
            $_SESSION["cart"]
            ?? []
            as $item
        ) {


            $itemQuantity =
                (int)
                (
                    $item[
                        "quantity"
                    ]
                    ?? 0
                );


            $itemPrice =
                (float)
                (
                    $item[
                        "price"
                    ]
                    ?? 0
                );


            $cartCount +=
                $itemQuantity;


            $cartTotal +=
                $itemPrice *
                $itemQuantity;
        }


        $response = [

            "success" =>
                true,

            "message" =>
                "Product removed from cart.",

            "cart_count" =>
                $cartCount,

            "cart_total" =>
                number_format(
                    $cartTotal,
                    2,
                    ".",
                    ""
                )

        ];


        $conn->close();


        sendCartResponse(
            $response,
            "removed"
        );
    }


    $response["message"] =
        "Cart item not found.";


    $conn->close();


    sendCartResponse(
        $response,
        "invalid"
    );
}


/* =========================================================
   INVALID ACTION
========================================================= */

$conn->close();


sendCartResponse(
    $response,
    "invalid"
);