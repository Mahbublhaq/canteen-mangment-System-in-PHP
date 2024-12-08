<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Card Payment</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f4f6f9;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            line-height: 1.6;
        }
        .payment-container {
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 450px;
            padding: 30px;
        }
        .payment-header {
            text-align: center;
            margin-bottom: 25px;
            color: #2c3e50;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #34495e;
            font-weight: 600;
        }
        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e6ed;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }
        .form-group input:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
        }
        .pay-button {
            width: 100%;
            padding: 15px;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s ease;
        }
        .pay-button:hover {
            background-color: #2980b9;
        }
        .pay-button:active {
            transform: scale(0.98);
        }
        .card-icon {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #bdc3c7;
        }
        .input-wrapper {
            position: relative;
        }
        @media (max-width: 480px) {
            .payment-container {
                width: 95%;
                padding: 20px;
            }
        }
    </style>
    <script>
        // Basic client-side validation
        function validateForm() {
            const cardNumber = document.querySelector('input[name="payment_details"]');
            const nameOnCard = document.querySelector('input[placeholder="Ex. John Doe"]');
            const amount = document.querySelector('input[name="deposit_amount"]');

            // Card number validation (simple check for 16 digits)
            if (!/^\d{4}\s\d{4}\s\d{4}\s\d{4}$/.test(cardNumber.value)) {
                alert('Please enter a valid card number in the format: XXXX XXXX XXXX XXXX');
                return false;
            }

            // Name validation (at least two words)
            if (!/^[A-Za-z]+\s[A-Za-z]+$/.test(nameOnCard.value)) {
                alert('Please enter a valid name (First Last)');
                return false;
            }

            // Amount validation minimun 500 tk 
            if (amount.value < 500) {
                alert('Minimum amount is 500 TK');
                return false;
            }

            // if payment successful go to vpayment.php page aler messge 24 px font size green and make sure  go back vpayment.php
            alert('Payment successful');
            window.location.href = 'vpayment.php';

        


          









            return true;
        }
    </script>
</head>
<body>
    
    <div class="payment-container">
        <form action="payment.php" method="POST" onsubmit="return validateForm()">
            <div class="payment-header">
                <h1>Card Payment</h1>
            </div>
            
            <div class="form-group">
                <label for="card_number">Card Number</label>
                <div class="input-wrapper">
                    <input 
                        type="text" 
                        name="payment_details" 
                        placeholder="1234 5678 9012 3456" 
                        pattern="\d{4}\s\d{4}\s\d{4}\s\d{4}"
                        maxlength="19"
                        required
                    >
                    <span class="card-icon">💳</span>
                </div>
            </div>
            
            <div class="form-group">
                <label for="name_on_card">Name on Card</label>
                <input 
                    type="text" 
                    name="name_on_card"
                    placeholder="Ex. John Doe" 
                    required
                >
            </div>
            
            <div class="form-group">
                <label for="amount">Amount</label>
                <input 
                    type="number" 
                    name="deposit_amount" 
                    placeholder="Enter payment amount"
                    min="0.01" 
                    step="0.01"
                    required
                >
            </div>
            
            <input type="hidden" name="payment_method" value="Card">
            
            <button type="submit" class="pay-button">Pay Now</button>
        </form>
    </div>
</body>
</html>