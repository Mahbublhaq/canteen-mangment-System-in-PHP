<?php
// Include database connection and PHPMailer
require_once '../db/db.php';
require '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Initialize variables
$message = '';
$messageType = '';

// Handle deposit insertion and status activation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Process deposit and activate account
    if (isset($_POST['activate_deposit'])) {
        $customer_id = intval($_POST['customer_id']);

        // Start a transaction
        mysqli_begin_transaction($conn);

        try {
            // Fetch customer details from meal_registration
            $query = "SELECT * FROM meal_registration WHERE customer_id = ? AND active = 0";
            $stmt = $conn->prepare($query);
            $stmt->bind_param('i', $customer_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 0) {
                throw new Exception("Customer not found or already active");
            }

            $customer = $result->fetch_assoc();
            $deposit = floatval($customer['deposit']);
            $name = $customer['name'];
            $email = $customer['email'];
            $varsity_id = $customer['varsity_id'];
            $meal_date = $customer['meal_date'];

            // Check if customer exists in meal table
            $checkMealQuery = "SELECT * FROM meal WHERE meal_id = ?";
            $checkStmt = $conn->prepare($checkMealQuery);
            $checkStmt->bind_param('i', $customer_id);
            $checkStmt->execute();
            $mealResult = $checkStmt->get_result();

            // Insert or update meal table
            if ($mealResult->num_rows === 0) {
                // Insert new meal entry
                $insertMealQuery = "INSERT INTO meal (meal_id, deposit, remain_balance, created_at) 
                                    VALUES (?, ?, ?, ?)";
                $insertStmt = $conn->prepare($insertMealQuery);
                $insertStmt->bind_param('isds', 
                    $customer_id, 
                    $deposit, 
                    $deposit, 
                    $meal_date
                );
                
                if (!$insertStmt->execute()) {
                    throw new Exception("Failed to insert into meal table");
                }
            } else {
                // Update existing meal entry
                $updateMealQuery = "UPDATE meal 
                                    SET deposit = deposit + ?, 
                                        remain_balance = remain_balance + ?, 
                                        last_updated = CURRENT_TIMESTAMP 
                                    WHERE meal_id = ?";
                $updateStmt = $conn->prepare($updateMealQuery);
                $updateStmt->bind_param('ddi', $deposit, $deposit, $customer_id);
                
                if (!$updateStmt->execute()) {
                    throw new Exception("Failed to update meal table");
                }
            }

            // Record deposit in deposit_history
            $depositHistoryQuery = "INSERT INTO deposit_history 
                                    (customer_id, deposit_amount, transaction_date) 
                                    VALUES (?, ?, CURRENT_TIMESTAMP)";
            $depositStmt = $conn->prepare($depositHistoryQuery);
            $depositStmt->bind_param('id', 
                $customer_id, 
                $deposit
            );
            
            if (!$depositStmt->execute()) {
                throw new Exception("Failed to record deposit history");
            }

            // Activate the meal registration
            $activateQuery = "UPDATE meal_registration SET active = 1 WHERE customer_id = ?";
            $activateStmt = $conn->prepare($activateQuery);
            $activateStmt->bind_param('i', $customer_id);
            
            if (!$activateStmt->execute()) {
                throw new Exception("Failed to activate meal registration");
            }

            // Send confirmation email
            $mail = new PHPMailer(true);
            try {
                //Server settings
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'gourob.haq@gmail.com';
                $mail->Password   = 'owtc hcch zufy cgey';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                $mail->Port       = 465;

                //Recipients
                $mail->setFrom('gourob.haq@gmail.com', 'City University Canteen');
                $mail->addAddress($email, $name);

                // Content
                $mail->isHTML(true);
                $mail->Subject = 'Successful Meal Registration';
                $mail->Body    = "
                    <div style='background-color: #4CAF50; color: white; padding: 20px; border-radius: 10px;'>
                        <h2>Meal Registration Successful!</h2>
                        <p>Hello $name,</p>
                        <p>Your meal registration has been activated.</p>
                        <p><strong>Current Balance: BDT: " . number_format($deposit, 2) . "</strong></p>
                        <p>Thank you for registering with City University Canteen.</p>
                    </div>";

                $mail->send();
            } catch (Exception $e) {
                // Log email error but don't stop the transaction
                error_log("Email sending failed: " . $mail->ErrorInfo);
            }

            // Commit the transaction
            mysqli_commit($conn);

            $message = "Deposit processed and account activated successfully!";
            $messageType = 'success';
        } catch (Exception $e) {
            // Rollback the transaction on error
            mysqli_rollback($conn);
            $message = "Error: " . $e->getMessage();
            $messageType = 'danger';
        }
    }
}

// Fetch all data from meal_registration
$query = "SELECT * FROM meal_registration ORDER BY id DESC";
$result = $conn->query($query);

if (!$result) {
    die("Query failed: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meal Registration Management</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background-color: #f4f6f9;
            font-family: 'Arial', sans-serif;
        }
        .container-fluid {
            max-width: 1400px;
        }
        .card {
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .table-responsive {
            border-radius: 15px;
            overflow: hidden;
        }
        .table {
            margin-bottom: 0;
        }
        .table thead {
            background-color: #4a90e2;
            color: white;
        }
        .table thead th {
            vertical-align: middle;
            font-weight: 600;
            border-bottom: none;
        }
        .table tbody tr {
            transition: background-color 0.3s ease;
        }
        .table tbody tr:hover {
            background-color: rgba(74, 144, 226, 0.1);
        }
        .status-active {
            color: #28a745;
            font-weight: bold;
        }
        .status-inactive {
            color: #dc3545;
            font-weight: bold;
        }
        .btn-activate {
            background-color: #28a745;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
        }
        .btn-activate:hover {
            background-color: #218838;
        }
        .message-container {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 1050;
            width: 80%;
            max-width: 600px;
        }
        @media (max-width: 768px) {
            .table-responsive {
                font-size: 0.9rem;
            }
            .btn {
                padding: 0.25rem 0.5rem;
                font-size: 0.875rem;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid py-4">
        <!-- Message Container -->
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $messageType; ?> text-center message-container alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header bg-primary text-white">
                <h2 class="mb-0 text-center">
                    <i class="bi bi-list-check me-2"></i>Meal Registration Management
                </h2>
            </div>
            <div class="card-body">
                <!-- Meal Registration Details -->
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Department</th>
                                <th>Deposit</th>
                                <th>Varsity ID</th>
                                <th>Email</th>
                                <th>Meal Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['customer_id']); ?></td>
                                <td><?php echo htmlspecialchars($row['name']); ?></td>
                                <td><?php echo htmlspecialchars($row['department']); ?></td>
                                <td><?php echo number_format($row['deposit'], 2); ?></td>
                                <td><?php echo htmlspecialchars($row['varsity_id']); ?></td>
                                <td><?php echo htmlspecialchars($row['email']); ?></td>
                                <td><?php echo htmlspecialchars($row['meal_date']); ?></td>
                                <td class="<?php echo $row['active'] ? 'status-active' : 'status-inactive'; ?>">
                                    <?php echo $row['active'] ? 'Active' : 'Inactive'; ?>
                                </td>
                                <td>
                                    <?php if (!$row['active']): ?>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="customer_id" value="<?php echo $row['customer_id']; ?>">
                                            <button type="submit" name="activate_deposit" class="btn btn-activate btn-sm">
                                                <i class="bi bi-power"></i> Activate
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="badge bg-success">Processed</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS and Dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-dismiss alert after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            var alertElement = document.querySelector('.alert');
            if (alertElement) {
                setTimeout(function() {
                    var alert = new bootstrap.Alert(alertElement);
                    alert.close();
                }, 5000);
            }
        });

        // Prevent form resubmission
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
</body>
</html>