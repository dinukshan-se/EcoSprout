<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$isLoggedIn = isset($_SESSION["user_id"]);

?>



<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ecosprout</title>
    <link rel="stylesheet" href="stylesheet.css">
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

<!-- nav_menu-->
<div class="nav-center" id="navMenu">

            <div class="dropdown">
                <a href="plants.php" class="dropbtn">
                    Plants
                </a>
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
        <!-- account/cart-->
<div class="nav-right">

    <?php if (isset($_SESSION["user_id"])): ?>

        <a href="userprofile.php">
            <img src="Images/Home/Account.png" alt="My Account">
        </a>

    <?php else: ?>

        <a href="#" id="accountBtn">
            <img src="Images/Home/Account.png" alt="Account">
        </a>

    <?php endif; ?>

    <a href="cart.php">
        <img src="Images/Home/Cart.png" alt="Cart">
    </a>

</div>
    </div>

    <!--about us-hero-->

    <section class="about-hero">

        <img src="Images/Aboutus/Hero.jpg" alt="EcoSprout Plant Nursery">

        <div class="about-hero-overlay"></div>

        <div class="about-hero-content">

            <p>ABOUT ECOSPROUT</p>

            <h1>
                Growing greener spaces<br>
                together
            </h1>

        </div>

    </section>


    <!-- ABOUT CONTENT -->

    <section class="about-content">

        <div class="about-content-inner">

            <!-- LEFT SMALL HEADING -->
            <div class="about-label">

                <p>
                    WELCOME TO<br>
                    ECOSPROUT
                </p>

            </div>


            <!-- RIGHT CONTENT -->
            <div class="about-main">

                <h2>About us!</h2>

                <p>
                    EcoSprout is a professional platform focused on plant nursery
                    and gardening. We provide useful, reliable and informative
                    content to help plant enthusiasts create healthier and greener
                    spaces.
                </p>

                <p>
                    Our goal is to turn our passion for plants and gardening into
                    a valuable online resource. We are committed to sharing
                    high-quality information about plant care, gardening methods,
                    nursery products and professional gardening services.
                </p>

                <p>
                    We will continue to provide helpful and knowledgeable content
                    for our customers and gardening community. Your support helps
                    us continue growing and improving EcoSprout.
                </p>

                <p>
                    We hope the information and services available through
                    EcoSprout help you enjoy gardening and care for your plants
                    with confidence.
                </p>

                <p>
                    For any inquiries or further information, please feel free to contact us via email at:<br>
                    <a href="helpdesk@EcoSprout.com">helpdesk@EcoSprout.com</a>
                </p>


                <!-- IMAGE GRID -->

                <div class="about-images">

                    <img class="about-large-image" src="Images/Aboutus/Gardening.jpg" alt="EcoSprout gardening">

                    <div class="about-small-images">

                        <img src="Images/Aboutus/plantcare.avif" alt="Plant care">

                        <img src="Images/Aboutus/nursery.webp" alt="EcoSprout nursery">

                    </div>

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

    <!-- ACCOUNT POPUP -->

    <?php if (!isset($_SESSION["user_id"])): ?>

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

                        <input type="email" name="email" placeholder="Email Address" required>

                        <input type="password" name="password" placeholder="Password" required>


                        <button type="submit" class="account-main-btn">
                            Login
                        </button>

                    </form>


                    <a href="#" class="forgot-password">
                        Forgot Password?
                    </a>


                    <div class="account-divider"></div>


                    <p class="new-customer-text">
                        New cutomer? <br> Complete the registration form.
                    </p>


                </div>


                <!-- REGISTER SIDE -->
                <div class="register-section">

                    <h2>Create an Account</h2>

                    <p class="account-subtitle">
                        Register as a customer or plant enthusiast
                    </p>


                    <form action="register.php" method="post">

                        <input type="text" name="full_name" placeholder="Full Name" required>

                        <input type="email" name="email" placeholder="Email Address" required>

                        <input type="tel" name="phone" placeholder="Phone Number" required>

                        <input type="text" name="home_address" placeholder="Home Address" required>

                        <input type="password" name="password" placeholder="Password" minlength="8" required>

                        <input type="password" name="confirm_password" placeholder="Confirm Password" minlength="8" required>


                        <label class="terms-row">

                            <input type="checkbox" name="terms" value="1" required>

                            <span>
                                I agree to the <a href="termsandprivacypolicy.html">terms and privacy policy</a>.
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