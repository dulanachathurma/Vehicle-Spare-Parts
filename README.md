# AutoParts Lanka - Vehicle Spare Parts Management System (VSPMS)

![PHP](https://img.shields.io/badge/PHP-8.1%2B-777BB4?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.0%2B-4479A1?style=for-the-badge&logo=mysql&logoColor=white)
![Stripe](https://img.shields.io/badge/Stripe-v21.3-635BFF?style=for-the-badge&logo=stripe&logoColor=white)
![License](https://img.shields.io/badge/License-MIT-green?style=for-the-badge)
![Theme](https://img.shields.io/badge/Theme-Light%20%26%20Dark%20Mode-blueviolet?style=for-the-badge)

> **Live Demonstration Website:** [https://spare-parts.infy.click](https://spare-parts.infy.click)

**AutoParts Lanka** is a robust, full-featured e-commerce and inventory management web platform built specifically for vehicle spare parts retailers in Sri Lanka. It combines intelligent vehicle model compatibility filtering, dynamic catalog browsing, secure checkout with the official **Stripe Payment Gateway**, real-time stock management, and comprehensive admin controls.

---

## Key Features

### 1. Vehicle Compatibility / Model Match (වාහනයට ගැළපීම)
* **Make, Model & Year Filtering**: Customers can select their exact vehicle brand (Toyota, Honda, Nissan, Suzuki, Mitsubishi, Mazda, etc.), model (e.g. Vitz, Axio, Aqua, Wagon R, Civic), and manufacturing year or chassis code (e.g., KSP90, NKE165, MH34S).
* **Guaranteed Fitment Badges**: Catalogue cards display direct fitment indicators so buyers never purchase incompatible parts.
* **Admin Compatibility Tagging**: Admins can tag spare parts with specific compatible vehicles directly from the product management panel.

### 2. Spare Parts Catalogue & Smart Search
* Comprehensive automotive categories: Engine & Drivetrain, Braking Systems, Suspension & Steering, Electrical & Lighting, Cooling Systems, Filters & Maintenance, and Body Parts.
* Keyword search, category filtering, brand filtering, and real-time stock status indicators.
* Detailed product pages with high-resolution part images, specifications, OEM part numbers, and compatibility matrices.

### 3. Professional Stripe Payment Gateway
* **Official Stripe PHP SDK (`stripe/stripe-php`)**: Clean server-side checkout session creation.
* **Zero Client Price Trust**: Prices, quantities, taxes, and order totals are calculated and verified exclusively on the server from MySQL.
* **Server-to-Server Webhook (`stripe_webhook.php`)**: Cryptographically verified (`STRIPE_WEBHOOK_SECRET`) with automatic handling for `checkout.session.completed`, `payment_intent.succeeded`, and `payment_intent.payment_failed`.
* **Atomic Stock Deduction**: Stock is only decremented inside a MySQL transaction upon verified payment confirmation with strict idempotency (eliminating duplicate stock deduction if webhooks fire multiple times).
* **Payment Cancellation Handling**: If customers cancel out of Stripe, orders remain Pending with stock untouched, allowing seamless retries from "My Orders".
* **Simulated Card Payment**: Built-in development payment simulator for offline sandbox testing.

### 4. Customer Portal & Order Tracking
* **Order History & Statuses**: Track order milestones (`Pending` → `Confirmed` → `Shipped` → `Delivered`).
* **Instant Digital Receipts**: Printable invoice receipts with transaction IDs, itemized breakdowns, and delivery details.
* **Returns & Refunds**: Customers can submit return/refund requests for delivered orders with reason codes and descriptions.

### 5. Admin Management Suite
* **Inventory Control**: Add, update, activate/deactivate spare parts, manage stock levels, and upload high-resolution product photos.
* **Order Fulfillment**: Review customer orders, update tracking numbers, assign delivery dates, and process partial or full refunds.
* **Return Management**: Review, approve, or reject customer return requests with administrative notes.

### 6. Modern Responsive UI & Full Dark Mode
* Bespoke, responsive CSS design system that adapts across Mobile, Tablet, and Desktop screens.
* Integrated **Light / Dark Mode toggle** with persistent local preference, customized high-contrast surfaces, and custom-tailored palette tokens.

---

## Technology Stack

* **Backend**: PHP 8.1+ (Native procedural & modular architecture, PDO prepared statements)
* **Database**: MySQL 8.0+ (InnoDB engine, strict foreign key constraints, atomic transactions)
* **Payment Processing**: Stripe API (Stripe PHP SDK v21.3)
* **Frontend**: HTML5, Vanilla JavaScript, Vanilla CSS (CSS Custom Properties design system)
* **Dependency Management**: Composer (Stripe SDK)
* **Server Environment**: Linux (Apache / PHP Built-in Server) or Windows (WAMP / XAMPP) or Docker

---

## Project Directory Structure

```
Vehicle-Spare-Parts/
├── admin/                     # Admin Management Module
│   ├── index.php              # Admin Dashboard
│   ├── orders/                # Order status management, returns & refunds
│   └── parts/                 # Add/Edit spare parts & vehicle compatibility
├── assets/                    # Static Assets
│   ├── css/                   # base.css, admin.css, catalogue.css, orders.css
│   ├── js/                    # base.js, cart.js, vehicle_filter.js
│   └── images/                # Brand logos and banners
├── auth/                      # Authentication Module
│   ├── login.php              # User & Admin Login
│   ├── register.php           # Customer Registration
│   ├── logout.php             # Session Termination
│   └── forgot_password.php    # Password Reset
├── catalogue/                 # Product Browsing & Search
│   ├── categories.php         # Category directory
│   ├── products.php           # Filterable parts catalogue
│   ├── product_details.php    # Single product view with fitment list
│   └── ajax_vehicles.php      # Vehicle Make/Model AJAX endpoints
├── config/                    # Application Configuration
│   ├── config.php             # Core bootstrap & system constants
│   ├── database.php           # PDO Database Connection provider
│   ├── stripe.php             # Stripe SDK configuration & credential helpers
│   ├── config.local.php       # Per-machine secrets (git-ignored)
│   └── config.local.example.php # Configuration template
├── database/                  # Database Schema & Migrations
│   ├── schema.sql             # Base relational database schema
│   ├── seed_core.sql          # Base system accounts & categories
│   ├── seed_catalogue.sql     # Seed parts, brands, and categories
│   ├── seed_gateways.sql      # Seed payment gateways
│   ├── stripe_payment_update.sql # Standalone Stripe migration
│   └── migrations/            # Sequential incremental migrations (003 - 008)
├── includes/                  # Reusable Layout Partials
│   ├── header.php             # Global HTML head, navbar, theme switcher
│   ├── footer.php             # Global footer
│   ├── functions.php          # Sanitization, money formatting, CSRF helpers
│   └── auth_guard.php         # Route access authorization middleware
├── logs/                      # Runtime transaction and webhook audit logs
├── orders/                    # Shopping Cart & Checkout Module
│   ├── cart.php               # Customer Cart UI
│   ├── cart_action.php        # Add, update, delete cart items
│   ├── checkout.php           # Delivery details & Payment method selection
│   ├── place_order.php        # Server-side order creation & gateway router
│   ├── my_orders.php          # Customer order history with payment status
│   └── order_details.php      # Order detail timeline & cancellation
├── payment/                   # Payment Processing Module
│   ├── stripe_checkout.php    # Stripe Checkout Session builder & redirect
│   ├── stripe_webhook.php     # Cryptographically verified Stripe webhook listener
│   ├── payment_success.php    # Verified payment confirmation landing page
│   ├── payment_cancel.php     # Payment cancellation & retry handler
│   ├── mock_gateway.php       # Development card simulator
│   ├── receipt.php            # Printable customer invoice receipt
│   └── lib/                   # Payment & Stripe service logic
├── uploads/                   # Uploaded part images
├── composer.json              # Composer dependency definition
└── README.md                  # Project documentation
```

---

## Installation & Setup

### 1. Prerequisites
* PHP 8.1 or higher (with `pdo_mysql`, `curl`, `json`, `mbstring` extensions)
* MySQL 8.0+ or MariaDB 10.4+
* [Composer](https://getcomposer.org/)

### 2. Clone the Repository
```bash
git clone https://github.com/kavindugimshan/Vehicle-Spare-Parts.git
cd Vehicle-Spare-Parts
```

### 3. Install Dependencies
```bash
composer install
```

### 4. Database Setup
1. Log in to your MySQL terminal or phpMyAdmin:
   ```sql
   CREATE DATABASE IF NOT EXISTS vspms_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```
2. Import the database files in this exact sequential order:
   ```bash
   mysql -u root -p vspms_db < database/schema.sql
   mysql -u root -p vspms_db < database/seed_core.sql
   mysql -u root -p vspms_db < database/seed_catalogue.sql
   mysql -u root -p vspms_db < database/seed_gateways.sql
   ```
3. Run all migrations in numeric order:
   ```bash
   mysql -u root -p vspms_db < database/migrations/003_part_images.sql
   mysql -u root -p vspms_db < database/migrations/004_order_contact_info.sql
   mysql -u root -p vspms_db < database/migrations/005_return_requests.sql
   mysql -u root -p vspms_db < database/migrations/006_vehicle_compatibility.sql
   mysql -u root -p vspms_db < database/migrations/007_expanded_vehicles_and_parts.sql
   mysql -u root -p vspms_db < database/migrations/008_stripe_payment.sql
   ```

### 5. Local Configuration
Create your private configuration file by copying the template:
```bash
cp config/config.local.example.php config/config.local.php
```

Edit `config/config.local.php` with your database credentials and Stripe keys:
```php
<?php
declare(strict_types=1);

// Base URL (Override if using custom port or subdirectory)
define('BASE_URL', 'http://localhost:8081');

// Database Connection
define('DB_HOST', 'localhost');
define('DB_NAME', 'vspms_db');
define('DB_USER', 'root');
define('DB_PASS', 'your_password');

// Stripe Payment Gateway (Test Mode)
define('STRIPE_PUBLISHABLE_KEY', 'pk_test_your_publishable_key');
define('STRIPE_SECRET_KEY',      'sk_test_your_secret_key');
define('STRIPE_WEBHOOK_SECRET',  'whsec_your_webhook_signing_secret');
define('STRIPE_CURRENCY',        'lkr'); // or 'usd'
```

### 6. Start Local Server
Run PHP's built-in web server from the project root:
```bash
php -S localhost:8081
```
Open your browser and navigate to: **`http://localhost:8081`**

---

## Stripe Integration & Webhook Testing

### 1. Test Mode Keys
Get your free API keys from the [Stripe Dashboard](https://dashboard.stripe.com/test/apikeys) with **Test Mode** enabled.
* `Publishable key`: Starts with `pk_test_...`
* `Secret key`: Starts with `sk_test_...`

### 2. Testing Webhooks Locally via Stripe CLI
To enable real-time local webhook processing without exposing your local machine:
1. Authenticate with your Stripe account:
   ```bash
   stripe login
   ```
2. Start the webhook tunnel:
   ```bash
   stripe listen --forward-to localhost:8081/payment/stripe_webhook.php
   ```
3. Copy the outputted signing secret (`whsec_...`) and update `STRIPE_WEBHOOK_SECRET` in `config/config.local.php`.

### 3. Test Card Credentials
On the Stripe hosted checkout screen, enter:
* **Card Number**: `4242 4242 4242 4242`
* **Expiration**: Any future date (e.g. `12/28`)
* **CVC**: Any 3 digits (e.g. `123`)

---

## Seed User Accounts

| Role | Username | Password | Notes |
|---|---|---|---|
| **Administrator** | `admin` | `Admin@123` | Full access to `/admin` dashboard, inventory, orders, and returns |
| **Customer** | `john_doe` | `Password123` | Pre-configured customer with sample order history |
| **Customer** | `jane_smith` | `Password123` | Sample customer account |

---

## Security Architecture

* **Zero Frontend Price Reliance**: Price tampering via DevTools or crafted POST requests is impossible; item prices are queried directly from the database during order creation and Stripe session generation.
* **Cryptographic Webhook Signatures**: The webhook endpoint rejects any payload that fails Stripe signature validation (`\Stripe\Webhook::constructEvent`).
* **Atomic Database Locking**: Order settlement and stock deductions execute inside MySQL transactions using `SELECT ... FOR UPDATE` row locks.
* **Strict Idempotency**: Duplicate webhook events are safely acknowledged without processing double charges or reducing stock twice.
* **CSRF Protection**: All mutating POST forms require valid session CSRF tokens (`csrfField()` and `verifyCsrf()`).
* **SQL Injection Prevention**: All dynamic SQL queries strictly utilize PDO prepared statements with bound parameters.
* **Credential Isolation**: Production API secrets and database passwords are kept in `config/config.local.php`, which is permanently git-ignored.

---

## License

This project is open-source and available under the **MIT License**.
