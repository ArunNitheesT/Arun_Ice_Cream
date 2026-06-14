# Arun Ice Creams - E-Commerce Website

A simple ice cream shop website built with PHP, SQLite, and vanilla JavaScript.

## Prerequisites

- PHP 7.4 or higher
- SQLite3 extension (usually comes with PHP)

## How to Run

1. Open terminal and go to the project folder:
```
cd Web-Tech
```

2. Start the PHP server:
```
php -S localhost:8000
```

3. Open http://localhost:8000 in your browser.

The database is created automatically on the first page load, no manual setup needed.

## Project Structure

```
Web-Tech/
├── index.php           - Home page
├── products.php        - Product listing with search/filter
├── cart.php            - Shopping cart
├── checkout.php        - Checkout form
├── confirmation.php    - Order success page
├── api/
│   ├── products.php    - Products API
│   ├── cart.php        - Cart API (session-based)
│   └── order.php       - Order placement API
├── assets/
│   ├── css/style.css   - All styles
│   └── js/             - JavaScript files
├── includes/
│   ├── header.php      - Shared header/nav
│   ├── footer.php      - Shared footer
│   └── db.php          - Database connection
└── db/
    ├── init.php        - DB setup and seed data
    └── .htaccess       - Blocks direct access to DB file
```

## Features

- Browse 30+ ice cream products
- Filter by category (Cones, Cups, Bars, etc.)
- Search products by name
- Add to cart, adjust quantities
- Checkout with delivery details
- Order confirmation page
- Responsive design (works on mobile)

## Tech Stack

- PHP 7.4+ with PDO/SQLite
- Vanilla JavaScript (no frameworks)
- CSS3 with custom properties
- Google Fonts (Pacifico + Nunito)
