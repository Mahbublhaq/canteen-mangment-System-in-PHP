<?php
require_once '../db/db.php';
require_once '../fpdf186/fpdf.php'; // Include FPDF library

if (!isset($_GET['order_id']) || !is_numeric($_GET['order_id'])) {
    die("Invalid order ID.");
}

$order_id = (int)$_GET['order_id'];

// Fetch order details
$sql = "SELECT * FROM orders WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Invalid order ID or order not found.");
}

$order = $result->fetch_assoc();
$stmt->close();

// Fetch customer name using guest_customer_id
$guest_customer_id = $order['gest_customer_id'];
$sqlCustomer = "SELECT customer_name, phone_number FROM guest_customer WHERE id = ?";
$stmtCustomer = $conn->prepare($sqlCustomer);
$stmtCustomer->bind_param("i", $guest_customer_id);
$stmtCustomer->execute();
$resultCustomer = $stmtCustomer->get_result();

if ($resultCustomer->num_rows === 0) {
    die("Customer not found.");
}

$customer = $resultCustomer->fetch_assoc();
$customer_name = $customer['customer_name'];
$phone = $customer['phone_number'];

$order_items = json_decode($order['order_details'], true);

// Set the custom paper size (80mm width, 297mm height)
$pdf = new FPDF();
$pdf->AddPage('P', array(80, 297)); // 80mm width and 297mm height for POS printer
$pdf->SetFont('Arial', '', 8); // Smaller font for compact design

// Title
$pdf->Cell(0, 6, 'Order Bill', 0, 1, 'C');

// Order Details
$pdf->Cell(0, 6, 'Order ID: ' . $order['id'], 0, 1);
$pdf->Cell(0, 6, 'Customer: ' . $customer_name, 0, 1);
$pdf->Cell(0, 6, 'Phone: ' . $phone, 0, 1);
$pdf->Cell(0, 6, 'Total: ' . number_format($order['total_cost'], 2), 0, 1);
$pdf->Ln(2); // Add a little space

// Table Header
$pdf->Cell(35, 6, 'Product', 1);
$pdf->Cell(20, 6, 'Qty', 1);
$pdf->Cell(20, 6, 'Price', 1);
$pdf->Cell(25, 6, 'Subtotal', 1);
$pdf->Ln();

// Table Rows
foreach ($order_items as $item) {
    $pdf->Cell(35, 6, $item['product_name'], 1);
    $pdf->Cell(20, 6, $item['quantity'], 1);
    $pdf->Cell(20, 6, number_format($item['price'], 2), 1);
    $pdf->Cell(25, 6, number_format($item['subtotal'], 2), 1);
    $pdf->Ln();
}

// Save the PDF to a file
$pdf_filename = 'order_' . $order_id . '_bill.pdf';
$pdf->Output('F', $pdf_filename); // F: save to file

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Bill</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 24px;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-color: #f7f7f7;
        }
        .container {
            width: 500px;
            height: 100%;
            background-color: #fff;
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            text-align: center;
            overflow: hidden;
            font-size: 30px;
        }
        h1 {
            color: #007BFF;
            font-size: 18px;
            margin: 0;
        }
        .icon {
            font-size: 24px;
            color: #007BFF;
            margin-bottom: 10px;
        }
        .content p {
            font-size: 24px;
            margin: 5px 0;
            color: #333;
        }
        table {
            width: 100%;
            margin-top: 10px;
            border-collapse: collapse;
            font-size: 20px;
        }
        th, td {
            padding: 5px;
            border: 1px solid #ddd;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
            color: #333;
        }
        button {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 8px 15px;
            cursor: pointer;
            margin-top: 15px;
            border-radius: 5px;
        }
        button:hover {
            background-color: #218838;
        }
        iframe {
            display: block;
            margin-top: 15px;
            width: 100%;
            height: 200px;
            border: none;
        }
    </style>
</head>
<body>

    <div class="container">
        <div class="icon">📜</div> <!-- Example icon -->
        <h1 style="font-size:34px;margin-top:2%">Order Bill</h1>

        <div class="content">
            <p><strong>Order ID:</strong> <?php echo $order['id']; ?></p>
            <p><strong>Customer:</strong> <?php echo $customer_name; ?></p>
            <p><strong>Phone:</strong> <?php echo $phone; ?></p>
            <p><strong>Total Cost:</strong> <?php echo number_format($order['total_cost'], 2); ?></p>
        </div>

        <h4>Order Items:</h4>
        <table>
            <tr>
                <th>Product</th>
                <th>Qty</th>
                <th>Price</th>
                <th>Subtotal</th>
            </tr>
            <?php foreach ($order_items as $item): ?>
            <tr>
                <td><?php echo $item['product_name']; ?></td>
                <td><?php echo $item['quantity']; ?></td>
                <td><?php echo number_format($item['price'], 2); ?></td>
                <td><?php echo number_format($item['subtotal'], 2); ?></td>
            </tr>
            <?php endforeach; ?>
        </table>

        <!-- Preview the PDF in an iframe -->
        <iframe src="<?php echo $pdf_filename; ?>"></iframe>

        <!-- Button to download PDF -->
        <button onclick="window.location.href='<?php echo $pdf_filename; ?>'">Download PDF</button>
    </div>

</body>
</html>
