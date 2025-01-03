




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
    $query = "SELECT 
                customer_id, 
                COUNT(*) as order_count, 
                COALESCE(SUM(total_cost), 0) as total_spent 
              FROM orders 
              GROUP BY customer_id
              ORDER BY total_spent DESC
              LIMIT 5";
    $result = mysqli_query($conn, $query) or handleDatabaseError($conn, $query);
    
    $top_customers = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $top_customers[] = $row;
        }
    }
    return $top_customers;
}

// Fixed function to get top 5 selling products
function getTopProducts($conn) {
    // First, check if JSON_TABLE is supported
    $check_json_support = mysqli_query($conn, "SELECT JSON_EXTRACT('[1]', '$[0]')");
    
    if ($check_json_support) {
        // Use JSON functions if supported
        $query = "SELECT 
                    product_name,
                    SUM(quantity) as total_quantity_sold
                FROM (
                    SELECT 
                        JSON_UNQUOTE(JSON_EXTRACT(items.item, '$.product_name')) as product_name,
                        CAST(JSON_UNQUOTE(JSON_EXTRACT(items.item, '$.quantity')) AS UNSIGNED) as quantity
                    FROM orders,
                        JSON_TABLE(
                            order_details,
                            '$[*]' COLUMNS (
                                item JSON PATH '$'
                            )
                        ) items
                ) product_sales
                GROUP BY product_name
                ORDER BY total_quantity_sold DESC
                LIMIT 5";
    } else {
        // Fallback query if JSON functions are not supported
        $query = "SELECT 
                    product_name,
                    COUNT(*) as total_quantity_sold
                FROM orders
                GROUP BY product_name
                ORDER BY total_quantity_sold DESC
                LIMIT 5";
    }
    
    $result = mysqli_query($conn, $query);
    if (!$result) {
        error_log("MySQL Error in getTopProducts: " . mysqli_error($conn));
        return [];
    }
    
    $top_products = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $top_products[] = $row;
    }
    return $top_products;
}

// Function to get recent orders
function getRecentOrders($conn, $limit = 10) {
    $query = "SELECT 
                id, 
                customer_id, 
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

// Get all required data
$today_sales = getTodaySales($conn);
$monthly_sales = getMonthlySales($conn);
$pending_orders = getPendingOrders($conn);
$daily_sales = getDailySales($conn);
$monthly_sales_graph = getMonthlySalesGraph($conn);
$top_customers = getTopCustomers($conn);
$top_products = getTopProducts($conn);
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
                        <h3 class="highlight-text">BD<?php echo number_format($today_sales['today_total_sales'], 2); ?></h3>
                        <p class="stat-detail"><?php echo $today_sales['today_order_count']; ?> Orders Today</p>
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
                        <h3 class="highlight-text">BD<?php echo number_format($monthly_sales['monthly_total_sales'], 2); ?></h3>
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
                                <tr>
                                    <th>Customer ID</th>
                                    <th>Orders</th>
                                    <th>Total Spent</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($top_customers as $customer): ?>
                                <tr>
                                    <td><span class="customer-id"><?php echo $customer['customer_id']; ?></span></td>
                                    <td><?php echo $customer['order_count']; ?></td>
                                    <td>BD<?php echo number_format($customer['total_spent'], 2); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
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
            </div>
        </div>

        <!-- Recent Orders Table -->
        <div class="dashboard-card glass-effect mb-4">
            <h2><i class="fas fa-history"></i> Recent Orders</h2>
            <div class="table-responsive custom-table">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Total</th>
                            <th>Net Total</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Payment</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($recent_orders as $order): ?>
                        <tr>
                            <td>#<?php echo $order['id']; ?></td>
                            <td><?php echo $order['customer_id']; ?></td>
                            <td>BD<?php echo number_format($order['total_cost'], 2); ?></td>
                            <td>BD<?php echo number_format($order['net_total'], 2); ?></td>
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