<?php
// Start output buffering
ob_start();

// Include database connection
require_once '../db/db.php';
include '../menu/adminmenu.php';

// Check if the user is an admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php"); // Redirect non-admins to the login page
    exit();
}

// Handle Confirm Button
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_order'])) {
    $orderId = intval($_POST['order_id']);
    $adminId = $_SESSION['user_id'];

    // Fetch admin name
    $adminQuery = "SELECT name FROM admins WHERE id = ?";
    $stmtAdmin = $conn->prepare($adminQuery);
    $stmtAdmin->bind_param("i", $adminId);
    $stmtAdmin->execute();
    $adminResult = $stmtAdmin->get_result();
    $adminRow = $adminResult->fetch_assoc();
    $adminName = $adminRow['name'];
    $stmtAdmin->close();

    // Update order status and admin details
    $updateQuery = "
        UPDATE orders 
        SET order_status = 'Confirmed', 
            admin_name = ?, 
            admin_id = ? 
        WHERE id = ?
    ";
    $stmt = $conn->prepare($updateQuery);
    $stmt->bind_param("sii", $adminName, $adminId, $orderId);

    if ($stmt->execute()) {
        echo "<script>alert('Order Confirmed Successfully');</script>";
        // Reload this page after order is confirmed
        header("Location: orders.php");
        exit();
    } else {
        echo "<script>alert('Failed to confirm order');</script>";
    }

    $stmt->close();
}

// Fetch pending orders
$query = "SELECT * FROM orders WHERE order_status = 'Pending'";
$result = $conn->query($query);

// End output buffering and flush
ob_end_flush();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pending Orders</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css">
    <style>
        body {
            font-family: 'Inter', 'Segoe UI', sans-serif;
            background-color: #f8f8f8;
        }
        .table {
            margin-left: 15%;
            width: 90%;
        }
        h1 {
            margin-top: 2%;
            margin-left: 20%;
            font-weight: 600;
        }
        .alert{
            margin-left: 20%;
        }
        h2{
            margin-left: 20%;
            color:crimson;

        }
    </style>
</head>
<body>
    <div class="container mt-1">
        <h2 class="text-center">Pending Orders</h2>

        <?php if ($result->num_rows > 0): ?>
            <table class="table table-striped table-bordered mt-4">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Customer ID</th>
                        <th>Order Details</th>
                        <th>Total Cost</th>
                        <th>Subtotal</th>
                        <th>Discount</th>
                        <th>Net Total</th>
                        <th>Created At</th>
                        <th>Payment Method</th>
                        <th>Discount Code</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()) : ?>
                        <tr>
                            <td><?= htmlspecialchars($row['id']); ?></td>
                            <td><?= htmlspecialchars($row['customer_id']); ?></td>
                            <td><?= htmlspecialchars($row['order_details']); ?></td>
                            <td><?= htmlspecialchars($row['total_cost']); ?></td>
                            <td><?= htmlspecialchars($row['subtotal']); ?></td>
                            <td><?= htmlspecialchars($row['discount_amount']); ?></td>
                            <td><?= htmlspecialchars($row['net_total']); ?></td>
                            <td><?= htmlspecialchars($row['created_at']); ?></td>
                            <td><?= htmlspecialchars($row['payment_method']); ?></td>
                            <td><?= htmlspecialchars($row['discount_code']); ?></td>
                            <td>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="order_id" value="<?= $row['id']; ?>">
                                    <button type="submit" name="confirm_order" class="btn btn-success btn-sm">Confirm</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="alert alert-info text-center mt-4">
                No Orders Available
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
