<?php

include '../db/db.php'; // Include database connection
include'../menu/adminmenu.php';
// Include Composer autoloader (if using Composer)
require '../vendor/autoload.php'; // Adjust the path based on your project structure

// Fetch data based on customer_id for display
$customer_id = isset($_GET['customer_id']) ? $_GET['customer_id'] : '';

// Query to get all data, ordered by the latest deposit transaction date
$query = "
    SELECT c.id AS customer_id, c.customer_name, c.phone, c.email, 
           m.lunch_meal, m.dinner_meal, m.remain_balance, m.lunch_quantity, m.dinner_quantity, 
           COALESCE(SUM(d.deposit_amount), 0) AS total_deposit, 
           MAX(d.transaction_date) AS latest_deposit_date, 
           GROUP_CONCAT(DISTINCT DATE_FORMAT(d.transaction_date, '%Y-%m-%d') ORDER BY d.transaction_date DESC) AS deposit_dates, 
           GROUP_CONCAT(DISTINCT FORMAT(d.deposit_amount, 2) ORDER BY d.transaction_date DESC) AS deposit_amounts,
           m.created_at AS meal_date_time
    FROM customers c
    LEFT JOIN meal m ON c.id = m.meal_id
    LEFT JOIN deposit_history d ON c.id = d.customer_id
    WHERE c.id LIKE '%$customer_id%' OR c.customer_name LIKE '%$customer_id%'
    GROUP BY c.id, m.id
    ORDER BY latest_deposit_date DESC
";

// Execute query and fetch results
$result = mysqli_query($conn, $query);

// Check if query execution is successful
if (!$result) {
    die('Error executing query: ' . mysqli_error($conn));
}

// PHPMailer setup for sending email (example)
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (isset($_POST['send_email'])) {
    $email = $_POST['email']; // Retrieve email from POST request

    $mail = new PHPMailer(true);
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'gourob.haq@gmail.com';
        $mail->Password = 'owtc hcch zufy cgey';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = 465;

        // Recipients
        $mail->setFrom('your-email@gmail.com', 'City University Canteen');
        $mail->addAddress($email);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Low Balance Notification';
        $mail->Body = 'Your balance is low. Please recharge your account.
        <p> City University Canteen</p>
        <p>Mobile:01710000000</p>
        <p>Email:citycanteen@cityuniversity.ac.bd</p>';


        $mail->send();
        echo '<script>alert("Email sent successfully!");</script>';
    } catch (Exception $e) {
        echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meal Sheet</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color:rgb(255, 253, 253);
            font-size: 16px;
        }
        .container {
            margin-top: 10px;
            max-width: 80%;
            width: 80%;
            overflow-x: auto;
            margin-left:20%;
            font-size: 14px;
        }
        .table {
            margin-top: 10px;
        }
        .table th, .table td {
            padding: 4px 6px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 70%;
        }
        .no-results {
            color: red;
            font-size: 12px;
        }
        .low-balance {
            color: red;
        }
        .input-group {
            margin-bottom: 10px;
        }
        #search-box {
            font-size: 16px;
            margin-left:70%
        
        }
        .btn {
            padding: 4px 8px;
            font-size: 12px;
        }
        h2{
            color:crimson;
           font-weight: 600;
        }
        h1{
            margin-top:10px;
            font-weight: 600;
        }
    </style>
</head>
<body>

<div class="container">
    <h2 class="text-center" style="font-size: 30px;">Meal Sheet</h2>
    
    <!-- Search Form -->
    <div class="input-group">
        <input type="text" class="form-control" id="search-box" placeholder="Search by Customer ID or Name" onkeyup="searchTable()">
        <button class="btn btn-primary" type="button" id="search-btn">Search</button>
    </div>
    <div id="no-results-message" class="no-results" style="display: none;">No results found</div>
    
    <!-- Display Meal Sheet -->
    <div style="max-height: 500px; overflow-y: auto;">
        <table class="table table-striped table-sm" id="meal-table">
            <thead>
                <tr>
                    <th>C_ID</th>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>Email</th>
                    <th>Lunch</th>
                    <th>Dinner</th>
                    <th>Balance</th>
                    <th>Action</th>
                    <th>L Qty</th>
                    <th>D Qty</th>
                    <th>Meal Date</th>
                    <th>Deposit</th>
                    <th>D_Dates</th>
                   
                    
                </tr>
            </thead>
            <tbody>
                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                    <tr class="<?= $row['remain_balance'] < 200 ? 'low-balance' : '' ?>">
                        <td><?= htmlspecialchars($row['customer_id']) ?></td>
                        <td><?= htmlspecialchars($row['customer_name']) ?></td>
                        <td><?= htmlspecialchars($row['phone']) ?></td>
                        <td><?= htmlspecialchars($row['email']) ?></td>
                        <td><?= $row['lunch_meal'] ? 'Yes' : 'No' ?></td>
                        <td><?= $row['dinner_meal'] ? 'Yes' : 'No' ?></td>
                        <td><?= number_format($row['remain_balance'], 2) ?></td>
                        <td>
                            <?php if ($row['remain_balance'] < 200): ?>
                                <form method="post" action="meal-sheet.php">
                                    <input type="hidden" name="email" value="<?= htmlspecialchars($row['email']) ?>">
                                    <button type="submit" name="send_email" class="btn btn-warning btn-sm">Send</button>
                                </form>
                            <?php endif; ?>
                        </td>
                        <td><?= $row['lunch_quantity'] > 1 ? $row['lunch_quantity'] : '-' ?></td>
                        <td><?= $row['dinner_quantity'] > 1 ? $row['dinner_quantity'] : '-' ?></td>
                        <td><?= $row['meal_date_time'] ?></td>
                        <td><?= number_format($row['total_deposit'], 2) ?></td>
                        <td><?= $row['deposit_dates'] ?></td>
                        
                        
                       
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>

<script>
    // Search functionality
    function searchTable() {
        var input = document.getElementById('search-box');
        var filter = input.value.toLowerCase();
        var table = document.getElementById('meal-table');
        var rows = table.getElementsByTagName('tr');
        var noResultsMessage = document.getElementById('no-results-message');
        var found = false;

        for (var i = 1; i < rows.length; i++) {
            var row = rows[i];
            var cells = row.getElementsByTagName('td');
            var customerID = cells[0].textContent.toLowerCase();
            var customerName = cells[1].textContent.toLowerCase();

            if (customerID.includes(filter) || customerName.includes(filter)) {
                row.style.display = '';
                found = true;
            } else {
                row.style.display = 'none';
            }
        }

        // Show "No results" message if no rows match the search
        noResultsMessage.style.display = found ? 'none' : 'block';
    }

    //email sending success message
    function sendEmail() {
        alert("Email sent successfully!");
    }
    //if any error occurs show here
    function sendEmailError() {
        alert("Message could not be sent. Mailer Error: ");
    }
</script>

</body>
</html>