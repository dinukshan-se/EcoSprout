// ==============================
// ACCOUNT POPUP
// ==============================

const accountBtn = document.getElementById("accountBtn");
const accountPopup = document.getElementById("accountPopup");
const accountClose = document.getElementById("accountClose");


// OPEN ACCOUNT POPUP
function openAccountPopup() {

    if (!accountPopup) {
        return;
    }

    accountPopup.classList.add("show");

    document.body.style.overflow = "hidden";

}


// CLOSE ACCOUNT POPUP
function closeAccountPopup() {

    if (!accountPopup) {
        return;
    }

    accountPopup.classList.remove("show");

    document.body.style.overflow = "";

}


// ACCOUNT ICON CLICK
if (accountBtn && accountPopup) {

    accountBtn.addEventListener("click", function (event) {

        event.preventDefault();

        openAccountPopup();

    });

}


// ACCOUNT POPUP CLOSE BUTTON
if (accountClose && accountPopup) {

    accountClose.addEventListener("click", function () {

        closeAccountPopup();

    });

}


// CLOSE ACCOUNT POPUP WHEN CLICKING OUTSIDE
if (accountPopup) {

    accountPopup.addEventListener("click", function (event) {

        if (event.target === accountPopup) {

            closeAccountPopup();

        }

    });

}


// ==============================
// CHECKOUT
// ==============================

const checkoutBtn = document.getElementById("checkoutBtn");


if (checkoutBtn) {

    checkoutBtn.addEventListener("click", function (event) {

        event.preventDefault();


        // ------------------------------
        // LOGGED-IN CUSTOMER
        // ------------------------------

        if (
            typeof window.ecoSproutLoggedIn !== "undefined" &&
            window.ecoSproutLoggedIn === true
        ) {

            window.location.href = "checkout.php";

            return;

        }


        // ------------------------------
        // GUEST CUSTOMER
        // SHOW ACCOUNT POPUP
        // ------------------------------

        openAccountPopup();

    });

}


// ==============================
// CLOSE ACCOUNT POPUP WITH ESC
// ==============================

document.addEventListener("keydown", function (event) {

    if (
        event.key === "Escape" &&
        accountPopup &&
        accountPopup.classList.contains("show")
    ) {

        closeAccountPopup();

    }

});


// ==============================
// ADD TO CART - AJAX
// ==============================

const cartForms =
    document.querySelectorAll(".add-to-cart-form");


cartForms.forEach(function (form) {

    form.addEventListener("submit", function (event) {

        event.preventDefault();


        const button =
            form.querySelector(".add-to-cart-btn");


        if (!button) {

            return;

        }


        const formData =
            new FormData(form);


        const originalText =
            button.textContent;


        button.disabled = true;

        button.textContent = "Adding...";


        fetch("cart_action.php", {

            method: "POST",

            headers: {
                "X-Requested-With": "XMLHttpRequest"
            },

            body: formData

        })

        .then(function (response) {

            if (!response.ok) {

                throw new Error(
                    "Server returned an error."
                );

            }


            return response.json();

        })

        .then(function (data) {

            if (data.success) {


                // ------------------------------
                // BUTTON SUCCESS MESSAGE
                // ------------------------------

                button.textContent =
                    "Added ✓";


                // ------------------------------
                // UPDATE CART COUNT
                // ------------------------------

                const cartCount =
                    document.querySelector(
                        ".cart-count"
                    );


                if (
                    cartCount &&
                    data.cart_count !== undefined
                ) {

                    cartCount.textContent =
                        data.cart_count;

                }


                // ------------------------------
                // RESTORE BUTTON
                // ------------------------------

                setTimeout(function () {

                    button.textContent =
                        originalText;

                    button.disabled =
                        false;

                }, 1500);


            } else {


                alert(
                    data.message ||
                    "Unable to add product to cart."
                );


                button.textContent =
                    originalText;

                button.disabled =
                    false;

            }

        })

        .catch(function (error) {

            console.error(
                "Add to cart error:",
                error
            );


            alert(
                "Something went wrong. Please try again."
            );


            button.textContent =
                originalText;

            button.disabled =
                false;

        });

    });

});



// ==============================
// BUY NOW - ADD ITEM + REDIRECT TO CART
// ==============================

const buyNowForms =
    document.querySelectorAll(".buy-now-form");


buyNowForms.forEach(function (form) {

    form.addEventListener("submit", function (event) {

        event.preventDefault();


        const button =
            form.querySelector(".buy-now-btn");


        if (!button) {
            return;
        }


        const formData =
            new FormData(form);


        const originalText =
            button.textContent;


        button.disabled = true;

        button.textContent = "Processing...";


        fetch("cart_action.php", {

            method: "POST",

            headers: {
                "X-Requested-With": "XMLHttpRequest"
            },

            body: formData

        })

        .then(function (response) {

            if (!response.ok) {

                throw new Error(
                    "Server returned an error."
                );

            }


            return response.json();

        })

        .then(function (data) {

            if (data.success) {

                // ------------------------------
                // UPDATE CART COUNT
                // ------------------------------

                const cartCount =
                    document.querySelector(
                        ".cart-count"
                    );


                if (
                    cartCount &&
                    data.cart_count !== undefined
                ) {

                    cartCount.textContent =
                        data.cart_count;

                }


                // ------------------------------
                // REDIRECT TO CART
                // ------------------------------

                window.location.href =
                    "cart.php";

                return;

            }


            alert(
                data.message ||
                "Unable to add product to cart."
            );


            button.textContent =
                originalText;

            button.disabled =
                false;

        })

        .catch(function (error) {

            console.error(
                "Buy now error:",
                error
            );


            alert(
                "Something went wrong. Please try again."
            );


            button.textContent =
                originalText;

            button.disabled =
                false;

        });

    });

});


// ==============================
// URL PARAMETERS
// ==============================

const urlParameters =
    new URLSearchParams(
        window.location.search
    );


// ==============================
// REGISTRATION SUCCESS MESSAGE
// ==============================

if (
    urlParameters.get("registered") ===
    "success"
) {

    alert(
        "Your EcoSprout account was created successfully!"
    );


    window.history.replaceState(
        {},
        document.title,
        window.location.pathname
    );

}


// ==============================
// LOGIN MESSAGES
// ==============================

const loginResult =
    urlParameters.get("login");


if (loginResult === "missing") {

    alert(
        "Please enter your email and password."
    );

}


if (loginResult === "invalid") {

    alert(
        "Incorrect email address or password."
    );

}


if (loginResult === "inactive") {

    alert(
        "Your account is inactive. Please contact EcoSprout."
    );

}


if (loginResult) {

    window.history.replaceState(
        {},
        document.title,
        window.location.pathname
    );

}


// ==============================
// LOGOUT SUCCESS MESSAGE
// ==============================

const logoutResult =
    urlParameters.get("logout");


if (logoutResult === "success") {

    alert(
        "You have logged out successfully."
    );


    window.history.replaceState(
        {},
        document.title,
        window.location.pathname
    );

}


// ==============================
// MOBILE NAVIGATION
// ==============================

const mobileMenuBtn =
    document.getElementById("mobileMenuBtn");


const navMenu =
    document.getElementById("navMenu");


if (mobileMenuBtn && navMenu) {

    mobileMenuBtn.addEventListener(
        "click",
        function () {


            mobileMenuBtn.classList.toggle(
                "active"
            );


            navMenu.classList.toggle(
                "mobile-open"
            );


            const isOpen =
                navMenu.classList.contains(
                    "mobile-open"
                );


            mobileMenuBtn.setAttribute(
                "aria-expanded",
                isOpen ? "true" : "false"
            );

        }
    );


    // ------------------------------
    // CLOSE MOBILE MENU AFTER LINK
    // ------------------------------

    const navLinks =
        navMenu.querySelectorAll("a");


    navLinks.forEach(function (link) {

        link.addEventListener(
            "click",
            function () {


                // KEEP DROPDOWN OPEN ON MOBILE
                if (
                    link.classList.contains(
                        "dropbtn"
                    ) &&
                    window.innerWidth <= 1024
                ) {

                    return;

                }


                navMenu.classList.remove(
                    "mobile-open"
                );


                mobileMenuBtn.classList.remove(
                    "active"
                );


                mobileMenuBtn.setAttribute(
                    "aria-expanded",
                    "false"
                );

            }
        );

    });


    // ------------------------------
    // RESET MENU AFTER RESIZE
    // ------------------------------

    window.addEventListener(
        "resize",
        function () {

            if (
                window.innerWidth > 1024
            ) {

                navMenu.classList.remove(
                    "mobile-open"
                );


                mobileMenuBtn.classList.remove(
                    "active"
                );


                mobileMenuBtn.setAttribute(
                    "aria-expanded",
                    "false"
                );

            }

        }
    );

}