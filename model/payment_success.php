<?php
// payment_success.php

// Start the session
session_start();

// Clear only cart-related session data
if (isset($_SESSION['cart'])) {
    unset($_SESSION['cart']); // Remove the cart session variable
}

// Redirect to the welcome.php page after 3 seconds to show the success message
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Successful</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
            text-align: center;
            padding: 50px;
        }
        .message {
            font-size: 24px;
            color: #28a745;
            margin-bottom: 20px;
        }
        .button {
            display: inline-block;
            padding: 10px 20px;
            font-size: 18px;
            color: #fff;
            background-color: #007bff;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            cursor: pointer;
        }
        .button:hover {
            background-color: #0056b3;
        }
    </style>
    <meta http-equiv="refresh" content="3;url=welcome.php"> <!-- Redirect after 3 seconds -->
</head>
<body>
    <div class="message">Payment Successful!</div>
    <a href="welcome.php" class="button">Go Back</a>
</body>
</html>
