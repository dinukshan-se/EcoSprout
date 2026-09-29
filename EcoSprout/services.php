<?php

session_start();
require_once "db_connection.php";

function serviceEscape($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

$selectedServiceId = filter_input(INPUT_GET, "service", FILTER_VALIDATE_INT);
if ($selectedServiceId === false || $selectedServiceId === null) {
    $selectedServiceId = 0;
}

$services = [];
$serviceSql = "
    SELECT
        s.service_id,
        s.service_name,
        s.starting_price,
        s.service_duration,
        s.service_area,
        s.image_path,
        s.description,
        sc.category_name
    FROM services s
    INNER JOIN service_categories sc
        ON sc.service_category_id = s.service_category_id
    WHERE s.status = 'ACTIVE'
    ORDER BY s.service_name
";

$serviceResult = $conn->query($serviceSql);
while ($row = $serviceResult->fetch_assoc()) {
    $services[] = $row;
}

$customerName = "";
$customerEmail = "";
$customerPhone = "";
$customerAddress = "";

if (isset($_SESSION["user_id"])) {
    $userId = (int) $_SESSION["user_id"];
    $userStatement = $conn->prepare("
        SELECT CONCAT_WS(' ', first_name, last_name), email, phone,
               CONCAT_WS(', ', address_line_1, address_line_2, city, postal_code)
        FROM users
        WHERE user_id = ? AND status = 'ACTIVE'
        LIMIT 1
    ");
    $userStatement->bind_param("i", $userId);
    $userStatement->execute();
    $userStatement->bind_result(
        $customerName,
        $customerEmail,
        $customerPhone,
        $customerAddress
    );
    $userStatement->fetch();
    $userStatement->close();
}

$conn->close();

if (empty($_SESSION["service_request_token"])) {
    $_SESSION["service_request_token"] = bin2hex(random_bytes(32));
}

$message = "";
$messageClass = "";
if (($_GET["request"] ?? "") === "success") {
    $message = "Your service request was submitted successfully.";
    $messageClass = "service-success";
} elseif (($_GET["request"] ?? "") === "missing") {
    $message = "Please complete all required service request fields.";
    $messageClass = "service-error";
} elseif (($_GET["request"] ?? "") === "invalid") {
    $message = "The selected service or preferred date is invalid.";
    $messageClass = "service-error";
} elseif (($_GET["request"] ?? "") === "failed") {
    $message = "The request could not be submitted. Please try again.";
    $messageClass = "service-error";
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gardening Services | EcoSprout</title>
    <link rel="stylesheet" href="stylesheet.css">
    <style>
        .service-heading { max-width:1200px; margin:120px auto 25px; padding:0 30px; color:#145837; }
        .service-heading h1 { font-family:Georgia,serif; font-weight:400; font-size:36px; }
        .service-details { font-size:13px; color:#145837; margin-top:8px; }
        .service-price { font-weight:bold; margin-top:8px; }
        .service-message { max-width:760px; margin:0 auto 20px; padding:13px; text-align:center; }
        .service-success { background:#e6f6e9; color:#145837; }
        .service-error { background:#ffecec; color:#a40000; }
        .service-textarea { width:100%; min-height:100px; padding:12px; border:1px solid #ccc; resize:vertical; }
        .service-form input, .service-form select { width:100%; }
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
    aria-label="Open menu"
    aria-expanded="false">
    
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

                <a href="cart.php">
                    <img src="Images/Home/Cart.png" alt="Cart">
                </a>

        </div>
    </div>

    <div class="service-heading">
        <h1>Gardening Services</h1>
        <p>Professional gardening support for homes and businesses in Kegalle.</p>
    </div>

    <section class="services-section">
        <?php if (count($services) === 0): ?>
            <p>No services are currently available.</p>
        <?php endif; ?>

        <?php foreach ($services as $service): ?>
            <article class="service-card">
                <img src="<?= serviceEscape($service["image_path"] ?: "Images/Home/Logo.png") ?>"
                     alt="<?= serviceEscape($service["service_name"]) ?>">
                <div class="service-info">
                    <div>
                        <h3><?= serviceEscape($service["service_name"]) ?></h3>
                        <p><?= serviceEscape($service["description"]) ?></p>
                        <p class="service-details">
                            <?= serviceEscape($service["category_name"]) ?>
                            <?php if (!empty($service["service_duration"])): ?>
                                · <?= serviceEscape($service["service_duration"]) ?>
                            <?php endif; ?>
                            <?php if (!empty($service["service_area"])): ?>
                                · <?= serviceEscape($service["service_area"]) ?>
                            <?php endif; ?>
                        </p>
                        <p class="service-price">
                            Starting from Rs. <?= number_format((float) $service["starting_price"], 2) ?>
                        </p>
                    </div>
                    <a href="services.php?service=<?= (int) $service["service_id"] ?>#request-service"
                       class="request-btn">Request</a>
                </div>
            </article>
        <?php endforeach; ?>
    </section>

    <section class="service-request" id="request-service">
        <h2>Request a Service</h2>

        <?php if ($message !== ""): ?>
            <div class="service-message <?= $messageClass ?>"><?= serviceEscape($message) ?></div>
        <?php endif; ?>

        <form class="service-form" action="request_service.php" method="post">
            <input type="hidden" name="service_request_token"
                   value="<?= serviceEscape($_SESSION["service_request_token"]) ?>">

            <div class="form-row">
                <label for="customer_name">Name</label>
                <input type="text" id="customer_name" name="customer_name"
                       value="<?= serviceEscape($customerName) ?>" required>
            </div>

            <div class="form-row">
                <label for="address">Address</label>
                <input type="text" id="address" name="address"
                       value="<?= serviceEscape($customerAddress) ?>" required>
            </div>

            <div class="form-row">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email"
                       value="<?= serviceEscape($customerEmail) ?>" required>
            </div>

            <div class="form-row">
                <label for="phone">Phone Number</label>
                <input type="tel" id="phone" name="phone"
                       value="<?= serviceEscape($customerPhone) ?>" required>
            </div>

            <div class="form-row">
                <label for="service_id">Select Service</label>
                <select id="service_id" name="service_id" required>
                    <option value="">Select a Service</option>
                    <?php foreach ($services as $service): ?>
                        <option value="<?= (int) $service["service_id"] ?>"
                            <?= (int) $selectedServiceId === (int) $service["service_id"] ? "selected" : "" ?>>
                            <?= serviceEscape($service["service_name"]) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-row">
                <label for="preferred_date">Preferred Date</label>
                <input type="date" id="preferred_date" name="preferred_date"
                       min="<?= date("Y-m-d") ?>">
            </div>

            <div class="form-row">
                <label for="customer_message">Message</label>
                <textarea id="customer_message" name="customer_message"
                          class="service-textarea"
                          placeholder="Describe the work you need"></textarea>
            </div>

            <button type="submit" class="service-submit">Submit Request</button>
        </form>
    </section>

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

                        <input type="email"
                               name="email"
                               placeholder="Email Address"
                               required>

                        <input type="password"
                               name="password"
                               placeholder="Password"
                               required>

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

                        <input type="text"
                               name="full_name"
                               placeholder="Full Name"
                               required>

                        <input type="email"
                               name="email"
                               placeholder="Email Address"
                               required>

                        <input type="tel"
                               name="phone"
                               placeholder="Phone Number"
                               required>

                        <input type="text"
                               name="home_address"
                               placeholder="Home Address"
                               required>

                        <input type="password"
                               name="password"
                               placeholder="Password"
                               minlength="8"
                               required>

                        <input type="password"
                               name="confirm_password"
                               placeholder="Confirm Password"
                               minlength="8"
                               required>

                        <label class="terms-row">

                            <input type="checkbox"
                                   name="terms"
                                   value="1"
                                   required>

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


<script src="script.js"></script>

</body>
</html>
