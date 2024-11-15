<?php
include '../db/db.php'; // Update with your actual database connection file

// SQL query to get the current month's total sales
$query = "
    SELECT SUM(total_cost) AS monthly_sales
    FROM orders
    WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())
";

$result = $conn->query($query);
$row = $result->fetch_assoc();
$monthly_sales = $row['monthly_sales'] ?? 0.00; // Default to 0 if no sales this month
$current_month = date("F Y"); // Format for the current month and year
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monthly Sales</title>
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
            width: 200px; /* Same as daily sales box */
            padding: 10px;
            background-color: #007bff;
            color: #fff;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
        .sales-amount {
            font-size: 1.2rem; /* Same as daily sales box */
            font-weight: bold;
        }
        .sales-text {
            font-size: 0.9rem; /* Same as daily sales box */
        }
    </style>
</head>
<body>

<div class="sales-box">
    <div class="sales-amount">BDT <?php echo number_format($monthly_sales, 2); ?></div>
    <div class="sales-text">Sales for <?php echo $current_month; ?></div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>
