<?php
// Database connection
require '../db/db.php';

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$hotOffers = $conn->query("SELECT * FROM stock WHERE category='Hot_Offer' AND active=1");
$comboOffers = $conn->query("SELECT * FROM stock WHERE category='Combo' AND active=1");
$meals = $conn->query("SELECT * FROM stock WHERE category='Meal' AND active=1");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Offers Page</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f3f7fa;
            padding: 20px;
        }
        h2 {
            font-size: 1.5rem;
            color: #444;
            text-align: center;
            margin-bottom: 20px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .container {
            display: flex;
            min-height: 80vh;
            border-left: 1px solid #ddd;
            padding-top: 15px;
        }
        .product-section {
            width: 70%;
            padding: 15px;
            background-color: #fff;
        }
        .cart-section {
            width: 25%;
            padding: 10px;
            position: fixed;
            right: 0;
            top: 0;
            height: 100vh;
            overflow-y: auto;
            background-color: #eaf4f8;
            border-left: 2px solid #ddd;
            box-shadow: -2px 0 5px rgba(0, 0, 0, 0.1);
        }
        .cart-section h4 {
            font-size: 1.4rem;
            font-weight: bold;
            text-align: center;
            margin-bottom: 20px;
        }
        .offer-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 16px;
        }
        .offer-card {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            background-color: #fff;
            border: 1px solid #ddd;
            border-radius: 10px;
            padding: 15px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s, box-shadow 0.3s, background-color 0.3s;
        }
        .offer-card:hover {
            background-color: #e0f2ff;
            transform: translateY(-10px) scale(1.03);
            box-shadow: 0 8px 12px rgba(0, 0, 0, 0.15);
        }
        .offer-card img {
            width: 100%;
            height: 120px;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 10px;
        }
        .offer-card h3 {
            font-size: 1.1rem;
            font-weight: bold;
            color: #333;
            margin-bottom: 8px;
        }
        .offer-card span {
            font-size: 0.9rem;
            font-weight: bold;
            color: #007bff;
            display: block;
            margin-top: 8px;
        }
        .btn-primary {
            background-color: #007bff;
            border: none;
            color: #fff;
            font-weight: bold;
            padding: 6px 12px;
            border-radius: 8px;
            transition: background-color 0.3s ease, transform 0.2s;
        }
        .btn-primary:hover {
            background-color: #0056b3;
            transform: translateY(-2px);
        }
        .cart-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
            padding: 10px;
            background-color: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 0.9rem;
        }
        .cart-item img {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 8px;
            margin-right: 8px;
        }
        .cart-controls {
            display: flex;
            align-items: center;
            font-size: 1rem;
        }
        .cart-controls button {
            background-color: transparent;
            border: none;
            font-size: 1.2rem;
            margin: 0 4px;
            color: #007bff;
        }
        .remove-btn {
            color: #ff4d4d;
            font-size: 1.3rem;
            cursor: pointer;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="product-section">
        <!-- Hot Offers Section -->
        <section class="offer-section">
            <h2>Hot Offers</h2>
            <div class="offer-grid">
                <?php while($offer = $hotOffers->fetch_assoc()): ?>
                    <div class="offer-card">
                        <img src="<?php echo $offer['product_image']; ?>" alt="<?php echo $offer['product_name']; ?>">
                        <h3><?php echo $offer['product_name']; ?></h3>
                        <span>Price: <?php echo $offer['price']; ?></span>
                        <button class="btn btn-primary mt-1">Add to Cart</button>
                    </div>
                <?php endwhile; ?>
            </div>
        </section>
        <!-- Combo Offers Section -->
        <section class="offer-section">
            <h2>Combo Offers</h2>
            <div class="offer-grid">
                <?php while($combo = $comboOffers->fetch_assoc()): ?>
                    <div class="offer-card">
                        <img src="<?php echo $combo['product_image']; ?>" alt="<?php echo $combo['product_name']; ?>">
                        <h3><?php echo $combo['product_name']; ?></h3>
                        <span>Price: <?php echo $combo['price']; ?></span>
                        <button class="btn btn-primary mt-1">Add to Cart</button>
                    </div>
                <?php endwhile; ?>
            </div>
        </section>
        <!-- Meals Section -->
        <section class="offer-section">
            <h2>Meals</h2>
            <div class="offer-grid">
                <?php while($meal = $meals->fetch_assoc()): ?>
                    <div class="offer-card">
                        <img src="<?php echo $meal['product_image']; ?>" alt="<?php echo $meal['product_name']; ?>">
                        <h3><?php echo $meal['product_name']; ?></h3>
                        <span>Price: <?php echo $meal['price']; ?></span>
                        <button class="btn btn-primary mt-1">Add to Cart</button>
                    </div>
                <?php endwhile; ?>
            </div>
        </section>
    </div>

    <div class="cart-section">
        <h4>Your Cart</h4>
        <div id="cartItems">
            <p>Your cart is empty.</p>
        </div>
    </div>
</div>
<script>

 document.addEventListener('DOMContentLoaded', () => {
        const addToCartButtons = document.querySelectorAll('.btn-primary');
        const cartContainer = document.getElementById('cartItems');

        addToCartButtons.forEach(button => {
            button.addEventListener('click', function() {
                const card = this.closest('.offer-card');
                const product = {
                    name: card.querySelector('h3').innerText,
                    price: card.querySelector('span').innerText,
                    image: card.querySelector('img').src,
                    quantity: 1,
                };

                let cart = JSON.parse(localStorage.getItem('cart')) || [];
                const existingItem = cart.find(item => item.name === product.name);

                if (existingItem) {
                    existingItem.quantity++;
                } else {
                    cart.push(product);
                }

                localStorage.setItem('cart', JSON.stringify(cart));
                updateCartDisplay();
            });
        });

        function updateCartDisplay() {
            let cart = JSON.parse(localStorage.getItem('cart')) || [];
            cartContainer.innerHTML = '';

            if (cart.length === 0) {
                cartContainer.innerHTML = '<p>Your cart is empty.</p>';
                return;
            }

            cart.forEach(item => {
                const cartItemDiv = document.createElement('div');
                cartItemDiv.className = 'cart-item';
                cartItemDiv.innerHTML = `<img src="${item.image}" alt="${item.name}">
                                         <div>
                                            <h5>${item.name}</h5>
                                            <div class="cart-controls">
                                                <button onclick="changeQuantity('${item.name}', -1)">-</button>
                                                <span>${item.quantity}</span>
                                                <button onclick="changeQuantity('${item.name}', 1)">+</button>
                                            </div>
                                            <span class="remove-btn" onclick="removeItem('${item.name}')">×</span>
                                         </div>`;
                cartContainer.appendChild(cartItemDiv);
            });
        }

        function changeQuantity(name, change) {
            let cart = JSON.parse(localStorage.getItem('cart'));
            const item = cart.find(item => item.name === name);
            item.quantity += change;

            if (item.quantity <= 0) {
                cart = cart.filter(item => item.name !== name);
            }

            localStorage.setItem('cart', JSON.stringify(cart));
            updateCartDisplay();
        }

        function removeItem(name) {
            let cart = JSON.parse(localStorage.getItem('cart'));
            cart = cart.filter(item => item.name !== name);

            localStorage.setItem('cart', JSON.stringify(cart));
            updateCartDisplay();
        }

        updateCartDisplay();
    });
</script>

</body>
</html>
