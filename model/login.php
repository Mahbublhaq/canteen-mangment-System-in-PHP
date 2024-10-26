<?php
require '../db/db.php'; // Database connection

session_start(); // Start the session

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? '';

    // Check if role is provided
    if (empty($role)) {
        echo "Please select a role!";
        exit;
    }

    // Determine the SQL query based on the role selection
    if ($role === "user") {
        $sql = "SELECT Id, Name, Password FROM customer WHERE Email = ?";
    } else if ($role === "admin") {
        $sql = "SELECT Id, Name, Password FROM admin WHERE Email = ?";
    } else {
        echo "Invalid role!";
        exit;
    }

    // Prepare the statement
    if ($stmt = $conn->prepare($sql)) {
        // Bind parameters
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        // Check if the user exists
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $db_password = $row['Password'];

            // Verify the password
            if (password_verify($password, $db_password)) {
                // Login successful, set session variables
                $_SESSION['Id'] = $row['Id']; // Store the user ID in the session
                $_SESSION['Name'] = $row['Name']; // Store the user name in the session

                // Redirect to the respective dashboard
                if ($role == 'admin') {
                    header("Location: ../admin_dashboard.php");
                } else {
                    header("Location: ../view/meal_registration.html");
                }
                exit(); // Ensure no further code is executed after the redirect
            } else {
                echo "Incorrect password!";
            }
        } else {
            echo "User not found!";
        }

        $stmt->close();
    } else {
        // If query preparation fails, display error
        echo "Error preparing query: " . $conn->error;
    }

    $conn->close();
}
?>
