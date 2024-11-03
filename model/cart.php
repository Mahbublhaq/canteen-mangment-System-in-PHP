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

// Calculate total price
function calculateTotalPrice() {
    $total = 0;
    foreach ($_SESSION['cart'] as $item) {
        $total += $item['price'] * $item['quantity'];
    }
    return $total;
}
$totalPrice = calculateTotalPrice();

// Handle order placement
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['place_order'])) {
    $deposit = 1000; // Replace this with the actual deposit value from session or database
    $meal_price = calculateTotalPrice();
    $remain_balance = $deposit - $meal_price;

    // Ensure we have a valid deposit and remaining balance
    if ($remain_balance < 0) {
        echo "Insufficient balance. Please add more funds.";
        exit();
    }

    // Insert into meal table for each item in the cart
    if (!empty($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $product_id => $item) {
            $lunch_meal = $item['product_name'];
            $dinner_meal = $lunch_meal; // Modify if needed

            // Check if the meal_id exists in the customers table
            $checkStmt = $conn->prepare("SELECT id FROM customers WHERE id = ?");
            $checkStmt->bind_param("i", $product_id);
            $checkStmt->execute();
            $checkStmt->store_result();

            if ($checkStmt->num_rows > 0) {
                // Insert into meal table if meal_id exists in customers
                $stmt = $conn->prepare("INSERT INTO meal (meal_id, lunch_meal, dinner_meal, deposit, meal_price, remain_balance, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
                $stmt->bind_param("issdds", $product_id, $lunch_meal, $dinner_meal, $deposit, $meal_price, $remain_balance);

                if (!$stmt->execute()) {
                    echo "Error inserting into meal table: " . $stmt->error;
                    exit();
                }
            } else {
                echo "Meal ID $product_id does not exist in the customers table.";
            }
            $checkStmt->close();
        }
    }

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
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<div class="container mt-5">
    <h2 class="text-center">Your Cart</h2>
    
    <?php if (empty($_SESSION['cart'])): ?>
        <div class="alert alert-warning text-center" role="alert">
            Your cart is empty. <a href="meal.php" class="alert-link">Continue Shopping</a>
        </div>
    <?php else: ?>
        <table class="table table-bordered">
            <thead>
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
                            $imagePath = "../uploads/" . ($item['product_image'] ?? null); // Use null coalescing
                            $defaultImage = "../uploads/default-image.jpg"; // Path to your default image
                            $imageSrc = !empty($item['product_image']) && file_exists($imagePath) ? $imagePath : $defaultImage;
                            ?>
                            <img src="<?php echo $imageSrc; ?>" style="width: 100px;">
                        </td>
                        <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                        <td><?php echo htmlspecialchars($item['product_details']); ?></td>
                        <td><?php echo number_format($item['price'], 2); ?></td>
                        <td>
                            <form method="POST" action="cart.php" class="form-inline">
                                <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                                <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" min="1" class="form-control" style="width: 60px;">
                                <button type="submit" name="update_cart" class="btn btn-success btn-sm ml-2">Update</button>
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
        <h4 class="text-right">Total Price: BDT <?php echo number_format($totalPrice, 2); ?></h4>

        <!-- Order Button -->
        <form method="POST" action="cart.php" class="text-right">
            <button type="submit" name="place_order" class="btn btn-primary">Place Order</button>
        </form>
    <?php endif; ?>
</div>

</body>
</html>
