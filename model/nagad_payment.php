<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Nagad Payment</title>
  <style>
    /* Reset some default styles */
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: Arial, sans-serif;
      background-color: #f0f0f0;
    }

    .container {
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
    }

    .payment-card {
      background-color: #fff;
      border-radius: 16px;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
      width: 100%;
      max-width: 400px;
      padding: 32px;
    }

    .nagad-logo {
      display: block;
      margin: 0 auto 24px;
      height: 80px;
    }

    .payment-info {
      background-color: #ff4b2b;
      border-radius: 12px;
      padding: 24px;
      color: #fff;
      margin-bottom: 24px;
    }

    .payment-info p {
      font-size: 18px;
      font-weight: bold;
      margin-bottom: 8px;
    }

    .form-group {
      margin-bottom: 20px;
    }

    .form-group label {
      display: block;
      font-size: 16px;
      font-weight: bold;
      color: #666;
      margin-bottom: 8px;
    }

    .form-group input {
      width: 100%;
      padding: 12px;
      border: 2px solid #ccc;
      border-radius: 8px;
      font-size: 18px;
    }

    .buttons {
      display: flex;
      justify-content: space-between;
    }

    .buttons button,
    .buttons a {
      flex: 1;
      padding: 14px;
      border: none;
      border-radius: 8px;
      font-size: 18px;
      font-weight: bold;
      cursor: pointer;
      transition: background-color 0.3s ease;
    }

    .buttons button {
      background-color: #ff4b2b;
      color: #fff;
    }

    .buttons button:hover {
      background-color: #e63d1e;
    }

    .buttons a {
      background-color: #fff;
      color: #ff4b2b;
      text-decoration: none;
    }

    .buttons a:hover {
      background-color: #f0f0f0;
    }

    .footer {
      text-align: center;
      color: #ff4b2b;
      font-weight: bold;
      font-size: 18px;
      margin-top: 24px;
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="payment-card">
      <img src="../static/nagad.png" style="height:150px;"alt="Nagad" class="nagad-logo">
      <div class="payment-info">
        <p>City University Canteen</p>
        <p>Marchent Mobile:+880170-000003</p>
        <!-- <p>Amount: BDT 91.84</p> -->
      </div>
      <form action="payment.php" method="POST">
        <input type="hidden" name="payment_method" value="Nagad">
        <div class="form-group">
          <label for="payment_details">Your Nagad Account Number</label>
          <input type="text" id="payment_details" name="payment_details" placeholder="e.g 01XXXXXXXXX" required>
        </div>
        <div class="form-group">
          <label for="deposit_amount">Amount</label>
          <input type="number" id="deposit_amount" name="deposit_amount" placeholder="e.g 1000" required>
        </div>
        <div class="buttons">
          <a href="vpayment.php">CLOSE</a>
          <button type="submit">CONFIRM</button>
        </div>
      </form>
      <p class="footer">16247</p>
    </div>
  </div>

  <script>
    // Phone number validation for Nagad (starts with 017, 018, 019, 016, 015, 013, 014 and is 11 digits long)
    function validatePhone(phone) {
      var reg = /^(017|018|019|016|015|013|014)\d{8}$/;
      return reg.test(phone);
    }

    // Amount validation (minimum amount is 500 TK)
    function validateAmount(amount) {
      return amount >= 500;
    }

    // Form validation
    document.querySelector('form').addEventListener('submit', function(e) {
      var phone = document.querySelector('input[name="payment_details"]').value;
      var amount = document.querySelector('input[name="deposit_amount"]').value;
      if (!validatePhone(phone)) {
        alert('Please enter a valid Nagad account number');
        e.preventDefault();
      } else if (!validateAmount(amount)) {
        alert('Minimum amount is 500 TK');
        e.preventDefault();
      }
    });

    // Redirect to vpayment.php after submission
    document.querySelector('button[type="submit"]').addEventListener('click', function() {
      setTimeout(function() {
        window.location.href = 'vpayment.php';
      }, 1000);
    });

    // Redirect to vpayment.php on close button click
    document.querySelector('.buttons a').addEventListener('click', function() {
      window.location.href = 'vpayment.php';
    });
    //if payment is successful show best alert message and redirect to vpayment.php
    if (window.location.search.includes('success=true')) {
      alert('Payment successful');
      window.location.href = 'vpayment.php';
    }
  </script>
</body>
</html>