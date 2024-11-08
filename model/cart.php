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
        'dinner' => 0
    ];

    foreach ($_SESSION['cart'] as $item) {
        $itemTotal = $item['price'] * $item['quantity'];
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

// Handle order placement
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['place_order'])) {
    $customer_id = $_SESSION['user_id']; // Assuming user_id is stored in session

    // Fetch the current remaining balance from meal_registration, set initial deposit to 100 if null
    $checkStmt = $conn->prepare("SELECT deposit FROM meal_registration WHERE id = ?");
    $checkStmt->bind_param("i", $customer_id);
    $checkStmt->execute();
    $checkStmt->bind_result($previous_balance);
    if (!$checkStmt->fetch()) {
        // If no existing deposit is found, set it to 100
        $previous_balance = 100;
        $insertStmt = $conn->prepare("INSERT INTO meal_registration (id, deposit) VALUES (?, ?)");
        $insertStmt->bind_param("id", $customer_id, $previous_balance);
        $insertStmt->execute();
        $insertStmt->close();
    }
    $checkStmt->close();

    // Calculate new remaining balance after order
    $totalOrderPrice = $lunchTotalPrice + $dinnerTotalPrice;
    $remain_balance = $previous_balance - $totalOrderPrice;

    // Check if balance is sufficient
    if ($remain_balance < 0) {
        echo "<div class='alert alert-danger'>Insufficient balance. Please add more funds.</div>";
        exit();
    }

    // Count total quantities for Lunch Meal and Dinner Meal
    $lunch_total_quantity = 0;
    $dinner_total_quantity = 0;

    foreach ($_SESSION['cart'] as $product_id => $item) {
        if ($item['product_name'] === "Lunch Meal") {
            $lunch_total_quantity += $item['quantity'];
        } elseif ($item['product_name'] === "Dinner Meal") {
            $dinner_total_quantity += $item['quantity'];
        }
    }

    // Insert a row for Lunch Meal if any are in the cart
    if ($lunch_total_quantity > 0) {
        $stmt = $conn->prepare("INSERT INTO meal (meal_id, lunch_meal, lunch_quantity, meal_price, remain_balance, created_at) VALUES (?, 1, ?, ?, ?, NOW())");
        $stmt->bind_param("iidd", $customer_id, $lunch_total_quantity, $lunchTotalPrice, $remain_balance);

        if (!$stmt->execute()) {
            echo "<div class='alert alert-danger'>Error inserting Lunch Meal into meal table: " . $stmt->error . "</div>";
            exit();
        }
        $stmt->close();
    }

    // Insert a row for Dinner Meal if any are in the cart
    if ($dinner_total_quantity > 0) {
        $stmt = $conn->prepare("INSERT INTO meal (meal_id, dinner_meal, dinner_quantity, meal_price, remain_balance, created_at) VALUES (?, 1, ?, ?, ?, NOW())");
        $stmt->bind_param("iidd", $customer_id, $dinner_total_quantity, $dinnerTotalPrice, $remain_balance);

        if (!$stmt->execute()) {
            echo "<div class='alert alert-danger'>Error inserting Dinner Meal into meal table: " . $stmt->error . "</div>";
            exit();
        }
        $stmt->close();
    }

    // Update the deposit in meal_registration table based on the new remain_balance
    $updateStmt = $conn->prepare("UPDATE meal_registration SET deposit = ? WHERE id = ?");
    $updateStmt->bind_param("di", $remain_balance, $customer_id);

    if (!$updateStmt->execute()) {
        echo "<div class='alert alert-danger'>Error updating deposit: " . $updateStmt->error . "</div>";
        exit();
    }
    $updateStmt->close();

    // Clear cart after order
    unset($_SESSION['cart']);
    header("Location: success.php"); // Redirect to a success page
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
            <h4>Total Price: BDT <?php echo number_format($lunchTotalPrice + $dinnerTotalPrice, 2); ?></h4>
            <form method="POST" action="cart.php">
                <button type="submit" name="place_order" class="btn btn-primary">Place Order</button>
            </form>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
