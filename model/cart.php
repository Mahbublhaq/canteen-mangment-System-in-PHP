<?php
session_start();
require '../db/db.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Update cart functionality
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_cart'])) {
    $product_id = $_POST['product_id'];
    $new_quantity = (int)$_POST['quantity'];
    if ($new_quantity > 0) {
        $_SESSION['cart'][$product_id]['quantity'] = $new_quantity;
    } else {
        unset($_SESSION['cart'][$product_id]);
    }
}

// Remove item from cart
if (isset($_GET['action']) && $_GET['action'] == 'remove') {
    $product_id = $_GET['id'];
    unset($_SESSION['cart'][$product_id]);
}

// Calculate total price for Lunch and Dinner items separately
function calculateMealTotals() {
    $totals = [
        'lunch' => 0,
        'dinner' => 0,
        'overall' => 0
    ];

    foreach ($_SESSION['cart'] as $item) {
        $itemTotal = $item['price'] * $item['quantity'];
        $totals['overall'] += $itemTotal;
        if ($item['product_name'] === "Lunch Meal") {
            $totals['lunch'] += $itemTotal;
        } elseif ($item['product_name'] === "Dinner Meal") {
            $totals['dinner'] += $itemTotal;
        }
    }
    return $totals;
}

$mealTotals = calculateMealTotals();
$lunchTotalPrice = $mealTotals['lunch'];
$dinnerTotalPrice = $mealTotals['dinner'];
$overallTotalPrice = $mealTotals['overall'];

// Handle order placement
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['place_order'])) {
    $customer_id = $_SESSION['user_id'];

    // Fetch the current deposit from meal_registration
    $checkStmt = $conn->prepare("SELECT deposit FROM meal_registration WHERE id = ?");
    $checkStmt->bind_param("i", $customer_id);
    $checkStmt->execute();
    $checkStmt->bind_result($previous_balance);
    $checkStmt->fetch();
    $checkStmt->close();

    if ($previous_balance === null) {
        $previous_balance = 100;
        $insertStmt = $conn->prepare("INSERT INTO meal_registration (id, deposit) VALUES (?, ?)");
        $insertStmt->bind_param("id", $customer_id, $previous_balance);
        if (!$insertStmt->execute()) {
            echo "<div class='alert alert-danger'>Error inserting initial deposit: " . $insertStmt->error . "</div>";
            exit();
        }
        $insertStmt->close();
    }

    // Calculate new remaining balance after order
    $totalOrderPrice = $lunchTotalPrice + $dinnerTotalPrice;
    $remain_balance = $previous_balance - $totalOrderPrice;

    if ($remain_balance < 0) {
        echo "<div class='alert alert-danger'>Insufficient balance. Please add more funds.</div>";
        exit();
    }

    $lunch_total_quantity = 0;
    $dinner_total_quantity = 0;

    foreach ($_SESSION['cart'] as $product_id => $item) {
        if ($item['product_name'] === "Lunch Meal") {
            $lunch_total_quantity += $item['quantity'];
        } elseif ($item['product_name'] === "Dinner Meal") {
            $dinner_total_quantity += $item['quantity'];
        }
    }

    if ($lunch_total_quantity > 0) {
        $stmt = $conn->prepare("INSERT INTO meal (meal_id, lunch_meal, lunch_quantity, meal_price, remain_balance, created_at) VALUES (?, 1, ?, ?, ?, NOW())");
        $stmt->bind_param("iidd", $customer_id, $lunch_total_quantity, $lunchTotalPrice, $remain_balance);
        if (!$stmt->execute()) {
            echo "<div class='alert alert-danger'>Error inserting Lunch Meal into meal table: " . $stmt->error . "</div>";
            exit();
        }
        $stmt->close();
    }

    if ($dinner_total_quantity > 0) {
        $stmt = $conn->prepare("INSERT INTO meal (meal_id, dinner_meal, dinner_quantity, meal_price, remain_balance, created_at) VALUES (?, 1, ?, ?, ?, NOW())");
        $stmt->bind_param("iidd", $customer_id, $dinner_total_quantity, $dinnerTotalPrice, $remain_balance);
        if (!$stmt->execute()) {
            echo "<div class='alert alert-danger'>Error inserting Dinner Meal into meal table: " . $stmt->error . "</div>";
            exit();
        }
        $stmt->close();
    }

    $updateStmt = $conn->prepare("UPDATE meal_registration SET deposit = ? WHERE id = ?");
    $updateStmt->bind_param("di", $remain_balance, $customer_id);
    if (!$updateStmt->execute()) {
        echo "<div class='alert alert-danger'>Error updating deposit: " . $updateStmt->error . "</div>";
        exit();
    }
    $updateStmt->close();

    // Prepare order details and calculate total cost for insertion
    $orderDetails = [];
    $totalCost = 0;

    foreach ($_SESSION['cart'] as $item) {
        $orderDetails[] = [
            'product_name' => $item['product_name'],
            'quantity' => $item['quantity'],
            'price' => $item['price']
        ];
        $totalCost += $item['price'] * $item['quantity'];
    }

    $orderDetailsJson = json_encode($orderDetails);

    // Insert order into 'orders' table
    $insertOrderStmt = $conn->prepare("INSERT INTO orders (customer_id, order_details, total_cost) VALUES (?, ?, ?)");
    $insertOrderStmt->bind_param("isd", $customer_id, $orderDetailsJson, $totalCost);

    if (!$insertOrderStmt->execute()) {
        echo "<div class='alert alert-danger'>Error inserting order: " . $insertOrderStmt->error . "</div>";
        exit();
    }

    $insertOrderStmt->close();

    // Clear cart after order
    unset($_SESSION['cart']);
    header("Location: success.php");
    exit();
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Cart</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .cart-container {
            margin-top: 50px;
        }
        .table-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
        }
        .empty-cart {
            padding: 30px;
            text-align: center;
        }
    </style>
</head>
<body>

<div class="container cart-container">
    <h2 class="text-center mb-4">Your Cart</h2>
    
    <?php if (empty($_SESSION['cart'])): ?>
        <div class="alert alert-warning text-center" role="alert">
            Your cart is empty. <a href="meal.php" class="alert-link">Continue Shopping</a>
        </div>
    <?php else: ?>
        <table class="table table-hover table-bordered align-middle">
            <thead class="table-dark">
                <tr>
                    <th>Image</th>
                    <th>Product Name</th>
                    <th>Details</th>
                    <th>Price (BDT)</th>
                    <th>Quantity</th>
                    <th>Total (BDT)</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($_SESSION['cart'] as $product_id => $item): ?>
                    <tr>
                        <td>
                            <?php 
                            $imagePath = "../uploads/" . ($item['product_image'] ?? 'default-image.jpg');
                            ?>
                            <img src="<?php echo file_exists($imagePath) ? $imagePath : '../uploads/default-image.jpg'; ?>" class="table-image rounded">
                        </td>
                        <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                        <td><?php echo htmlspecialchars($item['product_details']); ?></td>
                        <td><?php echo number_format($item['price'], 2); ?></td>
                        <td>
                            <form method="POST" action="cart.php" class="form-inline d-flex">
                                <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                                <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" min="1" class="form-control w-50 me-2">
                                <button type="submit" name="update_cart" class="btn btn-success btn-sm">Update</button>
                            </form>
                        </td>
                        <td><?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                        <td>
                            <a href="cart.php?action=remove&id=<?php echo $product_id; ?>" class="btn btn-danger btn-sm">Remove</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <div class="d-flex justify-content-between align-items-center">
            <h4>Total Price: BDT <?php echo number_format($overallTotalPrice, 2); ?></h4>
            <form method="POST" action="cart.php">
                <button type="submit" name="place_order" class="btn btn-primary">Place Order</button>
            </form>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
