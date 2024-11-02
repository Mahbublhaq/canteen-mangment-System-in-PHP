<?php

session_start();


include '../db/db.php'; // Make sure to include your database connection file

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $role = trim($_POST['role']);

    // Debug: Output the received role
    echo "Received Role: " . $role . "<br>";

    // Basic validation
    if (empty($email) || empty($password) || empty($role)) {
        echo "All fields are required.";
        exit;
    }

    // Prepare the SQL statement based on the role
    if ($role === 'customer') {
        $stmt = $conn->prepare("SELECT * FROM customers WHERE email = ?");
    } elseif ($role === 'admin') {
        $stmt = $conn->prepare("SELECT * FROM admins WHERE email = ?");
    } else {
        echo "Invalid role specified.";
        exit;
    }

    // Bind the parameters and execute
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    // Check if user exists
    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();

        // Debug: Output stored hash
        echo "Stored Hash: " . $user['password'] . "<br>";

        // Verify password
        if (password_verify($password, $user['password'])) {
            // Password is correct
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];

            // Redirect to the user dashboard or admin panel based on role
            if ($role == 'admin') {
                header("Location: /admin/dashboard.php");
            } else {
                header("Location: /model/meal_signup.php");
            }
            exit();
        } else {
            echo "Invalid password.";
        }
    } else {
        echo "No user found with this email.";
    }

    // Close the statement and connection
    $stmt->close();
    $conn->close();
}
?>
