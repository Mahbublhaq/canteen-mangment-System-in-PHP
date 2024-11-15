<?php
include '../db/db.php'; // Update with your actual database connection file

// SQL query to get today's total sales
$query = "
    SELECT SUM(total_cost) AS today_sales
    FROM orders
    WHERE DATE(created_at) = CURDATE()
";

$result = $conn->query($query);
$row = $result->fetch_assoc();
$today_sales = $row['today_sales'] ?? 0.00; // Default to 0 if no sales today
$today_date = date("Y-m-d"); // Format today's date
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Today's Sales</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f9;
            margin: 0;
            padding: 0;
        }
        .sales-box {
            position: absolute;
            top: 20px;
            left: 20px;
            width: 200px;
            padding: 10px;
            background-color: #007bff;
            color: #fff;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
        .sales-amount {
            font-size: 1.2rem;
            font-weight: bold;
        }
        .sales-text {
            font-size: 0.9rem;
        }
    </style>
</head>
<body>

<div class="sales-box">
    <div class="sales-amount">BDT <?php echo number_format($today_sales, 2); ?></div>
    <div class="sales-text">Today's (<?php echo $today_date; ?>) Sales</div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>
