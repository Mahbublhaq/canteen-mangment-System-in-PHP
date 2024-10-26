<?php
session_start();

// Include the database connection
require '../db/db.php';

// Check if the user is logged in (modify as necessary)
if (!isset($_SESSION['Id'])) {
    header("Location: login.php");
    exit();
}

// Handle the update of active status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $id = $_POST['product_id'];
    $new_status = $_POST['new_status'] === '1' ? 0 : 1; // Toggle status

    // Update the active status in the database
    $stmt = $conn->prepare("UPDATE stock SET active = ? WHERE id = ?");
    $stmt->bind_param("ii", $new_status, $id);
    $stmt->execute();
    $stmt->close();
}

// Fetch all products from the database
$sql = "SELECT * FROM stock";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products</title>
    <link rel="stylesheet" href="style.css"> <!-- Include your CSS file -->
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
        }

        h1 {
            text-align: center;
            color: #333;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            perspective: 1000px;
        }

        th, td {
            padding: 15px;
            text-align: center;
            border: 1px solid #ddd;
            transition: transform 0.3s;
        }

        th {
            background-color: #007bff;
            color: white;
        }

        tr:hover {
            transform: translateZ(20px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        button {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            cursor: pointer;
            transition: background-color 0.3s, transform 0.3s;
            border-radius: 5px;
        }

        button:hover {
            background-color: #0056b3;
            transform: scale(1.05);
        }

        img {
            max-width: 50px;
            border-radius: 5px;
        }

        a {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #007bff;
            text-decoration: none;
            font-weight: bold;
        }

        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <h1>Manage Products</h1>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Category</th>
                <th>Product Name</th>
                <th>Product Details</th>
                <th>Price</th>
                <th>Product Image</th>
                <th>Active Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $row['id']; ?></td>
                        <td><?php echo $row['category']; ?></td>
                        <td><?php echo $row['product_name']; ?></td>
                        <td><?php echo $row['product_details']; ?></td>
                        <td>BDT <?php echo number_format($row['price'], 2); ?></td>
                        <td><img src="../uploads/<?php echo $row['product_image']; ?>" alt="<?php echo $row['product_name']; ?>"></td>
                        <td><?php echo $row['active'] ? 'Active' : 'Inactive'; ?></td>
                        <td>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="product_id" value="<?php echo $row['id']; ?>">
                                <input type="hidden" name="new_status" value="<?php echo $row['active']; ?>">
                                <button type="submit" name="update_status">
                                    <?php echo $row['active'] ? 'Deactivate' : 'Activate'; ?>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="8">No products found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <a href="admin_dashboard.php">Back to Dashboard</a>

    <?php
    // Close the database connection
    $conn->close();
    ?>
</body>
</html>
