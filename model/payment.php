<?php
session_start();
include('../db/db.php'); // Include your database connection file

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_id = $_SESSION['user_id'];
    $payment_method = $_POST['payment_method'];
    $payment_details = $_POST['payment_details'];
    $deposit_amount = $_POST['deposit_amount'];

    // Insert into deposit_history
    $insertHistory = "INSERT INTO deposit_history (customer_id, payment_method, payment_details, deposit_amount) 
                      VALUES ('$customer_id', '$payment_method', '$payment_details', '$deposit_amount')";
    $conn->query($insertHistory);

    // Update meal_registration table
    $updateMealReg = "UPDATE meal_registration SET deposit = deposit + '$deposit_amount' 
                      WHERE customer_id = '$customer_id'";
    $conn->query($updateMealReg);

    // Update meal table
    $updateMeal = "UPDATE meal SET deposit = deposit + '$deposit_amount', 
                   remain_balance = remain_balance + '$deposit_amount' 
                   WHERE meal_id = '$customer_id'";
    $conn->query($updateMeal);

    echo "Payment Successful!";
}
?>
