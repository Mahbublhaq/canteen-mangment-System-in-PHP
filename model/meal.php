<?php
session_start();
require '../db/db.php'; // Include database connection

// Fetch all products where category is "Meal" and active status is 1
$sql = "SELECT * FROM products WHERE catagorey = 'Meal' AND active = 1";
$result = $conn->query($sql);
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_to_cart'])) {
    $product_id = $_GET['id'];
    $product_name = $_POST['product_name'] ?? '';
    $product_details = $_POST['product_details'] ?? '';
    $price = $_POST['price'] ?? 0;

    // Initialize cart if not set
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    // Check if product_name and product_details are available
    if ($product_name && $product_details) {
        // Check if the product is already in the cart
        if (isset($_SESSION['cart'][$product_id])) {
            $_SESSION['cart'][$product_id]['quantity'] += 1;
        } else {
            // Add new product to cart
            $_SESSION['cart'][$product_id] = [
                'product_name' => $product_name,
                'product_details' => $product_details,
                'price' => $price,
                'quantity' => 1
            ];
        }
    }
    
    // Redirect back to meal.php to avoid resubmitting the form
    header("Location: meal.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meal Menu</title>
    <link rel="stylesheet" href="style.css"> <!-- Link to a custom CSS file if you have -->
    <style>
        /* E-commerce Style */
        body { font-family: Arial, sans-serif; background-color: #f8f9fa; margin: 0; padding: 0; }
        .container { max-width: 1200px; margin: auto; padding: 20px; }
        h1 { text-align: center; color: #333; margin-top: 20px; }
        .product-grid { display: flex; flex-wrap: wrap; gap: 20px; justify-content: center; }
        .product-card { background-color: white; border-radius: 8px; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1); width: 300px; overflow: hidden; transition: transform 0.3s; }
        .product-card:hover { transform: translateY(-10px); }
        .product-card img { width: 100%; height: 200px; object-fit: cover; }
        .product-details { padding: 15px; text-align: center; }
        .product-title { font-size: 1.2em; font-weight: bold; color: #007bff; margin: 10px 0; }
        .product-description { font-size: 0.9em; color: #666; margin-bottom: 10px; }
        .product-price { font-size: 1.1em; font-weight: bold; color: #28a745; margin-bottom: 15px; }
        .button-group { display: flex; gap: 10px; justify-content: center; }
        .button { padding: 10px 20px; border-radius: 5px; color: white; border: none; cursor: pointer; transition: background-color 0.3s; }
        .add-to-cart { background-color: #007bff; }
        .order-now { background-color: #28a745; }
        .button:hover { opacity: 0.9; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Meal Menu</h1>
        <div class="product-grid">
            <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <div class="product-card">
                        <img src="../uploads/<?php echo $row['product_image']; ?>" alt="<?php echo $row['product_name']; ?>">
                        <div class="product-details">
                            <div class="product-title"><?php echo $row['product_name']; ?></div>
                            <div class="product-description"><?php echo $row['product_details']; ?></div>
                            <div class="product-price">BDT <?php echo number_format($row['price'], 2); ?></div>
                            <div class="button-group">
                                <form method="POST" action="cart.php" style="display:inline;">
                                    <input type="hidden" name="product_id" value="<?php echo $row['id']; ?>">
                                    <button type="submit" class="button add-to-cart">Add to Cart</button>
                                </form>
                                <form method="POST" action="order.php" style="display:inline;">
                                    <input type="hidden" name="product_id" value="<?php echo $row['id']; ?>">
                                    <button type="submit" class="button order-now">Order Now</button>
                                </form>
                                
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>No meals available.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

<?php
// Close the database connection
$conn->close();
?>
