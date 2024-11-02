<?php

//session pick from login
session_start();
//check session id
if (!isset($_SESSION['user_id'])) {
    header("Location: /view/login.html");
    exit();
}
///ptint session id
// echo "Session ID: " . $_SESSION['user_id'] . "<br>";

$customer_id = $_SESSION['user_id'];

require '../db/db.php'; // Database connection

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $department = $_POST['department'];
    $deposit = $_POST['deposit'];
    $varsity_id = $_POST['varsity_id'];
    $email = $_POST['email'];
    $meal_date = $_POST['meal_date'];

    // Insert data into meal_registration table
    $sql = "INSERT INTO meal_registration (customer_id, name, department, deposit, varsity_id, email, meal_date) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issdsss", $customer_id, $name, $department, $deposit, $varsity_id, $email, $meal_date);
    
    if ($stmt->execute()) {
        echo "<div class='success'>Meal registration successful!</div>";
    } else {
        echo "<div class='error'>Error: " . $stmt->error . "</div>";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meal Registration</title>
    <style>
        body {
            background: url('https://source.unsplash.com/1600x900/?meal,food') no-repeat center center fixed;
            background-size: cover;
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            overflow: hidden;
        }
        .registration-form {
            background: rgba(255, 255, 255, 0.9);
            padding: 30px;
            border-radius: 10px;
            width: 400px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
            transition: transform 0.3s ease-in-out;
            transform: perspective(1000px) rotateY(0deg);
        }
        .registration-form:hover {
            transform: perspective(1000px) rotateY(10deg);
        }
        h2 {
            font-weight: bold;
            text-align: center;
            color: #2e86c1;
        }
        label {
            display: block;
            font-weight: bold;
            margin-top: 15px;
            color: #333;
        }
        input, select {
            width: 100%;
            padding: 12px;
            margin-top: 5px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }
        .btn {
            background-color: #2e86c1;
            color: white;
            padding: 12px;
            font-size: 18px;
            font-weight: bold;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
            margin-top: 20px;
        }
        .btn:hover {
            background-color: #1b4f72;
        }
        .success, .error {
            color: #28a745;
            font-weight: bold;
            text-align: center;
            margin-top: 15px;
        }
    </style>
</head>
<body>

<div class="registration-form">
    <h2>Meal Registration</h2>
    <form action="/model/meal_signup.php" method="POST">
        <label for="name">Full Name:</label>
        <input type="text" name="name" required>

        <label for="department">Department:</label>
        <select name="department" required>
            <option value="Computer Science">Computer Science & Engineering</option>
            <option value="Business Administration">Business Administration</option>
            <option value="Electrical Engineering">Electrical & Electronic Engineering</option>
            <option value="Civil Engineering">Civil Engineering</option>
            <option value="Pharmacy">Pharmacy</option>
            <option value="English">English</option>
            <option value="Law">Law</option>
            <option value="Others">Others</option>

            <!-- Add more departments as needed -->
        </select>


        <label for="deposit">Initial Deposit:</label>
        <input type="number" step="0.01" name="deposit" placeholder="Minimum ADD 2000 TK" required>


        <label for="varsity_id">Varsity ID:</label>
        <input type="text" name="varsity_id" required>

        <label for="email">Email:</label>
        <input type="email" name="email" required>

        <label for="meal_date">Meal Date:</label>
        <input type="date" name="meal_date" required>

        <button type="submit" class="btn">Register Meal</button>
    </form>
</div>

</body>
</html>


