<?php
session_start();
require '../db/db.php'; // Include database connection

// Initialize cart if not set
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Calculate total price
$total_price = 0;

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Cart</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* Add your custom styles here */
        body { font-family: Arial, sans-serif; background-color: #f8f9fa; }
        .container { max-width: 800px; margin: auto; padding: 20px; }
        h1 { text-align: center; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 10px; text-align: left; border: 1px solid #ddd; }
        .button { padding: 10px 20px; background-color: #007bff; color: white; border: none; cursor: pointer; }
        .remove { background-color: #dc3545; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Your Cart</h1>
        <table>
            <thead>
                <tr>
                    <th>Product Name</th>
                    <th>Details</th>
                    <th>Price (BDT)</th>
                    <th>Quantity</th>
                    <th>Total (BDT)</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($_SESSION['cart'])): ?>
                    <?php foreach ($_SESSION['cart'] as $product_id => $product): ?>
                        <tr>
                            <td><?php echo isset($product['product_name']) ? $product['product_name'] : 'N/A'; ?></td>
                            <td><?php echo isset($product['product_details']) ? $product['product_details'] : 'N/A'; ?></td>
                            <td><?php echo isset($product['price']) ? number_format($product['price'], 2) : '0.00'; ?></td>
                            <td>
                                <form method="POST" action="update_cart.php" style="display:inline;">
                                    <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                                    <input type="number" name="quantity" value="<?php echo isset($product['quantity']) ? $product['quantity'] : 1; ?>" min="1">
                                    <button type="submit" class="button">Update</button>
                                </form>
                            </td>
                            <td>
                                <?php 
                                $total = isset($product['price']) ? ($product['price'] * $product['quantity']) : 0; 
                                echo number_format($total, 2);
                                ?>
                            </td>
                            <td>
                                <form method="POST" action="remove_cart.php" style="display:inline;">
                                    <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                                    <button type="submit" class="button remove">Remove</button>
                                </form>
                            </td>
                        </tr>
                        <?php $total_price += $total; ?>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align:center;">Your cart is empty.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        <h2>Total Price: BDT <?php echo number_format($total_price, 2); ?></h2>
        <form method="POST" action="order_now.php">
            <button type="submit" class="button">Order Now</button>
        </form>
    </div>
</body>
</html>
