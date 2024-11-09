<?php
session_start();
include '../db/db.php';

// Fetch all meal registration data
$allDataQuery = "SELECT mr.*, m.active AS meal_active, m.deposit AS meal_deposit, m.remain_balance AS remain_balance 
                 FROM meal_registration AS mr 
                 LEFT JOIN meal AS m ON mr.id = m.meal_id 
                 GROUP BY mr.id";
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
                         LEFT JOIN meal AS m ON mr.id = m.meal_id" . $searchQuery . " GROUP BY mr.id";
        $allDataResult = $conn->query($allDataQuery);
    }
}

// Toggle active status
if (isset($_GET['toggle_id'])) {
    $meal_id = $_GET['toggle_id'];
    $statusQuery = "SELECT active FROM meal WHERE meal_id = ?";
    $statusStmt = $conn->prepare($statusQuery);
    $statusStmt->bind_param("i", $meal_id);
    $statusStmt->execute();
    $statusResult = $statusStmt->get_result();
    $currentStatus = $statusResult->fetch_assoc()['active'];
    $newStatus = $currentStatus ? 0 : 1;

    $updateStatusQuery = "UPDATE meal SET active = ? WHERE meal_id = ?";
    $updateStatusStmt = $conn->prepare($updateStatusQuery);
    $updateStatusStmt->bind_param("ii", $newStatus, $meal_id);
    $updateStatusStmt->execute();

    header("Location: meal_update.php");
    exit();
}

$successMessage = "";

// Update deposit only on "Update Meal"
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_meal'])) {
    $meal_id = $_POST['meal_id'];
    $new_deposit = $_POST['deposit'];

    $checkQuery = "SELECT deposit FROM meal WHERE meal_id = ?";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->bind_param("i", $meal_id);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();

    if ($checkResult->num_rows > 0) {
        $existingDeposit = $checkResult->fetch_assoc()['deposit'];
        $totalDeposit = $existingDeposit + $new_deposit;

        if (!isset($_SESSION['last_deposit']) || $_SESSION['last_deposit'] != $new_deposit) {
            $updateQuery = "UPDATE meal SET deposit = ?, remain_balance = ?, created_at = CURRENT_TIMESTAMP WHERE meal_id = ?";
            $updateStmt = $conn->prepare($updateQuery);
            $updateStmt->bind_param("ddi", $totalDeposit, $totalDeposit, $meal_id);
            $updateStmt->execute();
            $_SESSION['last_deposit'] = $new_deposit;
            $successMessage = "Meal information updated successfully for meal ID: $meal_id";
        }
    } else {
        $insertQuery = "INSERT INTO meal (meal_id, deposit, remain_balance, created_at) VALUES (?, ?, ?, CURRENT_TIMESTAMP)";
        $insertStmt = $conn->prepare($insertQuery);
        $insertStmt->bind_param("idd", $meal_id, $new_deposit, $new_deposit);
        $insertStmt->execute();
        $_SESSION['last_deposit'] = $new_deposit;
        $successMessage = "Meal information inserted successfully for meal ID: $meal_id";
    }

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
        /* Custom Styles */
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
                        <td><input type="number" name="deposit" value="<?php echo $row['meal_deposit'] ?? ''; ?>" step="0.01" class="form-control" disabled></td>
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
