<?php
require '../db/db.php';
require '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Initialize variables for success and error messages
$successMessage = "";
$errorMessage = "";

// Check if the form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect form data
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $problem = $_POST['problem'] ?? '';
    $details = $_POST['details'] ?? '';
    $query = $_POST['query'] ?? '';

    // Check if the query has already been submitted (avoid re-insertion)
    if (!isset($_SESSION['query_submitted']) || $_SESSION['query_submitted'] !== $query) {
        try {
            // Prepare the SQL statement
            $stmt = $conn->prepare("INSERT INTO contact_queries (name, email, problem, details, query) VALUES (?, ?, ?, ?, ?)");
            if (!$stmt) {
                throw new Exception("Failed to prepare SQL statement: " . $conn->error);
            }

            // Bind parameters and execute the statement
            $stmt->bind_param("sssss", $name, $email, $problem, $details, $query);

            if ($stmt->execute()) {
                // Send confirmation email using PHPMailer
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
                    $mail->setFrom('your-email@gmail.com', 'City University Canteen');
                    $mail->addAddress('your-email@gmail.com', 'Admin');      

                    // Content
                    $mail->isHTML(true);                                     
                    $mail->Subject = 'New Contact Form Submission';
                    $mail->Body    = "<h1>New Query Received</h1>
                                    <p><strong>Name:</strong> $name</p>
                                    <p><strong>Email:</strong> $email</p>
                                    <p><strong>Problem:</strong> $problem</p>
                                    <p><strong>Details:</strong> $details</p>
                                    <p><strong>Query:</strong> $query</p>";

                    // Send the email
                    $mail->send();
                    $successMessage = "Your query has been successfully submitted, and an email has been sent to the admin.";
                    $_SESSION['query_submitted'] = $query;  // Store the query to prevent re-submission
                } catch (Exception $e) {
                    $successMessage = "Your query has been successfully submitted, but the email could not be sent.";
                }
            } else {
                throw new Exception("Database insertion failed: " . $stmt->error);
            }

            // Close the prepared statement
            $stmt->close();
        } catch (Exception $e) {
            $errorMessage = $e->getMessage();
        }
    } else {
        $errorMessage = "This query has already been submitted.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background: white;
         
        }

        .contact-header {
         
            font-weight: 600;
            color:black;
            padding: 20px;
            text-align: center;
            border-radius: 10px;
            margin-bottom: 10px;
        }

        .contact-card {
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            padding: 25px;
        }

        .form-label {
            font-weight: 600;
        }

        .form-control:focus {
            border-color: #4a90e2;
            box-shadow: 0 0 5px rgba(74, 144, 226, 0.6);
        }

        .btn-primary {
            background: linear-gradient(135deg, #4a90e2, #50e3c2);
            border: none;
            color: white;
            padding: 12px 25px;
            font-size: 16px;
            border-radius: 30px;
            transition: background 0.3s;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #50e3c2, #4a90e2);
        }

        .message {
            padding: 10px;
            border-radius: 5px;
            text-align: center;
            margin-top: 15px;
        }

        .success-message {
            background-color: #d4edda;
            color: #155724;
            border-left: 5px solid #28a745;
        }

        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            border-left: 5px solid #dc3545;
        }

        .icon {
            font-size: 1.3rem;
            margin-right: 10px;
            vertical-align: middle;
        }
    </style>
</head>

<body>
    <?php include '../menu/menu.php'; ?>

    <header class="contact-header">
        <h1>Contact City University Canteen</h1>
        <p><i class="icon bi bi-geo-alt"></i> Address: City University Campus</p>
        <p><i class="icon bi bi-envelope"></i> Contact: contact@citycanteen.com</p>
    </header>
    <?php if ($successMessage): ?>
                        <div class="message success-message" id="success-message">
                            <?php echo $successMessage; ?>
                        </div>
                    <?php elseif ($errorMessage): ?>
                        <div class="message error-message" id="error-message">
                            <?php echo $errorMessage; ?>
                        </div>
                    <?php endif; ?>
    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="contact-card p-4">
                    <h3 class="text-center"style="color:red;font-weight: 600;">Send Us Your Query</h3>
                    <form id="contact-form" method="POST" action="">
                        <div class="mb-3">
                            <label for="name" class="form-label">Customer Name</label>
                            <input type="text" class="form-control" id="name" name="name" value="<?php echo isset($_SESSION['name']) ? $_SESSION['name'] : ''; ?>" <?php echo isset($_SESSION['name']) ? 'readonly' : ''; ?> required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo isset($_SESSION['email']) ? $_SESSION['email'] : ''; ?>" <?php echo isset($_SESSION['email']) ? 'readonly' : ''; ?> required>
                        </div>
                        <div class="mb-3">
                            <label for="problem" class="form-label">Select Option</label>
                            <select class="form-select" id="problem" name="problem" required>
                                <option value="Deposit Problem">Deposit Problem</option>
                                <option value="Food Problem">Food Problem</option>
                                <option value="Delivery Problem">Delivery Problem</option>
                                <option value="Packing Problem">Packing Problem</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="mb-3" id="other-details" style="display: none;">
                            <label for="details" class="form-label">Write in Details</label>
                            <textarea class="form-control" id="details" name="details" rows="3"></textarea>
                        </div>
                        <div class="mb-3" id="query-div">
                            <label for="query" class="form-label">Any Query</label>
                            <textarea class="form-control" id="query" name="query" rows="3"></textarea>
                        </div>
                        <div class="text-center">
                            <button type="submit" class="btn btn-primary">Send</button>
                        </div>
                    </form>
                    
                 
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS (Optional) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle 'Other' details based on selected option
        document.getElementById('problem').addEventListener('change', function () {
            if (this.value === 'Other') {
                document.getElementById('other-details').style.display = 'block';
            } else {
                document.getElementById('other-details').style.display = 'none';
            }
        });
        //when seslct other than hide Any Query
        document.getElementById('problem').addEventListener('change', function () {
            if (this.value === 'Other') {
                document.getElementById('query-div').style.display = 'none';
            } else {
                document.getElementById('query-div').style.display = 'block';
            }
        });

      // relode to resubmitaion block auto relode  in 2 second and not show resubmitation if relode
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }

        //AFTER click send button  2 SECOND RELODE
        document.getElementById('contact-form').addEventListener('submit', function () {
            setTimeout(function () {
                location.reload();
            }, 2000);
        });
    </script>
</body>

</html>
