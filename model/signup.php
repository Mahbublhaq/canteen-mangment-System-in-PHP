<?php
session_start();

// Include database connection
include_once "../db/db.php";
// Include Google login and PHPMailer libraries
include_once "../vendor/autoload.php";

// Use PHPMailer namespaces
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Google Client Setup
$google_client = new Google_Client();
$google_client->setClientId('504819839343-3g6o301bhiuit1f4d1mel0sqsdq0abc2.apps.googleusercontent.com');
$google_client->setClientSecret('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');
$google_client->setRedirectUri('http://localhost:3000/model/signup.php');
$google_client->addScope('email');
$google_client->addScope('profile');

// Function to send registration email
function sendRegistrationEmail($email, $name, $deposit = 0) {
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
        $mail->Subject = 'Successfull Create User Account In City University Cantten ';
        $mail->Body    = "
            <div style='background-color:rgb(235, 239, 235); color: black;font-size:16px; padding: 20px; border-radius: 10px;'>
                <h2>Successfull Create User Account In City University Cantten </h2>
                <p>Hello $name,</p>
                <p>Thank you for registering with City University Canteen.</p>

                <p>Your account has been created successfully. You can now login to your account using your email and password.</p>
                <h3 style='background-color:red;color:white;'>Any query please contact with us.</h3>
                <p>Email:citycanteen@city_university.ac.bd</p>
                <p>Phone: 01700000000</p>

            </div>";
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        // Log email error but don't stop the transaction
        error_log("Email sending failed: " . $mail->ErrorInfo);
        return false;
    }
}

// Handle Google Signup/Login
if(isset($_GET["code"]))
{
    $token = $google_client->fetchAccessTokenWithAuthCode($_GET["code"]);

    if(!isset($token["error"]))
    {
        $google_client->setAccessToken($token['access_token']);
        $_SESSION['access_token'] = $token['access_token'];

        $google_service = new Google_Service_Oauth2($google_client);
        $data = $google_service->userinfo->get();
        $current_datetime = date('Y-m-d H:i:s');

        // Prepare Google signup data
        $google_email = $data['email'];
        $google_name = $data['given_name'] . ' ' . $data['family_name'];
        $google_profile_pic = $data['picture'];

        // Check if user already exists
        $stmt = $conn->prepare("SELECT * FROM customers WHERE email = ?");
        $stmt->bind_param("s", $google_email);
        $stmt->execute();
        $result = $stmt->get_result();

        if($result->num_rows == 0) {
            // Insert new Google user
            $insert_stmt = $conn->prepare("INSERT INTO customers (customer_name, email, password, profile_picture, created_at, signup_method) VALUES (?, ?, ?, ?, ?, 'google')");
            $hashed_password = password_hash(uniqid(), PASSWORD_DEFAULT); // Random password for Google signup
            $insert_stmt->bind_param("sssss", $google_name, $google_email, $hashed_password, $google_profile_pic, $current_datetime);
            $insert_stmt->execute();

            // Send registration email for Google signup
            sendRegistrationEmail($google_email, $google_name);
        }

        // Set session variables
        $_SESSION['customer_name'] = $google_name;
        $_SESSION['email'] = $google_email;
        header("Location: login.php"); // Redirect to dashboard
        exit();
    }
}

// Handle traditional signup form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and validate input
    $customer_name = filter_input(INPUT_POST, 'customer_name', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING);
    $address = filter_input(INPUT_POST, 'address', FILTER_SANITIZE_STRING);
    $current_datetime = date('Y-m-d H:i:s');

    // Validate inputs
    $errors = [];
    if (empty($customer_name)) $errors[] = "Name is required";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format";
    if (empty($password)) $errors[] = "Password is required";
    if ($password !== $confirm_password) $errors[] = "Passwords do not match";

    // Check if email already exists
    $stmt = $conn->prepare("SELECT * FROM customers WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    if($result->num_rows > 0) $errors[] = "Email already exists";

    // Handle profile picture upload
    $profile_picture = null;
    if(isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
        $upload_dir = 'uploads/profile_pictures/';
        if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
        
        $filename = uniqid() . '_' . basename($_FILES['profile_picture']['name']);
        $upload_path = $upload_dir . $filename;
        
        if(move_uploaded_file($_FILES['profile_picture']['tmp_name'], $upload_path)) {
            $profile_picture = $upload_path;
        } else {
            $errors[] = "Failed to upload profile picture";
        }
    }

    // If no errors, insert into database
    if (empty($errors)) {
        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Prepare and execute insert statement
        $insert_stmt = $conn->prepare("INSERT INTO customers (customer_name, email, password, phone, address, created_at, profile_picture, signup_method) VALUES (?, ?, ?, ?, ?, ?, ?, 'traditional')");
        $insert_stmt->bind_param("sssssss", $customer_name, $email, $hashed_password, $phone, $address, $current_datetime, $profile_picture);
        
        if($insert_stmt->execute()) {
            // Send registration email for traditional signup
            sendRegistrationEmail($email, $customer_name);

            // Set session and redirect
            $_SESSION['customer_name'] = $customer_name;
            $_SESSION['email'] = $email;
            header("Location: login.php");
            exit();
        } else {
            $errors[] = "Signup failed. Please try again.";
        }
    }
}

// Prepare Google login button
$login_button = '<a href="'.$google_client->createAuthUrl().'"><img src="../static/sign-in-with-google.png" style="width: 250px; height: auto;" /></a>';
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - City Canteen</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: url('../static/1.jpeg') no-repeat center center/cover;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0;
            padding: 0;
        }
        .signup-container {
            background: rgba(53, 53, 53, 0.85);
            border-radius: 15px;
            padding: 20px;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
        }
        .signup-container h2 {
            font-weight: bold;
            color: #333;
            text-align: center;
            margin-bottom: 20px;
        }
        .input-group {
            margin-bottom: 15px;
        }
        .input-group-text {
            background-color: #ff5722;
            color: #fff;
            border: none;
        }
        .btn-primary {
            background-color: #ff5722;
            border: none;
            width: 100%;
        }
        .btn-primary:hover {
            background-color: #e64a19;
        }
        .text-center p {
            color: #666;
            margin: 10px 0;
        }
        .error-message p {
            background-color: #f8d7da;
            color: #842029;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="signup-container">
        <h2 style="color:white;">Sign Up - City Canteen</h2>

        <?php
        if (!empty($errors)) {
            echo '<div class="error-message">';
            foreach ($errors as $error) {
                echo '<p>' . htmlspecialchars($error) . '</p>';
            }
            echo '</div>';
        }
        ?>

        <form method="POST" enctype="multipart/form-data">
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-person"></i></span>
                <input type="text" class="form-control" id="customer_name" name="customer_name" placeholder="Full Name" required
                       value="<?php echo isset($customer_name) ? htmlspecialchars($customer_name) : ''; ?>">
            </div>

            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                <input type="email" class="form-control" id="email" name="email" placeholder="Email" required
                       value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>">
            </div>

            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                <input type="password" class="form-control" id="password" name="password" placeholder="Ex-PassworD123" required>
            </div>

            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Confirm Password" required>
            </div>

            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                <input type="tel" class="form-control" id="phone" name="phone" placeholder="Phone Number"
                       value="<?php echo isset($phone) ? htmlspecialchars($phone) : ''; ?>">
            </div>

            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-house"></i></span>
                <textarea class="form-control" id="address" name="address" rows="3" placeholder="Address">
                    <?php echo isset($address) ? htmlspecialchars($address) : ''; ?>
                </textarea>
            </div>

            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-camera"></i></span>
                <input type="file" class="form-control" id="profile_picture" name="profile_picture" accept="image/*">
            </div>

            <button type="submit" class="btn btn-primary">Signup</button>
        </form>

        <div class="text-center">
            <p>Or signup with</p>
            <h3><?php echo $login_button; ?></h3>
            <p>Already have an account? <a href="login.php">Login</a></p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.querySelector('form');
        const emailInput = document.getElementById('email');
        const passwordInput = document.getElementById('password');
        const confirmPasswordInput = document.getElementById('confirm_password');
        const phoneInput = document.getElementById('phone');
        const nameInput = document.getElementById('customer_name');


        form.addEventListener('submit', function (event) {
            // Email validation
            const emailRegex = /^[a-zA-Z0-9._%+-]{7,}@(gmail\.com|outlook\.com|yahoo\.com)$/;
            if (!emailRegex.test(emailInput.value)) {
                event.preventDefault();
                alert('Invalid email. Email must end with @gmail.com, @outlook.com, or @yahoo.com and have at least 7 characters before "@"');
                emailInput.focus();
                return;
            }

            // Password validation
            const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])[A-Za-z\d]{8,}$/;
            //example 

            if (!passwordRegex.test(passwordInput.value)) {
                event.preventDefault();
                alert('Password must be at least 8 characters long, include one uppercase letter, and only contain letters.');
                passwordInput.focus();
                return;
            }

            // Confirm Password validation
            if (passwordInput.value !== confirmPasswordInput.value) {
                event.preventDefault();
                alert('Passwords do not match');
                confirmPasswordInput.focus();
                return;
            }

            // Phone number validation
            const phoneRegex = /^(017|016|108|019|014|013|015)\d{8}$/;
            if (!phoneRegex.test(phoneInput.value)) {
                event.preventDefault();
                alert('Invalid phone number. Phone number must start with 017, 016, 108, 019, 014, 013, or 015 and be 11 digits long.');
                phoneInput.focus();
                return;
            }
            //name validation
            const nameRegex = /^[a-zA-Z\s]{5,}$/;
            if (!nameRegex.test(nameInput.value)) {
                event.preventDefault();
                alert('Invalid name. Name must be at least 5 characters long and only contain letters.');
                nameInput.focus();
                return;
            }
        });
    });
</script>

</body>
</html>
