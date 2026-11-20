# 📚 Bookly — AI-Powered Boutique E-Commerce Platform

[![PHP Version](https://img.shields.io/badge/PHP-8.x-777BB4?style=flat&logo=php&logoColor=white)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?style=flat&logo=mysql&logoColor=white)](https://www.mysql.com/)
[![Google Gemini API](https://img.shields.io/badge/Google%20Gemini-AI%20Powered-8E75C4?style=flat&logo=google&logoColor=white)](https://ai.google.dev/)
[![JavaScript](https://img.shields.io/badge/JavaScript-ES6%2B%20AJAX-F7DF1E?style=flat&logo=javascript&logoColor=black)](https://developer.mozilla.org/)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)

> **Bookly** is an end-to-end boutique online bookstore platform engineered with pure PHP and MySQL, designed with an emphasis on **system security**, **ACID-compliant transaction processing**, and **intelligent AI assistance via Google Gemini API**.

---

## 🌟 Key Highlights & Engineering Features

### 🤖 1. Google Gemini AI Literary Concierge
- Integrated **Google Gemini API** to provide contextual, natural language book recommendations and reader assistance.
- Conversational customer guidance matching reading preferences, genres, and mood.

### 💳 2. Multi-Channel Simulated Payment Gateway
- Interactive checkout simulation supporting:
  - **Instant UPI / QR Authorization** (dynamic reference generation)
  - **Credit / Debit Card** transaction processing
  - **Demo Sandbox Wallet** & **Cash on Delivery (COD)**
- Full order lifecycle simulation: Token generation $\rightarrow$ Status handling $\rightarrow$ Real-time invoice fulfillment.

### 🔒 3. ACID-Compliant Checkout & Concurrency Control
- Employs database transactions (`$pdo->beginTransaction()`) combined with **row-level locking** (`SELECT ... FOR UPDATE`) in MySQL.
- Strictly eliminates race conditions and stock overselling during simultaneous customer purchases.
- Automatic rollback safety (`$pdo->rollBack()`) guarantees data consistency across orders, cart items, and coupon counters.

### 🛡️ 4. Enterprise-Grade Security Architecture
- **Zero SQL Injection**: 100% of database queries utilize **PDO Prepared Statements** with parameterized inputs.
- **CSRF Defense**: Cryptographically secure 32-byte session tokens (`random_bytes(32)`) validated via timing-attack resistant `hash_equals()`.
- **Password Security**: Full BCRYPT encryption (`password_hash` with adaptive cost factor).
- **XSS Sanitization**: Strict HTML entity encoding across all user-rendered inputs (`htmlspecialchars(..., ENT_QUOTES)`).

### 📖 5. Interactive Customer Experience
- **Anti-Fraud Reviews**: Review and star-rating submissions are restricted exclusively to **Verified Buyers** who have confirmed purchase orders.
- **2026 Reading Challenge**: Multi-shelf reading tracker (`Want to Read`, `Currently Reading`, `Finished`) with real-time completion progress bars.
- **Dynamic Invoicing**: Automated invoice calculation with printable invoice views (`INV-YYYY-XXXXX`).
- **Split-Screen Boutique Contact Hub**: 50/50 edge-to-edge layout with interactive Google Maps and topic-based message routing.
- **Dark Mode Design System**: Zero-flicker theme persistence via CSS variables and browser `localStorage`.

### ⚡ 6. Comprehensive Admin Suite
- Unified portal with role-based session isolation.
- Real-time catalog and inventory monitoring with inline price and stock editing.
- Automated **7-Day Order Replacement** approval and resolution management.

---

## 🛠️ Technology Stack

| Domain | Technologies Used |
|---|---|
| **Backend** | PHP 8.x (PDO, Session Isolation, RESTful AJAX endpoints) |
| **Database** | MySQL (InnoDB engine, 3NF normalization, Row-level locking) |
| **AI Integration** | Google Gemini API (LLM REST integration) |
| **Frontend** | Vanilla JavaScript (ES6+), Fetch API / AJAX, HTML5, CSS3 Custom Properties |
| **Tooling & Env** | XAMPP, Git, GitHub, VS Code |

---

## 🗄️ Database Architecture (3NF)

The schema is normalized to 3rd Normal Form (3NF) to maintain referential integrity:
- `users`: User profiles with BCRYPT authentication & role flags (`user`, `admin`).
- `books`: Catalog titles, authors, categories, pricing, stock levels, and cover images.
- `categories`: Book genre classifications.
- `cart`: User-scoped persistent shopping baskets.
- `orders` & `order_items`: Complete purchase history, shipping details, payment metadata, and item snapshots.
- `reviews`: Star ratings, feedback comments, and customer photos linked with buyer verification.
- `replacements`: 7-day post-delivery replacement requests and status pipelines.
- `user_bookshelf`: Reading challenge progress and shelf states.

---

## 🚀 Local Installation & Setup

### Prerequisites
- [XAMPP](https://www.apachefriends.org/) (PHP 8.0+ and MySQL)
- Git installed on your system

### Steps

1. **Clone the repository into your XAMPP web root:**
   ```bash
   cd C:/xampp/htdocs/
   git clone https://github.com/smitp8011/bookly-ecommerce-bookly-.git bookly
   ```

2. **Import Database:**
   - Open **phpMyAdmin** (`http://localhost/phpmyadmin`).
   - Create a new database named `bookly_db`.
   - Import the database SQL dump file located in the project.

3. **Configure Database Connection:**
   - Review or update `config.php` if your local MySQL port/credentials differ:
     ```php
     define('DB_HOST', 'localhost');
     define('DB_NAME', 'bookly_db');
     define('DB_USER', 'root');
     define('DB_PASS', ''); // Set your MySQL password
     ```

4. **Launch the Application:**
   - Start Apache and MySQL modules in the XAMPP Control Panel.
   - Open your browser and navigate to:
     ```text
     http://localhost/bookly/
     ```

---

## 👨‍💻 Author

**Smit Parmar**  
*Python & Full-Stack Developer*  
- **Email:** smitp8011@gmail.com  
- **GitHub:** [@smitp8011](https://github.com/smitp8011)  
- **Location:** Ankleshwar, Gujarat, India  

---

## 📄 License
This project is licensed under the [MIT License](LICENSE).
