<?php
/**
 * config.php - Database connection using PDO.
 * PDO (not deprecated mysql_ functions) with exceptions and real prepared statements.
 * Change DB_USER / DB_PASS if your MySQL differs from the XAMPP default.
 */
const DB_HOST = 'localhost';
const DB_NAME = 'farmtrack_db';
const DB_USER = 'root';
const DB_PASS = '';

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,   // true server-side prepared statements
        ]
    );
} catch (PDOException $e) {
    error_log($e->getMessage());                      // log detail, never show it to users
    http_response_code(500);
    exit('Database connection failed. Start MySQL in XAMPP and import sql/farmtrack_db.sql.');
}
