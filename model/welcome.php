<?php
session_start();
include '../db/db.php';

// Fetch products by category function
function fetchProductsByCategory($conn, $category) {
    $query = "SELECT * FROM products WHERE catagorey = '$category' AND Active = 1";
    return $conn->query($query);
}

// Fetch products for Hot Offers, Combos, and Meals categories
$hotOffers = fetchProductsByCategory($conn, 'Hot Offer');
$combos = fetchProductsByCategory($conn, 'Combo');
$meals = fetchProductsByCategory($conn, 'Meal');

// Handle add to cart functionality
if (isset($_POST['add_to_cart'])) {
    $product_id = $_POST['product_id'];

    // Fetch product details from the database
    $stmt = $conn->prepare("SELECT product_name, price, product_image, product_details FROM products WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $stmt->bind_result($product_name, $price, $product_image, $product_details);
    $stmt->fetch();
    $stmt->close();

    // If the product is found, add it to the cart
    if ($product_name) {
        $product = [
            'product_name' => $product_name,
            'price' => $price,
            'product_image' => $product_image,
            'product_details' => $product_details, // added details
            'quantity' => 1
        ];

        // If the product already exists in the cart, update the quantity
        if (isset($_SESSION['cart'][$product_id])) {
            $_SESSION['cart'][$product_id]['quantity'] += 1;
        } else {
            $_SESSION['cart'][$product_id] = $product;
        }
        // Redirect to the cart page
        header("Location: welcome.php");
        exit();
    } else {
        echo "<div class='alert alert-danger'>Product not found.</div>";
    }
}

// Close the database connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Canteen Management System</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: Arial, sans-serif;
        }

        .section-title {
            text-align: center;
            font-weight: bold;
            color: #007bff;
            margin: 30px 0;
            font-size: 1.8rem;
        }

        .product-card {
            background: #ffffff;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            overflow: hidden;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            padding: 10px;
        }

        .product-card:hover {
            transform: scale(1.02);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
            border-color: #007bff;
            background-color: #f8f9fa;
        }

        .product-image {
            width: 100%;
            height: 150px;
            object-fit: cover;
            border-bottom: 1px solid #007bff;
        }

        .product-content {
            padding: 8px;
            text-align: center;
        }

        .product-title {
            color: #343a40;
            font-weight: bold;
            font-size: 1.2 rem;
            margin-bottom: 5px;
            line-height: 1.2;
            text-transform: titlecase;
        }

        .product-details {
            /* show product details proper way full details show */
            color: #666;
            
            font-size: 0.9rem;
            margin-bottom: 5px;
            line-height: 1.2;
            

        }

        .product-price {
            color: #28a745;
            font-weight: bold;
            font-size: 0.9rem;
            margin-top: 7px;
        }

        .add-to-cart-btn {
            font-size: 0.8rem;
            padding: 5px 12px;
            border-radius: 4px;
            background-color: #007bff;
            color: #fff;
            transition: all 0.3s ease;
        }

        .add-to-cart-btn:hover {
            background-color: #0056b3;
            color: #fff;
        }
    </style>
</head>
<body>

<?php include '../menu/menu.php'; ?>

<div class="container mt-5">
    <h2 class="section-title">Hot Offers</h2>
    <div class="row">
        <?php if ($hotOffers && $hotOffers->num_rows > 0): ?>
            <?php while ($row = $hotOffers->fetch_assoc()): ?>
                <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                    <div class="product-card">
                        <?php if (!empty($row['product_image']) && file_exists('../uploads/' . $row['product_image'])): ?>
                            <img src="../uploads/<?= htmlspecialchars($row['product_image']) ?>" class="product-image" alt="<?= htmlspecialchars($row['product_name']) ?>">
                        <?php else: ?>
                            <img src="https://via.placeholder.com/500" class="product-image" alt="No Image Available">
                        <?php endif; ?>
                        <div class="product-content">
                            <h5 class="product-title"><?= htmlspecialchars($row['product_name']) ?></h5>
                            <p class="product-details"><?= htmlspecialchars($row['product_details']) ?></p>
                            <p class="product-price">BDT <?= htmlspecialchars($row['price']) ?></p>
                            <form method="post" action="welcome.php">
                                <input type="hidden" name="product_id" value="<?= htmlspecialchars($row['id']) ?>">
                                <button type="submit" name="add_to_cart" class="btn add-to-cart-btn"style="font-size: 0.8rem;
            padding: 5px 12px;
            border-radius: 4px;
            background-color: #007bff;
            color: #fff;
            transition: all 0.3s ease;">Add to Cart</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p class="text-center">No Hot Offer products found.</p>
        <?php endif; ?>
    </div>

    <!-- Combo Section -->
    <h2 class="section-title">Combo</h2>
    <div class="row">
        <?php if ($combos && $combos->num_rows > 0): ?>
            <?php while ($row = $combos->fetch_assoc()): ?>
                <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                    <div class="product-card">
                        <?php if (!empty($row['product_image']) && file_exists('../uploads/' . $row['product_image'])): ?>
                            <img src="../uploads/<?= htmlspecialchars($row['product_image']) ?>" class="product-image" alt="<?= htmlspecialchars($row['product_name']) ?>">
                        <?php else: ?>
                            <img src="https://via.placeholder.com/500" class="product-image" alt="No Image Available">
                        <?php endif; ?>
                        <div class="product-content">
                            <h5 class="product-title"><?= htmlspecialchars($row['product_name']) ?></h5>
                            <p class="product-details"><?= htmlspecialchars($row['product_details']) ?></p>
                            <p class="product-price">BDT <?= htmlspecialchars($row['price']) ?></p>
                            <form method="post" action="welcome.php">
                                <input type="hidden" name="product_id" value="<?= htmlspecialchars($row['id']) ?>">
                                <button type="submit" name="add_to_cart" class="btn add-to-cart-btn"style="font-size: 0.8rem;
            padding: 5px 12px;
            border-radius: 4px;
            background-color: #007bff;
            color: #fff;
            transition: all 0.3s ease;">Add to Cart</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p class="text-center">No Combo products found.</p>
        <?php endif; ?>
    </div>

    <!-- Meal Section -->
    <h2 class="section-title">Meal</h2>
    <div class="row">
        <?php if ($meals && $meals->num_rows > 0): ?>
            <?php while ($row = $meals->fetch_assoc()): ?>
                <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                    <div class="product-card">
                        <?php if (!empty($row['product_image']) && file_exists('../uploads/' . $row['product_image'])): ?>
                            <img src="../uploads/<?= htmlspecialchars($row['product_image']) ?>" class="product-image" alt="<?= htmlspecialchars($row['product_name']) ?>">
                        <?php else: ?>
                            <img src="https://via.placeholder.com/500" class="product-image" alt="No Image Available">
                        <?php endif; ?>
                        <div class="product-content">
                            <h5 class="product-title"><?= htmlspecialchars($row['product_name']) ?></h5>
                            <p class="product-details"><?= !empty($row['product_details']) ? htmlspecialchars($row['product_details']) : 'No details available' ?></p>

                            <p class="product-price">BDT <?= htmlspecialchars($row['price']) ?></p>
                            <form method="post" action="welcome.php">
                                <input type="hidden" name="product_id" value="<?= htmlspecialchars($row['id']) ?>">
                                <button type="submit" name="add_to_cart" class="btn add-to-cart-btn"style="font-size: 0.8rem;
            padding: 5px 12px;
            border-radius: 4px;
            background-color: #007bff;
            color: #fff;
            transition: all 0.3s ease;">Add to Cart</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p class="text-center">No Meal products found.</p>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>
