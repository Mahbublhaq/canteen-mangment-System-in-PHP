<?php
session_start(); // Start the session to use session variables
include '../db/db.php';

// Fetch all meal registration data
$allDataQuery = "SELECT mr.*, m.active AS meal_active, m.deposit AS meal_deposit, m.remain_balance AS remain_balance 
                 FROM meal_registration AS mr 
                 LEFT JOIN meal AS m ON mr.id = m.meal_id";
$allDataResult = $conn->query($allDataQuery);

// Handle search functionality
$searchQuery = "";
if (isset($_POST['search'])) {
    $meal_id = $_POST['meal_id'] ?? '';
    $name = $_POST['name'] ?? '';

    if ($meal_id || $name) {
        $searchQuery .= " WHERE 1=1";
        if ($meal_id) {
            $searchQuery .= " AND mr.id = " . intval($meal_id);
        }
        if ($name) {
            $searchQuery .= " AND mr.name LIKE '%" . $conn->real_escape_string($name) . "%'";
        }

        $allDataQuery = "SELECT mr.*, m.active AS meal_active, m.deposit AS meal_deposit, m.remain_balance AS remain_balance 
                         FROM meal_registration AS mr 
                         LEFT JOIN meal AS m ON mr.id = m.meal_id" . $searchQuery;
        $allDataResult = $conn->query($allDataQuery);
    }
}

// Toggle active status
if (isset($_GET['toggle_id'])) {
    $meal_id = $_GET['toggle_id'];

    // Get current active status
    $statusQuery = "SELECT active FROM meal WHERE meal_id = ?";
    $statusStmt = $conn->prepare($statusQuery);
    $statusStmt->bind_param("i", $meal_id);
    $statusStmt->execute();
    $statusResult = $statusStmt->get_result();
    $currentStatus = $statusResult->fetch_assoc()['active'];

    // Toggle status
    $newStatus = $currentStatus ? 0 : 1;
    $updateStatusQuery = "UPDATE meal SET active = ? WHERE meal_id = ?";
    $updateStatusStmt = $conn->prepare($updateStatusQuery);
    $updateStatusStmt->bind_param("ii", $newStatus, $meal_id);
    $updateStatusStmt->execute();

    header("Location: meal_update.php");
    exit();
}

// Update deposit only when the "Update Meal" button is clicked
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_meal'])) {
    $meal_id = $_POST['meal_id'];
    $new_deposit = $_POST['deposit'];

    // Fetch current deposit
    $checkQuery = "SELECT deposit FROM meal WHERE meal_id = ?";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->bind_param("i", $meal_id);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();

    // Check if the current deposit is the same as the last session deposit
    if ($checkResult->num_rows > 0) {
        $existingDeposit = $checkResult->fetch_assoc()['deposit'];
        $totalDeposit = $existingDeposit + $new_deposit;

        // Check if the new deposit is not the same as the last deposit in session
        if (!isset($_SESSION['last_deposit']) || $_SESSION['last_deposit'] != $new_deposit) {
            // Update deposit and remain_balance
            $updateQuery = "UPDATE meal SET deposit = ?, remain_balance = ?, created_at = CURRENT_TIMESTAMP WHERE meal_id = ?";
            $updateStmt = $conn->prepare($updateQuery);
            $updateStmt->bind_param("ddi", $totalDeposit, $totalDeposit, $meal_id);
            $updateStmt->execute();
            // Store the new deposit in the session
            $_SESSION['last_deposit'] = $new_deposit;
        }
    } else {
        // If no record exists for this meal_id, insert a new one
        $insertQuery = "INSERT INTO meal (meal_id, deposit, remain_balance, created_at) VALUES (?, ?, ?, CURRENT_TIMESTAMP)";
        $insertStmt = $conn->prepare($insertQuery);
        $insertStmt->bind_param("idd", $meal_id, $new_deposit, $new_deposit);
        $insertStmt->execute();
        // Store the new deposit in the session
        $_SESSION['last_deposit'] = $new_deposit;
    }

    echo "<div class='success'>Meal information updated successfully for meal ID: $meal_id</div>";
    header("Refresh: 2; url=meal_update.php"); // Refresh the page after 2 seconds
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meal Registration Data</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            flex-direction: column;
            align-items: center;
            background: #f4f4f9;
            margin: 0;
            padding: 20px;
        }
        .container {
            background: #ffffff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
            width: 90%;
            max-width: 1200px;
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            overflow-x: auto;
            display: block;
            max-height: 400px;
            overflow-y: auto;
        }
        table, th, td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        .success {
            color: #28a745;
            font-weight: bold;
            margin-bottom: 10px;
        }
        button {
            padding: 8px 12px;
            font-size: 14px;
            color: #ffffff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .update-btn {
            background-color: #007bff;
            margin-left: 10px;
        }
        .toggle-btn.active {
            background-color: #28a745;
        }
        .toggle-btn.inactive {
            background-color: #dc3545;
        }
        input[type="text"], input[type="number"] {
            padding: 5px;
            width: 100%;
            border: 1px solid #ddd;
            border-radius: 5px;
            margin-bottom: 10px;
        }
        .search-container {
            margin-bottom: 20px;
            display: flex;
            justify-content: flex-end;
        }
        .search-container input {
            width: 200px;
            margin-left: auto;
            margin-bottom: 10px;
        }
        .highlight {
            background-color: red !important;
            color: white !important;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>All Meal Registration Data</h2>
    <div class="search-container">
        <input type="text" id="searchInput" placeholder="Search by Meal ID or Name" oninput="highlightSearch()">
    </div>
    <form action="meal_update.php" method="POST">
        <table id="dataTable">
            <tr>
                <th>Select</th>
                <th>ID</th>
                <th>Customer ID</th>
                <th>Name</th>
                <th>Department</th>
                <th>Deposit</th>
                <th>Varsity ID</th>
                <th>Email</th>
                <th>Meal Date</th>
                <th>Active Status</th>
                <th>Action</th>
            </tr>
            <?php while ($row = $allDataResult->fetch_assoc()) : ?>
                <tr>
                    <td><input type="radio" name="meal_id" value="<?php echo $row['id']; ?>" required></td>
                    <td><?php echo $row['id']; ?></td>
                    <td><?php echo $row['customer_id']; ?></td>
                    <td><?php echo $row['name']; ?></td>
                    <td><?php echo $row['department']; ?></td>
                    <td>
                        <input type="number" name="deposit" value="<?php echo $row['meal_deposit'] ?? ''; ?>" step="0.01" required>
                    </td>
                    <td><?php echo $row['varsity_id']; ?></td>
                    <td><?php echo $row['email']; ?></td>
                    <td><?php echo $row['meal_date']; ?></td>
                    <td>
                        <a href="meal_update.php?toggle_id=<?php echo $row['id']; ?>">
                            <button type="button" class="toggle-btn <?php echo $row['meal_active'] ? 'active' : 'inactive'; ?>">
                                <?php echo $row['meal_active'] ? 'Active' : 'Inactive'; ?>
                            </button>
                        </a>
                    </td>
                    <td>
                        <button type="submit" name="update_meal" class="update-btn">Update Meal</button>
                    </td>
                </tr>
            <?php endwhile; ?>
        </table>
    </form>
</div>

<script>
    function highlightSearch() {
        const input = document.getElementById("searchInput").value.toLowerCase();
        const rows = document.querySelectorAll("#dataTable tr");

        rows.forEach((row, index) => {
            if (index > 0) { // skip header row
                row.classList.remove("highlight");
                const idCell = row.cells[1].textContent.toLowerCase();
                const nameCell = row.cells[3].textContent.toLowerCase();
                if (idCell.includes(input) || nameCell.includes(input)) {
                    row.classList.add("highlight");
                }
            }
        });
    }
</script>

</body>
</html>
