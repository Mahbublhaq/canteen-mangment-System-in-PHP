<?php
// Include database connection
include('../db/db.php'); // Adjust the path to your database connection file
require '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Fetch pending orders
$sql = "SELECT orders.id, customers.customer_name, customers.email, orders.order_details, orders.total_cost, orders.status, orders.created_at 
        FROM orders 
        JOIN customers ON orders.customer_id = customers.id 
        WHERE orders.status = 0  
        ORDER BY orders.created_at DESC";
$result = $conn->query($sql);

// Check for query errors
if (!$result) {
    die("Error fetching orders: " . $conn->error);
}

// Confirm order and send email
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'])) {
    $orderId = intval($_POST['order_id']);
    $sqlUpdate = "UPDATE orders SET status = 1 WHERE id = $orderId";
    
    if ($conn->query($sqlUpdate) === TRUE) {
        $customerEmail = $_POST['customer_email'];
        $customerName = $_POST['customer_name'];

        // Fetch the order details for the specific order
        $orderDetailsQuery = "SELECT order_details FROM orders WHERE id = $orderId";
        $orderDetailsResult = $conn->query($orderDetailsQuery);
        
        // Check for query errors
        if (!$orderDetailsResult) {
            die("Error fetching order details: " . $conn->error);
        }

        $orderDetailsRow = $orderDetailsResult->fetch_assoc();
        $orderDetailsRaw = $orderDetailsRow['order_details'];

        // Now we parse the raw order details
        $orderDetailsParsed = [];
        $items = explode(",", $orderDetailsRaw); // Assuming each item is separated by a comma

        foreach ($items as $item) {
            // Match the product name, quantity, and price
            if (preg_match('/^(.*?)\*(\d+)\s*BDT\s*([\d\.]+)/', trim($item), $matches)) {
                $orderDetailsParsed[] = [
                    'product_name' => $matches[1],
                    'quantity' => (int)$matches[2],
                    'price' => (float)$matches[3]
                ];
            }
        }

        // Format order details for email
        $orderDetailsFormatted = "";
        $totalCost = 0; // Initialize total cost for calculation

        foreach ($orderDetailsParsed as $item) {
            $price = $item['price'];
            $quantity = $item['quantity'];
            $total = $price * $quantity;  // Calculate total for the item
            $totalCost += $total;  // Add item total to overall cost

            // Format the details for email
            $orderDetailsFormatted .= "<li>" . htmlspecialchars($item['product_name']) . " - Quantity: " . $quantity . " - Price: " . number_format($price, 2) . " BDT - Total: " . number_format($total, 2) . " BDT</li>";
        }

        // Send email with order details included
        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'gourob.haq@gmail.com';
            $mail->Password = 'owtc hcch zufy cgey'; // Use App Password if 2FA is enabled
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = 465;

            // Recipients
            $mail->setFrom('city_canteen@cityuniversity.ac.bd', 'City University Canteen');
            $mail->addAddress($customerEmail, $customerName);

            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Order Confirmation - City University Canteen';
            $mail->Body    = "Hello $customerName,<br>Your order #$orderId has been confirmed.<br><br>Order Details:<br><ul>$orderDetailsFormatted</ul><br><div style='background-color: red; color: white; font-weight: bold; padding: 10px;width:10%;'>Total Cost: " . number_format($totalCost, 2) . " BDT</div><br><br>Thank you for ordering with us!<br><br>Best Regards,<br>City University Canteen Team,<br>Any queries? Contact us at: +8801601-337085,<br>Email: city_canteen@cityuniversity.ac.bd";

            // Send the email
            if ($mail->send()) {
                echo '<script>alert("Email sent successfully!"); window.location.href = window.location.href;</script>';
            } else {
                echo '<script>alert("Email sending failed. Please try again later.");</script>';
            }
        } catch (Exception $e) {
            echo "Error: Email could not be sent. {$mail->ErrorInfo}";
        }
    } else {
        echo "Error updating order: " . $conn->error;
    }
    exit;
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
            width: 60%; /* Set table width to 60% */
            background-color: white;
            border: 1px solid #ddd;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            font-size: 14px;
            margin: 0 auto; /* Center table */
        }
        table th, table td {
            padding: 10px;
            text-align: center;
            vertical-align: middle;
        }
        table th {
            background-color: #000;
            color: black;
        }
        .btn-confirm {
            background: linear-gradient(135deg, #28a745, #4bbf7f);
            color: white;
        }
        .btn-confirm:hover {
            background: #218838;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Pending Orders</h2>
    <?php if ($result->num_rows > 0): ?>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Customer Name</th>
                    <th>Customer Email</th>
                    <th>Order Details</th>
                    <th>Total Cost</th>
                    <th>Order Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $row['id']; ?></td>
                        <td><?php echo htmlspecialchars($row['customer_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                        <td>
                            <?php
                            $orderDetailsRaw = $row['order_details'];  // Get the raw order_details data

                            // Now we parse the raw order details
                            $orderDetailsParsed = [];
                            $items = explode(",", $orderDetailsRaw); // Assuming each item is separated by a comma

                            foreach ($items as $item) {
                                // Match the product name, quantity, and price
                                if (preg_match('/^(.*?)\*(\d+)\s*BDT\s*([\d\.]+)/', trim($item), $matches)) {
                                    $orderDetailsParsed[] = [
                                        'product_name' => $matches[1],
                                        'quantity' => (int)$matches[2],
                                        'price' => (float)$matches[3]
                                    ];
                                }
                            }

                            $orderDetailsFormatted = "";
                            $totalCost = 0;

                            foreach ($orderDetailsParsed as $item) {
                                $price = $item['price'];
                                $quantity = $item['quantity'];
                                $total = $price * $quantity;  // Calculate total for the item
                                $totalCost += $total;  // Add item total to overall cost

                                // Format the order details for display
                                $orderDetailsFormatted .= htmlspecialchars($item['product_name']) . " - Quantity: " . $quantity . " - Price: " . number_format($price, 2) . " BDT - Total: " . number_format($total, 2) . " BDT<br>";
                            }

                            echo $orderDetailsFormatted;
                            ?>
                        </td>
                        <td><?php echo number_format($row['total_cost'], 2); ?> BDT</td>
                        <td><?php echo date('Y-m-d H:i:s', strtotime($row['created_at'])); ?></td>
                        <td>
                            <form method="POST" action="">
                                <input type="hidden" name="order_id" value="<?php echo $row['id']; ?>">
                                <input type="hidden" name="customer_email" value="<?php echo $row['email']; ?>">
                                <input type="hidden" name="customer_name" value="<?php echo $row['customer_name']; ?>">
                                <button type="submit" class="btn btn-confirm">Confirm Order</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No pending orders found.</p>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
