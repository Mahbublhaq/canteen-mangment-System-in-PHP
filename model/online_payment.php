<?php
session_start();
include '../db/db.php';

$customer_id = $_GET['customer_id'] ?? null;
$order_details = $_GET['order_details'] ?? null;
$amount = $_GET['amount'] ?? null;

if (!$customer_id || !$order_details || !$amount) {
    echo "<div class='alert alert-danger'>Invalid payment request. Please try again.</div>";
    exit();
}

// Decode order details
$orderDetails = json_decode(urldecode($order_details), true);

// Handle payment confirmation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_payment'])) {
    $enteredAmount = $_POST['amount'];

    if ($enteredAmount != $amount) {
        echo "<div class='alert alert-danger'>The entered amount does not match the order amount.</div>";
    } else {
        // Insert order into the database after successful payment
        $stmt = $conn->prepare("INSERT INTO orders (customer_id, order_details, total_cost, payment_method) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isds", $customer_id, json_encode($orderDetails), $amount, $paymentMethod);

        $paymentMethod = 'Online';
        if ($stmt->execute()) {
            echo "<div class='alert alert-success'>Payment successful! Your order has been placed successfully. Your Order ID is: " . $stmt->insert_id . "</div>";
            unset($_SESSION['cart']); // Clear the cart after successful payment
        } else {
            echo "<div class='alert alert-danger'>Error placing order: " . $stmt->error . "</div>";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Online Payment</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h2 class="text-center">Online Payment</h2>
    <p class="text-center">Order Total: BDT <?php echo number_format($amount, 2); ?></p>

    <form method="POST" action="" class="mt-4">
        <div class="mb-3">
            <label for="amount" class="form-label">Enter Total Amount (BDT):</label>
            <input type="number" class="form-control" id="amount" name="amount" step="0.01" required>
        </div>
        <button type="submit" name="confirm_payment" class="btn btn-primary">Confirm Payment</button>
    </form>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
