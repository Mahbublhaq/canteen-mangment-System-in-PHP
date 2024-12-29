<?php
include '../db/db.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    
    // Prepare statement to prevent SQL injection
    $stmt = mysqli_prepare($conn, "SELECT id, name, email, problem, details, query, status, created_at, reply_message FROM contact_queries WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        // Sanitize the data before sending
        $row = array_map('htmlspecialchars', $row);
        
        // Convert to JSON and output
        header('Content-Type: application/json');
        echo json_encode($row);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Message not found']);
    }
} else {
    http_response_code(400);
    echo json_encode(['error' => 'No ID provided']);
}
?>