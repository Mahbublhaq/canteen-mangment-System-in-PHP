<?php
require '../db/db.php'; // Include your database connection

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $category = $_POST['category'];
    $product_name = $_POST['product_name'];
    $product_details = $_POST['product_details'];
    $price = $_POST['price'];
    
    // Handle the file upload
    $target_dir = "../uploads/"; // Directory to save images
    $target_file = $target_dir . basename($_FILES["product_image"]["name"]);
    move_uploaded_file($_FILES["product_image"]["tmp_name"], $target_file);
    
    // Prepare SQL query to insert into database
    $sql = "INSERT INTO stock (category, product_name, product_details, price, product_image) VALUES (?, ?, ?, ?, ?)";
    
    // Prepare the statement
    if ($stmt = $conn->prepare($sql)) {
        // Use "sssss" as you are binding 5 strings
        $stmt->bind_param("sssss", $category, $product_name, $product_details, $price, $target_file);
        
        if ($stmt->execute()) {
            echo "Product added successfully.";
        } else {
            echo "Error: " . $stmt->error;
        }

        $stmt->close();
    } else {
        echo "Error: " . $conn->error;
    }
    
    $conn->close();
}
?>
