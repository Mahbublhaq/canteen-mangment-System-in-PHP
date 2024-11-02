<?php
require '../db/db.php'; // Database connection

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $new_password = password_hash($_POST['new_password'], PASSWORD_DEFAULT); // Hashing new password
    $role = $_POST['role'] ?? '';

    // Determine the table based on selected role
    if ($role == 'customers') {
        $table = 'customers';
    } elseif ($role == 'admin') {
        $table = 'admin';
    } else {
        die("Invalid role selected.");
    }

    // Check if email and phone exist in the selected table
    $sql = "SELECT email, phone FROM $table WHERE email = ? AND phone = ?";
    $stmt = $conn->prepare($sql);

    if ($stmt === false) {
        die('Prepare failed: ' . $conn->error);  // Show the error if prepare fails
    }

    $stmt->bind_param("ss", $email, $phone);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Update password in the respective table
        $sql_update = "UPDATE $table SET Password = ? WHERE email = ? AND phone = ?";
        $stmt_update = $conn->prepare($sql_update);

        if ($stmt_update === false) {
            die('Prepare failed for update: ' . $conn->error);  // Show the error if prepare fails
        }

        $stmt_update->bind_param("sss", $new_password, $email, $phone);
        if ($stmt_update->execute()) {
            echo "Password updated successfully!";
        } else {
            echo "Error updating password.";
        }
    } else {
        echo "No account found with the provided email and phone number.";
    }

    // Close statement and connection
    $stmt->close();
    $conn->close();
}
?>
