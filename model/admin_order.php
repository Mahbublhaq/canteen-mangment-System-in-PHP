<?php
ob_start();
require_once '../db/db.php';
require_once '../menu/adminmenu.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Initialize cart if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Ensure admin role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Create updated guest_customer table if not exists
$createTableSQL = "
    CREATE TABLE IF NOT EXISTS guest_customer (
        id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        customer_name VARCHAR(255) NOT NULL,
        phone_number VARCHAR(20) NOT NULL,
        total_orders INT DEFAULT 0,
        total_spent DECIMAL(10,2) DEFAULT 0.00,
        last_order_date DATETIME DEFAULT NULL,
        order_details JSON DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
$conn->query($createTableSQL);

// Handle cart operations
if (isset($_POST['action'])) {
    $product_id = (int)$_POST['product_id'];
    switch ($_POST['action']) {
        case 'add':
            $_SESSION['cart'][$product_id] = ($_SESSION['cart'][$product_id] ?? 0) + 1;
            break;
        case 'remove':
            if (isset($_SESSION['cart'][$product_id])) {
                $_SESSION['cart'][$product_id]--;
                if ($_SESSION['cart'][$product_id] <= 0) {
                    unset($_SESSION['cart'][$product_id]);
                }
            }
            break;
    }
}

// Handle order submission
if (isset($_POST['place_order']) && !empty($_SESSION['cart'])) {
    $name = $conn->real_escape_string($_POST['customer_name']);
    $phone = $conn->real_escape_string($_POST['phone_number']);

    // Calculate totals
    $total = 0;
    $order_items = [];
    foreach ($_SESSION['cart'] as $pid => $qty) {
        $product_sql = "SELECT * FROM products WHERE id = $pid";
        $product_result = $conn->query($product_sql);
        $product = $product_result->fetch_assoc();

        $subtotal = $product['price'] * $qty;
        $total += $subtotal;

        $order_items[] = [
            'product_id' => $pid,
            'product_name' => $product['product_name'],
            'quantity' => $qty,
            'price' => $product['price'],
            'subtotal' => $subtotal,
        ];
    }

    // Check if customer exists
    $checkCustomerSQL = "SELECT * FROM guest_customer WHERE phone_number = ?";
    $stmtCheck = $conn->prepare($checkCustomerSQL);
    $stmtCheck->bind_param("s", $phone);
    $stmtCheck->execute();
    $result = $stmtCheck->get_result();
    
    if ($result->num_rows > 0) {
        // Update existing customer
        $customer = $result->fetch_assoc();
        $guest_id = $customer['id'];
        $new_total_orders = $customer['total_orders'] + 1;
        $new_total_spent = $customer['total_spent'] + $total;
        $current_time = date('Y-m-d H:i:s');
        
        $updateCustomerSQL = "
            UPDATE guest_customer 
            SET total_orders = ?,
                total_spent = ?,
                last_order_date = ?,
                order_details = JSON_ARRAY_APPEND(
                    IFNULL(order_details, JSON_ARRAY()),
                    '$',
                    CAST(? AS JSON)
                )
            WHERE id = ?";
        
        $stmtUpdate = $conn->prepare($updateCustomerSQL);
        $orderDetailsJson = json_encode($order_items);
        $stmtUpdate->bind_param(
            "idssi",
            $new_total_orders,
            $new_total_spent,
            $current_time,
            $orderDetailsJson,
            $guest_id
        );
        if (!$stmtUpdate->execute()) {
            die("Error updating customer: " . $stmtUpdate->error);
        }
        $stmtUpdate->close();
    } else {
        // Insert new customer
        $insertGuestSQL = "
            INSERT INTO guest_customer (
                customer_name,
                phone_number,
                total_orders,
                total_spent,
                last_order_date,
                order_details
            ) VALUES (?, ?, 1, ?, ?, JSON_ARRAY(?))";
        
        $stmtInsert = $conn->prepare($insertGuestSQL);
        $current_time = date('Y-m-d H:i:s');
        $orderDetailsJson = json_encode($order_items);
        $stmtInsert->bind_param(
            "ssdss",
            $name,
            $phone,
            $total,
            $current_time,
            $orderDetailsJson
        );
        
        if (!$stmtInsert->execute()) {
            die("Error inserting guest customer: " . $stmtInsert->error);
        }
        $guest_id = $conn->insert_id;
        $stmtInsert->close();
    }
    $stmtCheck->close();

    // Fetch admin info
    $adminId = $_SESSION['user_id'];
    $adminQuery = "SELECT name FROM admins WHERE id = ?";
    $stmtAdmin = $conn->prepare($adminQuery);
    $stmtAdmin->bind_param("i", $adminId);
    $stmtAdmin->execute();
    $adminResult = $stmtAdmin->get_result();
    $adminRow = $adminResult->fetch_assoc();
    $adminName = $adminRow['name'] ?? 'Unknown Admin';
    $stmtAdmin->close();

    // Insert order
    $order_details = json_encode($order_items);
    $orderSQL = "
        INSERT INTO orders (
            gest_customer_id,
            order_details,
            total_cost,
            subtotal,
            discount_amount,
            net_total,
            payment_method,
            order_status,
            admin_name,
            admin_id
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmtOrder = $conn->prepare($orderSQL);
    $discount = 0; // Assuming no discount
    $payment_method = 'ShoptoSell'; // Example
    $order_status = 'Confirmed';
    $net_total = $total;

    $stmtOrder->bind_param(
        "isddddsssi",
        $guest_id,
        $order_details,
        $total,
        $total,
        $discount,
        $net_total,
        $payment_method,
        $order_status,
        $adminName,
        $adminId
    );

    if ($stmtOrder->execute()) {
        $order_id = $conn->insert_id;
        $stmtOrder->close();
        header("Location: ../model/print_bill.php?order_id=$order_id");
        exit();
    } else {
        die("Error inserting order: " . $stmtOrder->error);
    }
}

ob_end_flush();

// Fetch products by category
$categories = ['Hot Offer', 'Combo', 'Meal', 'Regular'];
$products_by_category = [];
foreach ($categories as $category) {
    $sql = "SELECT * FROM products WHERE catagorey = '$category' AND Active = 1";
    $result = $conn->query($sql);
    $products_by_category[$category] = $result->fetch_all(MYSQLI_ASSOC);
}
?>




<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POS System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #34495e;
            --accent-color: #e74c3c;
        }
        
        body {
            background-color: #f5f6fa;
        }
        
        .main-content {
            margin-left: 20%;
            margin-right: 300px;
            padding: 20px;
        }
        
        .category-section {
            margin-bottom: 30px;
        }
        
        .category-title {
            color: var(--primary-color);
            border-left: 4px solid var(--accent-color);
            padding-left: 10px;
            margin-bottom: 20px;
        }
        
        .product-card {
            transition: all 0.3s ease;
            height: 100%;
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .product-image {
            height: 150px;
            object-fit: cover;
            border-top-left-radius: 10px;
            border-top-right-radius: 10px;
        }
        
        .cart-sidebar {
            position: fixed;
            right: 0;
            top: 0;
            width: 300px;
            height: 100vh;
            background: white;
            box-shadow: -2px 0 10px rgba(0,0,0,0.1);
            padding: 20px;
            overflow-y: auto;
        }
        
        .cart-title {
            color: var(--primary-color);
            border-bottom: 2px solid var(--accent-color);
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        
        .quantity-control {
            width: 120px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .success-alert {
            position: fixed;
            top: 20px;
            right: 320px;
            z-index: 1000;
        }
        
        .hot-offer-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background-color: var(--accent-color);
            color: white;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
        }
        
        .cart-item-card {
            border-left: 3px solid var(--accent-color);
            margin-bottom: 10px;
        }
        
        .place-order-btn {
            background-color: var(--primary-color);
            border: none;
            transition: all 0.3s ease;
        }
        
        .place-order-btn:hover {
            background-color: var(--secondary-color);
            transform: translateY(-2px);
        }
        
        .category-tabs {
            margin-bottom: 20px;
        }
        
        .category-tab {
            padding: 10px 20px;
            margin-right: 10px;
            border-radius: 20px;
            background-color: #fff;
            color: var(--primary-color);
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .category-tab.active {
            background-color: var(--primary-color);
            color: #fff;
        }
        h1{
            color:black;
            top: 2%;
            font-weight: 600px;
        }
    </style>
</head>
<body>

<div class="main-content">
    <?php if(isset($_SESSION['success'])): ?>
        <div class="alert alert-success success-alert">
            <?php 
                echo $_SESSION['success'];
                unset($_SESSION['success']);
            ?>
        </div>
    <?php endif; ?>

    <h3 class="mb-4">Manual Order's</h3>
    
    <!-- Category Tabs -->
    <div class="category-tabs d-flex flex-wrap">
        <?php foreach($categories as $category): ?>
            <div class="category-tab mb-2" onclick="scrollToCategory('<?php echo $category; ?>')">
                <?php echo $category; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Products by Category -->
    <?php foreach($categories as $category): ?>
        <div id="<?php echo $category; ?>" class="category-section">
            <h4 class="category-title"><?php echo $category; ?></h4>
            <div class="row g-3">
                <?php foreach($products_by_category[$category] as $product): ?>
                    <div class="col-md-3">
                        <div class="card product-card">
                            <?php if($category === 'Hot Offer'): ?>
                                <span class="hot-offer-badge">Hot Offer!</span>
                            <?php endif; ?>
                            
                            <img src="<?php echo htmlspecialchars($product['product_image']); ?>" 
                                 class="card-img-top product-image" 
                                 alt="<?php echo htmlspecialchars($product['product_name']); ?>">
                            
                            <div class="card-body p-3">
                                <h6 class="card-title mb-1"><?php echo htmlspecialchars($product['product_name']); ?></h6>
                                <p class="card-text small mb-2"><?php echo htmlspecialchars($product['product_details']); ?></p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="fw-bold">BDT <?php echo number_format($product['price'], 2); ?></span>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                        <input type="hidden" name="action" value="add">
                                        <button type="submit" class="btn btn-sm btn-primary">
                                            <i class="fas fa-cart-plus"></i> Add
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Cart Sidebar -->
<div class="cart-sidebar">
    <h5 class="cart-title">Shopping Cart</h5>
    
    <?php if(empty($_SESSION['cart'])): ?>
        <div class="text-center py-4">
            <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
            <p class="text-muted">Your cart is empty</p>
        </div>
    <?php else: ?>
        <div class="cart-items mb-3">
            <?php 
            $total = 0;
            foreach($_SESSION['cart'] as $pid => $qty):
                $product_sql = "SELECT * FROM products WHERE id = $pid";
                $product_result = $conn->query($product_sql);
                $product = $product_result->fetch_assoc();
                $subtotal = $product['price'] * $qty;
                $total += $subtotal;
            ?>
                <div class="card cart-item-card">
                    <div class="card-body p-2">
                        <h6 class="card-title mb-1"><?php echo htmlspecialchars($product['product_name']); ?></h6>
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="quantity-control">
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="product_id" value="<?php echo $pid; ?>">
                                    <input type="hidden" name="action" value="remove">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="fas fa-minus"></i>
                                    </button>
                                </form>
                                <span class="mx-2"><?php echo $qty; ?></span>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="product_id" value="<?php echo $pid; ?>">
                                    <input type="hidden" name="action" value="add">
                                    <button type="submit" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </form>
                            </div>
                            <span class="fw-bold">BDT <?php echo number_format($subtotal, 2); ?></span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="total mb-3">
            <h5 class="text-end">Total: BDT <?php echo number_format($total, 2); ?></h5>
        </div>
        
    <!-- Update the form HTML with proper validation attributes -->
<form method="POST" class="customer-form" onsubmit="return validateForm()">
    <div class="mb-2">
        <input type="text" 
               id="customer_name" 
               name="customer_name" 
               class="form-control" 
               placeholder="Customer Name" 
               pattern="^[A-Za-z\s]{3,50}$"
               title="Name should only contain letters and spaces, between 3-50 characters"
               required>
    </div>
    <div class="mb-2">
        <input type="tel" 
               id="phone_number" 
               name="phone_number" 
               class="form-control" 
               placeholder="Phone Number (e.g., 01712345678)" 
               pattern="^01[3-9]\d{8}$"
               title="Enter valid BD number starting with 013-019, 11 digits total"
               required>
    </div>
    <button type="submit" name="place_order" class="btn btn-success place-order-btn w-100">
        <i class="fas fa-receipt"></i> Place Order & Print Bill
    </button>
</form>
    <?php endif; ?>
</div>

<script>
function scrollToCategory(categoryId) {
    document.getElementById(categoryId).scrollIntoView({ behavior: 'smooth' });
    
    // Update active tab
    document.querySelectorAll('.category-tab').forEach(tab => {
        tab.classList.remove('active');
        if(tab.textContent.trim() === categoryId) {
            tab.classList.add('active');
        }
    });
}

//customer-form validation


// Auto-hide success alert
document.addEventListener('DOMContentLoaded', function() {
    const alert = document.querySelector('.success-alert');
    if(alert) {
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        }, 3000);
    }
});

function validateForm() {
    var customerName = document.getElementById('customer_name').value;
    var phoneNumber = document.getElementById('phone_number').value;

    // Validate customer name (letters and spaces only, 3-50 characters)
    var namePattern = /^[A-Za-z\s]{3,50}$/;
    if (!namePattern.test(customerName)) {
        alert("Please enter a valid customer name (letters and spaces only, 3-50 characters)");
        return false;
    }

    // Validate Bangladesh phone number
    // Must start with 01, followed by 3-9, then 8 more digits
    var phonePattern = /^01[3-9]\d{8}$/;
    if (!phonePattern.test(phoneNumber)) {
        alert("Please enter a valid Bangladesh phone number:\n- Must be 11 digits\n- Must start with 013-019");
        return false;
    }

    return true;
}

// Prevent form resubmission when page is refreshed
if (window.history.replaceState) {
    window.history.replaceState(null, null, window.location.href);
}

// Add event listener to clear form on successful submission
document.querySelector('.customer-form').addEventListener('submit', function() {
    if (validateForm()) {
        setTimeout(() => {
            this.reset();
        }, 100);
    }
});
</script>

</body>
</html>