<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $conn = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME, DB_PORT);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    // Get form data
    $student_name = trim($_POST['student_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $college_name = trim($_POST['college_name'] ?? '');
    $course = trim($_POST['course'] ?? '');
    $year_of_study = trim($_POST['year_of_study'] ?? '');
    $phone_number = trim($_POST['phone_number'] ?? '');
    $purpose_of_download = trim($_POST['purpose_of_download'] ?? '');
    $download_type = trim($_POST['download_type'] ?? 'journal');
    $download_item_id = trim($_POST['download_item_id'] ?? '');
    
    // Validate required fields
    if (empty($student_name) || empty($email) || empty($college_name) || empty($course) || empty($year_of_study)) {
        throw new Exception("Please fill all required fields");
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Please enter a valid email address");
    }
    
    // Create/update table structure with download tracking
    $createTableSQL = "
    CREATE TABLE IF NOT EXISTS journal_downloads (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL,
        college_name VARCHAR(500) NOT NULL,
        course VARCHAR(255) NOT NULL,
        year_of_study VARCHAR(50) NOT NULL,
        phone_number VARCHAR(20),
        purpose_of_download TEXT,
        download_type ENUM('journal', 'article') DEFAULT 'journal',
        download_item_id VARCHAR(100),
        download_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        user_ip VARCHAR(45),
        user_agent TEXT,
        INDEX idx_email (email),
        INDEX idx_download_type (download_type),
        INDEX idx_download_item (download_item_id),
        INDEX idx_download_date (download_date)
    ) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
    
    $conn->query($createTableSQL);
    
    // Get user IP and user agent for tracking
    $user_ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    // Insert the download record
    $stmt = $conn->prepare("INSERT INTO journal_downloads (student_name, email, college_name, course, year_of_study, phone_number, purpose_of_download, download_type, download_item_id, user_ip, user_agent) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("sssssssssss", $student_name, $email, $college_name, $course, $year_of_study, $phone_number, $purpose_of_download, $download_type, $download_item_id, $user_ip, $user_agent);
    
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    
    $stmt->close();
    $conn->close();
    
    // Determine the PDF path and filename based on download type and item
    $pdfPath = '';
    $filename = '';
    
    if ($download_type === 'journal') {
        switch ($download_item_id) {
            case 'volume-1':
                $pdfPath = 'assets/SIES_Journal_Volume_1.pdf';
                $filename = 'SIES_Journal_Volume_1.pdf';
                break;
            default:
                $pdfPath = 'assets/SIES_Journal_Volume_1.pdf';
                $filename = 'SIES_Journal_Volume_1.pdf';
        }
    } else if ($download_type === 'article') {
        // Map article IDs to their respective PDF files - matches actual articles in HTML
        switch ($download_item_id) {
            case 'article-1':
                $pdfPath = 'assets/articles/Digital_Transformation_Higher_Education.pdf';
                $filename = 'Digital_Transformation_Higher_Education.pdf';
                break;
            case 'article-2':
                $pdfPath = 'assets/articles/Sustainable_Business_Practices_SME.pdf';
                $filename = 'Sustainable_Business_Practices_SME.pdf';
                break;
            case 'article-3':
                $pdfPath = 'assets/articles/Cultural_Heritage_Digital_Storytelling.pdf';
                $filename = 'Cultural_Heritage_Digital_Storytelling.pdf';
                break;
            case 'article-4':
                $pdfPath = 'assets/articles/Machine_Learning_Financial_Risk.pdf';
                $filename = 'Machine_Learning_Financial_Risk.pdf';
                break;
            case 'article-5':
                $pdfPath = 'assets/articles/Social_Media_Youth_Mental_Health.pdf';
                $filename = 'Social_Media_Youth_Mental_Health.pdf';
                break;
            case 'article-6':
                $pdfPath = 'assets/articles/Environmental_Economics_Carbon_Pricing.pdf';
                $filename = 'Environmental_Economics_Carbon_Pricing.pdf';
                break;
            case 'article-7':
                $pdfPath = 'assets/articles/Contemporary_Indian_Literature.pdf';
                $filename = 'Contemporary_Indian_Literature.pdf';
                break;
            case 'article-8':
                $pdfPath = 'assets/articles/Supply_Chain_Management_COVID.pdf';
                $filename = 'Supply_Chain_Management_COVID.pdf';
                break;
            case 'article-9':
                $pdfPath = 'assets/articles/Urban_Planning_Smart_City.pdf';
                $filename = 'Urban_Planning_Smart_City.pdf';
                break;
            case 'article-10':
                $pdfPath = 'assets/articles/Gender_Representation_Corporate_Boards.pdf';
                $filename = 'Gender_Representation_Corporate_Boards.pdf';
                break;
            case 'article-11':
                $pdfPath = 'assets/articles/Blockchain_Banking_Financial_Services.pdf';
                $filename = 'Blockchain_Banking_Financial_Services.pdf';
                break;
            case 'article-12':
                $pdfPath = 'assets/articles/Climate_Change_Coastal_Maharashtra.pdf';
                $filename = 'Climate_Change_Coastal_Maharashtra.pdf';
                break;
            case 'article-13':
                $pdfPath = 'assets/articles/AI_Healthcare_Diagnostics.pdf';
                $filename = 'AI_Healthcare_Diagnostics.pdf';
                break;
            case 'article-14':
                $pdfPath = 'assets/articles/Microfinance_Women_Entrepreneurship.pdf';
                $filename = 'Microfinance_Women_Entrepreneurship.pdf';
                break;
            case 'article-15':
                $pdfPath = 'assets/articles/Digital_Marketing_Ecommerce.pdf';
                $filename = 'Digital_Marketing_Ecommerce.pdf';
                break;
            default:
                $pdfPath = 'assets/articles/sample_article.pdf';
                $filename = 'sample_article.pdf';
        }
    }
    
    // Check if PDF file exists
    if (!empty($pdfPath) && file_exists($pdfPath)) {
        echo json_encode([
            'success' => true, 
            'message' => 'Details saved successfully! Your download will start shortly.',
            'download_url' => $pdfPath,
            'filename' => $filename,
            'pdf_available' => true
        ]);
    } else {
        echo json_encode([
            'success' => true, 
            'message' => 'Your details have been saved successfully! The PDF will be available for download soon.',
            'pdf_available' => false,
            'info' => 'The requested content is currently being prepared for download. You will be notified via email once it becomes available.'
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