# EcoSprout 

EcoSprout is a gardening website where customers can explore plants and tools, request gardening services, place orders, and contact the business. It also includes an admin dashboard for managing products, services, staff, orders, and inquiries.

## Features

- Browse plants and gardening tools
- View and request gardening services
- Register, log in, and manage a user profile
- Add products to a cart and place orders
- View order history
- Send contact inquiries
- Manage website content and orders through the admin dashboard

## Technologies Used

- PHP
- MySQL
- HTML, CSS, and JavaScript

## Run Locally

1. Install a local PHP and MySQL environment, such as XAMPP.
2. Place the `EcoSprout` folder inside your web server's `htdocs` directory.
3. Start Apache and MySQL.
4. Create a database named `ecosprout_db`.
5. Import `SQL/ecosprout_db.sql` into the database.
6. Update `db_connection.php` with your local database username and password.
7. Open `http://localhost/EcoSprout/` in your browser.

## Project Structure

- `index.php` — home page
- `plants.php` and `tools.php` — product pages
- `services.php` — gardening services
- `cart.php` and `Checkout.php` — shopping cart and checkout
- `admindashboard.php` — admin dashboard
- `SQL/ecosprout_db.sql` — database export

## Note

This project uses PHP and MySQL, so uploading the code to GitHub does not make the website live. A hosting service that supports PHP and MySQL is needed to run it online.
