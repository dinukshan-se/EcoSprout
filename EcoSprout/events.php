<?php
session_start();
require_once "db_connection.php";

function eventEscape($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

// This page only needs the events table.
$events = [];
$eventSql = "
    SELECT event_id, event_name, event_date, start_time,
           venue, fee, image_path, description
    FROM events
    WHERE status = 'UPCOMING'
      AND event_date >= CURDATE()
    ORDER BY event_date, start_time
";

$statement = $conn->prepare($eventSql);
if (!$statement) {
    exit("Unable to load events: " . $conn->error);
}

$statement->execute();
$statement->bind_result(
    $eventId,
    $eventName,
    $eventDate,
    $startTime,
    $venue,
    $fee,
    $imagePath,
    $description
);

while ($statement->fetch()) {
    $events[] = [
        "event_id" => $eventId,
        "event_name" => $eventName,
        "event_date" => $eventDate,
        "start_time" => $startTime,
        "venue" => $venue,
        "fee" => $fee,
        "image_path" => $imagePath,
        "description" => $description
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
    <title>Events and Workshops | EcoSprout</title>
    <link rel="stylesheet" href="stylesheet.css">
    <style>
        .events-heading { max-width:1200px; margin:120px auto 25px; padding:0 30px; color:#145837; }
        .events-heading h1 { font-family:Georgia,serif; font-size:36px; font-weight:400; }
        .event-meta { font-size:13px; font-weight:600; color:#145837; }
        .event-fee { font-weight:bold; }
        .events-empty { width:100%; padding:40px; text-align:center; background:#f6f3ef; color:#145837; }
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

    <div class="events-heading">
        <h1>Events and Workshops</h1>
        <p>Learn practical gardening skills and connect with the EcoSprout community.</p>
    </div>

    <section class="events-section">
        <?php if (count($events) === 0): ?>
            <div class="events-empty">No upcoming events or workshops are currently available.</div>
        <?php endif; ?>

        <?php foreach ($events as $event): ?>
            <article class="event-card">
                <img src="<?= eventEscape($event["image_path"] ?: "Images/Home/Logo.png") ?>"
                     alt="<?= eventEscape($event["event_name"]) ?>">

                <div class="event-details">
                    <h2><?= eventEscape($event["event_name"]) ?></h2>
                    <p class="event-date">
                        <?= date("d F Y", strtotime($event["event_date"])) ?> |
                        <?= date("g:i A", strtotime($event["start_time"])) ?>
                    </p>
                    <p><?= eventEscape($event["description"]) ?></p>
                    <p class="event-meta">Venue: <?= eventEscape($event["venue"]) ?></p>
                    <p class="event-fee">
                        Fee: <?= (float) $event["fee"] === 0.0
                            ? "Free"
                            : "Rs. " . number_format((float) $event["fee"], 2) ?>
                    </p>
                    <a href="contact.php" class="event-btn">Contact</a>
                </div>
            </article>
        <?php endforeach; ?>
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
