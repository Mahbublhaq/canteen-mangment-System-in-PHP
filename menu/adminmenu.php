<?php
session_start();
$admin_id = $_SESSION["user_id"] ?? null;

// Database connection
require_once '../db/db.php';

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Verify admin is logged in
if (!$admin_id) {
    header("Location: ../login.php");
    exit();
}

// Error handling function
function handleDatabaseError($conn, $query) {
    error_log("MySQL Error: " . mysqli_error($conn) . "\nQuery: " . $query);
    return false;
}

// Fetch pending orders count with error handling
$pending_orders_query = "SELECT COUNT(*) as pending_count FROM orders WHERE order_status = 'pending'";
$pending_orders_result = mysqli_query($conn, $pending_orders_query) or handleDatabaseError($conn, $pending_orders_query);
$pending_orders = $pending_orders_result ? mysqli_fetch_assoc($pending_orders_result)['pending_count'] : 0;

// Fetch pending messages count with error handling
$pending_message_query = "SELECT COUNT(*) as pending_count FROM contact_queries WHERE status = 'pending'";
$pending_message_result = mysqli_query($conn, $pending_message_query);
$pending_message = 0;
if ($pending_message_result) {
    $row = mysqli_fetch_assoc($pending_message_result);
    $pending_message = $row['pending_count'];
}

//pending meal_update
$pending_meal_query = "SELECT COUNT(*) as pending_count FROM meal_registration WHERE active=0";
$pending_meal_result = mysqli_query($conn, $pending_meal_query);
$pending_meal = 0;

if ($pending_meal_result) {
    $row = mysqli_fetch_assoc($pending_meal_result);
    $pending_meal = $row['pending_count'];

}







// Fetch admin details with prepared statement
$admin_query = "SELECT name, profile_picture FROM admins WHERE id = ?";
$stmt = mysqli_prepare($conn, $admin_query);
mysqli_stmt_bind_param($stmt, "i", $admin_id);
mysqli_stmt_execute($stmt);
$admin_result = mysqli_stmt_get_result($stmt);
$admin = mysqli_fetch_assoc($admin_result);
mysqli_stmt_close($stmt);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin City Canteen</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome for Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    
    <style>
        :root {
            --bg-primary: #121212;
            --bg-secondary: #1e1e1e;
            --text-primary: #ffffff;
            --text-secondary: #b0b0b0;
            --accent-primary: rgb(204, 166, 251);
            --accent-secondary: #03dac6;
            --error-color: #ff4444;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', 'Segoe UI', sans-serif;
            background-color: var(--bg-primary);
            color: var(--text-primary);
            line-height: 1.6;
            overflow-x: hidden;
        }

        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            height: 100vh;
            width: 280px;
            background-color: var(--bg-secondary);
            padding: 20px;
            transition: transform 0.3s ease;
            z-index: 1000;
            box-shadow: 10px 0 20px rgba(0,0,0,0.2);
            overflow-y: auto;
        }

        .sidebar-toggle {
            position: fixed;
            top: 15px;
            left: 15px;
            z-index: 1100;
            background: var(--accent-primary);
            color: var(--bg-primary);
            border: none;
            padding: 10px 15px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: none;
        }
        .sidebar-toggle:hover {
            background: var(--accent-secondary);
        }
        .admin-profile {
            position: sticky;
            top: 0;
            background: var(--bg-secondary);
            z-index: 10;
            padding: 15px;
            border-radius: 12px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            transition: background 0.3s ease;
        }

        .admin-profile img {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            margin-right: 15px;
            border: 3px solid var(--accent-primary);
        }
        .admin-profile-info {
            flex-grow: 1;
        }

        .admin-profile-info h3 {
            margin: 0;
            font-size: 1.2rem;
            color: var(--accent-primary);
        }

        .admin-profile-info p {
            margin: 0;
            color: var(--text-secondary);
            font-size: 0.9rem;
        }
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: var(--error-color);
            color: var(--text-primary);
            border-radius: 50%;
            padding: 3px 8px;
            font-size: 0.7rem;
            min-width: 20px;
            text-align: center;
            animation: pulse 1.5s infinite;
            box-shadow: 0 0 10px var(--error-color);
        }

        @keyframes pulse {
            0% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.2); opacity: 0.8; }
            100% { transform: scale(1); opacity: 1; }
        }

        .nav-menu {
            list-style: none;
            padding: 0;
        }

        .nav-menu li {
            margin-bottom: 10px;
        }

        .nav-menu a {
            display: flex;
            align-items: center;
            color: var(--text-secondary);
            text-decoration: none;
            padding: 12px 15px;
            border-radius: 10px;
            transition: all 0.3s ease;
            position: relative;
        }

        .nav-menu a:hover {
            background: rgba(255,255,255,0.1);
            color: var(--text-primary);
        }

        .nav-menu a.active {
            background: var(--accent-primary);
            color: var(--bg-primary);
            font-weight: bold;
            box-shadow: 0 0 15px rgba(204, 166, 251, 0.4);
        }

        .nav-menu a i {
            margin-right: 15px;
            font-size: 1.2rem;
            width: 25px;
            text-align: center;
        }
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: var(--error-color);
            color: var(--text-primary);
            border-radius: 50%;
            padding: 3px 8px;
            font-size: 0.7rem;
            min-width: 20px;
            text-align: center;
            animation: pulse 1.5s infinite;
        }
        .logout-btn {
            position: sticky;
            bottom: 0;
            background: var(--bg-secondary);
            padding: 15px;
            margin-top: 25px;
        }

        .logout-btn a {
            display: flex;
            align-items: center;
            color: #ff4444;
            text-decoration: none;
            padding: 12px 15px;
            border-radius: 5px;
            transition: all 0.3s ease;
        }

        .logout-btn a:hover {
            background: rgba(255,68,68,0.1);
        }
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
        .content {
            margin-left: 280px;
            padding: 20px;
            transition: margin-left 0.3s ease;
        }

        @media (max-width: 992px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar-toggle {
                display: block;
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .content {
                margin-left: 0;
            }

            .content.sidebar-active {
                margin-left: 280px;
            }
        }
        

        /* Dark mode scrollbar */
        ::-webkit-scrollbar {
            width: 12px;
        }

        ::-webkit-scrollbar-track {
            background: var(--bg-secondary);
        }

        ::-webkit-scrollbar-thumb {
            background: var(--accent-primary);
            border-radius: 6px;
        }

        h1 {
            font-family: 'Arial', sans-serif;
    font-size: 3rem;
    color: #00ffea;
    text-align: center;
    text-transform: uppercase;
    letter-spacing: 3px;
    animation: glow 2s infinite alternate, float 4s infinite ease-in-out;
    background: linear-gradient(90deg, #00ffea, #ff00ff, #00ffea);
    background-size: 200% 200%;
    -webkit-background-clip: text;
    color: transparent;
    position: relative;
    overflow: hidden;
    margin-left: 10%;
    margin-top:2%;
        }

        @keyframes glow {
    from {
        text-shadow: 0 0 10px #00ffea, 0 0 20px #00ffea, 0 0 30px #00ffea;
    }
    to {
        text-shadow: 0 0 20px #ff00ff, 0 0 30px #ff00ff, 0 0 40px #ff00ff;
    }
}

@keyframes float {
    0%, 100% {
        transform: translateY(0);
    }
    50% {
        transform: translateY(-10px);
    }
}
    </style>
</head>
<body>
    <h1>City Canteen Admin Portal</h1>
    
    <!-- Sidebar Toggle Button -->
    <button class="sidebar-toggle" aria-label="Toggle Sidebar">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Sidebar Navigation -->
    <aside class="sidebar">
        <!-- Admin Profile -->
        <div class="admin-profile">
            <?php if(!empty($admin['profile_picture'])): ?>
                <img src="<?php echo htmlspecialchars($admin['profile_picture']); ?>" alt="Admin Profile">
            <?php else: ?>
                <img src="default-avatar.png" alt="Default Profile">
            <?php endif; ?>
            <div class="admin-profile-info">
                <h3><?php echo htmlspecialchars($admin['name']); ?></h3>
                <p>Administrator</p>
            </div>
        </div>

        <!-- Navigation Menu -->
        <ul class="nav-menu">
            <li>
                <a href="admin.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'admin.php') ? 'active' : ''; ?>">
                    <i class="fa fa-home"></i>
                    Home
                </a>
            </li>
            <li>
                <a href="admin_order.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'admin_order.php') ? 'active' : ''; ?>">
                <i class="fas fa-shopping-cart"></i> Make Order's
                   
                </a>
            </li>
            
            <li>
                <a href="orders.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'orders.php') ? 'active' : ''; ?>">
                    <i class="fas fa-clipboard-list"></i>
                    Orders
                    <?php if($pending_orders > 0): ?>
                        <span class="notification-badge"><?php echo $pending_orders; ?></span>
                    <?php endif; ?>
                </a>
            </li>
             <li>
                <a href="message_management.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'message_management.php') ? 'active' : ''; ?>">
                    <i class="fa-solid fa-message"></i>
                    Messages
                    <?php if($pending_message > 0): ?>
                        <span class="notification-badge"><?php echo $pending_message; ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li>
                <a href="order_success.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'order_success.php') ? 'active' : ''; ?>">
                    <i class="fas fa-check-circle"></i>
                    Successful Orders
                </a>
            </li>
            <li>
                <a href="meal-sheet.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'meal-sheet.php') ? 'active' : ''; ?>">
                    <i class="fas fa-utensils"></i>
                    Meal Sheet
                </a>
            </li>
            <li>
                <a href="meal_update.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'meal_update.php') ? 'active' : ''; ?>">
                    <i class="fas fa-edit"></i>
                    Update Meals Info
                    <?php if($pending_meal > 0): ?>
                        <span class="notification-badge"><?php echo $pending_meal; ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li>
                <a href="add_product.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'add_product.php') ? 'active' : ''; ?>">
                    <i class="fas fa-plus-circle"></i>
                    Add Product
                </a>
            </li>
            <li>
                <a href="active_status.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'active_status.php') ? 'active' : ''; ?>">
                    <i class="fas fa-chart-pie"></i>
                    Product Status
                </a>
            </li>
            <li>
                <a href="create_offer.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'create_offer.php') ? 'active' : ''; ?>">
                    <i class="fa fa-gift"></i>
                    Offers
                </a>
            </li>
           
            <li>
                <a href="../model/admin_profile.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'admin_profile.php') ? 'active' : ''; ?>">
                    <i class="fa fa-user"></i>
                    Profile
                </a>
            </li>
        </ul>

        <!-- Logout Button (Fixed at bottom) -->
        <div class="logout-btn">
            <a href="../model/logout.php">
                <i class="fas fa-sign-out-alt"></i>
                Logout
            </a>
        </div>
    </aside>
    
    <!-- Main Content Area -->
    <main class="content">
        <!-- Page content goes here -->
    </main>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Sidebar Toggle Functionality
        document.querySelector('.sidebar-toggle').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('active');
            document.querySelector('.content').classList.toggle('sidebar-active');
        });

        // Active Page Highlighting
        const currentLocation = location.pathname.split('/').pop();
        const navLinks = document.querySelectorAll('.nav-menu a');
        navLinks.forEach(link => {
            if(link.getAttribute('href') === currentLocation) {
                
                link.classList.add('active');
            }
        });
        


        
    </script>
</body>
</html>