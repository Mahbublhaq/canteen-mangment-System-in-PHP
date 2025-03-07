<?php
session_start();
include('../db/db.php');

// Include your database connection file

//if session not found give alert message to login
if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('Please login first.')</script>";
    echo "<script>window.location.href = '../view/login.html'</script>";
    exit();
}



// Assuming customer_id is stored in the session
$customer_id = $_SESSION['user_id'];

//if customer_id=meal table meal_id $sql="select meal_id from meal where meal_id='$customer_id'"; not found show alert message Please deposit first or Plesae wait for Validity check
$sql = "SELECT meal_id FROM meal WHERE meal_id='$customer_id'";
$result = $conn->query($sql);

if ($result->num_rows === 0) {
    echo "<script>alert('Please deposit first or wait for validity check.')</script>";
    echo "<script>window.location.href = '../model/welcome.php'</script>";
    exit();
}

// Fetch customer details including profile picture
$customerQuery = "SELECT c.customer_name, c.email, c.phone, c.address, 
                         c.profile_picture, 
                         m.deposit AS remain_balance 
                  FROM customers c
                  JOIN meal_registration m ON c.id = m.customer_id
                  WHERE c.id = '$customer_id'";
$customerResult = $conn->query($customerQuery);
$customerData = $customerResult->fetch_assoc();

// Fetch total deposit from deposit_history
$totalDepositQuery = "SELECT SUM(deposit_amount) AS total_deposit 
                      FROM deposit_history 
                      WHERE customer_id = '$customer_id'";
$totalDepositResult = $conn->query($totalDepositQuery);
$totalDepositData = $totalDepositResult->fetch_assoc();
$totalDeposit = $totalDepositData['total_deposit'] ?? 0;

// Fetch recent order history
$orderQuery = "
    SELECT id, created_at, order_details,net_total
    FROM orders
    WHERE customer_id = '$customer_id'
    ORDER BY created_at DESC
    LIMIT 5
";
$orderResult = $conn->query($orderQuery);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
</head>

<nav class="bg-red-500 text-white shadow-lg fixed w-full top-0 z-50">
        <div class="container mx-auto px-4 flex justify-between items-center py-3">
            <!-- Logo Section -->
            <div class="flex items-center space-x-2">
                <span class="text-lg font-semibold">City University Canteen</span>
            </div>
            <!-- Menu Items -->
            <div class="flex space-x-6 text-sm font-medium">
                <a href="welcome.php" class="flex items-center space-x-1 menu-item hover:text-blue-300 transition">
                    <i class="ri-home-line"></i>
                    <span>Home</span>
                </a>
                <a href="cart.php" class="flex items-center space-x-1 menu-item hover:text-blue-300 transition">
                    <i class="ri-shopping-cart-line"></i>
                    <span>Cart</span>
                </a>
                <!-- Dropdown Menu -->
                <div class="relative dropdown">
                    <a href="#" class="flex items-center space-x-1 menu-item hover:text-blue-300 transition">
                        <i class="ri-apps-line"></i>
                        <span>Category</span>
                        <i class="ri-arrow-down-s-line"></i>
                    </a>
                    <div class="absolute left-0 mt-2 bg-white text-blue-900 rounded shadow-lg dropdown-menu hidden">
                        <a href="hotoffer.php" class="block px-4 py-2 hover:bg-blue-100">Hot Offer</a>
                        <a href="combo.php" class="block px-4 py-2 hover:bg-blue-100">Combo</a>
                        <a href="meal.php" class="block px-4 py-2 hover:bg-blue-100">Meal</a>
                    </div>
                </div>
                <a href="logout.php" class="flex items-center space-x-1 menu-item hover:text-blue-300 transition">
                    <i class="ri-logout-box-line"></i>
                    <span>Logout</span>
                </a>
            </div>
        </div>
    </nav>








<body class="bg-gray-100"style="margin-top:2%;">
   <div class="container mx-auto px-4 py-8 max-w-4xl">
        <div class="bg-white shadow-xl rounded-2xl overflow-hidden">
            <!-- Header Section -->
            <div class="bg-gradient-to-r from-blue-500 to-purple-600 p-6">
                <div class="flex items-center">
                    <!-- Profile Picture -->
                    <?php 
$profile_pic = !empty($customerData['profile_picture']) 
    ? "../uploads/profiles/" . htmlspecialchars($customerData['profile_picture']) 
    : null;
?>

<div class="w-24 h-24 rounded-full border-4 border-white overflow-hidden mr-6">
    <?php if (!empty($profile_pic)): ?>
        <img src="<?php echo $profile_pic; ?>" 
             alt="Profile Picture" 
             class="w-full h-full object-cover">
             
    <?php else: ?>
        <div class="w-full h-full bg-blue-200 flex items-center justify-center text-blue-600">
            <i class="ri-user-line text-4xl"></i>
        </div>
    <?php endif; ?>
</div>

                    
                    <!-- Customer Name and Email -->
                    <div>
                        <h1 class="text-2xl font-bold text-white">
                            <?php echo htmlspecialchars($customerData['customer_name']); ?>
                        </h1>
                        <p class="text-blue-100">
                            <?php echo htmlspecialchars($customerData['email']); ?>
                        </p>
                        
                    </div>
                    <img src="../static/logo.png" alt="Logo" class="w-40 h-30  id="profile-preview" style="margin-left: 40%;">
                </div>
            </div>

            <!-- Dashboard Content -->
            <div class="p-6">
                <div class="grid md:grid-cols-3 gap-6">
                    <!-- Payment Summary -->
                    <div class="bg-blue-50 p-4 rounded-lg">
                        <h2 class="text-xl font-semibold text-blue-700 mb-4">
                            <i class="ri-wallet-line mr-2"></i>Payment Summary
                        </h2>
                        <div class="space-y-2">
                            <div>
                                <p class="text-gray-600">Remaining Balance</p>
                                <p class="text-2xl font-bold text-blue-600">
                                    <?php echo number_format($customerData['remain_balance'], 2); ?> TK
                                </p>
                            </div>
                            <div>
                                <p class="text-gray-600">Total Deposit</p>
                                <p class="text-xl font-semibold text-green-600">
                                    <?php echo number_format($totalDeposit, 2); ?> TK
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Customer Details -->
                    <div class="bg-green-50 p-4 rounded-lg">
                        <h2 class="text-xl font-semibold text-green-700 mb-4">
                            <i class="ri-user-location-line mr-2"></i>Contact Info
                        </h2>
                        <div class="space-y-2">
                            <p><i class="ri-phone-line mr-2 text-green-600"></i><?php echo htmlspecialchars($customerData['phone']); ?></p>
                            <p><i class="ri-map-pin-line mr-2 text-green-600"></i><?php echo htmlspecialchars($customerData['address']); ?></p>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="bg-purple-50 p-4 rounded-lg">
                        <h2 class="text-xl font-semibold text-purple-700 mb-4">
                            <i class="ri-money-dollar-circle-line mr-2"></i>Deposit Funds
                        </h2>
                        <div class="grid grid-cols-2 gap-2">
                            <a href="bkash_payment.php" class="bg-blue-500 text-white p-2 rounded text-center hover:bg-blue-600 transition">
                                <i class="ri-bank-card-line mr-1"></i>Bkash
                            </a>
                            <a href="Nagad_payment.php" class="bg-green-500 text-white p-2 rounded text-center hover:bg-green-600 transition">
                                <i class="ri-bank-card-line mr-1"></i>Nagad
                            </a>
                            <a href="Card_payment.php" class="bg-purple-500 text-white p-2 rounded text-center hover:bg-purple-600 transition">
                                <i class="ri-bank-card-line mr-1"></i>Card
                            </a>
                            <!-- <a href="add_funds.php" class="bg-yellow-500 text-white p-2 rounded text-center hover:bg-yellow-600 transition">
                                <i class="ri-add-circle-line mr-1"></i>Add Funds
                            </a> -->
                        </div>
                    </div>
                </div>

               <!-- Recent Orders -->
            <div class="p-6 bg-gray-50">
                <h2 class="text-xl font-semibold text-gray-700 mb-4">Recent Orders</h2>
                <?php if ($orderResult->num_rows > 0): ?>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-200">
                                <tr>
                                    <th class="p-3 text-left">Order Date</th>
                                    <th class="p-3 text-left">Order Details</th>
                                    <th class="p-3 text-right">Total Cost</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($order = $orderResult->fetch_assoc()): ?>
                                    <tr class="border-b hover:bg-gray-100">
                                        <td class="p-3"><?php echo htmlspecialchars(date('Y-m-d', strtotime($order['created_at']))); ?></td>
                                        <td class="p-3">
    <ul class="list-disc pl-5 text-gray-700">
        <?php 
        // Decode the order_details as an associative array
        $orderDetails = json_decode($order['order_details'], true);

        // Check if decoding was successful and the result is an array
        if (is_array($orderDetails)) {
            foreach ($orderDetails as $item) {
                // Ensure each item is an array and contains the expected keys
                if (is_array($item) && isset($item['product_name'], $item['quantity'])) {
                    echo '<li>';
                    echo htmlspecialchars($item['product_name']) . ' * ';
                    echo htmlspecialchars($item['quantity']);
                    echo '</li>';
                } else {
                    echo '<li class="text-red-500"> ' . htmlspecialchars(json_encode($item)) . '</li>';
                }
            }
        } else {
            echo '<li class="text-gray-500">Invalid or missing order details.</li>';
        }
        ?>
    </ul>
</td>


                                        <td class="p-3 text-right"><?php echo number_format($order['net_total'], 2); ?> TK</td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-center text-gray-500">No orders found.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <script>
         //dropdown menu
         const dropdowns = document.querySelectorAll('.dropdown');
        dropdowns.forEach(dropdown => {
            dropdown.addEventListener('click', function() {
                this.querySelector('.dropdown-menu').classList.toggle('hidden');
            });
        });
        //close dropdown menu on click outside
        window.addEventListener('click', function(e) {
            dropdowns.forEach(dropdown => {
                if (!dropdown.contains(e.target)) {
                    dropdown.querySelector('.dropdown-menu').classList.add('hidden');
                }
            });
        });
    
    </script>
</body>
</html>