<?php
//session_start();
require '../db/db.php';

// Fetch customer name from the database if session exists
$customer_name = "";
if (isset($_SESSION['user_id'])) {
    $customer_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT customer_name FROM customers WHERE id = ?");
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $stmt->bind_result($customer_name);
    $stmt->fetch();
    $stmt->close();
}

// Get cart product count
$cart_count = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;

// Check if user_id is set in the session (user is logged in)
$profile_link = isset($_SESSION['user_id']) ? "/model/profile.php" : "/view/login.html";

$conn->close();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-commerce Menu Bar</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        /* General Styles */
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f7fc;
        }

        /* Navbar Styles */
        .navbar {
            background-color: #2C3E50;
            padding: 15px 20px;
            position: sticky;
            top: 0;
            width: 100%;
            z-index: 10;
        }

        .navbar-brand {
            color: #00FFEA;
            font-size: 28px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.3s ease;
        }

        .navbar-brand:hover {
            color: #18d4d9;
        }

        .navbar-nav .nav-link {
            color: #ffffff;
            font-size: 18px;
            margin: 0 15px;
            font-weight: bold;
            text-transform: capitalize;
            padding: 10px;
            transition: color 0.3s ease, transform 0.3s ease;
        }

        .navbar-nav .nav-link:hover {
            color: #00FFEA;
            transform: scale(1.1);
        }

        /* Search Bar Styles */
        .search-bar input {
            width: 300px;
            padding: 10px;
            font-size: 16px;
            border-radius: 25px;
            border: 2px solid #ccc;
            transition: border-color 0.3s ease;
        }

        .search-bar input:focus {
            border-color: #00FFEA;
            outline: none;
        }

        .search-bar i {
            position: absolute;
            right: 20px;
            top: 12px;
            color: #00FFEA;
        }

        /* Cart notification */
        .cart-notify {
            position: relative;
            font-size: 16px;
            color: #fff;
            background-color: red;
            border-radius: 50%;
            padding: 5px 10px;
            top: -10px;
            right: -10px;
        }

        /* Profile icon and Customer name */
        .profile-icon {
            position: relative;
            font-size: 24px;
            color: #fff;
        }

        .customer-name {
            color: #00FFEA;
            font-weight: bold;
            font-size: 18px;
            margin-left: 10px;
            transition: color 0.3s ease;
        }

        .customer-name:hover {
            color: #f1f1f1;
        }

        /* Icon Animation */
        .icon-animated:hover {
            color: #00FFEA;
            transform: scale(1.2);
            transition: all 0.3s ease-in-out;
        }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark">
    <a class="navbar-brand" href="#">
        <img src="/static/logo.png" alt="Shop Logo" style="height: 80px;">
    </a>

    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarContent">
        <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarContent">
        <!-- Navbar Links -->
        <ul class="navbar-nav ml-auto">
            <li class="nav-item"><a class="nav-link icon-animated" href="#"><i class="fas fa-home"></i> Home</a></li>
            <li class="nav-item"><a class="nav-link icon-animated" href="#"><i class="fas fa-th-large"></i> Categories</a></li>
            <li class="nav-item"><a class="nav-link icon-animated" href="#"><i class="fas fa-percent"></i> Offers</a></li>
            <li class="nav-item"><a class="nav-link icon-animated" href="#"><i class="fas fa-info-circle"></i> About Us</a></li>
            <li class="nav-item"><a class="nav-link icon-animated" href="#"><i class="fas fa-phone-alt"></i> Contact</a></li>
        </ul>

        <!-- Search Bar -->
        <form class="search-bar mx-3 position-relative">
            <input type="text" placeholder="Search products..." class="form-control">
            <i class="fas fa-search"></i>
        </form>

        <!-- Cart and Profile Icons -->
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link icon-animated" href="/model/cart.php">
                    <i class="fas fa-shopping-cart"></i> Cart
                    <?php if ($cart_count > 0): ?>
                        <span class="cart-notify"><?php echo $cart_count; ?></span>
                    <?php endif; ?>
                </a>
                <li class="nav-item">
    <a class="nav-link icon-animated" href="<?php echo $profile_link; ?>">
        <i class="fas fa-user profile-icon"></i>
        <span class="customer-name"><?php echo htmlspecialchars($customer_name); ?></span>
    </a>
</li>
        </ul>
    </div>
</nav>

<!-- jQuery and Bootstrap JS -->
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.0/umd/popper.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

</body>
</html>
