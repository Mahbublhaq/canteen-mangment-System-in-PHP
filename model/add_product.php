<?php
require '../db/db.php'; // Include your database connection
 // Include your menu file

include '../menu/adminmenu.php'; // Include your menu file
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve form data
    $category = $_POST['category'] ?? null; // Get category from form
    $product_name = $_POST['product_name'];
    $product_details = $_POST['product_details'];
    $price = $_POST['price'];

    // Debugging: Check if category is received
    if ($category === null) {
        echo "<script>alert('Category is NULL'); window.history.back();</script>";
        exit;
    }

    // Handle the file upload
    $target_dir = "../uploads/"; // Directory to save images
    $target_file = $target_dir . basename($_FILES["product_image"]["name"]);

    // Ensure the uploads directory exists
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    // Move the uploaded file to the target directory
    if (move_uploaded_file($_FILES["product_image"]["tmp_name"], $target_file)) {
        // Prepare SQL query to insert into the database
        $sql = "INSERT INTO products (catagorey	, product_name, product_details, price, product_image, Active) VALUES (?, ?, ?, ?, ?, ?)";

        // Prepare the statement
        if ($stmt = $conn->prepare($sql)) {
            $active = 1; // Set the product as active by default
            // Bind parameters
            $stmt->bind_param("sssdsi", $category, $product_name, $product_details, $price, $target_file, $active);

            // Execute the statement
            if ($stmt->execute()) {
                echo "<script>alert('Product added successfully.'); window.location.href = '/path/to/your/product/list.php';</script>";
            } else {
                echo "<script>alert('Error: " . $stmt->error . "'); window.history.back();</script>";
            }

            $stmt->close();
        } else {
            echo "<script>alert('Error preparing statement: " . $conn->error . "'); window.history.back();</script>";
        }
    } else {
        echo "<script>alert('Error uploading file.'); window.history.back();</script>";
    }

    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color:rgb(255, 255, 255); /* Light background color */
            font-family: Arial, sans-serif; /* Use a sans-serif font */

        }
        .container {
            background-color: #ffffff; /* White background for the form */
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); /* Soft shadow for depth */
            margin-left:35%;
            width:40%;
             
        }
        .btn-primary {
            transition: background-color 0.3s ease, transform 0.3s ease; /* Smooth transition */
        }
        .btn-primary:hover {
            background-color: #0056b3; /* Darker blue on hover */
            transform: scale(1.05); /* Slightly enlarge button on hover */
        }
        .form-label {
            font-weight: bold; /* Bold labels for better visibility */
        }
        h1{
            margin-top:2%;
            color:black;
            text-align:center;
            font-weight: 600;
        }
    </style>
</head>
<body>
    

<div class="container mt-5">
    <h2 class="mb-4 text-center"style="color:crimson;font-weight:600;">Add Product</h2>
    <form action="/model/add_product.php" method="POST" enctype="multipart/form-data">
        <div class="mb-3">
            <label for="category" class="form-label">Category</label>
            <select class="form-select" id="category" name="category" required>
                <option value="" disabled selected>Select a category</option>
                <option value="Hot Offer">Hot Offer</option>
                <option value="Combo">Combo</option>
                <option value="Meal">Meal</option>
               
                <!-- Add more categories as needed -->
            </select>
        </div>
        <div class="mb-3">
            <label for="product_name" class="form-label">Product Name</label>
            <input type="text" class="form-control" id="product_name" name="product_name" required>
        </div>
        <div class="mb-3">
            <label for="product_details" class="form-label">Product Details</label>
            <input type="text" class="form-control" id="product_details" name="product_details" required>
        </div>
        <div class="mb-3">
            <label for="price" class="form-label">Price</label>
            <input type="number" class="form-control" id="price" name="price" step="0.01" required>
        </div>
        <div class="mb-3">
            <label for="product_image" class="form-label">Product Image</label>
            <input type="file" class="form-control" id="product_image" name="product_image" accept="image/*" required>
        </div>
        <button type="submit" class="btn btn-primary w-100">Add Product</button>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
