<?php
session_start();
require '../db/db.php';

// Error reporting for development (remove or comment out in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Initialize cart if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Update cart functionality
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_cart'])) {
    $product_id = $_POST['product_id'];
    $new_quantity = (int)$_POST['quantity'];
    
    if ($new_quantity > 0) {
        // If product already exists, update quantity
        if (isset($_SESSION['cart'][$product_id])) {
            $_SESSION['cart'][$product_id]['quantity'] = $new_quantity;
        }
    } else {
        // Remove item if quantity is 0 or less
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
        'overall' => 0, 
        'combo_total' => 0,
        'subtotal' => 0
    ];
    
    // Only process if cart is not empty
    if (!empty($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $product_id => $item) {
            $itemTotal = $item['price'] * $item['quantity'];
            $totals['subtotal'] += $itemTotal;
            $totals['overall'] += $itemTotal;

            // Check product category
            $checkCategoryStmt = $GLOBALS['conn']->prepare("SELECT catagorey FROM products WHERE id = ?");
            $checkCategoryStmt->bind_param("i", $product_id);
            $checkCategoryStmt->execute();
            $checkCategoryStmt->bind_result($category);
            $checkCategoryStmt->fetch();
            $checkCategoryStmt->close();

            // Categorize Lunch and Dinner meals
            if ($item['product_name'] === "Lunch Meal") {
                $totals['lunch'] += $itemTotal;
            } elseif ($item['product_name'] === "Dinner Meal") {
                $totals['dinner'] += $itemTotal;
            }

            // Check for Combo or Hot Offer
            if ($category === "Combo" || $category === "Hot Offer") {
                $totals['combo_total'] += $itemTotal;
            }
        }
    }
    return $totals;
}

// Calculate meal totals
$mealTotals = calculateMealTotals();
$lunchTotalPrice = $mealTotals['lunch'];
$dinnerTotalPrice = $mealTotals['dinner'];
$subtotalPrice = $mealTotals['subtotal'];
$overallTotalPrice = $mealTotals['overall'];
$comboTotal = $mealTotals['combo_total'];

// Initialize discount variables
$discountAmount = 0;
$discountMessage = '';
$netTotal = $subtotalPrice;  // Initialize net total as subtotal

// Handle coupon code application
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['apply'])) {
    // Check if discount code is provided
    if (!empty($_POST['discount_code'])) {
        $discount_code = trim($_POST['discount_code']);

        // Check if combo total meets minimum requirement
        if ($comboTotal >= 200) {
            // Prepare query to fetch discount details
            $query = "SELECT discount_amount FROM offers WHERE discount_code = ? AND expiry_date >= CURDATE()";
            $checkStmt = $conn->prepare($query);
            $checkStmt->bind_param("s", $discount_code);
            $checkStmt->execute();
            $checkStmt->bind_result($discount_amount);
            $fetchResult = $checkStmt->fetch();
            $checkStmt->close();

            if ($fetchResult && $discount_amount > 0) {
                // Ensure discount doesn't exceed subtotal
                $discountAmount = min($discount_amount, $subtotalPrice);
                $netTotal = max(0, $subtotalPrice - $discountAmount);
                $discountMessage = "Discount of BDT " . number_format($discountAmount, 2) . " applied successfully!";
                
                // Store discount details in session
                $_SESSION['applied_discount_code'] = $discount_code;
                $_SESSION['discount_amount'] = $discountAmount;
                $_SESSION['net_total'] = $netTotal;
            } else {
                $discountMessage = "Invalid discount code or expired!";
                $discountAmount = 0;
                $netTotal = $subtotalPrice;
                unset($_SESSION['applied_discount_code']);
                unset($_SESSION['discount_amount']);
                unset($_SESSION['net_total']);
            }
        } else {
            $discountMessage = "Coupon only valid for Combo/Hot Offer with a minimum spend of 200 tk!";
            $discountAmount = 0;
            $netTotal = $subtotalPrice;
            unset($_SESSION['applied_discount_code']);
            unset($_SESSION['discount_amount']);
            unset($_SESSION['net_total']);
        }
    } else {
        $discountMessage = "Please enter a valid coupon code.";
        $discountAmount = 0;
        $netTotal = $subtotalPrice;
        unset($_SESSION['applied_discount_code']);
        unset($_SESSION['discount_amount']);
        unset($_SESSION['net_total']);
    }
}

// Handle order placement
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['place_order'])) {
    // Validate customer login
    if (!isset($_SESSION['user_id'])) {
        echo "<div class='alert alert-danger'>Please login first.</div>";
        exit();
    }

    $customer_id = $_SESSION['user_id'];
    $payment_method = $_POST['payment_method'] ?? 'Cash on Delivery';

    // Prepare order details and total cost
    $orderDetails = [];
    $totalCost = 0;
    
    foreach ($_SESSION['cart'] as $item) {
        $orderDetails[] = "{$item['product_name']}*{$item['quantity']} BDT {$item['price']}";
        $totalCost += $item['price'] * $item['quantity'];
    }
    
    // Convert order details to JSON
    $orderDetailsJson = json_encode($orderDetails);

    // Determine final totals based on discount
    $finalSubtotal = $subtotalPrice;
    $finalDiscountAmount = 0;
    $finalNetTotal = $subtotalPrice;
    $finalDiscountCode = null;

    // Check if a discount was applied
    if (isset($_SESSION['applied_discount_code']) && 
        isset($_SESSION['discount_amount']) && 
        isset($_SESSION['net_total'])) {
        $finalDiscountAmount = $_SESSION['discount_amount'];
        $finalNetTotal = $_SESSION['net_total'];
        $finalDiscountCode = $_SESSION['applied_discount_code'];
    }

    // Verify customer existence
    $checkCustomerStmt = $conn->prepare("SELECT id FROM customers WHERE id = ?");
    $checkCustomerStmt->bind_param("i", $customer_id);
    $checkCustomerStmt->execute();
    $checkCustomerStmt->store_result();

    if ($checkCustomerStmt->num_rows === 0) {
        echo "<div class='alert alert-danger'>Customer does not exist. Please contact support.</div>";
        exit();
    }
    $checkCustomerStmt->close();

    // Check if meal is active
    $checkMealStmt = $conn->prepare("SELECT active FROM meal WHERE meal_id = ? AND active = 1");
    $checkMealStmt->bind_param("i", $customer_id);
    $checkMealStmt->execute();
    $checkMealStmt->bind_result($isActive);
    $checkMealStmt->fetch();
    $checkMealStmt->close();

    if (!$isActive) {
        echo "<div class='alert alert-warning'>Please Registration For Orders and Verify Details Submit </div>";
        exit();
    }

    // Calculate meal quantities
    $lunch_total_quantity = 0;
    $dinner_total_quantity = 0;

    foreach ($_SESSION['cart'] as $product_id => $item) {
        if ($item['product_name'] === "Lunch Meal") {
            $lunch_total_quantity += $item['quantity'];
        } elseif ($item['product_name'] === "Dinner Meal") {
            $dinner_total_quantity += $item['quantity'];
        }
    }

    // Calculate total order price and remaining balance
    $totalOrderPrice = $lunchTotalPrice + $dinnerTotalPrice;

    // Retrieve customer's deposit
    $checkStmt = $conn->prepare("SELECT deposit FROM meal_registration WHERE customer_id = ?");
    $checkStmt->bind_param("i", $customer_id);
    $checkStmt->execute();
    $checkStmt->bind_result($previous_balance);
    $checkStmt->fetch();
    $checkStmt->close();

    // Set default balance if not found
    if ($previous_balance === null) {
        $previous_balance = 100; // Default balance
        $insertStmt = $conn->prepare("INSERT INTO meal_registration (customer_id, deposit) VALUES (?, ?)");
        $insertStmt->bind_param("id", $customer_id, $previous_balance);
        $insertStmt->execute();
        $insertStmt->close();
    }

    // Calculate remaining balance
    $remain_balance = $previous_balance - $totalOrderPrice;

    // Check if sufficient balance
    if ($remain_balance < 0) {
        echo "<div class='alert alert-danger'>Insufficient balance. Please add more funds.</div>";
        exit();
    }

    // Insert or update lunch meals
    if ($lunch_total_quantity > 0) {
        $stmt = $conn->prepare("INSERT INTO meal (meal_id, lunch_meal, lunch_quantity, meal_price, remain_balance, created_at) 
                                VALUES (?, 1, ?, ?, ?, NOW()) 
                                ON DUPLICATE KEY UPDATE 
                                lunch_quantity = ?, meal_price = ?, remain_balance = ?");
        $stmt->bind_param("iiddidd", $customer_id, $lunch_total_quantity, $lunchTotalPrice, $remain_balance, $lunch_total_quantity, $lunchTotalPrice, $remain_balance);
        $stmt->execute();
        $stmt->close();
    }

    // Insert or update dinner meals
    if ($dinner_total_quantity > 0) {
        $stmt = $conn->prepare("INSERT INTO meal (meal_id, dinner_meal, dinner_quantity, meal_price, remain_balance, created_at) 
                                VALUES (?, 1, ?, ?, ?, NOW()) 
                                ON DUPLICATE KEY UPDATE 
                                dinner_quantity = ?, meal_price = ?, remain_balance = ?");
        $stmt->bind_param("iiddidd", $customer_id, $dinner_total_quantity, $dinnerTotalPrice, $remain_balance, $dinner_total_quantity, $dinnerTotalPrice, $remain_balance);
        $stmt->execute();
        $stmt->close();
    }

    // Update customer deposit
    $updateStmt = $conn->prepare("UPDATE meal_registration SET deposit = ? WHERE customer_id = ?");
    $updateStmt->bind_param("di", $remain_balance, $customer_id);
    $updateStmt->execute();
    $updateStmt->close();

    // Prepare order insertion
    $insertOrderStmt = $conn->prepare("INSERT INTO orders (
        customer_id, 
        order_details, 
        total_cost, 
        subtotal, 
        discount_amount, 
        net_total, 
        payment_method, 
        discount_code
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

    // Bind parameters
    $insertOrderStmt->bind_param(
        "ssddddss", 
        $customer_id, 
        $orderDetailsJson, 
        $totalCost,  
        $finalSubtotal,  
        $finalDiscountAmount, 
        $finalNetTotal,  
        $payment_method,
        $finalDiscountCode
    );

    // Execute order insertion
    if (!$insertOrderStmt->execute()) {
        echo "<div class='alert alert-danger'>Error inserting order: " . $insertOrderStmt->error . "</div>";
        exit();
    }
    $insertOrderStmt->close();

    // Clear session variables
    unset($_SESSION['cart']);
    unset($_SESSION['applied_discount_code']);
    unset($_SESSION['discount_amount']);
    unset($_SESSION['net_total']);

    // Redirect to success page
    header("Location: success.php");
    exit();
}
?>







<!-- Rest of the HTML remains the same as in the original script -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modern Shopping Cart</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2ecc71;
            --background-light: #f4f6f9;
        }

        body {
            background-color: var(--background-light);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .cart-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            padding: 30px;
            margin-top: 50px;
        }

        .cart-header {
            background: linear-gradient(135deg, var(--primary-color), #2980b9);
            color: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
        }

        .cart-item {
            transition: all 0.3s ease;
            border-bottom: 1px solid #f1f3f5;
            padding: 15px 0;
        }

        .cart-item:hover {
            background-color: #f8f9fa;
        }

        .product-image {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 8px;
        }

        .cart-summary {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 25px;
        }

        .btn-checkout {
            background: linear-gradient(135deg, var(--secondary-color), #27ae60);
            border: none;
            transition: transform 0.2s;
        }

        .btn-checkout:hover {
            transform: scale(1.05);
        }

        .quantity-control {
            max-width: 120px;
        }
    </style>
</head>
<?php include '../menu/menu.php'; ?>    
<body>
    <div class="container cart-container">
        <div class="cart-header d-flex justify-content-between align-items-center">
            <h2 class="mb-0">
                <i class="bi bi-cart-check me-2"></i>Your Shopping Cart
            </h2>
            <span class="badge bg-light text-dark">
                <?php echo count($_SESSION['cart'] ?? []); ?> Items
            </span>
        </div>

        <?php if (empty($_SESSION['cart'])): ?>
            <div class="text-center py-5">
                <i class="bi bi-cart-x display-4 text-muted mb-3"></i>
                <h3>Your cart is empty</h3>
                <p class="text-muted">Explore our menu and add some delicious meals!</p>
                <a href="welcome.php" class="btn btn-primary mt-3">
                    Continue Shopping
                </a>
            </div>
        <?php else: ?>
            <div class="row">
                <div class="col-lg-8">
                    <?php foreach ($_SESSION['cart'] as $product_id => $item): ?>
                        <div class="cart-item row align-items-center">
                            <div class="col-md-2">
                                <img src="../uploads/<?php echo $item['product_image'] ?? 'default-image.jpg'; ?>" 
                                     class="product-image" alt="Product Image">
                            </div>
                            <div class="col-md-4">
                                <h5 class="mb-1"><?php echo htmlspecialchars($item['product_name']); ?></h5>
                                <small class="text-muted"><?php echo htmlspecialchars($item['product_details'] ?? ''); ?></small>
                            </div>
                            <div class="col-md-2 text-center">
                                <span class="text-muted">BDT <?php echo number_format($item['price'], 2); ?></span>
                            </div>
                            <div class="col-md-2">
                                <form method="POST" action="cart.php" class="d-flex">
                                    <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                                    <input type="number" name="quantity" 
                                           value="<?php echo $item['quantity']; ?>" 
                                           min="1" class="form-control form-control-sm quantity-control me-2">
                                    <button type="submit" name="update_cart" 
                                            class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-arrow-repeat"></i>
                                    </button>
                                </form>
                            </div>
                            <div class="col-md-2 text-end">
                                <strong>BDT <?php echo number_format($item['price'] * $item['quantity'], 2); ?></strong>
                                <a href="cart.php?action=remove&id=<?php echo $product_id; ?>" 
                                   class="btn btn-sm btn-outline-danger ms-2">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="col-lg-4">
                    <div class="cart-summary">
                        <h4 class="border-bottom pb-2 mb-3">Order Summary</h4>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Subtotal</span>
                            <strong>BDT <?php echo number_format($subtotalPrice, 2); ?></strong>
                        </div>
                        
                        <!-- Coupon Code Section -->
                        <div class="mb-3">
                            <form method="POST" action="cart.php">
                                <label class="form-label">Coupon Code</label>
                                <div class="input-group">
                                    <input type="text" name="discount_code" class="form-control" placeholder="Enter Coupon Code">
                                    <button type="submit" name="apply" class="btn btn-primary">Apply</button>
                                </div>
                                <?php if (!empty($discountMessage)): ?>
                                    <div class="alert <?php echo $discountAmount > 0 ? 'alert-success' : 'alert-danger'; ?> mt-2">
                                        <?php echo htmlspecialchars($discountMessage); ?>
                                    </div>
                                <?php endif; ?>
                            </form>
                        </div>

                        <?php if ($discountAmount > 0): ?>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Discount</span>
                                <strong class="text-danger">- BDT <?php echo number_format($discountAmount, 2); ?></strong>
                            </div>
                        <?php endif; ?>

                        <div class="d-flex justify-content-between mb-3 pt-2 border-top">
                            <h5>Total</h5>
                            <h5>BDT <?php echo number_format($netTotal, 2); ?></h5>
                        </div>

                        <form method="POST" action="cart.php">
                            <div class="mb-3">
                                <label class="form-label">Payment Method</label>
                                <select name="payment_method" class="form-select">
                                    <option value="Cash on Delivery">Cash on Delivery</option>
                                    <option value="Online">Online Payment</option>
                                </select>
                            </div>
                            <button type="submit" name="place_order" 
                                    class="btn btn-checkout w-100">
                                <i class="bi bi-check-circle me-2"></i>Proceed to Checkout
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
<script>
    // Update quantity on input change
    document.querySelectorAll('.quantity-control').forEach(input => {
        input.addEventListener('change', function() {
            if (this.value < 1) {
                this.value = 1;
            }
        });
    });

    // Remove alert messages after 5 seconds
    setTimeout(() => {
        document.querySelectorAll('.alert').forEach(alert => {
            alert.style.display = 'none';
        });
    }, 5000);
</script>
</html>