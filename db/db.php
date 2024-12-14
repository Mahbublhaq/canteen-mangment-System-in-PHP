<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection details
$db_host = 'localhost';     // Usually 'localhost' for XAMPP
$db_username = 'root';      // Default XAMPP MySQL username
$db_password = '';          // Default XAMPP MySQL password (empty)
$db_name = 'canteen_mangment_system';  // Create this database in phpMyAdmin

// Attempt to create connection
try {
    $conn = new mysqli($db_host, $db_username, $db_password, $db_name);
    
    // Check connection
    if ($conn->connect_errno) {
        throw new Exception("Failed to connect to MySQL: " . $conn->connect_error);
    }
    
    // Set character set
    if (!$conn->set_charset("utf8mb4")) {
        throw new Exception("Error setting character set: " . $conn->error);
    }
} catch (Exception $e) {
    // Log the error and show a user-friendly message
    error_log($e->getMessage());
    die("Database connection error. Please check the logs.");
}