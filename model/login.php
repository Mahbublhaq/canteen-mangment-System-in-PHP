<?php
session_start();

// Include database connection
include_once '../db/db.php';
// Include Google login libraries
include_once "../vendor/autoload.php";

// Google Client Setup
$google_client = new Google_Client();
$google_client->setClientId('504819839343-3g6o301bhiuit1f4d1mel0sqsdq0abc2.apps.googleusercontent.com');
$google_client->setClientSecret('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');
$google_client->setRedirectUri('http://localhost:3000/model/login.php');
$google_client->addScope('email');
$google_client->addScope('profile');

// Handle Google Login
if(isset($_GET["code"]))
{
    $token = $google_client->fetchAccessTokenWithAuthCode($_GET["code"]);

    if(!isset($token["error"]))
    {
        $google_client->setAccessToken($token['access_token']);
        $_SESSION['access_token'] = $token['access_token'];

        $google_service = new Google_Service_Oauth2($google_client);
        $data = $google_service->userinfo->get();

        // Check if Google user exists in customers table
        $google_email = $data['email'];
        $stmt = $conn->prepare("SELECT * FROM customers WHERE email = ?");
        $stmt->bind_param("s", $google_email);
        $stmt->execute();
        $result = $stmt->get_result();

        if($result->num_rows > 0) {
            // User exists in customers table
            $user = $result->fetch_assoc();
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['name'] = $user['customer_name'];
            $_SESSION['role'] = 'customer';
            header("Location: welcome.php");
            exit();
        } 
        
        // Check if Google user exists in admins table
        $stmt = $conn->prepare("SELECT * FROM admins WHERE email = ?");
        $stmt->bind_param("s", $google_email);
        $stmt->execute();
        $result = $stmt->get_result();

        if($result->num_rows > 0) {
            // User exists in admins table
            $user = $result->fetch_assoc();
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['name'] = $user['admin_name'];
            $_SESSION['role'] = 'admin';
            header("Location: admin.php");
            exit();
        }

        // If Google user doesn't exist in either table, redirect to signup
        $_SESSION['google_signup_email'] = $google_email;
        $_SESSION['google_signup_name'] = $data['given_name'] . ' ' . $data['family_name'];
        $_SESSION['google_signup_picture'] = $data['picture'];
        header("Location: signup.php?google_signup=1");
        exit();
    }
}

// Handle traditional login
$login_errors = [];
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize inputs
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $role = $_POST['role'] ?? 'customer'; // Default to customer if not specified

    // Validate inputs
    if (empty($email)) $login_errors[] = "Email is required";
    if (empty($password)) $login_errors[] = "Password is required";

    if (empty($login_errors)) {
        // Determine the correct table based on role
        $table = ($role === 'admin') ? 'admins' : 'customers';
        $name_column = ($role === 'admin') ? 'admin_name' : 'customer_name';

        // Check user credentials
        $stmt = $conn->prepare("SELECT * FROM $table WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            // Verify password
            if(password_verify($password, $user['password'])) {
                // Successful login
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['name'] = $user[$name_column];
                $_SESSION['role'] = $role;

                // Redirect based on role
                header("Location: " . ($role === 'admin' ? "admin.php" : "welcome.php"));
                exit();
            } else {
                $login_errors[] = "Invalid email or password";
            }
        } else {
            $login_errors[] = "Invalid email or password";
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
    <title>Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.5/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body {
            background: url('../static/1.jpeg') no-repeat center center/cover;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0;
        }

        .login-container {
            background: rgba(252, 247, 247, 0.85);
            border-radius: 15px;
            padding: 20px;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
            animation: fadeIn 1s ease-in-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .login-container h2 {
            font-weight: bold;
            color: #333;
            text-align: center;
            margin-bottom: 20px;
        }

        .input-group-text {
            background-color: #0d6efd;
            color: #fff;
            border: none;
        }

        .btn-primary {
            background-color: #0d6efd;
            border: none;
            transition: all 0.3s ease-in-out;
        }

        .btn-primary:hover {
            background-color: #0b5ed7;
            transform: scale(1.05);
        }

        .text-center p {
            color: #666;
        }

        .error-message {
            background-color: #f8d7da;
            color: #842029;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            text-align: center;
        }

        /* Updated role input styling to match other inputs */
        .input-group-role {
            margin-bottom: 15px;
        }

        .input-group-role .input-group-text {
            background-color: #0d6efd;
            color: #fff;
            border: none;
        }

        .input-group-role .form-select {
            border-left: none;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>City Canteen Login</h2>

        <?php
        if (!empty($login_errors)) {
            echo '<div class="error-message">';
            foreach ($login_errors as $error) {
                echo '<p>' . htmlspecialchars($error) . '</p>';
            }
            echo '</div>';
        }
        ?>

        <form method="POST">
            <div class="input-group mb-3">
                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                <input type="email" class="form-control" id="email" name="email" placeholder="Email" required
                       value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>">
            </div>

            <div class="input-group mb-3">
                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
            </div>

            <div class="input-group input-group-role">
                <span class="input-group-text"><i class="bi bi-person"></i></span>
                <select class="form-select" id="role" name="role" required>
                    <option value="customer">Customer</option>
                    <option value="admin">Admin</option>
                </select>
            </div>

            <button type="submit" class="btn btn-primary w-100">Login</button>
        </form>

        <div class="text-center mt-3">
            <p>Or login with</p>
            <div><?php echo $login_button; ?></div>
            <p class="mt-3">Don't have an account? <a href="signup.php">Signup</a></p>
            <p><a href="reset_password.php">Forgot Password?</a></p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>