<?php
// Start session and check user login status
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: /view/login.html");
    exit();
}

$customer_id = $_SESSION['user_id'];
require '../db/db.php';

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
        $message = "<div class='success'>Meal registration successful! 
                    <p class='note'>Note: Please bring your ID card copy and deposit amount to contact the Meal Manager.</p></div>";
    } else {
        $message = "<div class='error'>Error: " . $stmt->error . "</div>";
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
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            overflow-x: hidden;
        }
       
        .registration-container {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin-top: 50px;
        }
        .registration-form {
            background: rgba(255, 255, 255, 0.95);
            padding: 40px;
            border-radius: 15px;
            width: 400px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.4);
            transition: transform 0.3s;
        }
        .registration-form:hover {
            transform: scale(1.05);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.6);
        }
        h2 {
            text-align: center;
            font-weight: bold;
            color: #2e86c1;
            margin-bottom: 20px;
        }
        label {
            font-weight: bold;
            color: #333;
            margin-top: 10px;
        }
        input, select {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
      

        .bt {
    width: 100%;
    padding: 12px;
    font-size: 16px;
    font-weight: bold;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    margin-top: 20px;
    background-color: #595959; /* Default background color */
    color: white; /* Text color */
    transition: background-color 0.3s, transform 0.2s;
}

.bt:hover {
    background-color: #ff69b4; 
    color:white;/* Different color on hover (hot pink) */
    transform: scale(1.05); /* Optional zoom-in effect */
}


        .success, .error {
            width: 90%;
            margin: 20px auto;
            text-align: center;
            padding: 15px;
            font-weight: bold;
            border-radius: 5px;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
        }
        .note {
            margin-top: 10px;
            font-size: 14px;
            font-style: italic;
            color: #555;
        }
    </style>
</head>
<body>
    <!-- <div class="menu-bar">
        <div>Meal Registration System</div>
        <div>
            <a href="/menu/menu.php">Home</a>
            <a href="/menu/menu.php">About</a>
            <a href="/menu/menu.php">Contact</a>
            <a href="/logout.php">Logout</a>
        </div>
    </div> -->
<?php include '../menu/menu.php'   ?>
    <div class="registration-container">
        <div class="registration-form">
            <h2>Meal Registration</h2>
            <?php if (isset($message)) echo $message; ?>
            <form action="" method="POST">
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
                </select>

                <label for="deposit">Initial Deposit:</label>
                <input type="number" step="0.01" name="deposit" placeholder="Minimum ADD 2000 TK" required>

                <label for="varsity_id">Varsity ID:</label>
                <input type="text" name="varsity_id" required>

                <label for="email">Email:</label>
                <input type="email" name="email" required>

                <label for="meal_date">Meal Date:</label>
                <input type="date" name="meal_date" required>

                <button type="submit" class="bt">Register Meal</button>

            </form>
        </div>
    </div>
</body>
</html>
