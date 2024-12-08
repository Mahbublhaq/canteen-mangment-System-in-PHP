<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Bkash Payment</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.16/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 flex justify-center items-center h-screen">
  <div class="bg-white rounded-lg shadow-lg w-full max-w-md p-8">
    <div class="flex justify-center mb-6">
      <img src="../static/bkash.png" style="height:100px" alt="bKash" class="h-12">
    </div>
    <div class="bg-pink-500 rounded-lg p-6 mb-6">
      <h1 class="text-white text-2xl font-medium mb-4">bKash Payment</h1>
      <p class="text-gray-200 font-medium mb-4">City University Canteen</p>
      <p class="text-gray-200 font-medium mb-4">Mobile: +8801710-000003</p>
      
      <!-- Payment Form -->
      <form action="payment.php" method="POST">
        <!-- Hidden Payment Method -->
        <input type="hidden" name="payment_method" value="Bkash">

        <!-- bKash Account Number -->
        <div class="bg-white rounded-lg p-4 mb-4">
          <p class="text-pink-500 font-medium mb-2">Your bKash Account Number</p>
          <input 
            type="text" 
            name="payment_details" 
            class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" 
            placeholder="e.g 01XXXXXXXXX" 
            required>
        </div>

        <!-- Deposit Amount -->
        <div class="bg-white rounded-lg p-4 mb-4">
          <p class="text-pink-500 font-medium mb-2">Amount</p>
          <input 
            type="number" 
            name="deposit_amount" 
            class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" 
            placeholder="e.g 1000" 
            required>
        </div>

        <!-- Buttons -->
        <div class="flex justify-between">
          <a href="vpayment.php" 
             class="bg-white text-pink-500 font-medium py-2 px-4 rounded-md hover:bg-pink-100 focus:outline-none focus:ring-2 focus:ring-pink-500 focus:ring-offset-2">
            CLOSE
          </a>
          <button 
            type="submit" 
            class="bg-white text-pink-500 font-medium py-2 px-4 rounded-md hover:bg-pink-100 focus:outline-none focus:ring-2 focus:ring-pink-500 focus:ring-offset-2">
            CONFIRM
          </button>
        </div>
      </form>
    </div>
    <p class="text-center text-pink-500 font-medium">16247</p>
  </div>
  <script>
    //phone number validation for bKash start with 017,018,019,016,015,013,014 and 11 degit long or wrong number
    function validatePhone(phone) {
      var reg = /^(017|018|019|016|015|013|014)\d{8}$/;
      return reg.test(phone);
    }
    //amount validation minimum ammount 500 tk
    function validateAmount(amount) {
      return amount >= 500;
    }
    //form validation
    document.querySelector('form').addEventListener('submit', function(e) {
      var phone = document.querySelector('input[name="payment_details"]').value;
      var amount = document.querySelector('input[name="deposit_amount"]').value;
      if (!validatePhone(phone)) {
        alert('Please enter a valid bKash account number');
        e.preventDefault();
      } else if (!validateAmount(amount)) {
        alert('Minimum amount is 500 TK');
        e.preventDefault();
      }
    });

    //after submit go back vpayment.php
    document.querySelector('button[type="submit"]').addEventListener('click', function() {
      setTimeout(function() {
        window.location.href = 'vpayment.php';
      }, 1000);
    });

    //close button go back vpayment.php
    document.querySelector('a').addEventListener('click', function() {
      window.location.href = 'vpayment.php';
    });
    
  </script>
</body>

</html>
