<?php
// Start session and check user login status
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: /model/login.php");
    exit();
}

$customer_id = $_SESSION['user_id'];
require '../db/db.php';

// Function to handle file upload
function handleFileUpload($file, $uploadDir) {
    // Create upload directory if it doesn't exist
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    // Generate unique filename
    $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $newFilename = uniqid() . '.' . $fileExtension;
    $targetPath = $uploadDir . $newFilename;

    // Validate file type
    $allowedTypes = ['jpg', 'jpeg', 'png'];
    if (!in_array($fileExtension, $allowedTypes)) {
        throw new Exception('Only JPG, JPEG & PNG files are allowed');
    }

    // Validate file size (max 5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        throw new Exception('File size should not exceed 5MB');
    }

    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        throw new Exception('Failed to upload file');
    }

    return $targetPath;
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $name = $_POST['name'];
        $department = $_POST['department'];
        $deposit = $_POST['deposit'];
        $varsity_id = $_POST['varsity_id'];
        $email = $_POST['email'];
        $meal_date = $_POST['meal_date'];

        // Handle ID card uploads
        $uploadDir = '../uploads/id_cards/';
        $idCardFrontPath = '';
        $idCardBackPath = '';

        if (isset($_FILES['idCardFront']) && $_FILES['idCardFront']['error'] === UPLOAD_ERR_OK) {
            $idCardFrontPath = handleFileUpload($_FILES['idCardFront'], $uploadDir);
        } else {
            throw new Exception('Front ID card image is required');
        }

        if (isset($_FILES['idCardBack']) && $_FILES['idCardBack']['error'] === UPLOAD_ERR_OK) {
            $idCardBackPath = handleFileUpload($_FILES['idCardBack'], $uploadDir);
        } else {
            throw new Exception('Back ID card image is required');
        }

        $sql = "INSERT INTO meal_registration (customer_id, name, department, deposit, varsity_id, email, meal_date, id_card_front, id_card_back) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);

// Fix: Add an extra 's' to the type definition string to match the 9 parameters
$stmt->bind_param("issdsssss", 
    $customer_id,    // i (integer)
    $name,          // s (string)
    $department,    // s (string)
    $deposit,       // d (double)
    $varsity_id,    // s (string)
    $email,         // s (string)
    $meal_date,     // s (string)
    $idCardFrontPath, // s (string)
    $idCardBackPath   // s (string)
);

if ($stmt->execute()) {
    $message = "<div class='success'>Meal registration successful! 
                <p class='note bg-warning'>Note: Please Wait For Sometime To Verify .</p></div>";
} else {
    throw new Exception($stmt->error);
}

$stmt->close();
    } catch (Exception $e) {
        $message = "<div class='error'>Error: " . $e->getMessage() . "</div>";
        
        // Clean up uploaded files if database insertion fails
        if (isset($idCardFrontPath) && file_exists($idCardFrontPath)) {
            unlink($idCardFrontPath);
        }
        if (isset($idCardBackPath) && file_exists($idCardBackPath)) {
            unlink($idCardBackPath);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meal Registration</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            min-height: 100vh;
        }
       
        .registration-container {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 40px 20px;
        }

        .registration-form {
            background: #ffffff;
            padding: 40px;
            border-radius: 20px;
            width: 70%;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        }

        h2 {
            text-align: center;
            color: #333;
            margin-bottom: 40px;
            font-size: 32px;
            position: relative;
            padding-bottom: 15px;
        }

        h2:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 4px;
            background: linear-gradient(90deg, #ff6b6b, #4ecdc4);
            border-radius: 2px;
        }

        h2 i {
            background: linear-gradient(45deg, #ff6b6b, #4ecdc4);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-right: 10px;
        }

        .form-row {
            display: flex;
            margin: 0 -15px 25px;
        }

        .input-group {
            flex: 1;
            padding: 0 15px;
            position: relative;
        }

        .input-group label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 500;
            font-size: 0.95rem;
        }

        .input-wrapper {
            position: relative;
        }

        .input-wrapper .icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 18px;
        }

        /* Colorful icons */
        .icon.fa-user { color: #4ecdc4; }
        .icon.fa-building { color: #ff6b6b; }
        .icon.fa-money-bill { color: #45b649; }
        .icon.fa-id-card { color: #6c5ce7; }
        .icon.fa-envelope { color: #a8e6cf; }
        .icon.fa-calendar { color: #ff8b94; }
        .icon.fa-upload { color: #3498db; }

        .input-wrapper input,
        .input-wrapper select {
            width: 100%;
            padding: 12px 15px 12px 45px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 15px;
            transition: all 0.3s;
            background-color: #fff;
        }

        .input-wrapper input:focus,
        .input-wrapper select:focus {
            border-color: #4ecdc4;
            box-shadow: 0 0 0 4px rgba(78, 205, 196, 0.1);
            outline: none;
        }

        .input-wrapper input[readonly] {
            background-color: #f8f9fa;
            border-color: #ddd;
        }

        .file-input-group {
            margin-bottom: 15px;
        }

        .file-input-wrapper {
            position: relative;
        }

        .file-input-wrapper input[type="file"] {
            display: none;
        }

        .file-input-wrapper label {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 20px;
            background: #f8f9fa;
            border: 2px dashed #ddd;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .file-input-wrapper label:hover {
            border-color: #4ecdc4;
            background: #f0fffd;
        }

        .preview-container {
            margin-top: 15px;
            text-align: center;
        }

        .preview-image {
            max-width: 200px;
            max-height: 150px;
            border-radius: 10px;
            display: none;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .bt {
            background: linear-gradient(45deg, #4ecdc4, #45b649);
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 10px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            margin-top: 20px;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .bt:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(78, 205, 196, 0.4);
        }

        .bt i {
            font-size: 20px;
        }

        .success, .error {
            padding: 15px;
            border-radius: 10px;
            margin: 20px 0;
            text-align: center;
            font-weight: 500;
        }

        .success {
            background: linear-gradient(45deg, #a8e6cf, #dcedc1);
            color: #1d4d4f;
        }

        .error {
            background: linear-gradient(45deg, #ff8b94, #ffd3b6);
            color: #943838;
        }

        @media (max-width: 768px) {
            .form-row {
                flex-direction: column;
                margin: 0;
            }

            .input-group {
                padding: 0;
                margin-bottom: 20px;
            }

            .registration-form {
                padding: 30px 20px;
                width: 100%;
                max-width: 500px;
            }
        }
    </style>
</head>
<body>
   
<?php include '../menu/menu.php' ?>
<div class="registration-container">
    <div class="registration-form">
        <h2><i class="fas fa-utensils"></i>Meal Registration</h2>
        <?php if (isset($message)) echo $message; ?>
        <form action="" method="POST" enctype="multipart/form-data">
            <!-- Name and Department Row -->
            <div class="form-row">
                <div class="input-group">
                    <label for="name">Full Name</label>
                    <div class="input-wrapper">
                        <i class="fas fa-user icon"></i>
                        <input type="text" name="name" value="<?php echo htmlspecialchars($_SESSION['name']); ?>" readonly required>
                    </div>
                </div>
                <div class="input-group">
                    <label for="department">Department</label>
                    <div class="input-wrapper">
                        <i class="fas fa-building icon"></i>
                        <select name="department" required>
                            <option value="">Select Department</option>
                            <option value="Computer Science">Computer Science & Engineering</option>
                            <option value="Business Administration">Business Administration</option>
                            <option value="Electrical Engineering">Electrical & Electronic Engineering</option>
                            <option value="Civil Engineering">Civil Engineering</option>
                            <option value="Pharmacy">Pharmacy</option>
                            <option value="English">English</option>
                            <option value="Law">Law</option>
                            <option value="Others">Others</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Deposit and ID Row -->
            <div class="form-row">
                <div class="input-group">
                    <label for="deposit">Initial Deposit (TK)</label>
                    <div class="input-wrapper">
                        <i class="fas fa-money-bill icon"></i>
                        <input type="number" name="deposit" placeholder="Minimum 2000 TK" required>
                    </div>
                </div>
                <div class="input-group">
                    <label for="varsity_id">Varsity ID</label>
                    <div class="input-wrapper">
                        <i class="fas fa-id-card icon"></i>
                        <input type="text" name="varsity_id" placeholder="Enter your varsity ID" required>
                    </div>
                </div>
            </div>

            <!-- Email and Date Row -->
            <div class="form-row">
                <div class="input-group">
                    <label for="email">Email Address</label>
                    <div class="input-wrapper">
                        <i class="fas fa-envelope icon"></i>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($_SESSION['email']); ?>" readonly required >
                    </div>
                </div>
                <div class="input-group">
                    <label for="meal_date">Meal Start Date</label>
                    <div class="input-wrapper">
                        <i class="fas fa-calendar icon"></i>
                        <input type="date" name="meal_date" required>
                    </div>
                </div>
            </div>

            <!-- ID Card Upload Row -->
            <div class="form-row">
                <div class="input-group">
                    <label>ID Card (Front)</label>
                    <div class="file-input-wrapper">
                        <label for="idCardFront">
                            <i class="fas fa-upload icon"></i>
                            <span>Choose Front Image</span>
                        </label>
                        <input type="file" name="idCardFront" id="idCardFront" accept="image/*" required>
                    </div>
                    <div class="preview-container">
                        <img id="frontPreview" class="preview-image" alt="Front ID Preview">
                    </div>
                </div>
                <div class="input-group">
                    <label>ID Card (Back)</label>
                    <div class="file-input-wrapper">
                        <label for="idCardBack">
                            <i class="fas fa-upload icon"></i>
                            <span>Choose Back Image</span>
                        </label>
                        <input type="file" name="idCardBack" id="idCardBack" accept="image/*" required>
                    </div>
                    <div class="preview-container">
                        <img id="backPreview" class="preview-image" alt="Back ID Preview">
                    </div>
                </div>
            </div>

            <button type="submit" class="bt">
                <i class="fas fa-check-circle"></i>
                Complete Registration
            </button>
        </form>
    </div>
</div>

<script>
function previewImage(input, previewId) {
    const preview = document.getElementById(previewId);
    const fileLabel = input.parentElement.querySelector('span');
    
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
            fileLabel.textContent = input.files[0].name;
        }
        reader.readAsDataURL(input.files[0]);
    }
}

document.getElementById('idCardFront').addEventListener('change', function() {
    previewImage(this, 'frontPreview');
});

document.getElementById('idCardBack').addEventListener('change', function() {
    previewImage(this, 'backPreview');
});

// Form validation
document.querySelector('form').addEventListener('submit', function(event) {
    const deposit = parseFloat(document.querySelector('input[name="deposit"]').value);
    if (deposit < 2000) {
        event.preventDefault();
        alert('Minimum deposit amount is 2000 TK');
    }
});
</script>

</body>
</html>