<?php
require '../db/db.php';
require '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $address = $_POST['address'] ?? '';
    $phone = $_POST['phone'] ?? '';

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $sql = "INSERT INTO customers (customer_name, email, password, address, phone) VALUES (?, ?, ?, ?, ?)";
    
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("sssss", $name, $email, $hashed_password, $address, $phone);

        // Execute and check if insertion was successful
        if ($stmt->execute()) {
            echo "New record created successfully";

            // Send confirmation email using PHPMailer
            $mail = new PHPMailer(true); // Create a new PHPMailer instance

            try {
                //Server settings
                $mail->isSMTP();                                         // Send using SMTP
                $mail->Host       = 'smtp.gmail.com';                    // Set the SMTP server to send through
                $mail->SMTPAuth   = true;                                // Enable SMTP authentication
                $mail->Username   = 'gourob.haq@gmail.com';              // Your Gmail address
                $mail->Password   = 'owtc hcch zufy cgey';               // Your Gmail password (or App Password if using 2FA)
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;         // Enable TLS encryption
                $mail->Port       = 465;                                 // TCP port for Gmail (usually 465 for SSL)

                //Recipients
                $mail->setFrom('your-email@gmail.com', 'City University Canteen');
                $mail->addAddress($email, $name);                        // Add a recipient

                // Content
                $mail->isHTML(true);                                     // Set email format to HTML
                $mail->Subject = 'Signup Successful in City University Canteen';
                $mail->Body    = "Hello $name,<br><br>Thank you for signing up at the City University Canteen.<br><br>Best Regards,<br>City University Canteen Team";

                // Send the email
                $mail->send();
                echo 'Signup confirmation email sent';
            } catch (Exception $e) {
                echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
            }
        } else {
            echo "Error: " . $stmt->error;
        }

        // Close the statement
        $stmt->close();
    } else {
        echo "Error: " . $conn->error;
    }
    header("Location: ../view/login.html");
    // Close the connection
    $conn->close();
}
