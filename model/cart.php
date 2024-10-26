<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Cart Page</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f4f4f4;
            padding: 20px;
        }
        .cart-item {
            display: flex;
            align-items: center;
            padding: 15px;
            margin-bottom: 10px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .cart-item img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 5px;
            margin-right: 15px;
        }
        .cart-item h5 {
            margin: 0;
            font-size: 1.1rem;
        }
        .cart-item p {
            margin: 0;
            color: #555;
        }
        .cart-item span {
            color: #333;
            font-weight: bold;
        }
        .quantity-input {
            width: 60px;
            margin-right: 10px;
        }
        .remove-btn {
            color: red;
            cursor: pointer;
        }
        .order-btn {
            margin-top: 20px;
        }
        .cart-item {
            display: flex;
            align-items: center;
            padding: 15px;
            margin-bottom: 10px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .cart-item img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 5px;
            margin-right: 15px;
        }
    </style>
</head>
<body>

<h2>Your Cart</h2>
<div id="cartItems"></div>
<button id="orderNowBtn" class="btn btn-success order-btn" style="display:none;">Order Now</button>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const cart = JSON.parse(localStorage.getItem('cart')) || [];
        const cartContainer = document.getElementById('cartItems');
        const orderNowBtn = document.getElementById('orderNowBtn');

        if (cart.length === 0) {
            cartContainer.innerHTML = '<p>Your cart is empty.</p>';
        } else {
            cart.forEach((item, index) => {
                const cartItem = document.createElement('div');
                cartItem.classList.add('cart-item');
                cartItem.innerHTML = `
                    <img src="${item.image}" alt="${item.name}">
                    <div>
                        <h5>${item.name}</h5>
                        <p>${item.details}</p>
                        <span>${item.price} BDT </span>
                        <input type="number" class="quantity-input" value="${item.quantity}" min="1" data-index="${index}">
                        <span class="remove-btn" data-index="${index}">Remove</span>
                    </div>
                `;
                cartContainer.appendChild(cartItem);
            });

            orderNowBtn.style.display = 'block'; // Show Order Now button

            // Update quantity and remove item functionality
            const quantityInputs = document.querySelectorAll('.quantity-input');
            const removeButtons = document.querySelectorAll('.remove-btn');

            quantityInputs.forEach(input => {
                input.addEventListener('change', (e) => {
                    const index = e.target.dataset.index;
                    const newQuantity = parseInt(e.target.value);
                    cart[index].quantity = newQuantity;
                    localStorage.setItem('cart', JSON.stringify(cart));
                });
            });

            removeButtons.forEach(button => {
                button.addEventListener('click', (e) => {
                    const index = e.target.dataset.index;
                    cart.splice(index, 1); // Remove item from cart
                    localStorage.setItem('cart', JSON.stringify(cart));
                    location.reload(); // Reload the page to reflect changes
                });
            });
        }

        // Order Now button functionality
        orderNowBtn.addEventListener('click', () => {
            alert('Order placed successfully!');
            localStorage.removeItem('cart'); // Clear cart
            location.reload(); // Reload the page
        });
    });
</script>

</body>
</html>
