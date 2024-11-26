<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Database connection
include '../db/db.php';

// Fetch active offers
$sql = "SELECT * FROM offers WHERE expiry_date >= CURDATE()";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Available Offers</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(120deg, #000428, #004e92);
            font-family: 'Arial', sans-serif;
            animation: gradientBackground 8s infinite alternate;
            color: #fff;
        }

        @keyframes gradientBackground {
            0% { background-position: 0% 50%; }
            100% { background-position: 100% 50%; }
        }

        h2 {
            color: #ffcc00;
            text-shadow: 3px 3px 6px rgba(0, 0, 0, 0.7);
        }

        .coupon-card {
            background: #ffffff;
            border-radius: 15px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.3);
            margin: 20px;
            overflow: hidden;
            transition: transform 0.5s, box-shadow 0.5s;
            perspective: 1000px;
        }

        .coupon-card:hover {
            transform: rotateY(10deg) scale(1.05);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
        }

        .card-content {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 20px;
            text-align: center;
        }

        .logo-section h5 {
            font-size: 24px;
            font-weight: bold;
            color: #004e92;
        }

        .validity {
            font-size: 14px;
            color: #666;
        }

        .countdown {
            font-size: 16px;
            color: #d63384;
            font-weight: bold;
            margin-top: 10px;
        }

        .discount-banner {
            background: linear-gradient(145deg, #ff512f, #dd2476);
            color: #fff;
            font-size: 40px;
            font-weight: bold;
            text-align: center;
            padding: 20px;
            clip-path: polygon(0 0, 100% 0, 100% 85%, 50% 100%, 0 85%);
            animation: bannerGlow 1.5s infinite;
        }

        @keyframes bannerGlow {
            0% { box-shadow: 0 0 10px #ff512f; }
            50% { box-shadow: 0 0 30px #dd2476; }
            100% { box-shadow: 0 0 10px #ff512f; }
        }

        .discount-code {
            display: inline-block;
            font-size: 18px;
            margin-top: 15px;
            padding: 10px 20px;
            border: 2px dashed #004e92;
            border-radius: 12px;
            cursor: pointer;
            transition: background-color 0.3s, transform 0.3s;
        }

        .discount-code:hover {
            background-color: #004e92;
            color: #fff;
            transform: scale(1.1);
        }

        @media (max-width: 768px) {
            .coupon-card {
                transform: none;
                margin: 15px;
            }

            .discount-banner {
                font-size: 28px;
                clip-path: none;
                border-radius: 10px;
            }
        }
    </style>
    <script>
        // Countdown Timer Function
        function startCountdown(expiryDate, elementId) {
            function updateTimer() {
                const now = new Date();
                const expiry = new Date(expiryDate);
                const difference = expiry - now;

                if (difference <= 0) {
                    document.getElementById(elementId).innerText = "Offer expired";
                    clearInterval(interval);
                    return;
                }

                const days = Math.floor(difference / (1000 * 60 * 60 * 24));
                const hours = Math.floor((difference % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const minutes = Math.floor((difference % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((difference % (1000 * 60)) / 1000);

                document.getElementById(elementId).innerText = `Time Left: ${days}d ${hours}h ${minutes}m ${seconds}s`;
            }

            const interval = setInterval(updateTimer, 1000);
            updateTimer();
        }

        // Copy discount code to clipboard
        function copyCode(code) {
            navigator.clipboard.writeText(code);
            alert("Copied: " + code);
        }
    </script>
</head>
<body>
    <?php include '../menu/menu.php'; ?>
    <div class="container mt-5">
        <h2 class="text-center mb-4">Available Discount Coupons</h2>
        <div class="row justify-content-center">
            <?php while ($row = $result->fetch_assoc()) { ?>
                <div class="col-md-4">
                    <div class="coupon-card">
                        <div class="card-content">
                            <h5>CITY CANTEEN</h5>
                            <p class="coupon-text">Coupon</p>
                            <span class="validity">Valid Until: <?php echo $row['expiry_date']; ?></span>
                            <div class="countdown" id="timer-<?php echo $row['id']; ?>"></div>
                            <div class="discount-code" onclick="copyCode('<?php echo $row['discount_code']; ?>')">
                                <?php echo $row['discount_code']; ?>
                            </div>
                        </div>
                        <div class="discount-banner">
                            <?php echo $row['discount_amount']; ?> TK OFF
                        </div>
                    </div>
                </div>
                <script>
                    startCountdown("<?php echo $row['expiry_date']; ?>", "timer-<?php echo $row['id']; ?>");
                </script>
            <?php } ?>
        </div>
    </div>
</body>
</html>
