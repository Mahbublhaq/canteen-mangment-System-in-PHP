<?php
include '../db/db.php'; // Database connection file
include'../menu/adminmenu.php';

// Function to get today's sales
function getTodaySales($conn) {
    $query = "SELECT 
                COALESCE(SUM(total_cost), 0) as today_total_sales, 
                COUNT(*) as today_order_count 
              FROM orders 
              WHERE DATE(created_at) = CURDATE()";
    $result = mysqli_query($conn, $query) or handleDatabaseError($conn, $query);
    return $result ? mysqli_fetch_assoc($result) : ['today_total_sales' => 0, 'today_order_count' => 0];
}

// Function to get monthly sales
function getMonthlySales($conn) {
    $query = "SELECT 
                COALESCE(SUM(total_cost), 0) as monthly_total_sales, 
                COUNT(*) as monthly_order_count 
              FROM orders 
              WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) 
              AND YEAR(created_at) = YEAR(CURRENT_DATE())";
    $result = mysqli_query($conn, $query) or handleDatabaseError($conn, $query);
    return $result ? mysqli_fetch_assoc($result) : ['monthly_total_sales' => 0, 'monthly_order_count' => 0];
}

// Function to get pending orders
function getPendingOrders($conn) {
    $query = "SELECT COUNT(*) as pending_count 
              FROM orders 
              WHERE order_status = 'pending'";
    $result = mysqli_query($conn, $query) or handleDatabaseError($conn, $query);
    return $result ? mysqli_fetch_assoc($result)['pending_count'] : 0;
}

// Function to get daily sales for last 30 days
function getDailySales($conn) {
    $query = "SELECT 
                DATE(created_at) as sale_date, 
                COALESCE(SUM(total_cost), 0) as daily_sales 
              FROM orders 
              WHERE created_at >= DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY)
              GROUP BY DATE(created_at)
              ORDER BY sale_date";
    $result = mysqli_query($conn, $query) or handleDatabaseError($conn, $query);
    
    $daily_sales = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $daily_sales[] = $row;
        }
    }
    return $daily_sales;
}

// Function to get monthly sales for last 12 months
function getMonthlySalesGraph($conn) {
    $query = "SELECT 
                YEAR(created_at) as sale_year, 
                MONTH(created_at) as sale_month, 
                COALESCE(SUM(total_cost), 0) as monthly_sales 
              FROM orders 
              WHERE created_at >= DATE_SUB(CURRENT_DATE(), INTERVAL 12 MONTH)
              GROUP BY sale_year, sale_month
              ORDER BY sale_year, sale_month";
    $result = mysqli_query($conn, $query) or handleDatabaseError($conn, $query);
    
    $monthly_sales = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $monthly_sales[] = $row;
        }
    }
    return $monthly_sales;
}

// Function to get top 5 customers
function getTopCustomers($conn) {
    // SQL query to fetch top 5 customers excluding customer_id = 0
    $query = "SELECT 
                customer_id, 
                COUNT(*) AS order_count, 
                COALESCE(SUM(total_cost), 0) AS total_spent 
              FROM orders 
              WHERE customer_id != 0  -- Exclude customer_id 0
              GROUP BY customer_id
              ORDER BY total_spent DESC
              LIMIT 5";
    
    // Execute the query and handle any errors
    $result = mysqli_query($conn, $query);
    if (!$result) {
        handleDatabaseError($conn, $query);
    }
    
    // Initialize an empty array to store top customers
    $top_customers = [];
    
    // Fetch the result and populate the top_customers array
    while ($row = mysqli_fetch_assoc($result)) {
        $top_customers[] = $row;
    }
    
    // Return the top customers array
    return $top_customers;
}

// Fixed function to get top 5 selling products
function getTopProducts($conn) {
    // Check if the server supports JSON_TABLE
    $check_json_support = mysqli_query($conn, "SELECT JSON_EXTRACT('[1]', '$[0]')");
    
    if ($check_json_support) {
        // Use JSON functions if supported
        $query = "
            SELECT 
                JSON_UNQUOTE(JSON_EXTRACT(item, '$.product_name')) AS product_name,
                SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(item, '$.quantity')) AS UNSIGNED)) AS total_quantity_sold
            FROM orders, JSON_TABLE(
                order_details,
                '$[*]' COLUMNS (
                    item JSON PATH '$'
                )
            ) AS items
            GROUP BY product_name
            ORDER BY total_quantity_sold DESC
            LIMIT 5;
        ";
    } else {
        // Fallback for servers without JSON_TABLE support
        $query = "
            SELECT 
                product_name,
                COUNT(*) AS total_quantity_sold
            FROM orders
            GROUP BY product_name
            ORDER BY total_quantity_sold DESC
            LIMIT 5;
        ";
    }
    
    // $result = mysqli_query($conn, $query);
    // if (!$result) {
    //     error_log("Error in getTopProducts: " . mysqli_error($conn));
    //     return [];
    // }
    
    // $top_products = [];
    // while ($row = mysqli_fetch_assoc($result)) {
    //     $top_products[] = $row;
    // }
    // return $top_products;
}


// Function to get recent orders
function getRecentOrders($conn, $limit = 10) {
    $query = "SELECT 
                id,
                customer_id, 
                gest_customer_id ,
                total_cost, 
                net_total, 
                order_status, 
                created_at, 
                payment_method 
              FROM orders 
              ORDER BY created_at DESC 
              LIMIT ?";
    
    $stmt = mysqli_prepare($conn, $query);
    if (!$stmt) {
        error_log("Prepare failed: " . mysqli_error($conn));
        return [];
    }
    
    mysqli_stmt_bind_param($stmt, "i", $limit);
    
    if (!mysqli_stmt_execute($stmt)) {
        error_log("Execute failed: " . mysqli_stmt_error($stmt));
        mysqli_stmt_close($stmt);
        return [];
    }
    
    $result = mysqli_stmt_get_result($stmt);
    $recent_orders = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $recent_orders[] = $row;
    }
    
    mysqli_stmt_close($stmt);
    return $recent_orders;
}


// Function to get total active products
function getTotalActiveProducts($conn) {
    $query = "SELECT COUNT(*) as total_active_products FROM products WHERE Active = 1";
    $result = mysqli_query($conn, $query);
    return $result ? mysqli_fetch_assoc($result)['total_active_products'] : 0;
}


// Function to get today's meal sales
function getTodayMealSales($conn) {
    $query = "SELECT 
                COALESCE(SUM(lunch_quantity * meal_price), 0) AS total_lunch_sales,
                COALESCE(SUM(dinner_quantity * meal_price), 0) AS total_dinner_sales,
                COALESCE(SUM(lunch_quantity + dinner_quantity) * meal_price, 0) AS total_meal_sales,
                COALESCE(SUM(lunch_quantity), 0) AS total_lunch_quantity,
                COALESCE(SUM(dinner_quantity), 0) AS total_dinner_quantity
              FROM meal
              WHERE DATE(created_at) = CURDATE() AND active = 1";
    
    $result = mysqli_query($conn, $query);
    
    if ($result) {
        return mysqli_fetch_assoc($result);
    }
    
    return [
        'total_lunch_sales' => 0,
        'total_dinner_sales' => 0,
        'total_meal_sales' => 0,
        'total_lunch_quantity' => 0,
        'total_dinner_quantity' => 0
    ];
}

// Fetch today's meal sales
$today_meal_sales = getTodayMealSales($conn);

// Safely assign values to avoid undefined variable errors
$total_meal_sales = isset($today_meal_sales['total_meal_sales']) ? number_format($today_meal_sales['total_meal_sales'], 2) : '0.00';
$total_lunch_quantity = isset($today_meal_sales['total_lunch_quantity']) ? $today_meal_sales['total_lunch_quantity'] : 0;
$total_lunch_sales = isset($today_meal_sales['total_lunch_sales']) ? number_format($today_meal_sales['total_lunch_sales'], 2) : '0.00';
$total_dinner_quantity = isset($today_meal_sales['total_dinner_quantity']) ? $today_meal_sales['total_dinner_quantity'] : 0;
$total_dinner_sales = isset($today_meal_sales['total_dinner_sales']) ? number_format($today_meal_sales['total_dinner_sales'], 2) : '0.00';

// Calculate total meal sales by adding lunch and dinner sales
$total_meal_sales_calculated = $total_lunch_sales + $total_dinner_sales;
$total_meal_sales = number_format($total_meal_sales_calculated, 2);








// Get all required data
$total_active_products = getTotalActiveProducts($conn);
$today_sales = getTodaySales($conn);
$monthly_sales = getMonthlySales($conn);
$pending_orders = getPendingOrders($conn);
$daily_sales = getDailySales($conn);
$monthly_sales_graph = getMonthlySalesGraph($conn);
$top_customers = getTopCustomers($conn);
//$top_products = getTopProducts($conn);
$recent_orders = getRecentOrders($conn);

?>





<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Home Page</title>
   
     <!-- Inside the <head> section, add Chart.js before your closing </head> tag -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- Replace the existing <main> section with this updated version -->
<main class="content">
    <div class="container-fluid">
        <!-- Dashboard Cards Row -->
        <div class="row g-4 mb-4">
        <div class="col-md-4">
                <div class="dashboard-card glass-effect">
                    <div class="card-icon-wrapper">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="card-details">
                        <h2>Today's Sales</h2>
                        <h3 class="highlight-text">BD <?php echo number_format($today_sales['today_total_sales'], 2); ?></h3>
                        <p class="stat-detail"><?php echo $today_sales['today_order_count']; ?> Orders Today</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
    <div class="dashboard-card glass-effect">
        <div class="card-icon-wrapper">
            <i class="fas fa-chart-line"></i>
        </div>
        <div class="card-details">
            <h2>Today's Meal Sales</h2>
            <h3 class="highlight-text">BD <?php echo $total_meal_sales; ?></h3>
            <p class="stat-detail">
                <?php echo $total_lunch_quantity; ?> Lunch Meals Sold - 
                BD <?php echo $total_lunch_sales; ?>
            </p>
            <p class="stat-detail">
                <?php echo $total_dinner_quantity; ?> Dinner Meals Sold - 
                BD <?php echo $total_dinner_sales; ?>
            </p>
        </div>
    </div>
</div>


            <div class="col-md-4">
                <div class="dashboard-card glass-effect">
                    <div class="card-icon-wrapper">
                        <i class="fas fa-calendar"></i>
                    </div>
                    <div class="card-details">
                        <h2>Monthly Sales</h2>
                        <h3 class="highlight-text">BD <?php echo number_format($monthly_sales['monthly_total_sales'], 2); ?></h3>
                        <p class="stat-detail"><?php echo $monthly_sales['monthly_order_count']; ?> Orders This Month</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="dashboard-card glass-effect">
                    <div class="card-icon-wrapper pulse">
                        <i class="fas fa-clipboard-list"></i>
                    </div>
                    <div class="card-details">
                        <h2>Pending Orders</h2>
                        <h3 class="highlight-text"><?php echo $pending_orders; ?></h3>
                        <p class="stat-detail">Needs Attention</p>
                    </div>
                </div>
            </div>



            <div class="col-md-4">
                <div class="dashboard-card glass-effect">
                    <div class="card-icon-wrapper pulse">
                    <i class="fab fa-product-hunt" style="color:rgb(52, 138, 2);"></i>
                    </div>
                    <div class="card-details">
                        <h2>Total Active Product's</h2>
                        <h3 class="highlight-text"><?php echo $total_active_products; ?></h3>
                        <p class="stat-detail">Needs Attention</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="row g-4 mb-4">
            <div class="col-md-6">
                <div class="chart-container glass-effect">
                    <h2>Daily Sales (Last 30 Days)</h2>
                    <div class="chart-wrapper">
                        <canvas id="dailySalesChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="chart-container glass-effect">
                    <h2>Monthly Sales (Last 12 Months)</h2>
                    <div class="chart-wrapper">
                        <canvas id="monthlySalesChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Customers and Products Row -->
        <div class="row g-4 mb-4">
            <div class="col-md-6">
                <div class="dashboard-card glass-effect">
                    <h2><i class="fas fa-users"></i> Top 5 Customers</h2>
                    <div class="table-responsive custom-table">
                        <table class="table">
                            <thead>
                                <tr >
                                    <th style="color: white;">Customer ID</th>
                                    <th style="color: white;">Orders</th>
                                    <th style="color: red;">Total Spent</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($top_customers as $customer): ?>
                                <tr>
                                    <td style="color:parpel"><span class="customer-id"><?php echo $customer['customer_id']; ?></span></td>
                                    <td><?php echo $customer['order_count']; ?></td>
                                    <td>BD <?php echo number_format($customer['total_spent'], 2); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

          

            <!-- <div class="col-md-6">
                <div class="dashboard-card glass-effect">
                    <h2><i class="fas fa-crown"></i> Top 5 Selling Products</h2>
                    <div class="table-responsive custom-table">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Units Sold</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($top_products as $product): ?>
                                <tr>
                                    <td><?php echo $product['product_name']; ?></td>
                                    <td><span class="badge bg-success"><?php echo $product['total_quantity_sold']; ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div> -->
        </div>

        <!-- Recent Orders Table -->
        <div class="dashboard-card glass-effect mb-4">
            <h2><i class="fas fa-history"></i> Recent Orders</h2>
            <div class="table-responsive custom-table">
                <table class="table">
                    <thead >
                        <tr >
                            <th style="color: white;">Order ID</th>
                            <th style="color: white;">Guest Customer</th>
                            <th style="color: white;">Customer</th>
                            <th style="color: white;">Total</th>
                            <th style="color: white;">Net Total</th>
                            <th style="color: white;">Status</th>
                            <th style="color: white;">Date</th>
                            <th style="color: white;">Payment</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($recent_orders as $order): ?>
                        <tr>
                            <td>#<?php echo $order['id']; ?></td>
                            <td><?php echo $order['gest_customer_id']; ?></td>
                            <td><?php echo $order['customer_id']; ?></td>
                            <td>BD <?php echo number_format($order['total_cost'], 2); ?></td>
                            <td>BD <?php echo number_format($order['net_total'], 2); ?></td>
                            <td><span class="badge bg-<?php echo $order['order_status'] == 'pending' ? 'warning' : 'success'; ?>"><?php echo $order['order_status']; ?></span></td>
                            <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                            <td><?php echo $order['payment_method']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<!-- Add this CSS before the closing </style> tag in your existing styles -->
<style>
    .glass-effect {
        background: rgba(30, 30, 30, 0.8);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.1);
        box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37);
    }

    .dashboard-card {
        padding: 25px;
        border-radius: 15px;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
        
    }

    .dashboard-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 12px 40px 0 rgba(0, 0, 0, 0.5);
    }

    .card-icon-wrapper {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background: var(--accent-primary);
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 15px;
    }

    .card-icon-wrapper i {
        font-size: 24px;
        color: var(--bg-primary);
    }

    .highlight-text {
        font-size: 2rem;
        font-weight: bold;
        color: var(--accent-primary);
        margin: 10px 0;
    }

    .stat-detail {
        color: var(--text-secondary);
        font-size: 0.9rem;
    }

    .chart-container {
        padding: 25px;
        border-radius: 15px;
        height: 400px;
    }

    .chart-wrapper {
        height: 300px;
    }

    .custom-table {
        margin-top: 20px;
    }

    .custom-table .table {
        margin-bottom: 0;
    }

    .custom-table thead th {
        background-color: rgba(204, 166, 251, 0.1);
        border-bottom: none;
        color: black;
        font-weight: 600;
    }

    .custom-table tbody tr {
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    .custom-table tbody tr:hover {
        background-color: rgba(204, 166, 251, 0.05);
    }

    .badge {
        padding: 8px 12px;
        border-radius: 30px;
        font-weight: normal;
    }

    .customer-id {
        background: rgba(204, 166, 251, 0.1);
        padding: 4px 8px;
        border-radius: 4px;
        color: var(--accent-primary);
    }

    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.1); }
        100% { transform: scale(1); }
    }

    .pulse {
        animation: pulse 2s infinite;
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .dashboard-card {
            margin-bottom: 20px;
        }
        
        .chart-container {
            height: 300px;
        }
        
        .chart-wrapper {
            height: 200px;
        }
    }
</style>

<!-- Add this JavaScript before the closing </body> tag -->
<script>
    // Chart.js Configurations
    Chart.defaults.color = '#b0b0b0';
    Chart.defaults.borderColor = 'rgba(255, 255, 255, 0.1)';

    // Daily Sales Chart make line chart
    const dailySalesCtx = document.getElementById('dailySalesChart').getContext('2d');
    new Chart(dailySalesCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode(array_map(function($item) {
                return date('M d', strtotime($item['sale_date']));
            }, $daily_sales)); ?>,
            datasets: [{
                label: 'Daily Sales (BD)',
                data: <?php echo json_encode(array_map(function($item) {
                    return $item['daily_sales'];
                }, $daily_sales)); ?>,
                fill: false,
                backgroundColor: 'rgba(204, 166, 251, 0.6)',
                borderColor: 'rgb(204, 166, 251)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(255, 255, 255, 0.1)'
                    }
                },
                x: {
                    grid: {
                        color: 'rgba(255, 255, 255, 0.1)'
                    }
                }
            }
        }
    });

    

    // Monthly Sales Chart
    const monthlySalesCtx = document.getElementById('monthlySalesChart').getContext('2d');
    new Chart(monthlySalesCtx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(array_map(function($item) {
                return date('M Y', strtotime($item['sale_year'] . '-' . $item['sale_month'] . '-01'));
            }, $monthly_sales_graph)); ?>,
            datasets: [{
                label: 'Monthly Sales (BD)',
                data: <?php echo json_encode(array_map(function($item) {
                    return $item['monthly_sales'];
                }, $monthly_sales_graph)); ?>,
                backgroundColor: 'rgba(204, 166, 251, 0.6)',
                borderColor: 'rgb(204, 166, 251)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(255, 255, 255, 0.1)'
                    }
                },
                x: {
                    grid: {
                        color: 'rgba(255, 255, 255, 0.1)'
                    }
                }
            }
        }
    });
</script>
</html>