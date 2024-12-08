<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require '../db/db.php';

// Fetch customer name from the database if session exists
$customer_name = isset($_SESSION['customer_name']) ? $_SESSION['customer_name'] : 'Guest';
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
    <title>City University Canteen</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <!-- Bootstrap CSS -->
<link href="https://stackpath.bootstrapcdn.com/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
<!-- Font Awesome (for icons) -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
<!-- Bootstrap CSS -->
<link href="https://stackpath.bootstrapcdn.com/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
<!-- Font Awesome (for icons) -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">


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
            background-color: Crimson;
            color:#F0FFFF;
            padding: 10px 20px;
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
            color: black;
            background-color: #00FFEA;
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
            font-size: 16px;
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

        /* Logout Icon */
        .logout-icon {
            color: #f1f1f1;
            font-size: 24px;
            margin-left: 10px;
            transition: color 0.3s ease;
            margin-top: 5px;
        }

        .logout-icon:hover {
            color: yellow;
        }


        /* Category Dropdown */
        .dropdown-menu {
            background-color: Crimson;
            border: none;
            border-radius: 0;
        }

        .dropdown-item {
            color: #ffffff;
            font-size: 16px;
            transition: color 0.3s ease;
        }

        .dropdown-item:hover {
            color: #00FFEA;
            background-color: #2C3E50;
        }

        /* Cart and Profile Icons */
        .navbar-nav {
            display: flex;
            align-items: center;
        }

        .navbar-nav .nav-link {
            margin: 0 10px;
        }

        /* Cart and Profile Icons */




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
    <li class="nav-item"><a class="nav-link icon-animated" href="/model/welcome.php"><i class="fas fa-home"></i> Home</a></li>

    <!-- Categories Dropdown -->
    <li class="nav-item dropdown" id="categoriesDropdown">
        <a class="nav-link dropdown-toggle" href="#" role="button" aria-expanded="false">
            <i class="fas fa-th-large"></i> Categories
        </a>
        <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="/model/hotoffer.php">Hot Offer</a></li>
            <li><a class="dropdown-item" href="/model/combo.php">Combo Offer</a></li>
            <li><a class="dropdown-item" href="/model/meal.php">Meal</a></li>

        </ul>
    </li>

    <li class="nav-item"><a class="nav-link icon-animated" href="/model/offer.php"><i class="fas fa-percent"></i> Offers</a></li>
    <!--Meal Registration-->
   
    <li class="nav-item"><a class="nav-link icon-animated" href="/model/meal_signup.php"><i class="fas fa-utensils"></i> Meal Registration</a></li>
    <li class="nav-item"><a class="nav-link icon-animated" href="../model/contact.php"><i class="fas fa-phone-alt"></i> Contact</a></li>
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
        <span class="customer-name"><?php echo htmlspecialchars($customer_name); ?>
                    
    </span>
    
    </a>
</li>
<a href="/model/logout.php" class="logout-icon" title="Logout">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                </li>
            </li>


        </ul>
    </div>
</nav>

<!-- jQuery and Bootstrap JS -->
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.0/umd/popper.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

<script>

document.getElementById("categoriesDropdown").addEventListener("click", function(event) {
    // Check if the click is on the dropdown toggle, not on links inside the menu
    if (event.target === this.querySelector("a.nav-link.dropdown-toggle")) {
        event.preventDefault(); // Prevent navigation for the toggle only
        const dropdownMenu = this.querySelector(".dropdown-menu");
        dropdownMenu.classList.toggle("show"); // Toggle dropdown visibility
    }
});

// Close dropdown when clicking outside
document.addEventListener("click", function(event) {
    const dropdownMenu = document.querySelector(".dropdown-menu.show");
    if (dropdownMenu && !event.target.closest("#categoriesDropdown")) {
        dropdownMenu.classList.remove("show");
    }
});

    //logout button hide if not login , show if login
    <?php if (!isset($_SESSION['user_id'])): ?>
    $('.logout-icon').hide();
    <?php endif; ?>




    //when click logout button show alert
    $('.logout-icon').click(function () {
        alert('You have been logged out successfully.');
    });
 //search bar 
    $('.search-bar input').focus(function () {
        $(this).animate({ width: '400px' }, 500);
    });

    $('.search-bar input').blur(function () {
        $(this).animate({ width: '300px' }, 500);
    });

    //seaech with alphabet match alphabet in red color
    $('.search-bar input').keyup(function () {
        var searchText = $(this).val().toLowerCase();
        var searchLength = searchText.length;

        $('.product-card').each(function () {
            var productName = $(this).find('.product-title').text().toLowerCase();
            var productDescription = $(this).find('.product-description').text().toLowerCase();

            if (productName.indexOf(searchText) > -1 || productDescription.indexOf(searchText) > -1) {
                var regex = new RegExp(searchText, 'gi');
                var highlightedName = productName.replace(regex, function (match) {
                    return '<span style="color: red;">' + match + '</span>';
                });
                var highlightedDescription = productDescription.replace(regex, function (match) {
                    return '<span style="color: red;">' + match + '</span>';
                });

                $(this).show();
                $(this).find('.product-title').html(highlightedName);
                $(this).find('.product-description').html(highlightedDescription);
            } else {
                $(this).hide();
            }
        });
    });




</script>

</body>
</html>

