<?php
require '../vendor/autoload.php';
include '../menu/adminmenu.php';
include '../db/db.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Handle message confirmation
if (isset($_POST['confirm_message'])) {
    $message_id = $_POST['message_id'];
    $email = $_POST['email'];
    $name = $_POST['name'];
    $reply_message = $_POST['reply_message'];
    
    try {
        mysqli_begin_transaction($conn);
        
        // Update message status and reply_message
        $update_query = "UPDATE contact_queries SET status='confirmed', reply_message=? WHERE id=?";
        $stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($stmt, "si", $reply_message, $message_id);
        mysqli_stmt_execute($stmt);
        
        // Send confirmation email
        $mail = new PHPMailer(true);
        
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'gourob.haq@gmail.com';
        $mail->Password = 'owtc hcch zufy cgey';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = 465;
        
        $mail->setFrom('gourob.haq@gmail.com', 'City University Canteen');
        $mail->addAddress($email, $name);
        
        $mail->isHTML(true);
        $mail->Subject = 'Query Response from City University Canteen';
        $mail->Body = "
            <div style='background-color: #4CAF50; color: white; padding: 20px; border-radius: 10px;'>
                <h2>Response to Your Query</h2>
                <p>Hello $name,</p>
                <p>Thank you for contacting City University Canteen. Here is our response to your query:</p>
                <div style='background-color: white; color: #333; padding: 15px; border-radius: 5px; margin: 10px 0;'>
                    $reply_message
                </div>
                <p>Best regards,<br>City University Canteen Team</p>
            </div>";
        
        $mail->send();
        mysqli_commit($conn);
        $success_message = "Message confirmed and response sent successfully!";
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $error_message = "Error: " . $e->getMessage();
    }
}

// Fetch all messages with reply_message
$query = "SELECT * FROM contact_queries ORDER BY created_at DESC";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Message Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .message-container {
            display: flex;
            height: calc(100vh - 100px);
            margin-top: 2%;
            margin-left: 20%;
        }
        .message-list {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
            border-right: 1px solid #dee2e6;
            background-color: #f8f9fa;
            border-radius: 10px;
            margin-right: 15px;
        }
        .message-detail {
            flex: 2;
            padding: 20px;
            overflow-y: auto;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }
        .message-item {
            padding: 15px;
            border: 1px solid #dee2e6;
            margin-bottom: 10px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            background-color: white;
            position: relative;
        }
        .message-item:hover {
            background-color: #dc3545;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .message-item.Pending {
            border-left: 4px solid #ffc107;
        }
        .message-item.confirmed {
            border-left: 4px solid #28a745;
        }
        .reply-box {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-top: 20px;
        }
        .reply-box textarea {
            border: 1px solid #ced4da;
            border-radius: 6px;
            padding: 10px;
            min-height: 120px;
            width: 100%;
            margin-bottom: 15px;
            transition: border-color 0.3s ease;
        }
        .reply-box textarea:focus {
            border-color: #4CAF50;
            outline: none;
            box-shadow: 0 0 0 2px rgba(76, 175, 80, 0.25);
        }
        .btn-send {
            background-color: #4CAF50;
            color: white;
            padding: 10px 25px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .btn-send:hover {
            background-color: #45a049;
            transform: translateY(-1px);
        }
        .response-box {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-top: 15px;
            border-left: 4px solid #28a745;
        }
        .confirmed-message {
            background-color: #d4edda;
            border-color: #c3e6cb;
            color: #155724;
            padding: 15px;
            border-radius: 8px;
            margin-top: 15px;
            margin-left: 20%;
        }
        h1 {
            margin-top: 2%;
            color: black;
            font-weight: 600;
        }
        .date-text {
            display: block;
            margin-top: 5px;
            color: #666;
        }
        .message-item:hover .date-text {
            color: #fff;
        }

        /* Custom badge styles */
        .custom-badge {
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
            float: right;
        }
        .custom-badge.Pending {
            background-color: #ffc107 !important;
            color: #000 !important;
        }
        .custom-badge.confirmed {
            background-color: #28a745 !important;
            color: #fff !important;
        }
        .message-item:hover .custom-badge.pending {
            background-color: #e5ac00 !important;
        }
        .message-item:hover .custom-badge.confirmed {
            background-color: #218838 !important;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success"style="margin-left:20%"><?php echo $success_message; ?></div>
        <?php endif; ?>
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger"style="margin-left:20%"><?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <div class="message-container">
            <!-- Message List -->
            <div class="message-list">
                <h3>Messages</h3>
                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                    <div class="message-item <?php echo $row['status']; ?>" 
                         onclick="showMessage(<?php echo $row['id']; ?>)"
                         data-id="<?php echo $row['id']; ?>">
                        <h5><?php echo htmlspecialchars($row['name']); ?></h5>
                        <span class="date-text">
                            <?php echo date('M d, Y H:i', strtotime($row['created_at'])); ?>
                        </span>
                        <span class="custom-badge <?php echo $row['status']; ?>">
                            <?php echo ucfirst($row['status']); ?>
                        </span>
                    </div>
                <?php endwhile; ?>
            </div>
            
            <!-- Message Detail -->
            <div class="message-detail" id="messageDetail">
                <div class="text-center text-muted">
                    <h4>Select a message to view details</h4>
                </div>
            </div>
        </div>
    </div>

    <script>
    function showMessage(id) {
    // Show loading state
    document.getElementById('messageDetail').innerHTML = `
        <div class="text-center">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Loading message details...</p>
        </div>
    `;

    // Log the fetch URL for debugging
    const fetchUrl = `get_message.php?id=${id}`;
    console.log('Fetching from:', fetchUrl);

    fetch(fetchUrl)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(response => {
            console.log('Server response:', response); // Debug log

            if (!response.success) {
                throw new Error(response.error || 'Unknown error occurred');
            }

            const data = response.data;
            
            const detailHtml = `
                <h3>Message Details</h3>
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">${data.name || ''}</h5>
                        <h6 class="card-subtitle mb-2 text-muted">${data.email || ''}</h6>
                        <p class="card-text"><strong>Problem:</strong> ${data.problem || ''}</p>
                        <p class="card-text"><strong>Details:</strong> ${data.details || ''}</p>
                        <p class="card-text"><strong>Query:</strong> ${data.query || ''}</p>
                        <p class="card-text"><small class="text-muted">Created: ${data.created_at || ''}</small></p>
                        
                        ${data.status !== 'confirmed' ? `
                            <form method="POST" action="">
                                <input type="hidden" name="message_id" value="${data.id}">
                                <input type="hidden" name="email" value="${data.email}">
                                <input type="hidden" name="name" value="${data.name}">
                                
                                <div class="reply-box">
                                    <h5>Your Response</h5>
                                    <textarea name="reply_message" class="form-control" 
                                        placeholder="Type your response here..." required></textarea>
                                    <button type="submit" name="confirm_message" class="btn btn-send">
                                        Send Response & Confirm
                                    </button>
                                </div>
                            </form>
                        ` : `
                            <div class="confirmed-message">
                                <h5>Message Confirmed</h5>
                                ${data.reply_message ? `
                                    <div class="response-box">
                                        <strong>Your Response:</strong>
                                        <p class="mt-2">${data.reply_message}</p>
                                    </div>
                                ` : '<p>No response was recorded.</p>'}
                            </div>
                        `}
                    </div>
                </div>
            `;
            document.getElementById('messageDetail').innerHTML = detailHtml;
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('messageDetail').innerHTML = `
                <div class="alert alert-danger">
                    <h5>Error</h5>
                    <p>${error.message}</p>
                    <hr>
                    <p class="mb-0">Technical details have been logged to the console.</p>
                </div>
            `;
        });
}

    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>