<?php
// Include your database connection file here
include('../db/db.php');

// Fetch orders with status 'Pending'
$sql = "SELECT orders.id, customers.customer_name, customers.email, orders.order_details, orders.total_cost, orders.status, orders.created_at 
        FROM orders 
        JOIN customers ON orders.customer_id = customers.id 
        WHERE orders.status = 'Pending'
        ORDER BY orders.created_at DESC";
$result = $conn->query($sql);

// Check if query execution was successful
if (!$result) {
    die("Error executing query: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pending Orders</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
      <style>
    
      body {
    background-color: #f8f9fa;
    font-family: 'Arial', sans-serif;
}
.container {
    margin-top: 30px;
}
h2 {
    background-color: #000;
    color: white;
    text-align: center;
    margin-bottom: 20px;
    font-size: 1.5rem;
    padding: 10px;
}
table {
    width: 80%; /* Set a percentage width */
    max-width: 1000px; /* Ensure it doesn't stretch too much */
    margin: 0 auto 20px auto; /* Center the table */
    background-color: white;
    border: 1px solid #ddd;
    border-radius: 5px;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    font-size: 12px; /* Adjust font size */
    table-layout: fixed; /* Ensure fixed column width */
}
table th, table td {
    padding: 8px; /* Adjust padding for compact design */
    text-align: center;
    vertical-align: middle;
    word-wrap: break-word; /* Prevent text overflow */
}
table th {
    background-color: black;
    color:black;
    font-size: 1rem;
}
table td {
    font-size: 12px; /* Adjust text size for smaller design */
}
.order-details {
    font-size: 0.8rem;
    padding: 0;
}
.btn-confirm, .btn-danger {
    border-radius: 5px;
    font-size: 0.8rem;
    transition: background-color 0.3s ease;
}
.btn-confirm {
    background: linear-gradient(135deg, #28a745, #4bbf7f);
    color: white;
}
.btn-confirm:hover {
    background: #218838;
}
.btn-danger {
    background: linear-gradient(135deg, #dc3545, #ff6f61);
    color: white;
}
.btn-danger:hover {
    background: #c82333;
}
ul {
    list-style-type: none;
    padding-left: 0;
}
li {
    padding: 3px 0;
}


</style>

</head>
<body>

<div class="container">
    <h2>Pending Orders</h2>

    <?php if ($result->num_rows > 0): ?>
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Customer Name</th>
                    <th>Customer Email</th>
                    <th>Order Details</th>
                    <th>Total Cost</th>
                    <th>Status</th>
                    <th>Order Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $row['id']; ?></td>
                        <td><?php echo $row['customer_name']; ?></td>
                        <td><?php echo $row['email']; ?></td>
                        <td>
                            <?php
                            // If it's already an array or string that doesn't require decoding
                            if (is_string($row['order_details'])) {
                                echo "<p>" . htmlspecialchars($row['order_details']) . "</p>";
                            } else {
                                // Decode JSON only if it's a valid JSON string
                                $orderDetails = json_decode($row['order_details'], true);
                                if (json_last_error() === JSON_ERROR_NONE && is_array($orderDetails)) {
                                    echo "<ul class='order-details'>";
                                    foreach ($orderDetails as $item) {
                                        echo "<li><strong>" . htmlspecialchars($item['item_name']) . "</strong> - Quantity: " . $item['quantity'] . " - Price: " . $item['price'] . "</li>";
                                    }
                                    echo "</ul>";
                                } else {
                                    echo "<p>No details available or invalid data format.</p>";
                                }
                            }
                            ?>
                        </td>
                        <td><?php echo number_format($row['total_cost'], 2); ?></td>
                        <td><?php echo ucfirst($row['status']); ?></td>
                        <td><?php echo $row['created_at']; ?></td>
                        <td>
                            <!-- Confirm order button -->
                            <button class="btn btn-confirm" onclick="confirmOrder(<?php echo $row['id']; ?>)">Confirm</button>
                            <!-- Cancel order button -->
                            <button class="btn btn-danger" onclick="cancelOrder(<?php echo $row['id']; ?>)">Cancel</button>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No pending orders found.</p>
    <?php endif; ?>
</div>

<!-- JavaScript for handling actions -->
<script>
    function confirmOrder(orderId) {
        if (confirm('Are you sure you want to confirm this order?')) {
            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'update_order_status.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onload = function() {
                if (xhr.status === 200) {
                    alert('Order confirmed successfully!');
                    location.reload();
                } else {
                    alert('Error confirming the order.');
                }
            };
            xhr.send('order_id=' + orderId + '&status=Confirmed');
        }
    }

    function cancelOrder(orderId) {
        if (confirm('Are you sure you want to cancel this order?')) {
            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'update_order_status.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onload = function() {
                if (xhr.status === 200) {
                    alert('Order canceled successfully!');
                    location.reload();
                } else {
                    alert('Error canceling the order.');
                }
            };
            xhr.send('order_id=' + orderId + '&status=Canceled');
        }
    }
</script>

</body>
</html>

<?php
// Close the database connection
$conn->close();
?>
