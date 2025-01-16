<?php
session_start();
require '../db/db.php';
require '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Update cart functionality
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_cart'])) {
    $product_id = $_POST['product_id'];
    $new_quantity = (int)$_POST['quantity'];
    
    if ($new_quantity > 0) {
        if (isset($_SESSION['cart'][$product_id])) {
            $_SESSION['cart'][$product_id]['quantity'] = $new_quantity;
        }
    } else {
        unset($_SESSION['cart'][$product_id]);
    }
}

// Remove item from cart
if (isset($_GET['action']) && $_GET['action'] == 'remove') {
    $product_id = $_GET['id'];
    unset($_SESSION['cart'][$product_id]);
}

// Calculate total price for different product types
function calculateMealTotals() {
    $totals = [
        'lunch' => 0,
        'dinner' => 0,
        'combo' => 0,
        'hot_offer' => 0,
        'overall' => 0,
        'subtotal' => 0
    ];
    
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

            // Categorize products
            switch($item['product_name']) {
                case "Lunch Meal":
                    $totals['lunch'] += $itemTotal;
                    break;
                case "Dinner Meal":
                    $totals['dinner'] += $itemTotal;
                    break;
            }

            // Add Combo and Hot Offer totals separately
            if ($category === "Combo") {
                $totals['combo'] += $itemTotal;
            } elseif ($category === "Hot Offer") {
                $totals['hot_offer'] += $itemTotal;
            }
        }
    }
    return $totals;
}

// Calculate all totals
$mealTotals = calculateMealTotals();
$lunchTotalPrice = $mealTotals['lunch'];
$dinnerTotalPrice = $mealTotals['dinner'];
$comboTotal = $mealTotals['combo'];
$hotOfferTotal = $mealTotals['hot_offer'];
$subtotalPrice = $mealTotals['subtotal'];
$specialItemsTotal = $comboTotal + $hotOfferTotal;

// Initialize discount variables
$discountAmount = 0;
$discountMessage = '';
$netTotal = $subtotalPrice;

// Handle coupon code application
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['apply'])) {
    if (!empty($_POST['discount_code'])) {
        $discount_code = trim($_POST['discount_code']);
        
        if ($specialItemsTotal >= 200) {
            $query = "SELECT discount_amount FROM offers WHERE discount_code = ? AND expiry_date >= CURDATE()";
            $checkStmt = $conn->prepare($query);
            $checkStmt->bind_param("s", $discount_code);
            $checkStmt->execute();
            $checkStmt->bind_result($discount_amount);
            $fetchResult = $checkStmt->fetch();
            $checkStmt->close();

            if ($fetchResult && $discount_amount > 0) {
                $discountAmount = min($discount_amount, $subtotalPrice);
                $netTotal = max(0, $subtotalPrice - $discountAmount);
                $discountMessage = "Discount of BDT " . number_format($discountAmount, 2) . " applied successfully!";
                $_SESSION['applied_discount_code'] = $discount_code;
                $_SESSION['discount_amount'] = $discountAmount;
                $_SESSION['net_total'] = $netTotal;
            } else {
                $discountMessage = "Invalid discount code or expired!";
                unset($_SESSION['applied_discount_code'], $_SESSION['discount_amount'], $_SESSION['net_total']);
            }
        } else {
            $discountMessage = "Coupon only valid for Combo/Hot Offer with a minimum spend of 200 tk!";
            unset($_SESSION['applied_discount_code'], $_SESSION['discount_amount'], $_SESSION['net_total']);
        }
    }
}

// Handle order placement
// Handle order placement
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['place_order'])) {
    if (!isset($_SESSION['user_id'])) {
        echo "<div class='alert alert-danger'>Please login first.</div>";
        exit();
    }

    $customer_id = $_SESSION['user_id'];
    $payment_method = $_POST['payment_method'] ?? 'Cash on Delivery';

    // Prepare order details
    $orderDetails = [];
    foreach ($_SESSION['cart'] as $item) {
        $orderDetails[] = "{$item['product_name']}*{$item['quantity']} BDT {$item['price']}";
    }
    $orderDetailsJson = json_encode($orderDetails);

    // Set final totals
    $finalSubtotal = $subtotalPrice;
    $finalDiscountAmount = isset($_SESSION['discount_amount']) ? $_SESSION['discount_amount'] : 0;
    $finalNetTotal = isset($_SESSION['net_total']) ? $_SESSION['net_total'] : $subtotalPrice;
    $finalDiscountCode = isset($_SESSION['applied_discount_code']) ? $_SESSION['applied_discount_code'] : null;

    // If payment method is Online, store details in session and redirect
    if ($payment_method === 'Online') {
        // Get customer details
        $customerStmt = $conn->prepare("SELECT customer_name, email, phone FROM customers WHERE id = ?");
        $customerStmt->bind_param("i", $customer_id);
        $customerStmt->execute();
        $customerStmt->bind_result($customerName, $customerEmail, $customerPhone);
        $customerStmt->fetch();
        $customerStmt->close();

        // Store all necessary information in session
        $_SESSION['pending_order'] = [
            'customer_id' => $customer_id,
            'customer_name' => $customerName,
            'customer_email' => $customerEmail,
            'customer_phone' => $customerPhone,
            'order_details' => $orderDetailsJson,
            'total_cost' => $total_deduction,
            'subtotal' => $finalSubtotal,
            'discount_amount' => $finalDiscountAmount,
            'net_total' => $finalNetTotal,
            'discount_code' => $finalDiscountCode,
            'payment_method' => $payment_method,
            'lunch_quantity' => $lunch_quantity,
            'dinner_quantity' => $dinner_quantity,
            'lunch_total' => $lunchTotalPrice,
            'dinner_total' => $dinnerTotalPrice,
            'special_items_total' => $specialItemsTotal
        ];

        // Redirect to online payment page
        header("Location: onlinepayment.php");
        exit();
    } 

    $customer_id = $_SESSION['user_id'];
    $payment_method = $_POST['payment_method'] ?? 'Cash on Delivery';

    // Prepare order details
    $orderDetails = [];
    foreach ($_SESSION['cart'] as $item) {
        $orderDetails[] = "{$item['product_name']}*{$item['quantity']} BDT {$item['price']}";
    }
    $orderDetailsJson = json_encode($orderDetails);

    // Set final totals
    $finalSubtotal = $subtotalPrice;
    $finalDiscountAmount = isset($_SESSION['discount_amount']) ? $_SESSION['discount_amount'] : 0;
    $finalNetTotal = isset($_SESSION['net_total']) ? $_SESSION['net_total'] : $subtotalPrice;
    $finalDiscountCode = isset($_SESSION['applied_discount_code']) ? $_SESSION['applied_discount_code'] : null;

    // Verify customer and meal status
    $checkCustomerStmt = $conn->prepare("SELECT id FROM customers WHERE id = ?");
    $checkCustomerStmt->bind_param("i", $customer_id);
    $checkCustomerStmt->execute();
    $checkCustomerStmt->store_result();
    if ($checkCustomerStmt->num_rows === 0) {
        echo "<div class='alert alert-danger'>Customer does not exist.</div>";
        exit();
    }
    $checkCustomerStmt->close();

    $checkMealStmt = $conn->prepare("SELECT active FROM meal WHERE meal_id = ? AND active = 1");
    $checkMealStmt->bind_param("i", $customer_id);
    $checkMealStmt->execute();
    $checkMealStmt->bind_result($isActive);
    $checkMealStmt->fetch();
    $checkMealStmt->close();

    if (!$isActive) {
        echo "<div class='alert alert-warning'>Please complete meal registration first.</div>";
        exit();
    }

    // Calculate quantities
    $lunch_quantity = 0;
    $dinner_quantity = 0;
    foreach ($_SESSION['cart'] as $item) {
        if ($item['product_name'] === "Lunch Meal") {
            $lunch_quantity += $item['quantity'];
        } elseif ($item['product_name'] === "Dinner Meal") {
            $dinner_quantity += $item['quantity'];
        }
    }

    // Get current deposit
    $depositStmt = $conn->prepare("SELECT deposit FROM meal_registration WHERE customer_id = ?");
    $depositStmt->bind_param("i", $customer_id);
    $depositStmt->execute();
    $depositStmt->bind_result($current_deposit);
    $depositStmt->fetch();
    $depositStmt->close();

    if ($current_deposit === null) {
        $current_deposit = 0;
    }

    // Calculate total deduction amount (meals + special items)
    $total_deduction = $lunchTotalPrice + $dinnerTotalPrice + $specialItemsTotal-$_SESSION['discount_amount'];
    $new_balance = $current_deposit - $total_deduction;

    if ($new_balance < 0) {
        echo "<div class='alert alert-danger'>Insufficient balance. Required: BDT " . 
             number_format($total_deduction, 2) . ", Available: BDT " . 
             number_format($current_deposit, 2) . "</div>";
        exit();
    }

    // Begin transaction
    $conn->begin_transaction();

    try {
        // Update meal registration balance
        $updateBalanceStmt = $conn->prepare("UPDATE meal_registration SET deposit = ? WHERE customer_id = ?");
        $updateBalanceStmt->bind_param("di", $new_balance, $customer_id);
        if (!$updateBalanceStmt->execute()) {
            throw new Exception("Failed to update balance");
        }
        $updateBalanceStmt->close();

        // Insert/update meal records
        if ($lunch_quantity > 0) {
            $mealStmt = $conn->prepare("INSERT INTO meal (meal_id, lunch_meal, lunch_quantity, meal_price, remain_balance, created_at) 
                                      VALUES (?, 1, ?, ?, ?, NOW()) 
                                      ON DUPLICATE KEY UPDATE 
                                      lunch_quantity = ?, meal_price = ?, remain_balance = ?");
            $mealStmt->bind_param("iiddidd", $customer_id, $lunch_quantity, $lunchTotalPrice, 
                                $new_balance, $lunch_quantity, $lunchTotalPrice, $new_balance);
            if (!$mealStmt->execute()) {
                throw new Exception("Failed to update lunch meal");
            }
            $mealStmt->close();
        }

        if ($dinner_quantity > 0) {
            $mealStmt = $conn->prepare("INSERT INTO meal (meal_id, dinner_meal, dinner_quantity, meal_price, remain_balance, created_at) 
                                      VALUES (?, 1, ?, ?, ?, NOW()) 
                                      ON DUPLICATE KEY UPDATE 
                                      dinner_quantity = ?, meal_price = ?, remain_balance = ?");
            $mealStmt->bind_param("iiddidd", $customer_id, $dinner_quantity, $dinnerTotalPrice, 
                                $new_balance, $dinner_quantity, $dinnerTotalPrice, $new_balance);
            if (!$mealStmt->execute()) {
                throw new Exception("Failed to update dinner meal");
            }
            $mealStmt->close();
        }

        // Insert order
        $orderStmt = $conn->prepare("INSERT INTO orders (
            customer_id, order_details, total_cost, subtotal, 
            discount_amount, net_total, payment_method, discount_code
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        
        $orderStmt->bind_param(
            "ssddddss",
            $customer_id,
            $orderDetailsJson,
            $total_deduction,
            $finalSubtotal,
            $finalDiscountAmount,
            $finalNetTotal,
            $payment_method,
            $finalDiscountCode
        );

        if (!$orderStmt->execute()) {
            throw new Exception("Failed to create order");
        }
        $order_id = $conn->insert_id;
        $orderStmt->close();

        // Commit transaction
        $conn->commit();

        // Send confirmation email
        $customerStmt = $conn->prepare("SELECT customer_name, email FROM customers WHERE id = ?");
        $customerStmt->bind_param("i", $customer_id);
        $customerStmt->execute();
        $customerStmt->bind_result($customerName, $customerEmail);
        $customerStmt->fetch();
        $customerStmt->close();

        if ($customerEmail) {
            try {
                $mail = new PHPMailer(true);
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'gourob.haq@gmail.com';
                $mail->Password = 'owtc hcch zufy cgey';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                $mail->Port = 465;

                $mail->setFrom('gourob.haq@gmail.com', 'City University Canteen');
                $mail->addAddress($customerEmail, $customerName);

                $orderDetailsHtml = "<ul>";
                foreach ($_SESSION['cart'] as $item) {
                    $orderDetailsHtml .= "<li>{$item['product_name']} x {$item['quantity']} - BDT {$item['price']}</li>";
                }
                $orderDetailsHtml .= "</ul>";

                $mail->isHTML(true);
                $mail->Subject = 'Order Confirmation - City University Canteen';
                $mail->Body = "
                    <div style='background-color:rgb(235, 239, 235); color: black;font-size:16px; padding: 20px; border-radius: 10px;'>
                        <h2>Order Confirmation</h2>
                        <p>Hello $customerName,</p>
                        <p>Thank you for your order from City University Canteen.</p>

                        <h3>Order Details:</h3>
                        $orderDetailsHtml

                        <p><strong>Order ID:</strong> $order_id</p>
                        <p><strong>Subtotal:</strong> BDT " . number_format($finalSubtotal, 2) . "</p>
                        <p><strong>Discount:</strong> BDT " . number_format($finalDiscountAmount, 2) . "</p>
                        <p><strong>Net Total:</strong> BDT " . number_format($finalNetTotal, 2) . "</p>
                        <p><strong>Remaining Balance:</strong> BDT " . number_format($new_balance, 2) . "</p>

                        <h3 style='background-color:green;color:white;'>Order Status: Confirmed</h3>
                        
                        <p>For any queries, please contact:</p>
                        <p>Email: citycanteen@city_university.ac.bd</p>
                        <p>Phone: 01700000000</p>
                    </div>";

                $mail->send();
            } catch (Exception $e) {
                error_log("Email sending failed: " . $mail->ErrorInfo);
            }
        }

        // Clear session data
        unset($_SESSION['cart']);
        unset($_SESSION['applied_discount_code']);
        unset($_SESSION['discount_amount']);
        unset($_SESSION['net_total']);

        // Redirect to success page
        header("Location: success.php");
        exit();

    } catch (Exception $e) {
        error_log("". $e->getMessage());
        $conn->rollback();
        echo "<div class='alert alert-danger'>Failed to place order. Please try again.</div>";
    }
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