<?php
session_start();
include '../db/db.php'; // Include the database connection
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-image: url('background.jpg'); /* Optional: Replace with your background image path */
            background-size: cover;
            background-position: center;
            font-family: Arial, sans-serif;
            margin: 0;
            display: flex;
            height: 100vh;
            color: #333;
        }

        .sidebar {
            width: 220px;
            background-color: #343a40;
            color: #fff;
            padding-top: 20px;
            position: fixed;
            height: 100vh;
            top: 0;
            left: 0;
        }

        .sidebar h3 {
            text-align: center;
            font-weight: bold;
            margin-bottom: 1.5rem;
        }

        .sidebar a {
            color: #ddd;
            padding: 12px 20px;
            display: block;
            text-decoration: none;
        }

        .sidebar a:hover {
            background-color: #495057;
            color: #fff;
        }

        .main-content {
            margin-left: 220px;
            padding: 20px;
            width: calc(100% - 220px);
        }

        .header {
            top: 0;
            color: white;
            background-color: #000040;
            font-size: 3rem;
            font-weight: bold;
            text-align: center;
            margin-bottom: 40px;
        }

        

        /* Today Sales Box */
        .today-sales {
            position: absolute;
            top: 12%;
            left: 20%;
            
            color: #fff;
            padding: 10px 20px;
            
           
            transition: background-color 0.3s ease;
        }
        .monthly-sales {
            position: absolute;
            top: 12%;
            left: 35%;
            
            color: #fff;
            padding: 10px 20px;
            
           
            transition: background-color 0.3s ease;
        }
       
    </style>
</head>

<body>

    <!-- Sidebar Menu -->
    <div class="sidebar">
        <h3>Admin Panel</h3>
        <a href="/model/orders.php">Orders</a>
        <a href="successful_orders.php">Successful Orders</a>
        <a href="meals.php">Meals</a>
        <a href="/model/meal_update.php">Update Meals Info</a>
        <a href="/view/add_product.html">Add Product</a>
        <a href="/model/active_status.php">Product Status</a>
    </div>

    <!-- Main Content -->
    <div class="main-content">

        <!-- Admin Dashboard Header -->
        <div class="header">Admin Dashboard</div>

        <!-- Today’s Sales Box (at the top-left) -->
        <div class="today-sales">
          
            <p> <?php include '../model/todaysells.php'; ?></p>
            
        </div>

        <!-- Monthly Sells -->
        <div class="monthly-sales">
          
          <p> <?php include '../model/monthlysell.php'; ?></p>
          
      </div>


    <!--New order insert in database show as a notification-->

        
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>

</html>