<?php
/**
 * Database configuration.
 * Update these four values to match your MySQL setup.
 */
define('DB_HOST', 'localhost');
define('DB_NAME', 'bookly_db');
define('DB_USER', 'root');
define('DB_PASS', 'root');

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    die('Database connection failed. Check config.php credentials.');
}

/**
 * AI Assistant Configuration
 * Get a free Gemini API Key at https://aistudio.google.com (optional)
 * Get a Grok API Key at https://console.x.ai (optional)
 */
define('GEMINI_API_KEY', getenv('GEMINI_API_KEY') ?: 'YOUR_GEMINI_API_KEY_HERE');
define('GROK_API_KEY', getenv('GROK_API_KEY') ?: '');
define('AI_PROVIDER', 'gemini');

/**
 * Razorpay Payment Gateway Configuration (Test Mode Sandbox)
 */
define('RAZORPAY_KEY_ID', getenv('RAZORPAY_KEY_ID') ?: 'rzp_test_YOUR_KEY_ID');
define('RAZORPAY_KEY_SECRET', getenv('RAZORPAY_KEY_SECRET') ?: 'YOUR_KEY_SECRET');


