<?php
/**
 * Database connection (PDO)
 * Update these four values to match your local XAMPP MySQL setup.
 */
$DB_HOST = 'localhost';
$DB_NAME = 'ceit_complaints';
$DB_USER = 'root';
$DB_PASS = '';

try {
    $pdo = new PDO(
        "mysql:host={$DB_HOST};dbname={$DB_NAME};charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    die('<div style="font-family:sans-serif;padding:40px;max-width:600px;margin:60px auto;background:#fff3f3;border:1px solid #f5c2c2;border-radius:8px;">
        <h2 style="color:#b02a2a;margin-top:0;">Database connection failed</h2>
        <p>Could not connect to MySQL. Please check:</p>
        <ul>
            <li>XAMPP\'s Apache and MySQL modules are running</li>
            <li>The database <code>ceit_complaints</code> has been imported (see schema.sql)</li>
            <li>The credentials in <code>config/db.php</code> match your MySQL setup</li>
        </ul>
        <p style="color:#888;font-size:13px;">Technical detail: ' . htmlspecialchars($e->getMessage()) . '</p>
    </div>');
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
