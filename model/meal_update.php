<?php
session_start(); // Start the session to store the success message

require_once('../db/db.php'); // Ensure the database connection is included

/**
 * Function to update deposits in the database
 */
function updateDeposits($conn, $mealId, $newDeposit) {
    global $successMessage;

    // Fetch existing deposit
//     // Assuming $mealId and $newDeposit are set from POST or GET
// $mealId = $_POST['meal_id']; // Replace with actual source
// $newDeposit = $_POST['new_deposit']; // Replace with actual source

// // Validate inputs
// if (!is_numeric($mealId) || !is_numeric($newDeposit)) {
//     die("Invalid input data. Meal ID and Deposit must be numeric.");
// }

// Database connection ($conn should already be established)

// Query to check if a matching customer_id exists in the meal_registration table
$checkQuery = "SELECT deposit FROM meal_registration WHERE customer_id = ?";
$checkStmt = $conn->prepare($checkQuery);
if (!$checkStmt) {
    die("Error in SELECT query: " . $conn->error);
}
$checkStmt->bind_param("i", $mealId);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();

if ($checkResult->num_rows > 0) {
    // Fetch existing deposit
    $existingDeposit = $checkResult->fetch_assoc()['deposit'];
    $updatedDeposit = $existingDeposit + $newDeposit;

    // Update the deposit in the meal_registration table
    $updateQuery = "UPDATE meal_registration SET deposit = ? WHERE customer_id = ?";
    $updateStmt = $conn->prepare($updateQuery);
    if (!$updateStmt) {
        die("Error in UPDATE query: " . $conn->error);
    }
    $updateStmt->bind_param("di", $updatedDeposit, $mealId);
    $updateStmt->execute();

    // Sync in the meal table
    $mealUpdateQuery = "INSERT INTO meal (meal_id, deposit, remain_balance) 
                        VALUES (?, ?, ?)
                        ON DUPLICATE KEY UPDATE deposit = ?, remain_balance = ?";
    $mealUpdateStmt = $conn->prepare($mealUpdateQuery);
    if (!$mealUpdateStmt) {
        die("Error in meal INSERT/UPDATE query: " . $conn->error);
    }
    $mealUpdateStmt->bind_param("idddd", $mealId, $updatedDeposit, $updatedDeposit, $updatedDeposit, $updatedDeposit);
    $mealUpdateStmt->execute();

    // Log the deposit change
    $logQuery = "INSERT INTO deposit_history (meal_id, deposit_amount) VALUES (?, ?)";
    $logStmt = $conn->prepare($logQuery);
    if (!$logStmt) {
        die("Error in INSERT log query: " . $conn->error);
    }
    $logStmt->bind_param("id", $mealId, $newDeposit);
    $logStmt->execute();

    // Success message
    $_SESSION['success_message'] = "Deposit successfully updated for meal ID: $mealId.";
} else {
    // If no record found, display error message
    $_SESSION['error_message'] = "No record found for meal ID: $mealId. Please verify the ID.";
}

// Redirect or display messages (for example, redirect back to the form page)

exit;
}

/**
 * Function to fetch meal registrations
 */
function fetchMealRegistrations($conn, $searchMealId = null, $searchName = null) {
    $query = "SELECT mr.*, m.active AS meal_active, m.deposit AS meal_deposit, m.remain_balance AS remain_balance 
              FROM meal_registration AS mr 
              LEFT JOIN meal AS m ON mr.id = m.meal_id";
    
    $conditions = [];
    if ($searchMealId) {
        $conditions[] = "mr.id = " . intval($searchMealId);
    }
    if ($searchName) {
        $conditions[] = "mr.name LIKE '%" . $conn->real_escape_string($searchName) . "%'";
    }
    if ($conditions) {
        $query .= " WHERE " . implode(" AND ", $conditions);
    }

    $query .= " GROUP BY mr.id";
    $result = $conn->query($query);

    if (!$result) {
        die("Error in SELECT query: " . $conn->error);
    }

    return $result;
}

// Handle deposit update submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_deposit'])) {
    $mealId = intval($_POST['meal_id']);
    $newDeposit = floatval($_POST['new_deposit']);
    updateDeposits($conn, $mealId, $newDeposit);

    // Redirect to avoid resubmission on page reload
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Fetch all meal registrations
$searchMealId = $_GET['search_meal_id'] ?? null;
$searchName = $_GET['search_name'] ?? null;
$allDataResult = fetchMealRegistrations($conn, $searchMealId, $searchName);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meal Update</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #f5f5f5;
            font-family: 'Arial', sans-serif;
            color: #333;
        }

        .container {
            max-width: 800px;
            margin: 50px auto;
        }

        .card {
            background-color: #fff;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0px 4px 12px rgba(0, 0, 0, 0.1);
        }

        .table th, .table td {
            vertical-align: middle;
            text-align: center;
        }

        .table {
            border-radius: 10px;
            overflow: hidden;
        }

        .table thead {
            background-color: black;
            color: white;
            font-size: 36px;
        }

        .table tbody {
            font-size: 16px;
            background-color: #f9f9f9;
        }

        .table-hover tbody tr:hover {
            background-color: #eaeaea;
            transform: scale(1.02);
            transition: transform 0.3s ease;
        }

        .btn {
            border-radius: 12px;
            padding: 10px 25px;
            transition: background-color 0.3s ease;
        }

        .btn-success {
            background-color: #28a745;
        }

        .btn-success:hover {
            background-color: #218838;
        }

        .form-control {
            border-radius: 10px;
            border: 1px solid #ddd;
            padding: 10px 15px;
        }

        .form-control:focus {
            box-shadow: 0 0 10px rgba(0, 255, 234, 0.7);
            border-color: #00ffea;
        }

        .input-group-text {
            background-color: #00ffea;
            color: #fff;
            border-radius: 10px 0 0 10px;
        }

        .success-message {
            background-color: #28a745;
            padding: 15px;
            color: white;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2 class="text-center mb-4">Meal Update</h2>

        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="success-message"><?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?></div>
        <?php endif; ?>

        <div class="card">
            <form method="POST" action="meal_update.php">
                <table class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>Select</th>
                            <th>Meal ID</th>
                            <th>Name</th>
                            <th>Deposit</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $allDataResult->fetch_assoc()): ?>
                            <tr>
                                <td><input type="radio" name="meal_id" value="<?php echo $row['customer_id']; ?>" required></td>
                                <td><?php echo $row['customer_id']; ?></td>
                                <td><?php echo $row['name']; ?></td>
                                <td><?php echo isset($row['deposit']) ? $row['deposit'] : 0; ?></td>
                                <td><?php echo $row['meal_active'] ? 'Active' : 'Inactive'; ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>

                <div class="form-group">
                    <label for="new_deposit">New Deposit:</label>
                    <input type="number" id="new_deposit" name="new_deposit" class="form-control" step="0.01" required>
                </div>

                <button type="submit" name="update_deposit" class="btn btn-success mt-3 w-100">Update Deposit</button>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
