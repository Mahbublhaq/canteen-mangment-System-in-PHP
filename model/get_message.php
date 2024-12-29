<?php
// Debug output
header('Content-Type: application/json');

// Log the current directory and file path
$debug_info = [
    'current_dir' => __DIR__,
    'requested_file' => __FILE__,
    'db_path' => realpath('../db/db.php')
];

try {
    // Try to include the database file
    if (!file_exists('../db/db.php')) {
        throw new Exception('Database file not found at: ' . realpath('../db/db.php'));
    }
    
    include '../db/db.php';
    
    if (!isset($conn)) {
        throw new Exception('Database connection not established');
    }

    if (isset($_GET['id'])) {
        $id = $_GET['id'];
        
        $stmt = mysqli_prepare($conn, "SELECT id, name, email, problem, details, query, status, created_at, reply_message FROM contact_queries WHERE id = ?");
        
        if (!$stmt) {
            throw new Exception('Prepare statement failed: ' . mysqli_error($conn));
        }
        
        mysqli_stmt_bind_param($stmt, "i", $id);
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception('Execute failed: ' . mysqli_stmt_error($stmt));
        }
        
        $result = mysqli_stmt_get_result($stmt);
        
        if ($row = mysqli_fetch_assoc($result)) {
            echo json_encode([
                'success' => true,
                'data' => $row
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'error' => 'Message not found'
            ]);
        }
        
        mysqli_stmt_close($stmt);
    } else {
        throw new Exception('No ID provided');
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'debug_info' => $debug_info
    ]);
}
?>