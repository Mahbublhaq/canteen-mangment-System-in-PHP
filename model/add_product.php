<?php
require '../db/db.php'; // Include your database connection

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
