<?php
include '../db/db.php'; // Database connection file

// Initialize variables
$today_sales = 0.00;
$total_sales_today = 0.00;
$total_lunch_meals_sold = 0;
$total_dinner_meals_sold = 0;
$monthly_sales = 0.00;
$top_customers = [];
$top_products = [];

// SQL query for Today's Total Sales
$query_today_sales = "
    SELECT SUM(total_cost) AS today_sales
    FROM orders
    WHERE DATE(created_at) = CURDATE()
";
$result_today_sales = $conn->query($query_today_sales);
if ($result_today_sales && $row = $result_today_sales->fetch_assoc()) {
    $today_sales = $row['today_sales'] ?? 0.00;
}

// SQL query for Today's Meal Sales
$query_meal_sales = "
    SELECT 
        SUM(CASE WHEN lunch_meal = 1 THEN lunch_quantity * meal_price ELSE 0 END) AS lunch_sales,
        SUM(CASE WHEN dinner_meal = 1 THEN dinner_quantity * meal_price ELSE 0 END) AS dinner_sales,
        SUM(CASE WHEN lunch_meal = 1 THEN lunch_quantity ELSE 0 END) AS total_lunch_meals_sold,
        SUM(CASE WHEN dinner_meal = 1 THEN dinner_quantity ELSE 0 END) AS total_dinner_meals_sold
    FROM meal
    WHERE DATE(created_at) = CURDATE() AND active = 1
";
$result_meal_sales = $conn->query($query_meal_sales);
if ($result_meal_sales && $row = $result_meal_sales->fetch_assoc()) {
    $total_sales_today = $row['lunch_sales'] + $row['dinner_sales'];
    $total_lunch_meals_sold = $row['total_lunch_meals_sold'];
    $total_dinner_meals_sold = $row['total_dinner_meals_sold'];
}

// SQL query for Monthly Sales
$query_monthly_sales = "
    SELECT SUM(total_cost) AS monthly_sales
    FROM orders
    WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())
";
$result_monthly_sales = $conn->query($query_monthly_sales);
if ($result_monthly_sales && $row = $result_monthly_sales->fetch_assoc()) {
    $monthly_sales = $row['monthly_sales'] ?? 0.00;
}

// SQL query for Top 10 Customers
$query_top_customers = "
    SELECT customer_id, SUM(total_cost) AS total_spent
    FROM orders
    GROUP BY customer_id
    ORDER BY total_spent DESC
    LIMIT 10
";
$result_top_customers = $conn->query($query_top_customers);
if ($result_top_customers) {
    while ($row = $result_top_customers->fetch_assoc()) {
        $top_customers[] = [
            'customer_id' => $row['customer_id'],
            'total_spent' => $row['total_spent']
        ];
    }
}

// SQL query for Top 5 Selling Products
$query_top_products = "
    SELECT order_details
    FROM orders
";
$result_top_products = $conn->query($query_top_products);
$product_sales = [];

if ($result_top_products) {
    while ($row = $result_top_products->fetch_assoc()) {
        $orderDetailsRaw = $row['order_details'];
        $items = explode(",", $orderDetailsRaw); // Assuming each item is separated by a comma

        foreach ($items as $item) {
            // Match product name and quantity using regex
            if (preg_match('/^(.*?)\*(\d+)\s*BDT\s*([\d\.]+)/', trim($item), $matches)) {
                $product_name = $matches[1];
                $quantity = (int)$matches[2];
                
                if (!isset($product_sales[$product_name])) {
                    $product_sales[$product_name] = 0;
                }
                $product_sales[$product_name] += $quantity;
            }
        }
    }
}

arsort($product_sales); // Sort by quantity in descending order
$top_products = array_slice($product_sales, 0, 5, true);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Sales Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Arial', sans-serif;
            padding: 20px;
        }
        .sales-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 10px;
            margin-left:10%;
          

        }
        .sales-card {
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
            padding: 10px;
            border-radius: 12px;
            text-align: center;
            transition: transform 0.3s ease, box-shadow 0.3s ease, background-color 0.3s ease;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        }
        .sales-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.3);
            background-color: #0056b3;
        }
        .sales-title {
            font-size: 1rem;
            font-weight: bold;
            margin-bottom: 6px;
        }
        .sales-amount {
            font-size: 1.2rem;
            font-weight: bold;
            margin-bottom: 6px;
        }
        .sales-info {
            font-size: 0.8rem;
            margin-bottom: 6px;
        }
        .sub-sales-info {
            font-size: 0.8rem;
            margin-top: 5px;
            text-align: left;
            padding: 0 5px;
        }
    </style>
</head>
<body>

<div class="sales-container">
    <!-- Today's Meal Sales -->
    <div class="sales-card">
        <div class="sales-title">Today's Meal Sales</div>
        <div class="sales-amount">BDT <?php echo number_format($total_sales_today, 2); ?></div>
        <div class="sales-info">Total meal sales for today</div>
        <div class="sub-sales-info">
            <strong>Lunch Meals Sold:</strong> <?php echo $total_lunch_meals_sold; ?><br>
            <strong>Dinner Meals Sold:</strong> <?php echo $total_dinner_meals_sold; ?>
        </div>
    </div>

    <!-- Today's Total Sales -->
    <div class="sales-card">
        <div class="sales-title">Today's Total Sales</div>
        <div class="sales-amount">BDT <?php echo number_format($today_sales, 2); ?></div>
        <div class="sales-info">Total sales for today</div>
    </div>

    <!-- Monthly Sales -->
    <div class="sales-card">
        <div class="sales-title">Monthly Sales</div>
        <div class="sales-amount">BDT <?php echo number_format($monthly_sales, 2); ?></div>
        <div class="sales-info">Sales this month</div>
    </div>

    <!-- Top 3 Customers -->
    <div class="sales-card">
        <div class="sales-title">Top 3 Customers</div>
        <ul>
            <?php 
            $top_3_customers = array_slice($top_customers, 0, 3);
            foreach ($top_3_customers as $customer) : ?>
                <li>Customer <?php echo $customer['customer_id']; ?>: BDT <?php echo number_format($customer['total_spent'], 2); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>

    <!-- Top 5 Selling Products -->
    <div class="sales-card">
        <div class="sales-title">Top 5 Selling Products</div>
        <ul>
            <?php foreach ($top_products as $product_name => $quantity) : ?>
                <li><?php echo htmlspecialchars($product_name); ?>: <?php echo $quantity; ?> sold</li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>

</body>
</html>

