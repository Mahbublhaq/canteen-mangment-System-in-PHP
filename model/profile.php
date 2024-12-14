<?php
session_start();
include '../db/db.php';
 
// Authentication and session checks
if (!isset($_SESSION['user_id'])) {
    header('Location: ../view/login.html');
    exit();
}

// Session timeout check
$inactive = 1800; // 30 minutes
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $inactive) {
    session_unset();
    session_destroy();
    header('Location: ../view/login.html?timeout=1');
    exit();
}
$_SESSION['last_activity'] = time();

// Handle profile picture upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_picture'])) {
    $user_id = $_SESSION['user_id'];
    $uploadDir = '../uploads/profiles/';
    
    // Ensure upload directory exists
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // Check for upload errors
    if ($_FILES['profile_picture']['error'] !== UPLOAD_ERR_OK) {
        $_SESSION['error'] = "File upload failed. Error code: " . $_FILES['profile_picture']['error'];
        header('Location: profile.php');
        exit();
    }

    // Validate file type and size
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $maxFileSize = 5 * 1024 * 1024; // 5MB
    
    $fileType = mime_content_type($_FILES['profile_picture']['tmp_name']);
    $fileSize = $_FILES['profile_picture']['size'];

    if (!in_array($fileType, $allowedTypes)) {
        $_SESSION['error'] = "Invalid file type. Only JPEG, PNG, GIF, and WebP are allowed.";
        header('Location: profile.php');
        exit();
    }

    if ($fileSize > $maxFileSize) {
        $_SESSION['error'] = "File size exceeds 5MB limit.";
        header('Location: profile.php');
        exit();
    }

    // Generate unique filename
    $fileExtension = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
    $newFilename = $user_id . '_profile_' . uniqid() . '.' . $fileExtension;
    $uploadPath = $uploadDir . $newFilename;

    // Move uploaded file
    if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $uploadPath)) {
        try {
            // Update profile picture in database
            $stmt = $conn->prepare("UPDATE customers SET profile_picture = ? WHERE id = ?");
            $stmt->bind_param("si", $newFilename, $user_id);
            $stmt->execute();

            $_SESSION['success'] = "Profile picture updated successfully!";
        } catch (Exception $e) {
            // If database update fails, remove the uploaded file
            unlink($uploadPath);
            $_SESSION['error'] = "Failed to update profile picture in database: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "Failed to move uploaded file.";
    }

    header('Location: profile.php');
    exit();
}

// Fetch customer data with order statistics
try {
    $user_id = $_SESSION['user_id'];
    
    // Fetch customer details with order statistics
    $stmt = $conn->prepare("
        SELECT 
            c.*,
            COUNT(o.id) as total_orders,
            COALESCE(SUM(o.total_cost), 0) as total_spent
        FROM 
            customers c
        LEFT JOIN 
            orders o ON c.id = o.customer_id
        WHERE 
            c.id = ?
        GROUP BY 
            c.id
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $customer = $result->fetch_assoc();
    
    if (!$customer) {
        session_unset();
        session_destroy();
        header('Location: ../view/login.html');
        exit();
    }
} catch (Exception $e) {
    error_log("Profile fetch error: " . $e->getMessage());
    header('Location: ../view/login.html?error=1');
    exit();
}

// Handle profile updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $editableFields = [
        'customer_name', 
        'address', 
        'delivery_address', 
        'delivery_notice', 
        'delivery_mobile'
    ];
    $updateFields = [];
    $params = [];
    $types = "";

    foreach ($editableFields as $field) {
        if (isset($_POST[$field]) && !empty(trim($_POST[$field]))) {
            $updateFields[] = "$field = ?";
            $params[] = trim($_POST[$field]);
            $types .= "s";
        }
    }
    
    if (!empty($updateFields)) {
        $params[] = $user_id;
        $types .= "i";
        $sql = "UPDATE customers SET " . implode(', ', $updateFields) . " WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $_SESSION['success'] = "Profile updated successfully!";
    } else {
        $_SESSION['error'] = "No valid update information provided.";
    }
    header('Location: profile.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Account Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <style>
        .menu {
            display: none;
        }
        .menu-toggle:checked + .menu {
            display: block;
        }
        
        /* Dropdown visibility */
        .dropdown:hover .dropdown-menu {
            display: block;
        }
        /* Customize hover effects */
        .menu-item:hover {
            color: #00ffea; /* Highlight color */
        }
    </style>
</head>

<body class="bg-gray-50">
    <!-- Menu Bar -->
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
          
    <div class="container mx-auto px-4 py-20">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <!-- Profile Overview Section -->
            <div class="md:col-span-1 bg-white shadow-xl rounded-lg p-6 text-center">
                <?php 
                $profile_pic = $customer['profile_picture'] 
                    ? "../uploads/profiles/" . htmlspecialchars($customer['profile_picture'])
                    : "https://via.placeholder.com/150";
                ?>
                <form method="POST" enctype="multipart/form-data">
                    <div class="relative mx-auto w-40 h-40 mb-4">
                        <img src="<?= $profile_pic ?>" 
                             alt="Profile Picture" 
                             class="w-full h-full rounded-full object-cover border-4 border-blue-100 shadow-md" 
                             id="profile-preview">
                        <label for="profile_picture_upload" class="absolute bottom-0 right-0 bg-blue-500 text-white rounded-full w-10 h-10 flex items-center justify-center cursor-pointer hover:bg-blue-600 transition">
                            <i class="ri-camera-line"></i>
                            <input type="file" 
                                   id="profile_picture_upload" 
                                   name="profile_picture" 
                                   class="hidden" 
                                   accept="image/jpeg,image/png,image/gif,image/webp"
                                   onchange="this.form.submit()">
                        </label>
                    </div>
                </form>
                
                <h2 class="text-2xl font-bold text-gray-800"><?= htmlspecialchars($customer['customer_name']) ?></h2>
                <p class="text-gray-600 mb-2"><?= htmlspecialchars($customer['email']) ?></p>
                <p class="text-gray-600 mb-2"><?= htmlspecialchars($customer['phone']) ?></p>
                <p class="text-gray-500 text-sm">Member since <?= date('F Y', strtotime($customer['created_at'] ?? 'now')) ?></p>
                
                <div class="mt-6 grid grid-cols-3 gap-4 border-t pt-4">
                    <div>
                        <p class="text-lg font-bold text-blue-600"><?= htmlspecialchars($customer['total_orders'] ?? 0) ?></p>
                        <p class="text-xs text-gray-500">Orders</p>
                    </div>
                    <div>
                        <p class="text-lg font-bold text-blue-600"><?= number_format($customer['total_spent'], 2) ?></p>
                        <p class="text-xs text-gray-500">Total Spent TK</p>
                    </div>
                    <div>
                        <p class="text-lg font-bold text-blue-600">
                            <?php 
                            $total_spent = $customer['total_spent'];
                            if ($total_spent < 2000) echo 'Bronze';
                            elseif ($total_spent < 3000) echo 'Silver';
                            else echo 'Gold';
                            ?>
                        </p>
                        <p class="text-xs text-gray-500">Tier</p>
                    </div>
                </div>
            </div>

            <!-- Profile Edit Section -->
            <div class="md:col-span-2 bg-white shadow-xl rounded-lg p-8">
                <!-- Success/Error Messages -->
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="bg-green-50 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert">
                        <p><?= htmlspecialchars($_SESSION['success']) ?></p>
                        <?php unset($_SESSION['success']); ?>
                    </div>
                <?php endif; ?>
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
                        <?= htmlspecialchars($_SESSION['error']) ?>
                        <?php unset($_SESSION['error']); ?>
                    </div>
                <?php endif; ?>
                <img src="../static/logo.png" alt="Logo" class="w-40 h-45  id="profile-preview" style="margin-left: 40%;">
                <h3 class="text-2xl font-semibold text-gray-800 mb-6 text-red-900">Edit Profile</h3>

                <form method="POST" enctype="multipart/form-data" class="space-y-6">
                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-gray-700 mb-2">Full Name</label>
                            <input type="text" 
                                   name="customer_name" 
                                   value="<?= htmlspecialchars($customer['customer_name']) ?>" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500">
                        </div>
                        <div>
                            <label class="block text-gray-700 mb-2">Email Address</label>
                            <input type="email" 
                                   value="<?= htmlspecialchars($customer['email']) ?>" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-100 cursor-not-allowed" 
                                   readonly>
                        </div>
                    </div>

                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-gray-700 mb-2">Delivery Mobile Number</label>
                            <input type="tel" 
                                   name="delivery_mobile" 
                                   value="<?= htmlspecialchars($customer['delivery_mobile'] ?? '') ?>" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500"
                                   pattern="01[0-9]{9}"
                                   title="Please enter a 11-digit phone number">
                        </div>
                        <div>
                            <label class="block text-gray-700 mb-2">Address</label>
                            <input type="text" 
                                   name="address" 
                                   value="<?= htmlspecialchars($customer['address']) ?>" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500">
                        </div>
                    </div>

                    <div>
                        <label class="block text-gray-700 mb-2">Delivery Address</label>
                        <input type="text" 
                               name="delivery_address" 
                               value="<?= htmlspecialchars($customer['delivery_address']) ?>" 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500">
                    </div>

                    <div>
                        <label class="block text-gray-700 mb-2">Delivery Instructions</label>
                        <textarea 
                            name="delivery_notice" 
                            rows="3" 
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500"
                        ><?= htmlspecialchars($customer['delivery_notice']) ?></textarea>
                    </div>

                    <div class="flex items-center justify-between">
                        <a href="../view/forget.html" class="text-blue-600 hover:underline">Change Password</a>
                        <button type="submit" 
                                name="update_profile" 
                                class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-red-700 transition">
                            Update Profile
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Profile picture preview
        document.getElementById('profile_picture_upload').addEventListener('change', function(event) {
            const file = event.target.files[0];
            const reader = new FileReader();
            
            reader.onload = function(e) {
                document.querySelector('.relative img').src = e.target.result;
            }
            
            reader.readAsDataURL(file);
        });
       
       //dropdown menu
         const dropdowns = document.querySelectorAll('.dropdown');
        dropdowns.forEach(dropdown => {
            dropdown.addEventListener('click', function() {
                this.querySelector('.dropdown-menu').classList.toggle('hidden');
            });
        });
    </script>
    <scrip src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js">

       

    </scrip>
</body>
</html>