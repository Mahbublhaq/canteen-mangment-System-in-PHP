<?php
// Include database connection and PHPMailer
require_once '../db/db.php';
require '../vendor/autoload.php';
include'../menu/adminmenu.php';
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background-color: #f5f7fa;
            font-family: 'Segoe UI', sans-serif;
            font-size: 0.875rem;
        }

        .main-container {
            padding: 1rem;
            margin-left: 20%;
            margin-right: 2%;
        }

        .card {
            border: none;
            border-radius: 0.75rem;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
        }

        .card-header {
            background: linear-gradient(135deg, #4a90e2, #357abd);
            border-radius: 0.75rem 0.75rem 0 0 !important;
            padding: 1rem;
            font-weight: bold;
            
        }

        .card-header h2 {
            font-size: 1.25rem;
            margin: 0;
        }

        .table-container {
            padding: 0.5rem;
        }

        .table {
            font-size:1rem;
            margin-bottom: 0;
        }

        .table > :not(caption) > * > * {
            padding: 0.5rem;
        }

        .table thead {
            background-color: #f8f9fa;
        }

        .table thead th {
            font-weight: 600;
            color: #495057;
            border-bottom: 2px solid #e9ecef;
            white-space: nowrap;
        }

        .table tbody tr:hover {
            background-color: #f8f9fa;
        }

        .id-card-image {
            width: 40px;
            height: 40px;
            object-fit: cover;
            border-radius: 4px;
            cursor: zoom-in;
            transition: transform 0.2s;
            border: 2px solid #e9ecef;
        }

        .id-card-image:hover {
            transform: scale(1.1);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .status-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 50rem;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .status-active {
            background-color: #d4edda;
            color: #155724;
        }

        .status-inactive {
            background-color: #f8d7da;
            color: #721c24;
        }

        .btn-activate {
            padding: 0.25rem 0.75rem;
            font-size: 0.75rem;
            border-radius: 50rem;
            background: linear-gradient(135deg, #28a745, #218838);
            border: none;
            color: white;
            transition: all 0.2s;
        }

        .btn-activate:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 6px rgba(40, 167, 69, 0.2);
        }

        .modal-content {
            border-radius: 1rem;
            border: none;
        }

        .modal-header {
            background: linear-gradient(135deg, #4a90e2, #357abd);
            color: white;
            border-radius: 1rem 1rem 0 0;
            padding: 1rem;
        }

        .modal-body {
            padding: 1.5rem;
        }

        .modal-image {
            max-width: 100%;
            height: auto;
            border-radius: 0.5rem;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.1);
        }

        /* Responsive adjustments */
        @media (max-width: 992px) {
            .main-container {
                margin-left: 0;
                margin-right: 0;
            }

            .table-container {
                overflow-x: auto;
            }
            h1 {
            margin-top: 2%;
            color: black;
            font-weight: 600;
        }
        }
    </style>
</head>
<body>
  
    
    <div class="main-container">
        <div class="card">
            <div class="card-header">
                <h2 class="text-white text-center mb-0">
                    <i class="bi bi-list-check me-2"></i>Meal Registration Management
                </h2>
            </div>
            <div class="card-body p-0">
                <div class="table-container">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Department</th>
                                <th>Deposit</th>
                                <th>Varsity ID</th>
                                <th>Start Date</th>
                                <th>Status</th>
                                <th>ID Front</th>
                                <th>ID Back</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['customer_id']); ?></td>
                                <td>
                                    <div class="d-flex flex-column ">
                                        <span><?php echo htmlspecialchars($row['name']); ?></span>
                                        <small class="text-muted"><?php echo htmlspecialchars($row['email']); ?></small>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($row['department']); ?></td>
                                <td><?php echo number_format($row['deposit'], 2); ?> TK</td>
                                <td><?php echo htmlspecialchars($row['varsity_id']); ?></td>
                                <td><?php echo date('d M Y', strtotime($row['meal_date'])); ?></td>
                                <td>
                                    <span class="status-badge <?php echo $row['active'] ? 'status-active' : 'status-inactive'; ?>">
                                        <?php echo $row['active'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if (!empty($row['id_card_front'])): ?>
                                        <img src="<?php echo htmlspecialchars($row['id_card_front']); ?>" 
                                             alt="Front ID" 
                                             class="id-card-image"
                                             onclick="showImage(this.src, '<?php echo htmlspecialchars($row['name']); ?> - Front ID')">
                                    <?php else: ?>
                                        <span class="badge bg-secondary">No image</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($row['id_card_back'])): ?>
                                        <img src="<?php echo htmlspecialchars($row['id_card_back']); ?>" 
                                             alt="Back ID" 
                                             class="id-card-image"
                                             onclick="showImage(this.src, '<?php echo htmlspecialchars($row['name']); ?> - Back ID')">
                                    <?php else: ?>
                                        <span class="badge bg-secondary">No image</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!$row['active']): ?>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="customer_id" value="<?php echo $row['customer_id']; ?>">
                                            <button type="submit" name="activate_deposit" class="btn btn-activate">
                                                <i class="bi bi-check-circle me-1"></i>Activate
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="badge bg-success">
                                            <i class="bi bi-check2-circle me-1"></i>Processed
                                        </span>
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

    <!-- Image Preview Modal -->
    <div class="modal fade" id="imagePreviewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center p-0">
                    <img id="previewImage" src="" alt="ID Card Preview" class="modal-image">
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Image preview functionality
        function showImage(src, title) {
            const modal = new bootstrap.Modal(document.getElementById('imagePreviewModal'));
            document.querySelector('#imagePreviewModal .modal-title').textContent = title;
            document.getElementById('previewImage').src = src;
            modal.show();
        }

        // Auto-dismiss alerts
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    bootstrap.Alert.getOrCreateInstance(alert).close();
                }, 5000);
            });
        });
    </script>
</body>
</html>