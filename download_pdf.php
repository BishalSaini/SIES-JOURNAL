<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

$debug_log = "Debug: Starting download_pdf.php at " . date('Y-m-d H:i:s') . "\n";
file_put_contents('debug.log', $debug_log, FILE_APPEND);

require_once 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $debug_log = "Debug: POST data received: " . json_encode($_POST) . "\n";
    file_put_contents('debug.log', $debug_log, FILE_APPEND);
    
    $conn = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    $student_name = trim($_POST['student_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $college_name = trim($_POST['college_name'] ?? '');
    $course = trim($_POST['course'] ?? '');
    $year_of_study = trim($_POST['year_of_study'] ?? '');
    $phone_number = trim($_POST['phone_number'] ?? '');
    $purpose_of_download = trim($_POST['purpose_of_download'] ?? '');
    
    if (empty($student_name) || empty($email) || empty($college_name) || empty($course) || empty($year_of_study)) {
        throw new Exception("Please fill all required fields");
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Please enter a valid email address");
    }
    
    $createTableSQL = "
    CREATE TABLE IF NOT EXISTS student_downloads (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL,
        college_name VARCHAR(500) NOT NULL,
        course VARCHAR(255) NOT NULL,
        year_of_study VARCHAR(50) NOT NULL,
        phone_number VARCHAR(20),
        purpose_of_download TEXT,
        download_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_email (email),
        INDEX idx_download_date (download_date)
    ) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
    
    $conn->query($createTableSQL);
    
    // Insert the data 
    $stmt = $conn->prepare("INSERT INTO student_downloads (student_name, email, college_name, course, year_of_study, phone_number, purpose_of_download) VALUES (?, ?, ?, ?, ?, ?, ?)");
    
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("sssssss", $student_name, $email, $college_name, $course, $year_of_study, $phone_number, $purpose_of_download);
    
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    
    $stmt->close();
    $conn->close();
    
    $pdfPath = 'assets/SIES_Journal_Volume_1.pdf';
    if (!file_exists($pdfPath)) {
        echo json_encode([
            'success' => true, 
            'message' => 'Your details have been saved successfully! The PDF will be available for download soon.',
            'pdf_available' => false,
            'info' => 'The journal PDF is currently being prepared for download. You will be notified via email once it becomes available.'
        ]);
    } else {
        echo json_encode([
            'success' => true, 
            'message' => 'Details saved successfully! You can now download the PDF.',
            'download_url' => $pdfPath,
            'pdf_available' => true
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
}
?>