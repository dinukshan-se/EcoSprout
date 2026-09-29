<?php

session_start();
require_once "db_connection.php";

function toolEscape($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

$categoryId = filter_input(INPUT_GET, "category", FILTER_VALIDATE_INT);
$purpose = $_GET["purpose"] ?? "";
$priceRange = $_GET["price"] ?? "";
$sort = $_GET["sort"] ?? "newest";
$search = trim($_GET["search"] ?? "");

$allowedPurposes = [
    "Digging", "Cutting", "Watering",
    "Planting", "Cleaning", "Maintenance"
];

if (!in_array($purpose, $allowedPurposes, true)) {
    $purpose = "";
}

$allowedPrices = ["under-1000", "1000-2500", "2500-5000", "5000-plus"];
if (!in_array($priceRange, $allowedPrices, true)) {
    $priceRange = "";
}

$sortOptions = [
    "low-high" => "t.price ASC, t.tool_name ASC",
    "high-low" => "t.price DESC, t.tool_name ASC",
    "a-z" => "t.tool_name ASC",
    "newest" => "t.created_at DESC, t.tool_id DESC"
];

if (!isset($sortOptions[$sort])) {
    $sort = "newest";
}

// Load tool categories for the filter.
$categories = [];
$categoryResult = $conn->query("
    SELECT tool_category_id, category_name
    FROM tool_categories
    ORDER BY category_name
");

while ($categoryRow = $categoryResult->fetch_assoc()) {
    $categories[] = $categoryRow;
}

$sql = "
    SELECT
        t.tool_id,
        t.tool_name,
        t.brand,
        t.price,
        t.quantity,
        t.purpose,
        t.material,
        t.description,
        tc.category_name,
        COALESCE(
            (SELECT ti.image_path
             FROM tool_images ti
             WHERE ti.tool_id = t.tool_id
             ORDER BY ti.is_main DESC, ti.sort_order ASC
             LIMIT 1),
            'Images/Home/Logo.png'
        ) AS image_path
    FROM tools t
    INNER JOIN tool_categories tc
        ON tc.tool_category_id = t.tool_category_id
    WHERE t.status = 'ACTIVE'
";

$parameterTypes = "";
$parameters = [];


if ($categoryId !== false && $categoryId !== null && $categoryId > 0) {
    $sql .= " AND t.tool_category_id = ?";
    $parameterTypes .= "i";
    $parameters[] = $categoryId;
} else {
    $categoryId = 0;
}

if ($purpose !== "") {
    $sql .= " AND t.purpose = ?";
    $parameterTypes .= "s";
    $parameters[] = $purpose;
}

if ($priceRange === "under-1000") {
    $sql .= " AND t.price < 1000";
} elseif ($priceRange === "1000-2500") {
    $sql .= " AND t.price BETWEEN 1000 AND 2500";
} elseif ($priceRange === "2500-5000") {
    $sql .= " AND t.price > 2500 AND t.price <= 5000";
} elseif ($priceRange === "5000-plus") {
    $sql .= " AND t.price > 5000";
}

if ($search !== "") {
    $searchValue = "%" . $search . "%";

    $sql .= "
        AND (
            t.tool_name LIKE ?
            OR t.brand LIKE ?
            OR t.purpose LIKE ?
            OR t.description LIKE ?
            OR tc.category_name LIKE ?
        )
    ";

    $parameterTypes .= "sssss";
    $parameters[] = $searchValue;
    $parameters[] = $searchValue;
    $parameters[] = $searchValue;
    $parameters[] = $searchValue;
    $parameters[] = $searchValue;
}

$sql .= " ORDER BY " . $sortOptions[$sort];
$statement = $conn->prepare($sql);

if (!$statement) {
    exit("Unable to load tools: " . $conn->error);
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
    $toolId,
    $toolName,
    $brand,
    $price,
    $quantity,
    $toolPurpose,
    $material,
    $description,
    $categoryName,
    $imagePath
);

$toolProducts = [];
while ($statement->fetch()) {
    $toolProducts[] = [
        "tool_id" => $toolId,
        "tool_name" => $toolName,
        "brand" => $brand,
        "price" => $price,
        "quantity" => $quantity,
        "purpose" => $toolPurpose,
        "material" => $material,
        "description" => $description,
        "category_name" => $categoryName,
        "image_path" => $imagePath
    ];
}

$statement->close();
$conn->close();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gardening Tools | EcoSprout</title>
    <link rel="stylesheet" href="stylesheet.css">
    <style>
        .tool-search { display: flex; gap: 8px; flex: 1; max-width: 430px; margin: 0 25px; }
        .tool-search input { flex: 1; padding: 11px; border: 1px solid #ccc; }
        .tool-search button { padding: 11px 18px; border: 0; background: #145837; color: #fff; cursor: pointer; }
        .tool-filter-form select { width: 100%; padding: 9px; margin: 7px 0 13px; border: 1px solid #ccc; }
        .tool-card-meta { min-height: 38px; font-size: 12px !important; }
        .tool-stock { margin: 8px 0; }
        .tool-stock.out { color: #a40000; }
        .add-to-cart-btn { width: 100%; margin-top: 8px; padding: 10px; border: 0; background: #145837; color: white; cursor: pointer; }
        .add-to-cart-btn:disabled { background: #999; cursor: not-allowed; }
        .tools-empty { grid-column: 1 / -1; padding: 40px; text-align: center; background: #f6f3ef; color: #145837; }
        .tool-count { color: #145837; font-size: 13px; margin-bottom: 18px; }

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
        <a href="index.php">
            <img src="Images/Home/Logo.png" alt="EcoSprout Logo">
        </a>
    </div>

    <button
        class="mobile-menu-btn"
        id="mobileMenuBtn"
        type="button"
        aria-label="Open navigation menu"
        aria-expanded="false"
    >
        <span></span>
        <span></span>
        <span></span>
    </button>

    <div class="nav-center" id="navMenu">
            <div class="dropdown">
                <a href="plants.php" class="dropbtn">Plants</a>
                <div class="dropdown-content">
                    <a href="plants.php?category=1">Indoor Plants</a>
                    <a href="plants.php?category=2">Outdoor Plants</a>
                    <a href="plants.php?category=3">Ornamental Plants</a>
                    <a href="plants.php?category=4">Edible Plants</a>
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

            <a href="cart.php"><img src="Images/Home/Cart.png" alt="Cart"></a>
        </div>
    </div>

    <main class="tools-page">
        <div class="tools-top">
            <h1>Tools</h1>

            <form action="tools.php" method="get" class="product-search-form">
                <?php if ($categoryId > 0): ?>
                    <input type="hidden" name="category" value="<?= (int) $categoryId ?>">
                <?php endif; ?>

                <?php if ($purpose !== ""): ?>
                    <input type="hidden" name="purpose" value="<?= toolEscape($purpose) ?>">
                <?php endif; ?>

                <?php if ($priceRange !== ""): ?>
                    <input type="hidden" name="price" value="<?= toolEscape($priceRange) ?>">
                <?php endif; ?>

                <input type="hidden" name="sort" value="<?= toolEscape($sort) ?>">

                <input
                    type="search"
                    name="search"
                    class="product-search-input"
                    placeholder="Search tools..."
                    value="<?= toolEscape($search) ?>"
                >

                <button type="submit" class="product-search-btn">
                    Search
                </button>

                <?php if ($search !== ""): ?>
                    <a href="tools.php" class="product-search-clear" title="Clear search">&times;</a>
                <?php endif; ?>
            </form>

            <form action="tools.php" method="get">
                <?php if ($categoryId > 0): ?>
                    <input type="hidden" name="category" value="<?= (int) $categoryId ?>">
                <?php endif; ?>

                <?php if ($purpose !== ""): ?>
                    <input type="hidden" name="purpose" value="<?= toolEscape($purpose) ?>">
                <?php endif; ?>

                <?php if ($priceRange !== ""): ?>
                    <input type="hidden" name="price" value="<?= toolEscape($priceRange) ?>">
                <?php endif; ?>

                <?php if ($search !== ""): ?>
                    <input type="hidden" name="search" value="<?= toolEscape($search) ?>">
                <?php endif; ?>

                <select class="sort-box" name="sort" onchange="this.form.submit()">
                    <option value="newest" <?= $sort === "newest" ? "selected" : "" ?>>Newest</option>
                    <option value="low-high" <?= $sort === "low-high" ? "selected" : "" ?>>Price: Low to High</option>
                    <option value="high-low" <?= $sort === "high-low" ? "selected" : "" ?>>Price: High to Low</option>
                    <option value="a-z" <?= $sort === "a-z" ? "selected" : "" ?>>Name: A - Z</option>
                </select>
            </form>
        </div>

        <p class="tool-count"><?= count($toolProducts) ?> tool(s) found</p>

        <div class="tools-layout">
            <aside class="tool-filter">
                <h2>Filter</h2>

                <form action="tools.php" method="get" class="tool-filter-form">

                    <label for="category">Category</label>
                    <select id="category" name="category">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= (int) $category["tool_category_id"] ?>"
                                <?= (int) $categoryId === (int) $category["tool_category_id"] ? "selected" : "" ?>>
                                <?= toolEscape($category["category_name"]) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <label for="purpose">Purpose</label>
                    <select id="purpose" name="purpose">
                        <option value="">All Purposes</option>
                        <?php foreach ($allowedPurposes as $purposeOption): ?>
                            <option value="<?= toolEscape($purposeOption) ?>"
                                <?= $purpose === $purposeOption ? "selected" : "" ?>>
                                <?= toolEscape($purposeOption) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <label for="price">Price</label>
                    <select id="price" name="price">
                        <option value="">All Prices</option>
                        <option value="under-1000" <?= $priceRange === "under-1000" ? "selected" : "" ?>>Under Rs. 1,000</option>
                        <option value="1000-2500" <?= $priceRange === "1000-2500" ? "selected" : "" ?>>Rs. 1,000 - 2,500</option>
                        <option value="2500-5000" <?= $priceRange === "2500-5000" ? "selected" : "" ?>>Rs. 2,500 - 5,000</option>
                        <option value="5000-plus" <?= $priceRange === "5000-plus" ? "selected" : "" ?>>Rs. 5,000+</option>
                    </select>

                    <input type="hidden" name="sort" value="<?= toolEscape($sort) ?>">
                    <?php if ($search !== ""): ?>
                        <input type="hidden" name="search" value="<?= toolEscape($search) ?>">
                    <?php endif; ?>
                    <button type="submit" class="clear-filter">Apply Filter</button>
                    <a href="tools.php" class="clear-filter" style="display:block;text-align:center;text-decoration:none;box-sizing:border-box;">Clear Filter</a>
                </form>
            </aside>

            <section class="tool-products">
                <?php if (count($toolProducts) === 0): ?>
                    <div class="tools-empty">No tools match the selected filters.</div>
                <?php endif; ?>

                <?php foreach ($toolProducts as $tool): ?>
                    <article class="tool-product-card">
                        <img src="<?= toolEscape($tool["image_path"]) ?>"
                             alt="<?= toolEscape($tool["tool_name"]) ?>">

                        <div class="tool-product-info">
                            <h3><?= toolEscape($tool["tool_name"]) ?></h3>
                            <p><?= toolEscape($tool["brand"]) ?> · <?= toolEscape($tool["category_name"]) ?></p>
                            <p class="tool-card-meta">
                                <?= toolEscape($tool["purpose"]) ?>
                                <?php if (!empty($tool["material"])): ?>
                                    · <?= toolEscape($tool["material"]) ?>
                                <?php endif; ?>
                            </p>
                            <p><strong>Rs. <?= number_format((float) $tool["price"], 2) ?></strong></p>

                            <?php if ((int) $tool["quantity"] > 0): ?>

    <p class="tool-stock">
        In stock: <?= (int) $tool["quantity"] ?>
    </p>

    <!-- PRODUCT ACTIONS -->
    <div class="tool-product-actions">

        <!-- VIEW PRODUCT -->
        <a 
            href="tool-details.php?id=<?= (int) $tool["tool_id"] ?>" 
            class="view-product-btn"
        >
            View Product
        </a>

        <!-- BUY NOW -->
        <form action="cart_action.php" method="post" class="buy-now-form">
            <input type="hidden" name="action" value="add">
            <input type="hidden" name="product_type" value="tool">
            <input type="hidden" name="product_id" value="<?= (int) $tool["tool_id"] ?>">
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
    <input type="hidden" name="product_type" value="tool">
    <input type="hidden" name="product_id" value="<?= (int) $tool["tool_id"] ?>">
    <input type="hidden" name="quantity" value="1">

    <button type="submit" class="add-to-cart-btn">
        Add to Cart
    </button>
</form>

<?php else: ?>
                                <p class="tool-stock out">Out of stock</p>
                                <button type="button" class="add-to-cart-btn" disabled>Out of Stock</button>
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


        <div class="footer-bottom">
            <p>© 2026 EcoSprout. All rights reserved.</p>
        </div>

    </footer>

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

    <script src="script.js?v=<?= time() ?>"></script>
</body>
</html>
