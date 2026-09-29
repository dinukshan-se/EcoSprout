<?php

session_start();
require_once "db_connection.php";

function plantEscape($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

$categoryId = filter_input(INPUT_GET, "category", FILTER_VALIDATE_INT);
$plantSize = $_GET["size"] ?? "";
$light = $_GET["light"] ?? "";
$difficulty = $_GET["difficulty"] ?? "";
$petFriendly = ($_GET["pet"] ?? "") === "1";
$airCleaner = ($_GET["air"] ?? "") === "1";
$sort = $_GET["sort"] ?? "newest";
$search = trim($_GET["search"] ?? "");

$allowedSizes = ["Small", "Medium", "Large"];
$allowedLights = ["Low Light", "Indirect Light", "Full Sunlight", "Partial Shade"];
$allowedDifficulties = ["Easy Care", "Medium Care", "Advanced Care"];

if (!in_array($plantSize, $allowedSizes, true)) {
    $plantSize = "";
}
if (!in_array($light, $allowedLights, true)) {
    $light = "";
}
if (!in_array($difficulty, $allowedDifficulties, true)) {
    $difficulty = "";
}

$sortOptions = [
    "low-high" => "p.price ASC, p.plant_name ASC",
    "high-low" => "p.price DESC, p.plant_name ASC",
    "a-z" => "p.plant_name ASC",
    "newest" => "p.created_at DESC, p.plant_id DESC"
];
if (!isset($sortOptions[$sort])) {
    $sort = "newest";
}

$categories = [];
$categoryLinks = [];
$selectedCategoryName = "";
$categoryResult = $conn->query("
    SELECT plant_category_id, category_name
    FROM plant_categories
    ORDER BY category_name
");

while ($row = $categoryResult->fetch_assoc()) {
    $categories[] = $row;
    $categoryLinks[strtolower($row["category_name"])] = (int) $row["plant_category_id"];

    if ((int) $categoryId === (int) $row["plant_category_id"]) {
        $selectedCategoryName = $row["category_name"];
    }
}

if ($categoryId === false || $categoryId === null || $categoryId < 1) {
    $categoryId = 0;
    $selectedCategoryName = "";
}

$sql = "
    SELECT
        p.plant_id,
        p.plant_name,
        p.price,
        p.quantity,
        p.plant_size,
        p.light_requirement,
        p.pot_size,
        p.pot_color,
        p.pet_friendly,
        p.difficulty,
        p.air_cleaner,
        p.description,
        pc.category_name,
        COALESCE(
            (SELECT pi.image_path
             FROM plant_images pi
             WHERE pi.plant_id = p.plant_id
             ORDER BY pi.is_main DESC, pi.sort_order ASC
             LIMIT 1),
            'Images/Home/Logo.png'
        ) AS image_path
    FROM plants p
    INNER JOIN plant_categories pc
        ON pc.plant_category_id = p.plant_category_id
    WHERE p.status = 'ACTIVE'
";

$parameterTypes = "";
$parameters = [];

if ($categoryId > 0) {
    $sql .= " AND p.plant_category_id = ?";
    $parameterTypes .= "i";
    $parameters[] = $categoryId;
}
if ($plantSize !== "") {
    $sql .= " AND p.plant_size = ?";
    $parameterTypes .= "s";
    $parameters[] = $plantSize;
}
if ($light !== "") {
    $sql .= " AND p.light_requirement = ?";
    $parameterTypes .= "s";
    $parameters[] = $light;
}
if ($difficulty !== "") {
    $sql .= " AND p.difficulty = ?";
    $parameterTypes .= "s";
    $parameters[] = $difficulty;
}
if ($petFriendly) {
    $sql .= " AND p.pet_friendly = 1";
}
if ($airCleaner) {
    $sql .= " AND p.air_cleaner = 1";
}

if ($search !== "") {
    $searchValue = "%" . $search . "%";

    $sql .= "
        AND (
            p.plant_name LIKE ?
            OR p.description LIKE ?
            OR pc.category_name LIKE ?
        )
    ";

    $parameterTypes .= "sss";
    $parameters[] = $searchValue;
    $parameters[] = $searchValue;
    $parameters[] = $searchValue;
}

$sql .= " ORDER BY " . $sortOptions[$sort];
$statement = $conn->prepare($sql);

if (!$statement) {
    exit("Unable to load plants: " . $conn->error);
}

if ($parameterTypes !== "") {
    $bindValues = [];
    foreach ($parameters as $key => $value) {
        $bindValues[$key] = &$parameters[$key];
    }
    $statement->bind_param($parameterTypes, ...$bindValues);
}

$statement->execute();
$statement->bind_result(
    $plantId,
    $plantName,
    $price,
    $quantity,
    $resultSize,
    $resultLight,
    $potSize,
    $potColor,
    $resultPetFriendly,
    $resultDifficulty,
    $resultAirCleaner,
    $description,
    $categoryName,
    $imagePath
);

$plantProducts = [];
while ($statement->fetch()) {
    $plantProducts[] = [
        "plant_id" => $plantId,
        "plant_name" => $plantName,
        "price" => $price,
        "quantity" => $quantity,
        "plant_size" => $resultSize,
        "light_requirement" => $resultLight,
        "pot_size" => $potSize,
        "pot_color" => $potColor,
        "pet_friendly" => $resultPetFriendly,
        "difficulty" => $resultDifficulty,
        "air_cleaner" => $resultAirCleaner,
        "description" => $description,
        "category_name" => $categoryName,
        "image_path" => $imagePath
    ];
}

$statement->close();
$conn->close();
$pageHeading = $selectedCategoryName === "" ? "All Plants" : $selectedCategoryName . " Plants";

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= plantEscape($pageHeading) ?> | EcoSprout</title>
    <link rel="stylesheet" href="stylesheet.css">
    <style>
        .plant-filter-form select { width:100%; padding:9px; margin:7px 0 13px; border:1px solid #ccc; }
        .filter-check { display:block; margin:10px 0; color:#145837; font-size:13px; }
        .plant-product-card { background:#f6f3ef; overflow:hidden; min-height:390px; }
        .plant-product-card img { width:100%; height:230px; object-fit:cover; display:block; }
        .plant-product-info { padding:14px; color:#145837; }
        .plant-product-info h3 { font-family:Georgia,serif; font-size:17px; font-weight:400; margin:0 0 6px; }
        .plant-product-info p { font-size:13px; margin:5px 0; }
        .plant-card-meta { min-height:36px; }
        .plant-stock.out { color:#a40000; }
        .add-to-cart-btn { width:100%; margin-top:9px; padding:10px; border:0; background:#145837; color:#fff; cursor:pointer; }
        .add-to-cart-btn:disabled { background:#999; cursor:not-allowed; }
        .plants-empty { grid-column:1/-1; padding:40px; text-align:center; background:#f6f3ef; color:#145837; }
        .plant-count { color:#145837; font-size:13px; margin-bottom:18px; }

        .product-search-form {
            display:flex;
            align-items:center;
            width:100%;
            max-width:420px;
            margin:0 20px;
        }
        .product-search-input {
            flex:1;
            min-width:0;
            height:42px;
            padding:0 14px;
            border:1px solid #cfd8d2;
            border-right:0;
            border-radius:22px 0 0 22px;
            background:#fff;
            color:#145837;
            outline:none;
        }
        .product-search-btn {
            height:42px;
            padding:0 20px;
            border:1px solid #145837;
            border-radius:0 22px 22px 0;
            background:#145837;
            color:#fff;
            cursor:pointer;
        }
        .product-search-clear {
            width:34px;
            height:34px;
            margin-left:8px;
            display:flex;
            align-items:center;
            justify-content:center;
            border-radius:50%;
            background:#f1f5f2;
            color:#145837;
            text-decoration:none;
            font-size:20px;
            line-height:1;
        }
        .product-search-clear:hover {
            background:#145837;
            color:#fff;
        }
        @media (max-width:900px) {
            .plants-top,
            .tools-top {
                flex-wrap:wrap;
                gap:12px;
            }
            .product-search-form {
                order:3;
                max-width:none;
                width:100%;
                margin:0;
            }
        }
    </style>
</head>
<body>
    <div class="navbar">
        <div class="nav-logo">
            <a href="index.php"><img src="Images/Home/Logo.png" alt="EcoSprout Logo"></a>
        </div>

        <button class="mobile-menu-btn" id="mobileMenuBtn" type="button" aria-label="Open menu">
            <span></span>
            <span></span>
            <span></span>
        </button>

        <div class="nav-center" id="navMenu">
            <div class="dropdown">
                <a href="plants.php" class="dropbtn">Plants</a>
                <div class="dropdown-content">
                    <?php foreach (["indoor", "outdoor", "ornamental", "edible"] as $categoryKey): ?>
                        <?php if (isset($categoryLinks[$categoryKey])): ?>
                            <a href="plants.php?category=<?= $categoryLinks[$categoryKey] ?>">
                                <?= plantEscape(ucfirst($categoryKey)) ?> Plants
                            </a>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
            <a href="tools.php">Tools</a>
            <a href="services.php">Services</a>
            <a href="events.php">Events</a>
            <a href="aboutus.php">About Us</a>
            <a href="contact.php">Contact</a>
        </div>

        <div class="nav-right">
    <?php if (isset($_SESSION["user_id"])): ?>

    <a href="userprofile.php">
        <img src="Images/Home/Account.png" alt="My Account">
    </a>

<?php else: ?>

    <a href="#" id="accountBtn">
        <img src="Images/Home/Account.png" alt="Account Login">
    </a>

<?php endif; ?>

    <a href="cart.php" class="cart-link">

    <img src="Images/Home/Cart.png" alt="Cart">

    <?php
    $cartCount = 0;

    if (isset($_SESSION["cart"]) && is_array($_SESSION["cart"])) {

        foreach ($_SESSION["cart"] as $item) {
            $cartCount += (int) $item["quantity"];
        }

    }
    ?>

    <span class="cart-count">
        <?= $cartCount ?>
    </span>

</a>
</div>
    </div>

    <main class="plants-page">
        <div class="plants-top">
            <h1><?= plantEscape($pageHeading) ?></h1>

            <form action="plants.php" method="get" class="product-search-form">
                <?php if ($categoryId > 0): ?>
                    <input type="hidden" name="category" value="<?= (int) $categoryId ?>">
                <?php endif; ?>

                <?php if ($plantSize !== ""): ?>
                    <input type="hidden" name="size" value="<?= plantEscape($plantSize) ?>">
                <?php endif; ?>

                <?php if ($light !== ""): ?>
                    <input type="hidden" name="light" value="<?= plantEscape($light) ?>">
                <?php endif; ?>

                <?php if ($difficulty !== ""): ?>
                    <input type="hidden" name="difficulty" value="<?= plantEscape($difficulty) ?>">
                <?php endif; ?>

                <?php if ($petFriendly): ?>
                    <input type="hidden" name="pet" value="1">
                <?php endif; ?>

                <?php if ($airCleaner): ?>
                    <input type="hidden" name="air" value="1">
                <?php endif; ?>

                <input type="hidden" name="sort" value="<?= plantEscape($sort) ?>">

                <input
                    type="search"
                    name="search"
                    class="product-search-input"
                    placeholder="Search plants..."
                    value="<?= plantEscape($search) ?>"
                >

                <button type="submit" class="product-search-btn">
                    Search
                </button>

                <?php if ($search !== ""): ?>
                    <a href="plants.php" class="product-search-clear" title="Clear search">&times;</a>
                <?php endif; ?>
            </form>

            <form action="plants.php" method="get">
                <?php if ($categoryId > 0): ?>
                    <input type="hidden" name="category" value="<?= (int) $categoryId ?>">
                <?php endif; ?>

                <input type="hidden" name="size" value="<?= plantEscape($plantSize) ?>">
                <input type="hidden" name="light" value="<?= plantEscape($light) ?>">
                <input type="hidden" name="difficulty" value="<?= plantEscape($difficulty) ?>">

                <?php if ($petFriendly): ?><input type="hidden" name="pet" value="1"><?php endif; ?>
                <?php if ($airCleaner): ?><input type="hidden" name="air" value="1"><?php endif; ?>

                <?php if ($search !== ""): ?>
                    <input type="hidden" name="search" value="<?= plantEscape($search) ?>">
                <?php endif; ?>

                <select class="sort-box" name="sort" onchange="this.form.submit()">
                    <option value="newest" <?= $sort === "newest" ? "selected" : "" ?>>Newest</option>
                    <option value="low-high" <?= $sort === "low-high" ? "selected" : "" ?>>Price: Low to High</option>
                    <option value="high-low" <?= $sort === "high-low" ? "selected" : "" ?>>Price: High to Low</option>
                    <option value="a-z" <?= $sort === "a-z" ? "selected" : "" ?>>Name: A - Z</option>
                </select>
            </form>
        </div>

        <p class="plant-count"><?= count($plantProducts) ?> plant(s) found</p>

        <div class="plants-layout">
            <aside class="plant-filter">
                <h2>Filter</h2>
                <form action="plants.php" method="get" class="plant-filter-form">

                    <label for="category">Category</label>
                    <select id="category" name="category">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= (int) $category["plant_category_id"] ?>"
                                <?= (int) $categoryId === (int) $category["plant_category_id"] ? "selected" : "" ?>>
                                <?= plantEscape($category["category_name"]) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <label for="size">Plant Size</label>
                    <select id="size" name="size">
                        <option value="">All Sizes</option>
                        <?php foreach ($allowedSizes as $option): ?>
                            <option value="<?= plantEscape($option) ?>" <?= $plantSize === $option ? "selected" : "" ?>><?= plantEscape($option) ?></option>
                        <?php endforeach; ?>
                    </select>

                    <label for="light">Light Requirement</label>
                    <select id="light" name="light">
                        <option value="">All Light Levels</option>
                        <?php foreach ($allowedLights as $option): ?>
                            <option value="<?= plantEscape($option) ?>" <?= $light === $option ? "selected" : "" ?>><?= plantEscape($option) ?></option>
                        <?php endforeach; ?>
                    </select>

                    <label for="difficulty">Difficulty</label>
                    <select id="difficulty" name="difficulty">
                        <option value="">All Care Levels</option>
                        <?php foreach ($allowedDifficulties as $option): ?>
                            <option value="<?= plantEscape($option) ?>" <?= $difficulty === $option ? "selected" : "" ?>><?= plantEscape($option) ?></option>
                        <?php endforeach; ?>
                    </select>

                    <label class="filter-check">
                        <input type="checkbox" name="pet" value="1" <?= $petFriendly ? "checked" : "" ?>> Pet Friendly
                    </label>
                    <label class="filter-check">
                        <input type="checkbox" name="air" value="1" <?= $airCleaner ? "checked" : "" ?>> Air Cleaner
                    </label>

                    <input type="hidden" name="sort" value="<?= plantEscape($sort) ?>">
                    <?php if ($search !== ""): ?>
                        <input type="hidden" name="search" value="<?= plantEscape($search) ?>">
                    <?php endif; ?>
                    <button type="submit" class="clear-filter">Apply Filter</button>
                    <a href="plants.php" class="clear-filter" style="display:block;text-align:center;text-decoration:none;box-sizing:border-box;">Clear Filter</a>
                </form>
            </aside>

            <section class="plant-products">
                <?php if (count($plantProducts) === 0): ?>
                    <div class="plants-empty">No plants match the selected filters.</div>
                <?php endif; ?>

                <?php foreach ($plantProducts as $plant): ?>
                    <article class="plant-product-card">
                        <img src="<?= plantEscape($plant["image_path"]) ?>"
                             alt="<?= plantEscape($plant["plant_name"]) ?>">
                        <div class="plant-product-info">
                            <h3><?= plantEscape($plant["plant_name"]) ?></h3>
                            <p><?= plantEscape($plant["category_name"]) ?> · <?= plantEscape($plant["plant_size"] ?: "Size not specified") ?></p>
                            <p class="plant-card-meta">
                                <?= plantEscape($plant["light_requirement"] ?: "Light not specified") ?>
                                <?php if (!empty($plant["difficulty"])): ?> · <?= plantEscape($plant["difficulty"]) ?><?php endif; ?>
                            </p>
                            <p><strong>Rs. <?= number_format((float) $plant["price"], 2) ?></strong></p>

                            <?php if ((int) $plant["quantity"] > 0): ?>

    <p class="plant-stock">
        In stock: <?= (int) $plant["quantity"] ?>
    </p>

    <!-- PRODUCT ACTION BUTTONS -->
    <div class="plant-product-actions">

        <!-- VIEW PRODUCT -->
        <a
            href="plant-details.php?id=<?= (int) $plant["plant_id"] ?>"
            class="view-product-btn"
        >
            View Product
        </a>

        <!-- BUY NOW -->
        <form action="cart_action.php" method="post" class="buy-now-form">
            <input type="hidden" name="action" value="add">
            <input type="hidden" name="product_type" value="plant">
            <input type="hidden" name="product_id" value="<?= (int) $plant["plant_id"] ?>">
            <input type="hidden" name="quantity" value="1">
            <input type="hidden" name="buy_now" value="1">

            <button type="submit" class="buy-now-btn">
                Buy Now
            </button>
        </form>

    </div>

    <!-- ADD TO CART -->
    <form class="add-to-cart-form">

        <input type="hidden" name="action" value="add">
        <input type="hidden" name="product_type" value="plant">
        <input type="hidden" name="product_id" value="<?= (int) $plant["plant_id"] ?>">
        <input type="hidden" name="quantity" value="1">

        <button type="submit" class="add-to-cart-btn">
            Add to Cart
        </button>

    </form>

<?php else: ?>

    <p class="plant-stock out">
        Out of stock
    </p>

    <button type="button" class="add-to-cart-btn" disabled>
        Out of Stock
    </button>

<?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </section>
        </div>
    </main>

    <!--footer-->
    <footer class="footer">

        <div class="footer-container">

            <!--logo-->
            <div class="footer-column footer-brand">

                <img src="Images/Home/Logo-white.png" alt="EcoSprout Logo">

                <p>
                    Quality plants, gardening tools and professional services
                    to help you create healthier and greener spaces.
                </p>
                <div class="footer_social">
                    <img src="Images/Home/white-wp.png">
                    <img src="Images/Home/white-fb.png">
                    <img src="Images/Home/white-inster.png">
                </div>

            </div>


            <!-- explore-->
            <div class="footer-column">

                <h3>Explore</h3>

                <a href="plants.php">Plants</a>
                <a href="tools.php">Tools</a>
                <a href="services.php">Services</a>
                <a href="events.php">Events</a>
                <a href="aboutus.php">About Us</a>
                <a href="contact.php">Contact</a>

            </div>


            <!--contact-->
            <div class="footer-column">

                <h3>Contact</h3>

                <p>helpdesk@EcoSprout.com</p>
                <p>+94 71 234 5678</p>
                <p>Kegalle, Sri Lanka</p>

            </div>


            <!--time-->
            <div class="footer-column">

                <h3>Opening Hours</h3>

                <p>Mon - Sat: 9:00 AM - 7:00 PM</p>
                <p>Sunday: 10:00 AM - 4:00 PM</p>

            </div>

        </div>

        <!--pop up-->
        <?php if (!isset($_SESSION["user_id"])): ?>

    <!-- ACCOUNT POPUP -->
    <div class="account-popup" id="accountPopup">

        <div class="account-popup-box">

            <!-- CLOSE BUTTON -->
            <button class="account-close" id="accountClose">
                ×
            </button>

            <h1>EcoSprout Account</h1>

            <div class="account-popup-content">

                <!-- LOGIN SIDE -->
                <div class="login-section">

                    <h2>Customer Login</h2>

                    <p class="account-subtitle">
                        Access your EcoSprout account
                    </p>

                    <form action="login.php" method="post">

                        <input
                            type="email"
                            name="email"
                            placeholder="Email Address"
                            required
                        >

                        <input
                            type="password"
                            name="password"
                            placeholder="Password"
                            required
                        >

                        <button type="submit" class="account-main-btn">
                            Login
                        </button>

                    </form>

                    <a href="#" class="forgot-password">
                        Forgot Password?
                    </a>

                    <div class="account-divider"></div>

                    <p class="new-customer-text">
                        New customer? <br>
                        Complete the registration form.
                    </p>

                </div>


                <!-- REGISTER SIDE -->
                <div class="register-section">

                    <h2>Create an Account</h2>

                    <p class="account-subtitle">
                        Register as a customer or plant enthusiast
                    </p>

                    <form action="register.php" method="post">

                        <input
                            type="text"
                            name="full_name"
                            placeholder="Full Name"
                            required
                        >

                        <input
                            type="email"
                            name="email"
                            placeholder="Email Address"
                            required
                        >

                        <input
                            type="tel"
                            name="phone"
                            placeholder="Phone Number"
                            required
                        >

                        <input
                            type="text"
                            name="home_address"
                            placeholder="Home Address"
                            required
                        >

                        <input
                            type="password"
                            name="password"
                            placeholder="Password"
                            minlength="8"
                            required
                        >

                        <input
                            type="password"
                            name="confirm_password"
                            placeholder="Confirm Password"
                            minlength="8"
                            required
                        >

                        <label class="terms-row">

                            <input
                                type="checkbox"
                                name="terms"
                                value="1"
                                required
                            >

                            <span>
                                I agree to the
                                <a href="termsandprivacypolicy.html">
                                    terms and privacy policy
                                </a>.
                            </span>

                        </label>

                        <button type="submit" class="account-main-btn">
                            Create Account
                        </button>

                    </form>

                </div>

            </div>

        </div>

    </div>

<?php endif; ?>



        <div class="footer-bottom">
            <p>© 2026 EcoSprout. All rights reserved.</p>
        </div>

    </footer>

    <script src="script.js?v=<?= time() ?>"></script>
</body>
</html>
