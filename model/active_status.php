<?php



// Include the database connection
require '../db/db.php';
include'../menu/adminmenu.php';

// Handle the update of active status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $id = $_POST['product_id'];
    $new_status = $_POST['new_status'] === '1' ? 0 : 1; // Toggle status

    // Update the active status in the database
    $stmt = $conn->prepare("UPDATE products SET active = ? WHERE id = ?");
    $stmt->bind_param("ii", $new_status, $id);
    $stmt->execute();
    $stmt->close();
}

// Fetch all products from the database
$sql = "SELECT * FROM products";
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
            font-family: 'Inter', 'Segoe UI', sans-serif;
            background-color:rgb(248, 248, 248);
            margin: 0;
            padding: 20px;
            color:black;
        }
        .nav-menu a.active {
            background: var(--accent-primary);
            color: var(--bg-primary);
        }

        h3 {
            text-align: center;
            font-weight: 600;
            margin-left:20%;
            
        }
        h1{
            text-align:center;
            margin-top:2%;
            margin-left:20%;
            color:black;
            font-weight: 600;
        }

        table {
            width:70%;
            border-collapse: collapse;
            margin: 20px 0;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            perspective: 1000px;
            margin-left:25%;
        }

        th, td {
            padding: 15px;
            text-align: center;
            border: 1px solid #ddd;
            transition: transform 0.3s;
        }

        th {
            background-color:rgb(36, 126, 230);
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

       
    </style>
</head>
<body>
    <h3 style="color:crimson;font-weight:600;">Manage Products</h3>

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
                        <td><?php echo $row['catagorey']; ?></td>
                        <td><?php echo $row['product_name']; ?></td>
                        <td><?php echo $row['product_details']; ?></td>
                        <td>BDT <?php echo number_format($row['price'], 2); ?></td>
                        <td><img src="../uploads/<?php echo $row['product_image']; ?>" alt="<?php echo $row['product_name']; ?>"></td>
                        <td style="background-color: <?php echo $row['Active'] ? '#28a745' : '#dc3545'; ?>; color: white; font-weight: bold; padding: 10px; border-radius: 5px;">
    <?php echo $row['Active'] ? 'Active' : 'Inactive'; ?>
</td>
<td>
    <form method="POST" style="display:inline;">
        <input type="hidden" name="product_id" value="<?php echo $row['id']; ?>">
        <input type="hidden" name="new_status" value="<?php echo $row['Active']; ?>">
        <button type="submit" name="update_status" style="background-color: <?php echo $row['Active'] ? '#dc3545' : '#28a745'; ?>; color: white; border: none; padding: 10px 20px; cursor: pointer; border-radius: 5px;">
            <?php echo $row['Active'] ? 'Deactivate' : 'Activate'; ?>
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

    <a href="admin.php">Back to Dashboard</a>

    <?php
    // Close the database connection
    $conn->close();
    ?>
</body>
</html>
