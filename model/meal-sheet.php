<?php
session_start();
include '../db/db.php'; // Include database connection

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
        $mail->Body = 'Your balance is low. Please recharge your account.';

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
            background-color: #f4f4f4;
        }
        .container {
            margin-top: 30px;
        }
        .table {
            margin-top: 20px;
        }
        .no-results {
            color: red;
        }
        .low-balance {
            color: red;
        }
    </style>
</head>
<body>

<div class="container">
    <h2 class="text-center">Meal Sheet</h2>
    
    <!-- Search Form -->
    <div class="input-group mb-4">
        <input type="text" class="form-control" id="search-box" placeholder="Search by Customer ID or Name" onkeyup="searchTable()">
        <button class="btn btn-primary" type="button" id="search-btn">Search</button>
    </div>
    <div id="no-results-message" class="no-results" style="display: none;">No results found</div>
    
    <!-- Display Meal Sheet -->
    <table class="table table-striped" id="meal-table">
        <thead>
            <tr>
                <th>Customer ID</th>
                <th>Customer Name</th>
                <th>Phone</th>
                <th>Email</th>
                <th>Lunch Meal</th>
                <th>Dinner Meal</th>
                <th>Remaining Balance</th>
                <th>Lunch Quantity</th>
                <th>Dinner Quantity</th>
                <th>Meal Date Time</th>
                <th>Total Deposit</th>
                <th>Deposit Dates</th>
                <th>Deposit Amounts</th>
            
                <th>Email Sent</th>
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
                    <td><?= $row['lunch_quantity'] > 1 ? $row['lunch_quantity'] : '-' ?></td>
                    <td><?= $row['dinner_quantity'] > 1 ? $row['dinner_quantity'] : '-' ?></td>
                    <td><?= $row['meal_date_time'] ?></td>
                    <td><?= number_format($row['total_deposit'], 2) ?></td>
                    <td><?= $row['deposit_dates'] ?></td>
                    <td><?= $row['deposit_amounts'] ?></td>
                    
                    <td>
                        <?php if ($row['remain_balance'] < 200): ?>
                            <form method="post" action="meal-sheet.php">
                                <input type="hidden" name="email" value="<?= htmlspecialchars($row['email']) ?>">
                                <button type="submit" name="send_email" class="btn btn-warning">Send Email</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
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


        //email sending success message
        function sendEmail() {
            alert("Email sent successfully!");
        }
        //if any error occurs show heare
        function sendEmailError() {
            alert("Message could not be sent. Mailer Error: ");
        }



    }
</script>

</body>
</html>