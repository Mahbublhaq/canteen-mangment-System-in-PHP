<?php
session_start();
require '../db/db.php'; // Include database connection

// Fetch all products where category is "Meal" and active status is 1
$sql = "SELECT * FROM products WHERE catagorey = 'Meal' AND active = 1";
$result = $conn->query($sql);

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_to_cart'])) {
    $product_id = $_POST['product_id'];
    $product_name = $_POST['product_name'];
    $product_image = $_POST['product_image'];
    $product_details = $_POST['product_details'];
    $price = $_POST['price'];

    // Initialize cart if not set
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    // Check if product is already in the cart
    if (isset($_SESSION['cart'][$product_id])) {
        $_SESSION['cart'][$product_id]['quantity'] += 1;
    } else {
        // Add new product to cart
        $_SESSION['cart'][$product_id] = [
            'product_name' => $product_name,
            'product_image' => $product_image,
            'product_details' => $product_details,
            'price' => $price,
            'quantity' => 1
        ];
    }
    
    // Redirect back to avoid form resubmission
    header("Location: meal.php");
    exit();
}

// Handle order submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['place_order'])) {
    $product_id = $_POST['product_id']; // Get the product_id from POST
    // Check if product exists in the cart
    if (isset($_SESSION['cart'][$product_id])) {
        $meal_id = $product_id; // Use the product_id as the meal_id
        $lunch_meal = $_SESSION['cart'][$product_id]['product_name'];
        $dinner_meal = $lunch_meal; // Modify this logic if necessary
        $deposit = 100; // Replace with your logic to get deposit amount
        $meal_price = $_SESSION['cart'][$product_id]['price'];
        $remain_balance = $deposit - $meal_price;

        // Insert the order into the meal table
        $stmt = $conn->prepare("INSERT INTO meal (meal_id, lunch_meal, dinner_meal, deposit, meal_price, remain_balance, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("issdds", $meal_id, $lunch_meal, $dinner_meal, $deposit, $meal_price, $remain_balance);

        if ($stmt->execute()) {
            // Successfully inserted
            unset($_SESSION['cart']); // Clear the cart
            header("Location: success.php"); // Redirect to a success page
            exit();
        } else {
            // Handle error
            echo "Error: " . $stmt->error;
        }
    } else {
        echo "Error: Product not found in the cart.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meal Menu</title>
    <link rel="stylesheet" href="style.css">
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
                                <form method="POST" action="meal.php" style="display:inline;">
                                    <input type="hidden" name="product_id" value="<?php echo $row['id']; ?>">
                                    <input type="hidden" name="product_name" value="<?php echo $row['product_name']; ?>">
                                    <input type="hidden" name="product_details" value="<?php echo $row['product_details']; ?>">
                                    <input type="hidden" name="product_image" value="<?php echo $row['product_image']; ?>">
                                    <input type="hidden" name="price" value="<?php echo $row['price']; ?>">
                                    <button type="submit" name="add_to_cart" class="button add-to-cart">Add to Cart</button>
                                   
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="alert alert-warning">No products found.</div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

<?php
$conn->close();
?>
