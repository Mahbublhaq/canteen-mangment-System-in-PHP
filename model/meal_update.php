<?php
session_start();
include '../db/db.php';

$successMessage = "";

// Fetch all meal registration data with deposit from meal_registration table
function fetchMealRegistrations($conn, $searchMealId = null, $searchName = null) {
    $query = "SELECT mr.*, m.active AS meal_active, m.deposit AS meal_deposit, m.remain_balance AS remain_balance 
              FROM meal_registration AS mr 
              LEFT JOIN meal AS m ON mr.id = m.meal_id";
    
    if ($searchMealId || $searchName) {
        $query .= " WHERE 1=1";
        if ($searchMealId) {
            $query .= " AND mr.id = " . intval($searchMealId);
        }
        if ($searchName) {
            $query .= " AND mr.name LIKE '%" . $conn->real_escape_string($searchName) . "%'";
        }
    }
    
    $query .= " GROUP BY mr.id";
    return $conn->query($query);
}

// Handle search functionality
$searchMealId = $_POST['meal_id'] ?? '';
$searchName = $_POST['name'] ?? '';
$allDataResult = fetchMealRegistrations($conn, $searchMealId, $searchName);

// Toggle active status
if (isset($_GET['toggle_id'])) {
    $mealId = $_GET['toggle_id'];
    $statusQuery = "SELECT active FROM meal WHERE meal_id = ?";
    $statusStmt = $conn->prepare($statusQuery);
    $statusStmt->bind_param("i", $mealId);
    $statusStmt->execute();
    $statusResult = $statusStmt->get_result();
    $currentStatus = $statusResult->fetch_assoc()['active'];
    $newStatus = $currentStatus ? 0 : 1;

    $updateStatusQuery = "UPDATE meal SET active = ? WHERE meal_id = ?";
    $updateStatusStmt = $conn->prepare($updateStatusQuery);
    $updateStatusStmt->bind_param("ii", $newStatus, $mealId);
    $updateStatusStmt->execute();

    header("Location: meal_update.php");
    exit();
}

// Function to update deposits in both meal_registration and meal tables and log in deposit_history
function updateDeposits($conn, $mealId, $newDeposit) {
    global $successMessage;

    // 1. Retrieve and update deposit in meal_registration
    $checkQuery = "SELECT deposit FROM meal_registration WHERE id = ?";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->bind_param("i", $mealId);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows > 0) {
        $existingDeposit = $checkResult->fetch_assoc()['deposit'];
        $updatedDeposit = $existingDeposit + $newDeposit;

        // Update deposit in meal_registration
        $updateQuery = "UPDATE meal_registration SET deposit = ? WHERE id = ?";
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->bind_param("di", $updatedDeposit, $mealId);
        $updateStmt->execute();
    }

    // 2. Update deposit in meal table to match meal_registration deposit
    $mealUpdateQuery = "INSERT INTO meal (meal_id, deposit, remain_balance, created_at) 
                        VALUES (?, ?, ?, CURRENT_TIMESTAMP)
                        ON DUPLICATE KEY UPDATE deposit = ?, remain_balance = ?";
    $mealUpdateStmt = $conn->prepare($mealUpdateQuery);
    $mealUpdateStmt->bind_param("idddd", $mealId, $updatedDeposit, $updatedDeposit, $updatedDeposit, $updatedDeposit);
    $mealUpdateStmt->execute();

    // 3. Insert the deposit amount into deposit_history table
    $logDepositQuery = "INSERT INTO deposit_history (customer_id, deposit_amount) VALUES (?, ?)";
    $logDepositStmt = $conn->prepare($logDepositQuery);
    $logDepositStmt->bind_param("id", $mealId, $newDeposit);
    $logDepositStmt->execute();

    $successMessage = "Deposit successfully updated for meal ID: $mealId";
}

// Handle deposit update request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_meal'])) {
    $mealId = $_POST['meal_id'];
    $newDeposit = $_POST['deposit'];
    updateDeposits($conn, $mealId, $newDeposit);
    echo "<meta http-equiv='refresh' content='2'>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Meal Registration Data</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .container { margin-top: 50px; }
        table { width: 100%; }
        th, td { text-align: center; }
        th { background-color: #00adb5; color: white; font-weight: bold; }
        .radio-col { width: 5%; }
        .action-button { padding: 8px 12px; color: white; border-radius: 4px; transition: 0.3s ease; }
        .action-button:hover { transform: scale(1.1); }
        .active { background-color: #6c757d; }
        .inactive { background-color: #ff5722; }
        .update-button { background-color: #00adb5; }
        .success-alert { position: fixed; top: 10px; right: 10px; min-width: 300px; animation: fadeInOut 5s ease; }
        @keyframes fadeInOut { 0%, 100% { opacity: 0; } 20%, 80% { opacity: 1; } }
    </style>
</head>
<body>

<div class="container">
    <h2 class="text-center mb-4">All Meal Registration Data</h2>

    <?php if ($successMessage): ?>
        <div class="alert alert-success success-alert">
            <?php echo $successMessage; ?>
        </div>
    <?php endif; ?>

    <form action="meal_update.php" method="POST" onsubmit="return validateForm()">
        <table class="table table-hover table-bordered">
            <thead>
                <tr>
                    <th>Select</th>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Deposit</th>
                    <th>Active Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $allDataResult->fetch_assoc()) : ?>
                    <tr>
                        <td class="radio-col"><input type="radio" name="meal_id" value="<?php echo $row['id']; ?>" onclick="selectRow(this)" required></td>
                        <td><?php echo $row['id']; ?></td>
                        <td><?php echo $row['name']; ?></td>
                        <td>
                            <input type="number" name="deposit" value="<?php echo $row['deposit'] ?? '0'; ?>" step="0.01" class="form-control" disabled>
                        </td>
                        <td>
                            <a href="meal_update.php?toggle_id=<?php echo $row['id']; ?>">
                                <button type="button" class="action-button <?php echo $row['meal_active'] ? 'active' : 'inactive'; ?>">
                                    <?php echo $row['meal_active'] ? 'Active' : 'Inactive'; ?>
                                </button>
                            </a>
                        </td>
                        <td>
                            <button type="submit" name="update_meal" class="btn update-button" disabled>Update Meal</button>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </form>
</div>

<script>
function selectRow(radio) {
    document.querySelectorAll('tbody tr').forEach(row => {
        row.querySelector('input[name="deposit"]').disabled = true;
        row.querySelector('button[name="update_meal"]').disabled = true;
    });

    const selectedRow = radio.closest('tr');
    selectedRow.querySelector('input[name="deposit"]').disabled = false;
    selectedRow.querySelector('button[name="update_meal"]').disabled = false;
}

function validateForm() {
    const depositInput = document.querySelector('input[name="deposit"]:not([disabled])');
    if (!depositInput || depositInput.value === '') {
        alert('Please enter a deposit amount before updating.');
        return false;
    }
    return true;
}
</script>
</body>
</html>