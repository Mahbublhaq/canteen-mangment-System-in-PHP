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
    <title>City Canteen - Your Premium Food Destination</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.carousel.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.theme.default.min.css" rel="stylesheet">
    <style>
        /* Modern Variables */
        :root {
            --primary-color: #ff4757;
            --secondary-color: #2f3542;
            --accent-color: #ffa502;
            --text-color: #2f3542;
            --light-gray: #f1f2f6;
            --white: #ffffff;
            --shadow: 0 5px 15px rgba(0,0,0,0.1);
            --transition: all 0.3s ease;
        }

        /* Base Styles */
        body {
            font-family: 'Poppins', sans-serif;
            color: var(--text-color);
            overflow-x: hidden;
        }

        /* Enhanced Hero Section */
        .hero-section {
            position: relative;
            min-height: 60vh; /* Reduced height */
            display: flex;
            align-items: center;
            padding: 0;
            margin-bottom: 4rem;
            overflow: hidden;
        }

        .hero-slideshow {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1;
        }

        .slide {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            transition: opacity 1s ease-in-out;
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }

        .slide.active {
            opacity: 1;
        }

        .slide::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(0,0,0,0.8), rgba(0,0,0,0.6));
        }

        .hero-content {
            position: relative;
            z-index: 2;
            text-align: center;
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem;
        }

        .hero-title {
            font-size: 3.5rem; /* Reduced size */
            font-weight: 800;
            color: var(--white);
            margin-bottom: 1rem;
            animation: fadeInDown 1s ease;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }

        .hero-subtitle {
            font-size: 1.5rem; /* Reduced size */
            color: var(--white);
            margin-bottom: 2rem;
            animation: fadeInUp 1s ease 0.3s;
            opacity: 0;
            animation-fill-mode: forwards;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.3);
        }

        /* Slide Indicators */
        .slide-indicators {
            position: absolute;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 10px;
            z-index: 3;
        }

        .indicator {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.5);
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .indicator.active {
            background: var(--primary-color);
            transform: scale(1.2);
        }

        /* New Animations */
        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .hero-section {
                min-height: 50vh;
            }
            
            .hero-title {
                font-size: 2.5rem;
            }
            
            .hero-subtitle {
                font-size: 1.2rem;
            }
        }
        /* Enhanced Product Cards */
        .product-card {
            position: relative;
            border: none;
            border-radius: 20px;
            overflow: hidden;
            background: var(--white);
            box-shadow: var(--shadow);
            transition: var(--transition);
            margin-bottom: 2.5rem;
        }

        .product-card:hover {
            transform: translateY(-15px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.15);
        }

        .product-image {
            height: 250px;
            object-fit: cover;
            transition: var(--transition);
        }

        .product-card:hover .product-image {
            transform: scale(1.1);
        }

        .product-content {
            padding: 2rem;
            background: linear-gradient(to bottom, transparent, rgba(255,255,255,0.9) 10%, white);
            margin-top: -60px;
            position: relative;
            z-index: 2;
        }

        .product-title {
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--secondary-color);
            margin-bottom: 1rem;
        }

        .product-details {
            font-size: 1rem;
            color: #666;
            margin-bottom: 1.5rem;
            line-height: 1.6;
        }

        .product-price {
            font-size: 1.6rem;
            color: var(--primary-color);
            font-weight: 800;
            margin-bottom: 1.5rem;
        }

        .add-to-cart-btn {
            background: linear-gradient(45deg, var(--primary-color), var(--accent-color));
            color: var(--white);
            border: none;
            padding: 1rem 2rem;
            border-radius: 50px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: var(--transition);
            width: 100%;
        }

        .add-to-cart-btn:hover {
            background: linear-gradient(45deg, var(--accent-color), var(--primary-color));
            transform: translateY(-3px);
        }

        /* Enhanced Section Titles */
        .section-title {
            text-align: center;
            margin-bottom: 4rem;
            position: relative;
            padding-bottom: 2rem;
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--secondary-color);
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 5px;
            background: linear-gradient(45deg, var(--primary-color), var(--accent-color));
            border-radius: 50px;
        }

        /* Enhanced Marquee */
        .marquee-container {
            background: linear-gradient(45deg, var(--primary-color), var(--accent-color));
            padding: 1.2rem 0;
            margin-bottom: 3rem;
            position: relative;
            overflow: hidden;
        }

        .marquee-text {
            color: var(--white);
            font-size: 1.3rem;
            font-weight: 600;
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 2px;
            animation: marquee 20s linear infinite;
            white-space: nowrap;
        }

        /* Modern Footer */
        .footer {
            background: var(--secondary-color);
            color: var(--white);
            padding: 5rem 0 2rem;
            margin-top: 6rem;
            position: relative;
        }

        .footer::before {
            content: '';
            position: absolute;
            top: -50px;
            left: 0;
            right: 0;
            height: 50px;
            background: linear-gradient(45deg, var(--primary-color), var(--accent-color));
            clip-path: polygon(0 0, 100% 100%, 100% 100%, 0% 100%);
        }

        .footer-title {
            font-size: 1.4rem;
            font-weight: 700;
            margin-bottom: 2rem;
            color: var(--primary-color);
            position: relative;
            padding-bottom: 1rem;
        }

        .footer-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 50px;
            height: 3px;
            background: var(--accent-color);
        }

        .footer-links {
            list-style: none;
            padding: 0;
        }

        .footer-links li {
            margin-bottom: 1rem;
        }

        .footer-links a {
            color: #ddd;
            text-decoration: none;
            transition: var(--transition);
            display: flex;
            align-items: center;
        }

        .footer-links a i {
            margin-right: 10px;
            color: var(--primary-color);
        }

        .footer-links a:hover {
            color: var(--primary-color);
            transform: translateX(10px);
        }

        .social-links {
            display: flex;
            gap: 1.5rem;
        }

        .social-links a {
            color: var(--white);
            background: rgba(255,255,255,0.1);
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: var(--transition);
        }

        .social-links a:hover {
            background: var(--primary-color);
            transform: translateY(-5px);
        }

        /* Advanced Animations */
        @keyframes slideInLeft {
            from {
                opacity: 0;
                transform: translateX(-100px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(100px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes marquee {
            0% { transform: translateX(100%); }
            100% { transform: translateX(-100%); }
        }

        /* Enhanced Carousel Navigation */
        .owl-carousel .owl-nav button {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: var(--primary-color) !important;
            color: var(--white) !important;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            transition: var(--transition);
            box-shadow: var(--shadow);
        }

        .owl-carousel .owl-nav button:hover {
            background: var(--accent-color) !important;
            transform: translateY(-50%) scale(1.1);
        }

        .owl-carousel .owl-nav button.owl-prev {
            left: -25px;
        }

        .owl-carousel .owl-nav button.owl-next {
            right: -25px;
        }

        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .hero-title {
                font-size: 3rem;
            }
            
            .hero-subtitle {
                font-size: 1.4rem;
            }
            
            .section-title {
                font-size: 2rem;
            }
        }
        
    </style>
</head>
<body>

<!-- Include your existing menu.php here -->
<?php include '../menu/menu.php'; ?>

<!-- Hero Section -->
<section class="hero-section">
    <div class="hero-slideshow">
        <!-- Background slides -->
        <div class="slide active" style="background-image: url('../static/c11.jpg')"></div>
        <div class="slide" style="background-image: url('../static/c2.jpeg')"></div>
        <div class="slide" style="background-image: url('../static/c1.jpg')"></div>
        <div class="slide" style="background-image: url('../static/c2.jpg')"></div>
    </div>
    <div class="hero-content">
        <h1 class="hero-title">Welcome to City University Canteen</h1>
        <p class="hero-subtitle">Discover Delicious Meals and Amazing Offers</p>
    </div>
    <div class="slide-indicators">
        <span class="indicator active"></span>
        <span class="indicator"></span>
        <span class="indicator"></span>
        <span class="indicator"></span>
    </div>
</section>

<!-- Marquee -->
<div class="marquee-container">
    <div class="marquee-text">
        Welcome To City Canteen || 🎉 Discount Starts Now ✨ Use Coupon Code!
    </div>
</div>

<div class="container">
    <!-- Hot Offers Section -->
    <h2 class="section-title">Hot Offers</h2>
    <div class="owl-carousel hot-offers-carousel">
        <?php if ($hotOffers && $hotOffers->num_rows > 0): ?>
            <?php while ($row = $hotOffers->fetch_assoc()): ?>
                <div class="product-card">
                    <?php if (!empty($row['product_image']) && file_exists('../uploads/' . $row['product_image'])): ?>
                        <img src="../uploads/<?= htmlspecialchars($row['product_image']) ?>" class="product-image" alt="<?= htmlspecialchars($row['product_name']) ?>">
                    <?php else: ?>
                        <img src="/api/placeholder/500/500" class="product-image" alt="No Image Available">
                    <?php endif; ?>
                    <div class="product-content">
                        <h5 class="product-title"><?= htmlspecialchars($row['product_name']) ?></h5>
                        <p class="product-details"><?= htmlspecialchars($row['product_details']) ?></p>
                        <p class="product-price">BDT <?= htmlspecialchars($row['price']) ?></p>
                        <form method="post" action="welcome.php">
                            <input type="hidden" name="product_id" value="<?= htmlspecialchars($row['id']) ?>">
                            <button type="submit" name="add_to_cart" class="add-to-cart-btn">
                                <i class="fas fa-shopping-cart"></i> Add to Cart
                            </button>
                        </form>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php endif; ?>
    </div>

    <!-- Rest of your sections (Combo, Meal) with the same enhanced card design -->
   

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
</div>

<!-- Footer -->
<footer class="footer">
    <div class="container">
        <div class="row">
            <div class="col-md-3">
                <h4 class="footer-title">About Us</h4>
                <p>City Canteen provides quality food and excellent service to our university community.</p>
            </div>
            <div class="col-md-3">
                <h4 class="footer-title">Quick Links</h4>
                <ul class="footer-links">
                    <li><a href="#">Home</a></li>
                    <li><a href="#">Menu</a></li>
                    <li><a href="#">Special Offers</a></li>
                    <li><a href="#">Contact Us</a></li>
                </ul>
            </div>
            <div class="col-md-3">
                <h4 class="footer-title">Contact Info</h4>
                <ul class="footer-links">
                    <li><i class="fas fa-map-marker-alt"></i> University Campus</li>
                    <li><i class="fas fa-phone"></i> +880 1234567890</li>
                    <li><i class="fas fa-envelope"></i> info@citycanteen.com</li>
                </ul>
            </div>
            <div class="col-md-3">
                <h4 class="footer-title">Follow Us</h4>
                <div class="social-links">
                    <a href="#"><i class="fab fa-facebook"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-linkedin"></i></a>
                </div>
            </div>
        </div>
        <div class="text-center mt-4">
            <p>&copy; 2025 City Canteen. All rights reserved.</p>
        </div>
    </div>
</footer>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/owl.carousel.min.js"></script>
<script>
    // Enhanced carousel initialization
    $(document).ready(function(){
        $('.hot-offers-carousel').owlCarousel({
            loop: true,
            margin: 30,
            nav: true,
            dots: false,
            autoplay: true,
            autoplayTimeout: 5000,
            smartSpeed: 1000,
            responsive:{
                0: { items: 1 },
                768: { items: 2 },
                992: { items: 3 },
                1200: { items: 4 }
            }
        });
    });
    document.addEventListener('DOMContentLoaded', function() {
    const slides = document.querySelectorAll('.slide');
    const indicators = document.querySelectorAll('.indicator');
    let currentSlide = 0;
    
    function showSlide(index) {
        // Remove active class from all slides and indicators
        slides.forEach(slide => slide.classList.remove('active'));
        indicators.forEach(indicator => indicator.classList.remove('active'));
        
        // Add active class to current slide and indicator
        slides[index].classList.add('active');
        indicators[index].classList.add('active');
    }
    
    function nextSlide() {
        currentSlide = (currentSlide + 1) % slides.length;
        showSlide(currentSlide);
    }
    
    // Add click events to indicators
    indicators.forEach((indicator, index) => {
        indicator.addEventListener('click', () => {
            currentSlide = index;
            showSlide(currentSlide);
        });
    });
    
    // Auto advance slides
    setInterval(nextSlide, 3000);
});
</script>
</body>
</html>