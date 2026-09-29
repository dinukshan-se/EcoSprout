<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: index.php?login=required");
    exit;
}

require_once "db_connection.php";

$userId = $_SESSION["user_id"];

$sql = "
    SELECT
        first_name,
        last_name,
        phone,
        alternative_phone,
        address_line_1,
        address_line_2,
        city,
        postal_code,
        email
    FROM users
    WHERE user_id = ?
      AND status = 'ACTIVE'
    LIMIT 1
";

$statement = $conn->prepare($sql);
$statement->bind_param("i", $userId);
$statement->execute();

$statement->bind_result(
    $firstName,
    $lastName,
    $phone,
    $alternativePhone,
    $addressLine1,
    $addressLine2,
    $city,
    $postalCode,
    $email
);

if (!$statement->fetch()) {
    $statement->close();
    $conn->close();
    session_destroy();

    header("Location: index.php?login=invalid");
    exit;
}

$statement->close();
$conn->close();

function escapeValue($value)
{
    return htmlspecialchars(
        $value ?? "",
        ENT_QUOTES,
        "UTF-8"
    );
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile | EcoSprout Nursery</title>
    <link rel="stylesheet" href="stylesheet.css">
</head>

<body class="profile-body">

    <!-- Profile Header -->
    <header class="profile-header">

        <div class="nav-logo">
            <a href="index.php">
                <img src="Images/Home/Logo.png" alt="EcoSprout Logo">
            </a>
        </div>
        


        <nav class=" profile-navigation" aria-label="Account navigation">

            <a href="userprofile.php" class="profile-nav-link active">
                Account
            </a>

            <a href="userorders.php" class="profile-nav-link">
                Orders
            </a>
        </nav>

        <div class="profile-header-actions">
            <div class="nav-right">

                <a href="cart.php">
                    <img src="Images/Home/Cart.png" alt="Cart">
                </a>

            </div>
            <a href="logout.php" class="profile-logout-button">
    Log Out
</a>
        </div>
    </header>


    <!-- Main Profile Section -->
    <main class="profile-main">

        <div class="profile-title">
            <p>My EcoSprout Account</p>

            <span>
                View and update your personal information.
            </span>
        </div>

        <section class="profile-card">

            <!-- Personal Information -->
            <form action="update_profile.php" method="post" class="profile-form">

                <div class="profile-section-heading">


                    <div>
                        <h2>Personal Information</h2>
                        <p>Update your account contact details.</p>
                    </div>
                </div>

                <div class="profile-form-grid">

                    <div class="profile-form-group">
                        <label for="firstName">
                            First Name
                            <span>*</span>
                        </label>

                        <input type="text"
       id="firstName"
       name="first_name"
       value="<?= escapeValue($firstName) ?>"
       required>
                    </div>

                    <div class="profile-form-group">
                        <label for="lastName">
                            Last Name
                            <span>*</span>
                        </label>

                        <input type="text"
       id="lastName"
       name="last_name"
       value="<?= escapeValue($lastName) ?>"
       required>
                    </div>

                    <div class="profile-form-group">
                        <label for="phoneNumber">
                            Phone Number
                            <span>*</span>
                        </label>

                        <input type="tel"
       id="phoneNumber"
       name="phone_number"
       value="<?= escapeValue($phone) ?>"
       required>
                    </div>

                    <div class="profile-form-group">
                        <label for="secondPhone">
                            Alternative Phone Number
                        </label>

                        <input type="tel"
       id="secondPhone"
       name="alternative_phone"
       value="<?= escapeValue($alternativePhone) ?>">
                    </div>

                    <div class="profile-form-group">
                        <label for="addressLine1">
                            Address Line 1
                            <span>*</span>
                        </label>

                        <input type="text"
       id="addressLine1"
       name="address_line_1"
       value="<?= escapeValue($addressLine1) ?>"
       required>
                    </div>

                    <div class="profile-form-group">
                        <label for="addressLine2">
                            Address Line 2
                        </label>

                        <input type="text"
       id="addressLine2"
       name="address_line_2"
       value="<?= escapeValue($addressLine2) ?>">
                    </div>

                    <div class="profile-form-group">
                        <label for="city">
                            City or Town
                            <span>*</span>
                        </label>

                        <input type="text"
        id="city"
       name="city"
       value="<?= escapeValue($city) ?>"
       required>
                    </div>

                    <div class="profile-form-group">
                        <label for="zipcode">
                            Zip Code
                            <span>*</span>
                        </label>

                        <input type="text"
       id="zipcode"
       name="zipcode"
       value="<?= escapeValue($postalCode) ?>">
                    </div>


                    <div class="profile-form-group profile-full-width">
                        <label for="emailAddress">
                            Email Address
                            <span>*</span>
                        </label>

                        <input type="email" id="emailAddress" name="email" placeholder="example@email.com"
                            autocomplete="email" required>
                    </div>

                </div>

                <div class="profile-form-actions">
                    <button type="reset" class="profile-secondary-button">
                        Cancel Changes
                    </button>

                    <button type="submit" class="profile-primary-button">
                        Save Changes
                    </button>
                </div>
            </form>


            <!-- Password Form -->
            <form action="change_password.php"
      method="post"
      class="profile-password-form">

    <div class="profile-section-heading">
        <div>
            <h2>Change Password</h2>
            <p>Use at least eight characters for your new password.</p>
        </div>
    </div>

    <div class="profile-form-grid">

        <div class="profile-form-group">
            <label for="currentPassword">
                Current Password
                <span>*</span>
            </label>

            <input type="password"
                   id="currentPassword"
                   name="current_password"
                   placeholder="Enter current password"
                   required>
        </div>

        <div class="profile-form-group">
            <label for="newPassword">
                New Password
                <span>*</span>
            </label>

            <input type="password"
                   id="newPassword"
                   name="new_password"
                   placeholder="Enter new password"
                   minlength="8"
                   required>
        </div>

        <div class="profile-form-group">
            <label for="confirmPassword">
                Confirm New Password
                <span>*</span>
            </label>

            <input type="password"
                   id="confirmPassword"
                   name="confirm_password"
                   placeholder="Enter new password again"
                   minlength="8"
                   required>
        </div>

    </div>

    <button type="submit" class="profile-primary-button">
        Update Password
    </button>

</form>

        </section>
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
                <a href="service.php">Services</a>
                <a href="events.php">Events</a>
                <a href="aboutus.php">About Us</a>
                <a href="contact.php">Contact Us</a>

            </div>


            <!--contact-->
            <div class="footer-column">

                <h3>Contact</h3>

                <p>info@ecosprout.lk</p>
                <p>+94 77 123 4567</p>
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

<script>
    const profileStatus =
        <?= json_encode($_GET["profile"] ?? "") ?>;

    if (profileStatus === "updated") {
        alert("Your profile was updated successfully.");
    }

    if (profileStatus === "missing") {
        alert("Please complete all required profile fields.");
    }

    if (profileStatus === "invalid_email") {
        alert("Please enter a valid email address.");
    }

    if (profileStatus === "email_exists") {
        alert("That email address is already used by another account.");
    }

    if (profileStatus !== "") {
        window.history.replaceState(
            {},
            document.title,
            window.location.pathname
        );
    }
</script>


<script>
    const passwordStatus =
        <?= json_encode($_GET["password"] ?? "") ?>;

    const passwordMessages = {
        updated: "Your password was updated successfully.",
        missing: "Please complete all password fields.",
        short: "The new password must contain at least 8 characters.",
        mismatch: "The new passwords do not match.",
        incorrect: "Your current password is incorrect."
    };

    if (passwordMessages[passwordStatus]) {
        alert(passwordMessages[passwordStatus]);

        window.history.replaceState(
            {},
            document.title,
            window.location.pathname
        );
    }
</script>

</body>

</html>