<?php

session_start();
require_once "db_connection.php";

function contactEscape($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

$fullName = "";
$email = "";
$phone = "";

if (isset($_SESSION["user_id"])) {
    $userId = (int) $_SESSION["user_id"];
    $statement = $conn->prepare("
        SELECT CONCAT_WS(' ', first_name, last_name), email, phone
        FROM users
        WHERE user_id = ? AND status = 'ACTIVE'
        LIMIT 1
    ");
    $statement->bind_param("i", $userId);
    $statement->execute();
    $statement->bind_result($fullName, $email, $phone);
    $statement->fetch();
    $statement->close();
}

$conn->close();

if (empty($_SESSION["inquiry_token"])) {
    $_SESSION["inquiry_token"] = bin2hex(random_bytes(32));
}

$inquiryResult = $_GET["inquiry"] ?? "";
$notice = "";
$noticeClass = "";

if ($inquiryResult === "success") {
    $notice = "Your message was sent successfully.";
    $noticeClass = "contact-success";
} elseif ($inquiryResult === "missing") {
    $notice = "Please complete all required fields.";
    $noticeClass = "contact-error";
} elseif ($inquiryResult === "failed") {
    $notice = "Your message could not be sent. Please try again.";
    $noticeClass = "contact-error";
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us | EcoSprout</title>
    <link rel="stylesheet" href="stylesheet.css">
    <style>
        .contact-notice { margin-bottom:18px; padding:13px; text-align:center; }
        .contact-success { background:#e6f6e9; color:#145837; }
        .contact-error { background:#ffecec; color:#a40000; }
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

    <section class="contact-section">
        <p class="contact-intro">Send your plant-care questions or contact our nursery team.</p>

        <div class="contact-container">
            <div class="contact-form-box">
                <h2>Send Us a Message</h2>

                <?php if ($notice !== ""): ?>
                    <div class="contact-notice <?= $noticeClass ?>"><?= contactEscape($notice) ?></div>
                <?php endif; ?>

                <form class="contact-form" action="submit_inquiry.php" method="post">
                    <input type="hidden" name="inquiry_token"
                           value="<?= contactEscape($_SESSION["inquiry_token"]) ?>">

                    <div class="contact-row">
                        <label for="full_name">Full Name:</label>
                        <input type="text" id="full_name" name="full_name"
                               value="<?= contactEscape($fullName) ?>"
                               placeholder="Enter your full name" required>
                    </div>

                    <div class="contact-row">
                        <label for="email">Email Address:</label>
                        <input type="email" id="email" name="email"
                               value="<?= contactEscape($email) ?>"
                               placeholder="Enter your email address" required>
                    </div>

                    <div class="contact-row">
                        <label for="phone">Phone Number:</label>
                        <input type="tel" id="phone" name="phone"
                               value="<?= contactEscape($phone) ?>"
                               placeholder="Enter your phone number">
                    </div>

                    <div class="contact-row">
                        <label for="subject">Subject:</label>
                        <input type="text" id="subject" name="subject"
                               placeholder="Enter subject" required>
                    </div>

                    <div class="contact-row message-row">
                        <label for="message">Message:</label>
                        <textarea id="message" name="message"
                                  placeholder="Type your message here..." required></textarea>
                    </div>

                    <div class="privacy-row">
                        <input type="checkbox" id="privacy" required>
                        <label for="privacy">I agree to the privacy policy</label>
                    </div>

                    <button type="submit" class="contact-submit">Submit</button>
                </form>
            </div>

            <div class="contact-info-box">
                <h2>Contact Information</h2>
                <div class="contact-info-row"><strong>Phone:</strong><span>+94 71 234 5678</span></div>
                <div class="contact-info-row"><strong>Email:</strong><span>helpdesk@EcoSprout.com</span></div>
                <div class="contact-info-row"><strong>Address:</strong><span>Kegalle, Sri Lanka</span></div>
                <div class="contact-info-row">
                    <strong>Opening Hours:</strong>
                    <span>Monday - Saturday: 8:00 AM - 5:00 PM<br>Sunday: Closed</span>
                </div>
                <div class="contact-map">
                    <iframe src="https://www.google.com/maps?q=Kegalle,Sri%20Lanka&amp;output=embed"
                            loading="lazy" title="EcoSprout location"></iframe>
                </div>
            </div>
        </div>
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