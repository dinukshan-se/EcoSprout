<?php

require_once "session_config.php";

$currentRole =
    strtoupper($_SESSION["role"] ?? "");

$isLoggedIn =
    isset($_SESSION["user_id"]) &&
    isset($_SESSION["logged_in"]) &&
    $_SESSION["logged_in"] === true;

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

    
<!-- NAVIGATION BAR -->
<div class="navbar">

    <!-- LOGO -->
    <div class="nav-logo">
        <a href="index.php">
            <img src="Images/Home/Logo.png" alt="EcoSprout Logo">
        </a>
    </div>

 

    <!-- HAMBURGER BUTTON -->
    <button class="mobile-menu-btn"
            id="mobileMenuBtn"
            type="button"
            aria-label="Open navigation menu"
            aria-expanded="false">

        <span></span>
        <span></span>
        <span></span>

    </button>

    <!-- NAVIGATION MENU -->
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

    <!-- ACCOUNT / CART -->
    <div class="nav-right">

        <?php if (!$isLoggedIn): ?>

            <a href="#"
               id="accountBtn"
               class="nav-account-link"
               title="Login or Register">

                <img src="Images/Home/Account.png"
                     alt="Login or Register"
                     class="nav-account-icon">

            </a>

        <?php elseif (
            $currentRole === "ADMIN" ||
            $currentRole === "STAFF"
        ): ?>

            <a href="admindashboard.php"
               class="nav-account-link"
               title="Admin Dashboard">

                <img src="Images/Home/Account.png"
                     alt="Admin Dashboard"
                     class="nav-account-icon">

            </a>

        <?php else: ?>

            <a href="userprofile.php"
               class="nav-account-link"
               title="My Account">

                <img src="Images/Home/Account.png"
                     alt="My Account"
                     class="nav-account-icon">

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

    <section class="hero">

        <!-- Background Video -->
        <video class="hero-video" autoplay muted loop>
            <source src="Videos/Home/Home-vid.mp4" video/mp4">
        </video>

        <!-- Hero Content -->
        <div class="hero-content">
            <p class="hero-small-title">Kegalle - Sri Lanka</p>
            <p class="hero-small-title">GROW WITH NATURE</p>

            <h1>
                Bring nature into<br>
                every space
            </h1>

            <p class="hero-description">
                Discover beautiful plants, gardening tools and everything
                you need to create a greener and healthier space.
            </p>

            <div class="hero-buttons">

                <a href="plants.php" class="hero-btn primary-btn">
                    PLANTS
                    <span>→</span>
                </a>

                <a href="tools.php" class="hero-btn secondary-btn">
                    TOOLS
                </a>
            </div>
        </div>
    </section>


    <section class="intro-section">
        <h3>Find your choice !</h3>

        <h1>Welcome to EcoSprout</h1>

        <p>
            EcoSprout is your trusted destination for quality plants, gardening tools,
            expert services and practical plant care guidance. Whether you are creating
            a peaceful indoor space or building a beautiful outdoor garden, we are here
            to help you grow with confidence.
        </p>

    </section>


    <!-- PLANT TYPE SECTION -->
    <section class="plant-types">

        <div class="plant-card">
            <img src="Images/Home/Indoor_plant.webp" alt="Indoor Plants">

            <div class="plant-card-content">
                <h3>Indoor Plants</h3>
                <p>Perfect plants for homes, offices and indoor spaces.</p>
            </div>
        </div>


        <div class="plant-card">
            <img src="Images/Home/Outdoor_plants.webp" alt="Outdoor Plants">

            <div class="plant-card-content">
                <h3>Outdoor Plants</h3>
                <p>Beautiful plants designed for gardens and outdoor areas.</p>
            </div>
        </div>


        <div class="plant-card">
            <img src="Images/Home/Ornamental_Plants.jpg" alt="Ornamental Plants">

            <div class="plant-card-content">
                <h3>Ornamental Plants</h3>
                <p>Decorative plants that add colour and style to your space.</p>
            </div>
        </div>


        <div class="plant-card">
            <img src="Images/Home/Edible_Plants.jpg" alt="Edible Plants">

            <div class="plant-card-content">
                <h3>Edible Plants</h3>
                <p>Grow fresh herbs, vegetables and other edible varieties.</p>
            </div>
        </div>

    </section>

    <div class="browse-plants">
        <a href="plants.php">
            Browse all plants
            <span>→</span>
        </a>
    </div>


    <!-- TOOL TIP SECTION -->

    <section class="tool-tip">

        <div class="tool-tip-image">
            <img src="Images/Home/tools-kit.jpg" alt="Gardening Tools">
        </div>

        <div class="tool-tip-content">

            <p class="tool-tip-small">GARDENING TOOLS</p>

            <h2>
                The right tools make
                gardening easier
            </h2>

            <p class="tool-tip-description">
                Choose strong and comfortable gardening tools that match the job.
                Clean your tools after use, keep cutting tools sharp, and store them
                in a dry place to make them last longer and keep your plants healthy.
            </p>

        </div>

    </section>


    <!--tool types-->

    <section class="tool-types">

        <div class="tool-card">
            <img src="Images/Home/tool-watering.webp" alt="Watering Tools">

            <div class="tool-card-content">
                <h3>Watering Tools</h3>
                <p>Watering cans, spray bottles and tools for keeping plants hydrated.</p>
            </div>
        </div>

        <div class="tool-card">
            <img src="Images/Home/tool-cutting.jpg" alt="Cutting Tools">

            <div class="tool-card-content">
                <h3>Cutting Tools</h3>
                <p>Pruners and cutters for trimming plants and removing damaged growth.</p>
            </div>
        </div>

        <div class="tool-card">
            <img src="Images/Home/tool-hand.webp" alt="Hand Tools">

            <div class="tool-card-content">
                <h3>Hand Tools</h3>
                <p>Essential hand tools for loosening soil, digging and everyday garden care.</p>
            </div>
        </div>

        <div class="tool-card">
            <img src="Images/Home/tool-planting.avif" alt="Planting Tools">

            <div class="tool-card-content">
                <h3>Planting Tools</h3>
                <p>Useful tools for planting, repotting and preparing soil for healthy growth.</p>
            </div>
        </div>

    </section>

    <div class="browse-tools">
        <a href="tools.php">
            Browse all tools
            <span>→</span>
        </a>
    </div>



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

                        <div class="register-name-row">
                            <input type="text" name="first_name" placeholder="First Name" autocomplete="given-name" required>
                            <input type="text" name="last_name" placeholder="Last Name" autocomplete="family-name" required>
                        </div>

                        <input type="email" name="email" placeholder="Email Address" autocomplete="email" required>

                        <input type="tel" name="phone" placeholder="Phone Number" required>

                        <input type="text" name="home_address" placeholder="Home Address" required>

                        <input type="password" name="password" placeholder="Password" minlength="8" required>

                        <input type="password" name="confirm_password" placeholder="Confirm Password" minlength="8"
                            required>

                        <label class="terms-row">

                            <input type="checkbox" name="terms" value="1" required>

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

    <script src="script.js"></script>

</body>

</html>