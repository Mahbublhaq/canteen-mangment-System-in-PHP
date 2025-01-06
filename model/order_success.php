<?php
// Include database connection and admin menu
require_once '../db/db.php';
require_once '../menu/adminmenu.php';

class OrdersView {
    private $conn;

    public function __construct($database_connection) {
        $this->conn = $database_connection;
    }

    // Improved method to get orders with robust filtering// Improved method to get orders with robust filtering
    public function getAllOrders($filter = 'all', $start_date = null, $end_date = null) {
        $query = "SELECT 
                    o.id, 
                    o.customer_id, 
                    c.customer_name AS regular_customer_name, 
                    c.phone AS regular_customer_phone,
                    o.gest_customer_id, 
                    gc.customer_name AS guest_customer_name, 
                    gc.phone_number AS guest_customer_phone,
                    o.order_details, 
                    o.total_cost, 
                    o.subtotal, 
                    o.discount_amount, 
                    o.net_total, 
                    o.created_at, 
                    o.payment_method, 
                    o.discount_code, 
                    o.order_status, 
                    o.admin_name, 
                    o.admin_id
                  FROM orders o
                  LEFT JOIN customers c ON o.customer_id = c.id
                  LEFT JOIN guest_customer gc ON o.gest_customer_id = gc.id
                  WHERE 1=1";
    
        // Filtering logic
        switch ($filter) {
            case '30_days':
                $query .= " AND o.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
                break;
            case 'today':
                $query .= " AND DATE(o.created_at) = CURDATE()";
                break;
            case 'custom':
                if ($start_date && $end_date) {
                    $query .= " AND DATE(o.created_at) BETWEEN '$start_date' AND '$end_date'";
                }
                break;
        }
    
        $query .= " ORDER BY o.created_at DESC";
    
        $result = $this->conn->query($query);
    
        $orders = [];
        $total_earn = 0;
    
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $orders[] = $row;
                $total_earn += $row['net_total'];
            }
        }
    
        return [
            'orders' => $orders,
            'total_earn' => $total_earn
        ];
    }
    
}
// Handle the page
class OrdersViewController {
    private $ordersView;

    public function __construct($database_connection) {
        $this->ordersView = new OrdersView($database_connection);
    }

    public function renderOrdersView() {
        // Sanitize input
        $filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
        $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : null;
        $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : null;

        // Get orders based on filter
        $ordersData = $this->ordersView->getAllOrders($filter, $start_date, $end_date);
        $totalEarn = $ordersData['total_earn'];
        $orders = $ordersData['orders'];
        ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Orders Management</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }
        .container {
            margin-left: 20%;
            margin-right: 10px;
            padding: 20px;
        }
        .filter-section {
            background-color: #800000;
            padding: 15px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            width: 90%;
        }
        .filter-form {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .filter-form label {
            color: white;
        }
        .filter-form select {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .filter-form input[type="date"] {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .filter-form input[type="submit"] {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background-color:rgb(237, 244, 240);
            color:crimson;
            cursor: pointer;
            margin-left: 30px;
            font-weight: 600;
        }

        .filter-form input[type="submit"]:hover {
            background-color:rgb(20, 192, 100);
            color: white;
        }
        
        .total-earnings {
            background-color:rgb(234, 20, 106);
            padding: 10px;
            color: white;
            width: 40%;
            border-radius: 5px;
            margin-bottom: 15px;
            text-align: left;
        }
        table {
            width: 90%;
            border-collapse: collapse;
            background-color: #fff;
            color:black;
            box-shadow: 0 5px 8px rgba(238, 85, 85, 0.1);
        }
        th, td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }
        th {
            background-color:rgb(0, 0, 0);
            color: white;
            font-weight: bold;
        }
        tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        tr:hover {
            background-color: #e9ecef;
        }
        .filter-form select, 
        .filter-form input[type="date"],
        .filter-form input[type="submit"] {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        h1{
            color:black;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="filter-section">
            <form method="GET" class="filter-form">
                <select name="filter">
                    <option value="all" <?= $filter === 'all' ? 'selected' : '' ?>>All Orders</option>
                    <option value="30_days" <?= $filter === '30_days' ? 'selected' : '' ?>>Last 30 Days</option>
                    <option value="today" <?= $filter === 'today' ? 'selected' : '' ?>>Today's Orders</option>
                    <option value="custom" <?= $filter === 'custom' ? 'selected' : '' ?>>Custom Date Range</option>
                </select>
                
                <label>From Date: 
                    <input type="date" name="start_date" value="<?= htmlspecialchars($start_date ?? '') ?>">
                </label>
                <label>To Date: 
                    <input type="date" name="end_date" value="<?= htmlspecialchars($end_date ?? '') ?>">
                </label>
                
                <input type="submit" value="Apply Filter">
            </form>
        </div>

        <div class="total-earnings">
            <h2>Total Earnings:       BDT <?= number_format($totalEarn, 2) ?></h2>
        </div>
        
        <table>
    <thead>
        <tr>
            <th>Order ID</th>
            <th>Customer Name</th>
            <th>Customer Phone</th>
            <th>Total Cost</th>
            <th>Discount</th>
            <th>Net Total</th>
            <th>Order Date</th>
            <th>Payment Method</th>
            <th>Order Status</th>
            <th>Ap_Admin ID</th>
            <th>Ap_By</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($orders)): ?>
            <tr>
                <td colspan="11" style="text-align: center;">No orders found</td>
            </tr>
        <?php else: ?>
            <?php foreach ($orders as $order): ?>
            <tr>
                <td><?= htmlspecialchars($order['id']) ?></td>
                <td>
                    <?php if (!empty($order['guest_customer_name'])): ?>
                        <span style="color: red;">G</span> 
                        <?= htmlspecialchars($order['guest_customer_name']) ?>
                    <?php else: ?>
                        <?= htmlspecialchars($order['regular_customer_name'] ?? 'N/A') ?>
                    <?php endif; ?>
                </td>
                <td>
                    <?= htmlspecialchars($order['guest_customer_phone'] ?? $order['regular_customer_phone'] ?? 'N/A') ?>
                </td>
                <td>BDT <?= number_format($order['total_cost'], 2) ?></td>
                <td>BDT <?= number_format($order['discount_amount'], 2) ?></td>
                <td>BDT <?= number_format($order['net_total'], 2) ?></td>
                <td><?= htmlspecialchars($order['created_at']) ?></td>
                <td><?= htmlspecialchars($order['payment_method']) ?></td>
                <td><?= htmlspecialchars($order['order_status']) ?></td>
                <td><?= htmlspecialchars($order['admin_id']) ?></td>
                <td><?= htmlspecialchars($order['admin_name']) ?></td>
            </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>


    </div>
</body>
</html>
        <?php
    }
}

// Run the application
try {
    // Assuming $conn is the database connection from db.php
    $ordersViewController = new OrdersViewController($conn);
    $ordersViewController->renderOrdersView();
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>