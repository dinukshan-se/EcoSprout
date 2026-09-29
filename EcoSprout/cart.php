<?php

require_once "session_config.php";

require_once "db_connection.php";

$isLoggedIn =
    isset($_SESSION["user_id"]) &&
    isset($_SESSION["logged_in"]) &&
    $_SESSION["logged_in"] === true;

$currentRole =
    strtoupper(trim($_SESSION["role"] ?? ""));


// ==============================
// INITIALIZE CART
// ==============================

if (
    !isset($_SESSION["cart"]) ||
    !is_array($_SESSION["cart"])
) {

    $_SESSION["cart"] = [];

}


// ==============================
// UPDATE CART QUANTITY
// ==============================

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["update_cart"])
) {

    $cartKey =
        $_POST["cart_key"] ?? "";

    $newQuantity =
        isset($_POST["quantity"])
        ? (int) $_POST["quantity"]
        : 1;


    if (
        $cartKey !== "" &&
        isset($_SESSION["cart"][$cartKey])
    ) {

        $item =
            $_SESSION["cart"][$cartKey];


        $productType =
            $item["product_type"];

        $productId =
            (int) $item["product_id"];


        // ------------------------------
        // GET CURRENT STOCK
        // ------------------------------

        if ($productType === "plant") {

            $stmt =
                $conn->prepare(
                    "SELECT quantity, price, plant_name
                     FROM plants
                     WHERE plant_id = ?
                     LIMIT 1"
                );

        } else {

            $stmt =
                $conn->prepare(
                    "SELECT quantity, price, tool_name
                     FROM tools
                     WHERE tool_id = ?
                     LIMIT 1"
                );

        }


        $stmt->bind_param(
            "i",
            $productId
        );

        $stmt->execute();

        $result =
            $stmt->get_result();


        if ($result->num_rows > 0) {

            $product =
                $result->fetch_assoc();


            $stock =
                (int) $product["quantity"];


            if ($newQuantity < 1) {

                $newQuantity = 1;

            }


            if ($newQuantity > $stock) {

                $newQuantity = $stock;

            }


            if ($stock > 0) {

                $_SESSION["cart"][$cartKey]["quantity"] =
                    $newQuantity;

                $_SESSION["cart"][$cartKey]["price"] =
                    (float) $product["price"];


                if ($productType === "plant") {

                    $_SESSION["cart"][$cartKey]["name"] =
                        $product["plant_name"];

                } else {

                    $_SESSION["cart"][$cartKey]["name"] =
                        $product["tool_name"];

                }

            } else {

                unset(
                    $_SESSION["cart"][$cartKey]
                );

            }

        }


        $stmt->close();

    }


    header(
        "Location: cart.php"
    );

    exit;

}


// ==============================
// REMOVE CART ITEM
// ==============================

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["remove_item"])
) {

    $cartKey =
        $_POST["cart_key"] ?? "";


    if (
        $cartKey !== "" &&
        isset($_SESSION["cart"][$cartKey])
    ) {

        unset(
            $_SESSION["cart"][$cartKey]
        );

    }


    header(
        "Location: cart.php"
    );

    exit;

}


// ==============================
// LOAD CART ITEMS
// ==============================

$cartItems = [];

$subtotal = 0;


foreach (
    $_SESSION["cart"]
    as $cartKey => $item
) {

    $productType =
        $item["product_type"];

    $productId =
        (int) $item["product_id"];


    // ------------------------------
    // PLANT
    // ------------------------------

    if ($productType === "plant") {

        $stmt =
            $conn->prepare(
                "SELECT
                    plant_id,
                    plant_name,
                    price,
                    quantity,
                    status
                 FROM plants
                 WHERE plant_id = ?
                 LIMIT 1"
            );

    }

    // ------------------------------
    // TOOL
    // ------------------------------

    else {

        $stmt =
            $conn->prepare(
                "SELECT
                    tool_id,
                    tool_name,
                    price,
                    quantity,
                    status
                 FROM tools
                 WHERE tool_id = ?
                 LIMIT 1"
            );

    }


    $stmt->bind_param(
        "i",
        $productId
    );

    $stmt->execute();

    $result =
        $stmt->get_result();


    if ($result->num_rows > 0) {

        $product =
            $result->fetch_assoc();


        // ------------------------------
        // PRODUCT DETAILS
        // ------------------------------

        if ($productType === "plant") {

            $name =
                $product["plant_name"];

        } else {

            $name =
                $product["tool_name"];

        }


        $price =
            (float) $product["price"];

        $stock =
            (int) $product["quantity"];


        $quantity =
            (int) $item["quantity"];


        // ------------------------------
        // CHECK STOCK
        // ------------------------------

        if ($stock <= 0) {

            unset(
                $_SESSION["cart"][$cartKey]
            );

            $stmt->close();

            continue;

        }


        if ($quantity > $stock) {

            $quantity =
                $stock;

            $_SESSION["cart"][$cartKey]["quantity"] =
                $quantity;

        }


        if ($quantity < 1) {

            $quantity = 1;

            $_SESSION["cart"][$cartKey]["quantity"] =
                $quantity;

        }


        // ------------------------------
        // IMAGE
        // ------------------------------

        $imagePath =
            "Images/Home/Logo.png";


        if ($productType === "plant") {

            $imageStmt =
                $conn->prepare(
                    "SELECT image_path
                     FROM plant_images
                     WHERE plant_id = ?
                     ORDER BY is_main DESC, sort_order ASC
                     LIMIT 1"
                );

        } else {

            $imageStmt =
                $conn->prepare(
                    "SELECT image_path
                     FROM tool_images
                     WHERE tool_id = ?
                     ORDER BY is_main DESC, sort_order ASC
                     LIMIT 1"
                );

        }


        $imageStmt->bind_param(
            "i",
            $productId
        );

        $imageStmt->execute();

        $imageResult =
            $imageStmt->get_result();


        if (
            $imageResult->num_rows > 0
        ) {

            $imageRow =
                $imageResult->fetch_assoc();


            if (
                !empty(
                    $imageRow["image_path"]
                )
            ) {

                $imagePath =
                    $imageRow["image_path"];

            }

        }


        $imageStmt->close();


        // ------------------------------
        // SUBTOTAL
        // ------------------------------

        $itemSubtotal =
            $price * $quantity;


        $subtotal +=
            $itemSubtotal;


        // ------------------------------
        // SAVE ITEM
        // ------------------------------

        $cartItems[] = [

            "cart_key" =>
                $cartKey,

            "product_type" =>
                $productType,

            "product_id" =>
                $productId,

            "name" =>
                $name,

            "price" =>
                $price,

            "quantity" =>
                $quantity,

            "stock" =>
                $stock,

            "image" =>
                $imagePath,

            "subtotal" =>
                $itemSubtotal

        ];

    }


    $stmt->close();

}


// ==============================
// CART TOTAL
// ==============================

$total =
    $subtotal;


// ==============================
// CART COUNT
// ==============================

$cartCount = 0;


foreach (
    $_SESSION["cart"]
    as $item
) {

    $cartCount +=
        (int) $item["quantity"];

}

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Shopping Cart | EcoSprout Nursery</title>

    <link
        rel="stylesheet"
        href="stylesheet.css"
    >

</head>


<body>


<!-- ============================== -->
<!-- HEADER -->
<!-- ============================== -->

<header class="site-header">

    <div class="header-container">

        <!-- LOGO -->
        <a href="index.php" class="logo-link">

            <img
                src="Images/Home/Logo.png"
                alt="EcoSprout Nursery"
                class="site-logo"
            >

        </a>


        <!-- NAVIGATION -->
        <nav
            class="main-nav"
            id="navMenu"
        >

            <a href="index.php">
                Home
            </a>

            <a href="plants.php">
                Plants
            </a>

            <a href="tools.php">
                Tools
            </a>

            <a href="services.php">
                Services
            </a>

            <a href="workshops.php">
                Workshops
            </a>

            <a href="contact.php">
                Contact
            </a>

        </nav>


        <!-- HEADER ACTIONS -->
        <div class="header-actions">

            <!-- ACCOUNT -->
            <?php if (!$isLoggedIn): ?>

                <button
                    type="button"
                    id="accountBtn"
                    class="account-btn"
                    title="Login or Register"
                >
                    <img
                        src="Images/Home/Account.png"
                        alt="Login or Register"
                    >
                </button>

            <?php elseif ($currentRole === "ADMIN" || $currentRole === "STAFF"): ?>

                <a
                    href="admindashboard.php"
                    class="account-btn"
                    title="Admin Dashboard"
                >
                    <img
                        src="Images/Home/Account.png"
                        alt="Admin Dashboard"
                    >
                </a>

            <?php else: ?>

                <a
                    href="userprofile.php"
                    class="account-btn"
                    title="My Account"
                >
                    <img
                        src="Images/Home/Account.png"
                        alt="My Account"
                    >
                </a>

            <?php endif; ?>


            <!-- CART -->
            <a
                href="cart.php"
                class="cart-link"
            >

                <img
                    src="Images/Home/Cart.png"
                    alt="Cart"
                >

                <?php

                $cartCount = 0;

                if (
                    isset($_SESSION["cart"]) &&
                    is_array($_SESSION["cart"])
                ) {

                    foreach (
                        $_SESSION["cart"]
                        as $item
                    ) {

                        $cartCount +=
                            (int) $item["quantity"];

                    }

                }

                ?>

                <span class="cart-count">
                    <?= $cartCount ?>
                </span>

            </a>


            <!-- MOBILE MENU -->
            <button
                type="button"
                id="mobileMenuBtn"
                class="mobile-menu-btn"
                aria-label="Open navigation menu"
                aria-expanded="false"
            >
                ☰
            </button>

        </div>

    </div>

</header>


<!-- ============================== -->
<!-- CART PAGE -->
<!-- ============================== -->

<main class="cart-page">

    <div class="cart-container">


        <div class="cart-page-header">

            <h1>
                Shopping Cart
            </h1>

            <p>
                Review your selected plants and gardening tools.
            </p>

        </div>


        <?php if (count($cartItems) > 0) { ?>


            <div class="cart-layout">


                <!-- ============================== -->
                <!-- CART ITEMS -->
                <!-- ============================== -->

                <div class="cart-items">


                    <?php foreach (
                        $cartItems
                        as $item
                    ) { ?>


                        <div
                            class="cart-item"
                        >


                            <div class="cart-item-image">

                                <img
                                    src="<?= htmlspecialchars($item["image"]) ?>"
                                    alt="<?= htmlspecialchars($item["name"]) ?>"
                                >

                            </div>


                            <div class="cart-item-details">

                                <h3>

                                    <?= htmlspecialchars(
                                        $item["name"]
                                    ) ?>

                                </h3>


                                <p class="cart-item-type">

                                    <?= ucfirst(
                                        $item["product_type"]
                                    ) ?>

                                </p>


                                <p class="cart-item-price">

                                    Rs.
                                    <?= number_format(
                                        $item["price"],
                                        2
                                    ) ?>

                                </p>


                                <p class="cart-item-stock">

                                    Stock:
                                    <?= $item["stock"] ?>

                                </p>


                            </div>


                            <div class="cart-item-actions">


                                <!-- UPDATE -->

                                <form
                                    method="POST"
                                    class="cart-quantity-form"
                                >

                                    <input
                                        type="hidden"
                                        name="update_cart"
                                        value="1"
                                    >

                                    <input
                                        type="hidden"
                                        name="cart_key"
                                        value="<?= htmlspecialchars(
                                            $item["cart_key"]
                                        ) ?>"
                                    >


                                    <label>
                                        Quantity
                                    </label>


                                    <input
                                        type="number"
                                        name="quantity"
                                        value="<?= $item["quantity"] ?>"
                                        min="1"
                                        max="<?= $item["stock"] ?>"
                                    >


                                    <button
                                        type="submit"
                                        class="update-cart-btn"
                                    >
                                        Update
                                    </button>

                                </form>


                                <!-- REMOVE -->

                                <form
                                    method="POST"
                                    class="remove-cart-form"
                                >

                                    <input
                                        type="hidden"
                                        name="remove_item"
                                        value="1"
                                    >

                                    <input
                                        type="hidden"
                                        name="cart_key"
                                        value="<?= htmlspecialchars(
                                            $item["cart_key"]
                                        ) ?>"
                                    >


                                    <button
                                        type="submit"
                                        class="remove-cart-btn"
                                    >
                                        Remove
                                    </button>

                                </form>


                            </div>


                            <div class="cart-item-subtotal">

                                <span>
                                    Subtotal
                                </span>

                                <strong>

                                    Rs.
                                    <?= number_format(
                                        $item["subtotal"],
                                        2
                                    ) ?>

                                </strong>

                            </div>


                        </div>


                    <?php } ?>


                </div>


                <!-- ============================== -->
                <!-- CART SUMMARY -->
                <!-- ============================== -->

                <aside class="cart-summary">


                    <h2>
                        Order Summary
                    </h2>


                    <div class="summary-row">

                        <span>
                            Items
                        </span>

                        <span>
                            <?= $cartCount ?>
                        </span>

                    </div>


                    <div class="summary-row">

                        <span>
                            Subtotal
                        </span>

                        <span>

                            Rs.
                            <?= number_format(
                                $subtotal,
                                2
                            ) ?>

                        </span>

                    </div>


                    <div class="summary-row total-row">

                        <strong>
                            Total
                        </strong>

                        <strong>

                            Rs.
                            <?= number_format(
                                $total,
                                2
                            ) ?>

                        </strong>

                    </div>


                    <!-- CHECKOUT BUTTON -->

                    <button
                        type="button"
                        id="checkoutBtn"
                        class="checkout-btn"
                    >

                        Proceed to Checkout

                    </button>


                    <a
                        href="plants.php"
                        class="continue-shopping-link"
                    >

                        Continue Shopping

                    </a>


                </aside>


            </div>


        <?php } else { ?>


            <!-- ============================== -->
            <!-- EMPTY CART -->
            <!-- ============================== -->

            <div class="empty-cart">

                <div class="empty-cart-icon">
                    🛒
                </div>

                <h2>
                    Your Cart is Empty
                </h2>

                <p>
                    You haven't added any products to your cart yet.
                </p>


                <a
                    href="plants.php"
                    class="shop-now-btn"
                >

                    Shop Plants

                </a>

            </div>


        <?php } ?>


    </div>

</main>


<!-- ============================== -->
<!-- ACCOUNT POPUP -->
<!-- ============================== -->

<div class="account-popup" id="accountPopup">

    <div class="account-popup-box account-popup-box-wide">

        <button type="button" class="account-close" id="accountClose">
            &times;
        </button>

        <h1>EcoSprout Account</h1>

        <div class="account-popup-content">

            <div class="login-section">
                <h2>Customer Login</h2>
                <p class="account-subtitle">Access your EcoSprout account</p>

                <form action="login.php" method="post">
                    <input type="hidden" name="redirect" value="checkout">
                    <input type="email" name="email" placeholder="Email Address" required>
                    <input type="password" name="password" placeholder="Password" required>
                    <button type="submit" class="account-main-btn">Login</button>
                </form>

                <a href="#" class="forgot-password">Forgot Password?</a>
                <div class="account-divider"></div>
                <p class="new-customer-text">
                    New customer? <br> Complete the registration form.
                </p>
            </div>

            <div class="register-section">
                <h2>Create an Account</h2>
                <p class="account-subtitle">Register as a customer or plant enthusiast</p>

                <form action="register.php" method="post">
                    <input type="hidden" name="redirect" value="checkout">
                    <input type="text" name="full_name" placeholder="Full Name" required>
                    <input type="email" name="email" placeholder="Email Address" required>
                    <input type="tel" name="phone" placeholder="Phone Number" required>
                    <input type="text" name="home_address" placeholder="Home Address" required>
                    <input type="password" name="password" placeholder="Password" minlength="8" required>
                    <input type="password" name="confirm_password" placeholder="Confirm Password" minlength="8" required>

                    <label class="terms-row">
                        <input type="checkbox" name="terms" value="1" required>
                        <span>I agree to the <a href="termsandprivacypolicy.html">terms and privacy policy</a>.</span>
                    </label>

                    <button type="submit" class="account-main-btn">Create Account</button>
                </form>
            </div>

        </div>
    </div>
</div>

<script>
window.ecoSproutLoggedIn = <?= $isLoggedIn ? "true" : "false" ?>;
</script>

<!-- ============================== -->
<!-- JAVASCRIPT -->
<!-- ============================== -->

<script>
    window.isLoggedIn = <?= (
        isset($_SESSION["user_id"]) &&
        isset($_SESSION["logged_in"]) &&
        $_SESSION["logged_in"] === true
    ) ? "true" : "false" ?>;
</script>

<script>
window.ecoSproutLoggedIn = <?= $isLoggedIn ? "true" : "false" ?>;
</script>

<script src="script.js?v=<?= time() ?>"></script>


</body>

</html>