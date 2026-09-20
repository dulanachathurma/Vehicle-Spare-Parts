# AutoParts Lanka — Vehicle Spare Parts Management System (VSPMS)

<div align="center">

[![Live Demo](https://img.shields.io/badge/Live_Demo-autopartslanka.wuaze.com-0284c7?style=for-the-badge&logo=googlechrome&logoColor=white)](https://autopartslanka.wuaze.com/)
[![PHP](https://img.shields.io/badge/PHP-8.1%20--%208.3-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-8.0%2B-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://www.mysql.com/)
[![Stripe](https://img.shields.io/badge/Stripe-v21.3-635BFF?style=for-the-badge&logo=stripe&logoColor=white)](https://stripe.com/)
[![License](https://img.shields.io/badge/License-MIT-10b981?style=for-the-badge)](LICENSE)
[![Theme](https://img.shields.io/badge/UI-Light%20%26%20Dark%20Mode-8b5cf6?style=for-the-badge)](https://autopartslanka.wuaze.com/)

<br>

**A modern, enterprise-grade e-commerce platform and inventory management system designed for automotive spare parts retailers in Sri Lanka.**

[🌐 Explore Live Website](https://autopartslanka.wuaze.com/) • [✨ Features](#-key-features) • [🚀 Quick Start](#-installation--local-setup) • [📦 Deployment](#-production-deployment-infinityfree) • [🔐 Security](#-security-architecture)

</div>

---

## 📌 Project Overview

**AutoParts Lanka** is a full-featured web application engineered specifically to solve the complexities of vehicle spare parts merchandising. It incorporates intelligent vehicle model compatibility matching (by make, model, year, and chassis code), dynamic hierarchical categorization, real-time inventory management with low-stock alerts, customer order tracking, and an integrated **Stripe Payment Gateway** with verified server-side settlement.

> **Production Deployment:** [https://autopartslanka.wuaze.com](https://autopartslanka.wuaze.com)

---

## ✨ Key Features

### 1. 🚗 Vehicle Compatibility Matching 
* **Precise Model Filtering**: Customers select their exact vehicle brand (Toyota, Honda, Suzuki, Nissan, Mitsubishi, Mazda, etc.), model (Aqua, Prius, Wagon R, Fit, Hiace, Every, etc.), and chassis code (e.g. `NHP10`, `ZVW30`, `MH34S`, `GP5`, `KDH200`).
* **Fitment Badges**: Direct compatibility indicators on parts cards prevent customers from purchasing mismatched components.
* **Compatibility Matrices**: Product detail pages list all verified vehicle chassis variations supported by each part.

### 2. 🔍 Catalogue Browsing & Advanced Search
* **Automotive Categories**: Engine & Drivetrain, Braking Systems, Suspension & Steering, Electrical & Lighting, Cooling, and Body Panels.
* **Faceted Multi-Filter Search**: Search by keywords, OEM part number, price slider, manufacturer brand, and country of origin.
* **Search Analytics**: Anonymous guest and member search logging to identify high-demand automotive parts.

### 3. 💳 Secure Stripe Payment Gateway
* **Stripe Checkout**: Seamless card payments powered by the official `stripe/stripe-php` SDK.
* **Server-Authoritative Pricing**: Zero client-side trust — prices, shipping, discounts, and order amounts are strictly re-verified against the database.
* **Atomic Stock Deduction**: Inventory counts are decremented inside database transactions only upon verified payment confirmation.
* **Fallback Verification**: Handles payment completion seamlessly on hosting environments with webhook restrictions through cryptographic session retrieval.

### 4. 👤 Customer Experience & Order Portal
* **Live Order Milestones**: Track fulfillment progress (`Pending` → `Confirmed` → `Shipped` → `Delivered` / `Cancelled`).
* **Digital Invoices**: Instant itemized receipts with payment references and delivery addresses.
* **Return & Refund Workflow**: Customers can request returns with reason notes for delivered merchandise directly from their dashboard.

### 5. 🛡️ Comprehensive Administration Suite
* **Interactive Dashboard**: Real-time snapshot of store activity (total sales, pending orders, registered members, low stock warnings).
* **Inventory & Stock Alerts**: Add, update, and toggle active status for spare parts, set minimum stock thresholds, and upload high-resolution product photos.
* **Registered Customers Management**: Dedicated admin view displaying registered customer profiles, contact info, total orders placed, and lifetime spend.
* **Order Fulfillment**: Update order fulfillment statuses, tracking details, and approve/reject return requests.
* **Payment Gateway Management**: Configure, toggle, and inspect active payment gateways.

### 6. 🎨 Modern Design & Dark Mode
* Bespoke responsive CSS grid system designed for mobile, tablet, and desktop screens.
* Integrated **Light / Dark Mode** theme switcher with instant preference persistence in local storage.

---

## 🛠️ Technology Stack

| Layer | Technology | Details |
|---|---|---|
| **Backend** | PHP 8.1+ / 8.3 | Procedural & modular architecture, strict types, PDO prepared statements |
| **Database** | MySQL 8.0+ / MariaDB | InnoDB engine, relational constraints, foreign keys, transaction locking |
| **Payment Gateway** | Stripe API v21 | Stripe PHP SDK, Checkout Sessions, Customer Receipts |
| **Frontend** | HTML5, Modern CSS, ES6 JS | Custom CSS variables design system, responsive flexbox/grid, theme engine |
| **Dependency Management** | Composer | Handles Stripe SDK and external vendor libraries |
| **Web Server** | Apache 2.4 / Nginx / PHP CLI | Configured with `mod_rewrite`, HTTPS enforcement, and security headers |

---

## 📁 Project Directory Structure

```
Vehicle-Spare-Parts/
├── admin/                     # Admin Management Suite
│   ├── dashboard.php          # Administrative overview dashboard
│   ├── admins/                # Admin account creation & management
│   ├── gateways/              # Payment gateway settings
│   ├── orders/                # Order status management, returns & refunds
│   ├── parts/                 # Spare part catalogue & stock alert controls
│   ├── reports/               # Sales, inventory, and search analytics
│   ├── requests/              # Customer product request management
│   ├── taxonomy/              # Categories, brands, and countries management
│   └── users/                 # Registered customer directory & order history
├── assets/                    # Static Frontend Assets
│   ├── css/                   # base.css, admin.css, catalogue.css, orders.css
│   ├── js/                    # base.js, cart.js, vehicle_filter.js, catalogue.js
│   └── images/                # Brand graphics, SVG icons, and default assets
├── auth/                      # Authentication & Member Area
│   ├── login.php              # Secure login (Admin & Customer routing)
│   ├── register.php           # Customer account registration
│   ├── logout.php             # Session termination
│   └── forgot_password.php    # Password reset link dispatch
├── catalogue/                 # Product Discovery & Browsing
│   ├── products.php           # Filterable spare parts catalog
│   ├── categories.php         # Hierarchical category directory
│   ├── product_details.php    # Product details & vehicle compatibility list
│   └── search.php             # Multi-parameter faceted search
├── config/                    # System & Credential Configurations
│   ├── config.php             # Global bootstrap, constants, & autoloader
│   ├── database.php           # Singleton PDO connection provider
│   ├── stripe.php             # Stripe client configuration & helpers
│   ├── config.local.php       # Environment credentials (git-ignored)
│   └── config.local.example.php # Safe credentials template
├── database/                  # Relational Schema & Seed Data
│   ├── schema.sql             # Base relational tables & constraints
│   ├── seed_core.sql          # Seed accounts & default gateways
│   ├── seed_catalogue.sql     # Seed categories, brands, & parts
│   ├── seed_gateways.sql      # Supported gateway records
│   ├── deploy_dump.sql        # Full database snapshot for deployment
│   └── migrations/            # Incremental schema evolution scripts
├── includes/                  # Shared Layout Partials & Helpers
│   ├── header.php             # HTML head, styles, and dark-mode switcher
│   ├── navbar.php             # Responsive top navigation bar & cart counter
│   ├── footer.php             # Global site footer
│   ├── functions.php          # CSRF protection, sanitization, formatters
│   ├── auth_guard.php         # Session access control middleware
│   └── admin_sidebar.php      # Admin panel navigation sidebar
├── orders/                    # Cart & Order Placement
│   ├── cart.php               # Shopping cart interface
│   ├── checkout.php           # Delivery address & gateway selection
│   ├── place_order.php        # Server-side order creation & dispatch
│   └── my_orders.php          # Customer order history & tracking
├── payment/                   # Payment Processing Endpoints
│   ├── stripe_checkout.php    # Stripe Session initiator
│   ├── payment_success.php    # Post-payment confirmation & stock decrement
│   ├── payment_cancel.php     # Cancellation & retry handler
│   └── receipt.php            # Printable customer digital receipt
├── uploads/                   # Uploaded part images & attachments
├── test_db.php                # Diagnostic utility for deployment health
├── .htaccess                  # Apache security & URL rewrite rules
├── composer.json              # Project dependencies
└── README.md                  # Project documentation
```

---

## 🚀 Installation & Local Setup

### 1. Prerequisites
* **PHP 8.1 or higher** (with extensions: `pdo_mysql`, `curl`, `mbstring`, `json`, `openssl`)
* **MySQL 8.0+** or **MariaDB 10.4+**
* **Composer** ([Download](https://getcomposer.org/))

### 2. Clone the Repository
```bash
git clone https://github.com/kavindugimshan/Vehicle-Spare-Parts.git
cd Vehicle-Spare-Parts
```

### 3. Install Composer Dependencies
```bash
composer install
```

### 4. Setup Local Database
1. Create a fresh database in MySQL:
   ```sql
   CREATE DATABASE vspms_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```
2. Import the full schema and dataset:
   ```bash
   mysql -u root -p vspms_db < database/deploy_dump.sql
   ```
   *(Alternatively, import `database/deploy_dump.sql` via phpMyAdmin)*.

### 5. Configure Local Credentials
Copy the sample configuration file:
```bash
cp config/config.local.example.php config/config.local.php
```

Edit `config/config.local.php` with your local settings:
```php
<?php
declare(strict_types=1);

define('BASE_URL', 'http://localhost:8081');

// Database Settings
define('DB_HOST', 'localhost');
define('DB_NAME', 'vspms_db');
define('DB_USER', 'root');
define('DB_PASS', 'your_password');

// Stripe Test Mode Keys
define('STRIPE_PUBLISHABLE_KEY', 'pk_test_your_key');
define('STRIPE_SECRET_KEY',      'sk_test_your_key');
define('STRIPE_WEBHOOK_SECRET',  'whsec_your_secret');
define('STRIPE_CURRENCY',        'lkr');
```

### 6. Run the Application
Start PHP's built-in web server:
```bash
php -S localhost:8081
```
Open **[http://localhost:8081](http://localhost:8081)** in your browser.

---

## 📦 Production Deployment (InfinityFree)

The application includes built-in compatibility for shared hosts like **InfinityFree**:

1. **Upload Files**: Upload the project files into the remote `htdocs/` folder.
2. **Database Import**:
   - Create a MySQL database in the control panel.
   - Open **phpMyAdmin**, select the created database, and import [`database/deploy_dump.sql`](database/deploy_dump.sql).
3. **Configure `config.local.php`**:
   Create `htdocs/config/config.local.php` with your production host credentials:
   ```php
   <?php
   declare(strict_types=1);

   define('BASE_URL', 'https://autopartslanka.wuaze.com');

   define('DB_HOST', 'sql211.infinityfree.com');
   define('DB_USER', 'if0_42962126');
   define('DB_PASS', 'Chan1nduSE11');
   define('DB_NAME', 'if0_42962126_autoparts');

   define('STRIPE_PUBLISHABLE_KEY', 'pk_test_...');
   define('STRIPE_SECRET_KEY',      'sk_test_...');
   define('STRIPE_WEBHOOK_SECRET',  'whsec_not_used');
   define('STRIPE_CURRENCY',        'lkr');
   ```
4. **Environment Health Check**:
   Open `https://autopartslanka.wuaze.com/test_db.php` in your browser to verify database connectivity, table integrity, and product records.

---

## 🔑 Default Test Accounts

| Role | Username | Email | Password | Access / Permissions |
|---|---|---|---|---|
| **System Administrator** | `admin` | `admin@autopartslanka.lk` | `Admin@123` | Full access to `/admin` dashboard, inventory, orders, customer lists, and financial reports |
| **Verified Customer** | `john_doe` | `john.doe@example.com` | `Password123` | Customer storefront, shopping cart, checkout, order history |
| **Verified Customer** | `jane_smith` | `jane.smith@example.com` | `Password123` | Customer storefront & order history |
| **Verified Customer** | `Chanindu` | `chanindu.imanjith@gmail.com` | `Admin@123` / Personal | Customer account with sample orders |
| **Verified Customer** | `Dulana` | `dulanachathurma99@gmail.com` | `Admin@123` / Personal | Customer account with sample orders |
---

## 🔐 Security Architecture

* **Server-Side Price Authority**: Form parameters never dictate order pricing. Product rates, applicable taxes, and shipping fees are loaded directly from the database prior to creating Stripe checkout sessions.
* **SQL Injection Prevention**: 100% of user inputs pass through PDO prepared statements with strict parameter binding.
* **CSRF Token Validation**: All mutating operations (order submission, status changes, part updates, authentication) require valid cryptographic session tokens (`csrfField()` and `verifyCsrf()`).
* **Session Security**: Session identifiers are regenerated upon login (`session_regenerate_id(true)`) to prevent session fixation attacks.
* **Credential Isolation**: Local credentials, database passwords, and payment secrets reside strictly in `config/config.local.php`, isolated from version control.
* **Direct Access Denial**: Sensitive server-side directories (`/config`, `/database`, `/logs`, `/vendor`) are protected from direct HTTP access via `.htaccess` rewrite rules.

---

## 📄 License

This project is licensed under the **MIT License** — see the [LICENSE](LICENSE) file for details.

<div align="center">
  <sub>Built with ❤️ for automotive retailers and car owners across Sri Lanka.</sub>
</div>
