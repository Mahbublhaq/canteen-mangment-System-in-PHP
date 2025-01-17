<?php
// Start session and include required files
session_start();
require_once '../db/db.php';

// Initialize variables
$error_message = '';
$success = false;
$customerData = null;

// Function to safely redirect
function safeRedirect($url) {
    if (!headers_sent()) {
        header("Location: " . $url);
        exit();
    } else {
        echo "<script>window.location.href='" . $url . "';</script>";
        exit();
    }
}

// Validate session and data
if (!isset($_SESSION['pending_order']) || !isset($_SESSION['user_id'])) {
    safeRedirect("cart.php");
}

$pending_order = $_SESSION['pending_order'];
$customer_id = $_SESSION['user_id'];

// Fetch customer details
try {
    $stmt = $conn->prepare("SELECT customer_name, email, phone FROM customers WHERE id = ?");
    if (!$stmt) {
        throw new Exception("Failed to prepare customer query");
    }
    
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $customerData = $result->fetch_assoc();
    $stmt->close();
    
    if (!$customerData) {
        throw new Exception("Customer data not found");
    }
} catch (Exception $e) {
    $error_message = "Error fetching customer data: " . $e->getMessage();
}

// Handle payment submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Validate required fields
        if (!isset($_POST['payment_method']) || empty($_POST['payment_method'])) {
            throw new Exception("Payment method is required");
        }
        
        if (!isset($_POST['amount']) || empty($_POST['amount'])) {
            throw new Exception("Amount is required");
        }

        // Store POST data
        $paymentMethod = $_POST['payment_method'];
        $phone = $_POST['phone'] ?? '';
        $amount = floatval($_POST['amount']);
        $cardNumber = $_POST['card_number'] ?? '';
        $expiry = $_POST['expiry'] ?? '';
        $transactionId = uniqid('TXN_');

        // Validate amount
        $expected_amount = floatval($pending_order['net_total']);
        if ($amount !== $expected_amount) {
            throw new Exception("Payment amount does not match order total");
        }

        // Start transaction
        $conn->begin_transaction();

        try {
            // First Query: Insert into onlinepayment - FIXED QUERY AND BIND_PARAM
            $paymentSql = "INSERT INTO onlinepayment (customer_id, customer_name, email, phone, 
                          order_details, total_amount, payment_method, payment_number, card_number, 
                          card_expiry, transaction_id, payment_status) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'completed')";

            $paymentStmt = $conn->prepare($paymentSql);
            if (!$paymentStmt) {
                throw new Exception("Failed to prepare payment statement: " . $conn->error);
            }

            $orderDetailsJson = json_encode($pending_order['order_details']);
            
            // Fixed bind_param call - Corrected parameter count
            $paymentStmt->bind_param("issssdsssss", 
                $customer_id,
                $customerData['customer_name'],
                $customerData['email'],
                $phone,
                $orderDetailsJson,
                $amount,
                $paymentMethod,
                $phone,
                $cardNumber,
                $expiry,
                $transactionId
            );

            if (!$paymentStmt->execute()) {
                throw new Exception("Failed to insert payment: " . $paymentStmt->error);
            }
            $paymentId = $paymentStmt->insert_id;
            $paymentStmt->close();

            // Second Query: Insert into orders - FIXED QUERY
            $orderSql = "INSERT INTO orders (customer_id, order_details, total_cost, 
                        subtotal, discount_amount, net_total, payment_method, 
                        order_status, admin_name, admin_id) 
                        VALUES (?, ?, ?, ?, ?, ?, 'Online', 'Pending', '', 0)";

            $orderStmt = $conn->prepare($orderSql);
            if (!$orderStmt) {
                throw new Exception("Failed to prepare order statement: " . $conn->error);
            }

            // Fixed bind_param for orders table
            $orderStmt->bind_param("isdddd", 
                $customer_id,
                $orderDetailsJson,
                $amount,
                $pending_order['subtotal'],
                $pending_order['discount_amount'],
                $amount
            );

            if (!$orderStmt->execute()) {
                throw new Exception("Failed to insert order: " . $orderStmt->error);
            }
            $orderId = $orderStmt->insert_id;
            $orderStmt->close();

            // Commit transaction
            $conn->commit();
            
            // Clear session data
            unset($_SESSION['pending_order']);
            
            // Redirect to success page
            safeRedirect("payment_success.php?order_id=" . $orderId);

        } catch (Exception $e) {
            $conn->rollback();
            throw $e;
        }

    } catch (Exception $e) {
        $error_message = $e->getMessage();
        echo "<div class='alert alert-danger'>" . htmlspecialchars($error_message) . "</div>";
    }
}

include '../menu/menu.php';
?>

<!-- Rest of your HTML remains the same -->

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Processing</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .payment-method-card {
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid #dee2e6;
            border-radius: 8px;
        }
        .payment-method-card:hover {
            border-color: #0d6efd;
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .payment-method-card.selected {
            border-color: #0d6efd;
            background-color: #f8f9fa;
        }
        .payment-icon {
            font-size: 2rem;
            margin-bottom: 10px;
        }
        .order-item {
            padding: 10px;
            border-bottom: 1px solid #dee2e6;
        }
        .alert {
            margin: 20px;
            padding: 15px;
            border-radius: 4px;
        }
        .alert-danger {
            background-color: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
        }
    </style>
</head>
<body class="bg-light">
    <<div class="container py-5">
        <div class="row justify-content-center g-4">
            <!-- Order Summary Card -->
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h3 class="card-title mb-0">Order Summary</h3>
                    </div>
                    <div class="card-body">
                        <div class="customer-info mb-4">
                            <h5 class="border-bottom pb-2">Customer Information</h5>
                            <p><i class="fas fa-user me-2"></i> <?php echo htmlspecialchars($customerData['customer_name']); ?></p>
                            <p><i class="fas fa-envelope me-2"></i><?php echo htmlspecialchars($customerData['email']); ?></p>
                            <p><i class="fas fa-phone me-2"></i><?php echo htmlspecialchars($customerData['phone']); ?></p>
                        </div>
                        
                        <div class="order-details mb-4">
    <h5 class="border-bottom pb-2">Order Details</h5>
    <?php 
    $orderDetails = json_decode($pending_order['order_details'], true);
    if ($orderDetails) {
        foreach ($orderDetails as $item) {
            // Parse the item string (format: "ProductName*Quantity BDT Price")
            list($productInfo, $priceInfo) = explode(" BDT ", $item);
            list($productName, $quantity) = explode("*", $productInfo);
            ?>
            <div class="order-item">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="fw-medium"><?php echo htmlspecialchars($productName); ?></span>
                    </div>
                    <div class="text-end">
                        <span class="text-muted">x<?php echo $quantity; ?></span>
                        <span class="ms-3">BDT <?php echo number_format((float)$priceInfo, 2); ?></span>
                    </div>
                </div>
            </div>
            <?php
        }
    } else {
        ?>
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle me-2"></i>
            No order details available
        </div>
        <?php
    }
    ?>
</div>
                        
                        <div class="pricing-summary">
                            <div class="d-flex justify-content-between mb-2">
                                <span>Subtotal:</span>
                                <span>BDT <?php echo number_format($pending_order['subtotal'] ?? 0, 2); ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Discount:</span>
                                <span>-BDT <?php echo number_format($pending_order['discount_amount'] ?? 0, 2); ?></span>
                            </div>
                            <div class="d-flex justify-content-between fw-bold border-top pt-2">
                                <span>Total:</span>
                                <span>BDT <?php echo number_format($pending_order['net_total'] ?? 0, 2); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

           <!-- Payment Method Selection -->
<div class="col-md-6">
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
            <h3 class="card-title mb-0">Payment Method</h3>
        </div>
        <div class="card-body">
            <div class="row g-3 mb-4">
                <div class="col-6">
                    <div class="payment-method-card text-center p-3" data-bs-toggle="modal" data-bs-target="#mobilePaymentModal" onclick="setPaymentMethod('bkash')">
                        <i class="fas fa-mobile-alt payment-icon text-danger fs-3 mb-2"></i>
                        <h5>bKash</h5>
                    </div>
                </div>
                <div class="col-6">
                    <div class="payment-method-card text-center p-3" data-bs-toggle="modal" data-bs-target="#mobilePaymentModal" onclick="setPaymentMethod('nagad')">
                        <i class="fas fa-wallet payment-icon text-warning fs-3 mb-2"></i>
                        <h5>Nagad</h5>
                    </div>
                </div>
                <div class="col-6">
                    <div class="payment-method-card text-center p-3" data-bs-toggle="modal" data-bs-target="#mobilePaymentModal" onclick="setPaymentMethod('rocket')">
                        <i class="fas fa-rocket payment-icon text-info fs-3 mb-2"></i>
                        <h5>Rocket</h5>
                    </div>
                </div>
                <div class="col-6">
                    <div class="payment-method-card text-center p-3" data-bs-toggle="modal" data-bs-target="#cardPaymentModal">
                        <i class="fas fa-credit-card payment-icon text-success fs-3 mb-2"></i>
                        <h5>Card</h5>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Mobile Payment Modal -->
<div class="modal fade" id="mobilePaymentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">City University Canteen Payment Gateway</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-4">
                    <img id="paymentMethodLogo" src="" alt="Payment Method Logo" class="img-fluid mb-3" style="max-height: 60px;">
                    <h4 id="paymentMethodTitle"></h4>
                </div>
                <form action="onlinepayment.php" method="POST">
                    <input type="hidden" name="payment_method" id="payment_method">
                    <div class="mb-3">
                        <label class="form-label">Phone Number</label>
                        <input type="tel" class="form-control" name="phone" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Amount (BDT)</label>
                        <input type="number" class="form-control" name="amount" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Confirm Payment</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Card Payment Modal -->
<div class="modal fade" id="cardPaymentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">City University Canteen Payment Gateway</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="onlinepayment.php" method="POST">
                    <input type="hidden" name="payment_method" value="card">
                    <div class="mb-3">
                        <label class="form-label">Card Number</label>
                        <input type="text" class="form-control" name="card_number" required>
                    </div>
                    <div class="row mb-3">
                        <div class="col">
                            <label class="form-label">Expiry Date</label>
                            <input type="text" class="form-control" placeholder="MM/YY" name="expiry" required>
                        </div>
                        <div class="col">
                            <label class="form-label">CVV</label>
                            <input type="text" class="form-control" name="cvv" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Amount (BDT)</label>
                        <input type="number" class="form-control" name="amount" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Pay Now</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function setPaymentMethod(method) {
    document.getElementById('payment_method').value = method;
    document.getElementById('paymentMethodTitle').textContent = method.charAt(0).toUpperCase() + method.slice(1);
    
    // Set logo based on payment method
    const logoSrc = {
        'bkash': 'path/to/bkash-logo.png',
        'nagad': 'path/to/nagad-logo.png',
        'rocket': 'path/to/rocket-logo.png'
    };
    document.getElementById('paymentMethodLogo').src = logoSrc[method];
}
</script>

<style>
.payment-method-card {
    border: 1px solid #dee2e6;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.payment-method-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.payment-icon {
    font-size: 2rem;
    margin-bottom: 0.5rem;
}
</style>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const expectedAmount = <?php echo json_encode($pending_order['net_total'] ?? 0); ?>;
            const amountInputs = document.querySelectorAll('input[name="amount"]');
            
            amountInputs.forEach(input => {
                input.value = expectedAmount;
                input.readOnly = true;
            });

            const paymentForms = document.querySelectorAll('form');
            paymentForms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    const submittedAmount = parseFloat(this.querySelector('input[name="amount"]').value);
                    const paymentMethod = this.querySelector('input[name="payment_method"]').value;
                    
                    if (!paymentMethod) {
                        e.preventDefault();
                        alert('Please select a payment method');
                        return false;
                    }
                    
                    if (submittedAmount !== expectedAmount) {
                        e.preventDefault();
                        alert(`Payment amount (৳${submittedAmount}) must match order total (৳${expectedAmount})`);
                        return false;
                    }
                    
                    return true;
                });
            });
        });

        function setPaymentMethod(method) {
            document.getElementById('payment_method').value = method;
            document.getElementById('paymentMethodTitle').textContent = 
                method.charAt(0).toUpperCase() + method.slice(1);
        }
    </script>
</body>
</html>