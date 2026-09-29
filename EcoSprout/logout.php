<?php

session_start();

// Remove all values stored in the current session.
$_SESSION = [];

// Remove the session cookie from the browser, when sessions use cookies.
if (ini_get("session.use_cookies")) {
    $cookieParameters = session_get_cookie_params();

    setcookie(
        session_name(),
        "",
        time() - 42000,
        $cookieParameters["path"],
        $cookieParameters["domain"],
        $cookieParameters["secure"],
        $cookieParameters["httponly"]
    );
}

// Delete the server-side session.
session_destroy();

header("Location: index.php?logout=success");
exit;

