<?php
include '../db/db.php';
//menu bar
include '../menu/adminmenu.php';


// Initialize variables for messages
$success = false;
$error = false;
$success_message = "";
$error_message = "";

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $offer_name = trim($_POST["offer_name"]);
    $discount_code = trim($_POST["discount_code"]);
    $discount_amount = (int) $_POST["discount_amount"];
    $expiry_date = $_POST["expiry_date"];

    // Validate inputs
    if (empty($offer_name) || empty($discount_code) || $discount_amount < 0 || $discount_amount > 100 || empty($expiry_date)) {
        $error = true;
        $error_message = "Please fill out all fields correctly.";
    } else {
        // Insert into database
        $stmt = $conn->prepare("INSERT INTO offers (offer_name, discount_code, discount_amount, expiry_date) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssis", $offer_name, $discount_code, $discount_amount, $expiry_date);

        if ($stmt->execute()) {
            $success = true;
            $success_message = "Offer added successfully!";
        } else {
            $error = true;
            $error_message = "Error: " . $stmt->error;
        }

        $stmt->close();
    }
}

$conn->close();
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Offer</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/js/all.min.js"></script>
    <style>
            @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        
        body {
            font-family: 'Inter', 'Segoe UI', sans-serif;
            background-color: #f8f9fa;
        }

        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.15);
        }

        .card-header {
            background: linear-gradient(45deg, #4F46E5, #7C3AED);
            color: white;
            border-radius: 15px 15px 0 0 !important;
            padding: 1.5rem;
        }

        .form-label {
            font-weight: 500;
            color: #4B5563;
            margin-bottom: 0.5rem;
        }

        .form-control, .form-select {
            border-radius: 10px;
            padding: 0.75rem 1rem;
            border: 2px solid #E5E7EB;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: #4F46E5;
            box-shadow: 0 0 0 0.25rem rgba(79, 70, 229, 0.1);
        }

        .btn-primary {
            background: linear-gradient(45deg, #4F46E5, #7C3AED);
            border: none;
            padding: 0.75rem 2rem;
            border-radius: 10px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(79, 70, 229, 0.4);
        }

        .input-group-text {
            background: #F3F4F6;
            border: 2px solid #E5E7EB;
            border-radius: 10px 0 0 10px;
        }

        .form-control.is-invalid {
            border-color: #DC2626;
        }

        .invalid-feedback {
            color: #DC2626;
            font-size: 0.875rem;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-fade-in {
            animation: fadeInUp 0.5s ease forwards;
        }

        .success-message {
            display: none;
            background-color: #D1FAE5;
            color: #065F46;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1rem;
            animation: fadeInUp 0.5s ease forwards;
        }
        h1{
            text-align: center;
            margin-top: 2%;
            font-weight: 600;
            margin-left: 15%;
        }
        .container{
            margin-left: 15%;
        }
    </style>
</head>
<body>
     
    <!-- Message Container -->
    <div class="message-container">
        <?php if ($success): ?>
        <div class="toast toast-success show" role="alert">
            <div class="toast-header">
                <i class="fas fa-check-circle text-success me-2"></i>
                <strong class="me-auto">Success</strong>
                <button type="button" class="btn-close" onclick="this.parentElement.parentElement.style.display='none'"></button>
            </div>
            <div class="toast-body">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($error): ?>
        <div class="toast toast-error show" role="alert">
            <div class="toast-header">
                <i class="fas fa-exclamation-circle text-danger me-2"></i>
                <strong class="me-auto">Error</strong>
                <button type="button" class="btn-close" onclick="this.parentElement.parentElement.style.display='none'"></button>
            </div>
            <div class="toast-body">
                <?php echo htmlspecialchars($error); ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <div class="container py-3">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card animate-fade-in">
                    <div class="card-header">
                        <h4 class="mb-0">
                            <i class="fas fa-gift me-2"></i>
                            Add New Offer
                        </h4>
                    </div>
                    <div class="card-body p-4">
                        <form id="offerForm" action="../model/create_offer.php" method="POST" class="needs-validation" novalidate>
                            <!-- Your existing form fields remain the same -->
                            <div class="row g-4">
                                <div class="col-md-12">
                                    <label for="offerName" class="form-label">Offer Name</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-tag"></i>
                                        </span>
                                        <input type="text" class="form-control" id="offerName" name="offer_name" required>
                                        <div class="invalid-feedback">
                                            Please provide an offer name.
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label for="discountCode" class="form-label">Discount Code</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-ticket"></i>
                                        </span>
                                        <input type="text" class="form-control" id="discountCode" name="discount_code" required>
                                        <div class="invalid-feedback">
                                            Please provide a discount code.
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label for="discountAmount" class="form-label">Discount Amount (Tk)</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-percent"></i>
                                        </span>
                                        <input type="number" class="form-control" id="discountAmount" name="discount_amount" min="0" max="100" required>
                                        <div class="invalid-feedback">
                                            Please provide a valid discount amount (0-100).
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-12">
                                    <label for="expiryDate" class="form-label">Expiry Date</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-calendar"></i>
                                        </span>
                                        <input type="date" class="form-control" id="expiryDate" name="expiry_date" required>
                                        <div class="invalid-feedback">
                                            Please select an expiry date.
                                        </div>
                                    </div>
                                </div>

                                <div class="col-12 mt-4">
                                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                        <a href="../model/offer.php" class="btn btn-outline-secondary me-2">
                                            <i class="fas fa-arrow-left me-2"></i>
                                            Back
                                        </a>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save me-2"></i>
                                            Save Offer
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
    <script>
        // Form validation
        (function() {
            'use strict'
            var forms = document.querySelectorAll('.needs-validation')
            Array.prototype.slice.call(forms)
                .forEach(function(form) {
                    form.addEventListener('submit', function(event) {
                        if (!form.checkValidity()) {
                            event.preventDefault()
                            event.stopPropagation()
                        }
                        form.classList.add('was-validated')
                    }, false)
                })
        })()

        // Auto-hide messages after 5 seconds
        setTimeout(function() {
            document.querySelectorAll('.toast').forEach(function(toast) {
                toast.style.display = 'none';
            });
        }, 5000);
    </script>
</body>
</html>