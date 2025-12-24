<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config.php';
require_once '../PHPMailer/PHPMailer.php';
require_once '../PHPMailer/SMTP.php';
require_once '../PHPMailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $fullName = trim($_POST['fullName'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $institution = trim($_POST['institution'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    
    if (empty($fullName) || empty($email) || empty($subject) || empty($message)) {
        throw new Exception("Please fill all required fields");
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Please enter a valid email address");
    }
    
    if (strlen($message) < 10) {
        throw new Exception("Message must be at least 10 characters long");
    }

    // Database connection
    $conn = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);
    
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }
    
    // Insert data into database
    $stmt = $conn->prepare("INSERT INTO contact_submissions (full_name, email, phone, institution, subject, message) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $fullName, $email, $phone, $institution, $subject, $message);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to save contact form data");
    }
    
    $stmt->close();
    $conn->close();
    
    $mail = new PHPMailer(true);
    
    $mail->isSMTP();
    $mail->Host       = SMTP_HOST;
    $mail->SMTPAuth   = true;
    $mail->Username   = SMTP_USERNAME;
    $mail->Password   = SMTP_PASSWORD;
    $mail->SMTPSecure = SMTP_ENCRYPTION;
    $mail->Port       = SMTP_PORT;
    
    $mail->SMTPDebug = 0; 
    
    $mail->setFrom(SMTP_USERNAME, FROM_NAME);
    $mail->addAddress(ADMIN_EMAIL, ADMIN_NAME);
    $mail->addReplyTo($email, $fullName);
    
    $mail->isHTML(true);
    $mail->Subject = "Contact Form: " . $subject;
    $mail->Body = createSimpleEmailHTML($fullName, $email, $phone, $institution, $subject, $message);
    
    $mail->send();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Message sent successfully! We\'ll get back to you within 24-48 hours.'
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false, 
        'message' => 'Failed to send email: ' . $e->getMessage()
    ]);
}

function createSimpleEmailHTML($fullName, $email, $phone, $institution, $subject, $message) {
    $currentDateTime = date('F j, Y \a\t g:i A');
    
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; }
            .header { background: #1e3a8a; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; background: #f8fafc; }
            .field { margin-bottom: 15px; }
            .label { font-weight: bold; color: #1e3a8a; }
            .value { margin-top: 5px; padding: 10px; background: white; border-left: 3px solid #0ea5e9; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>New Contact Form Submission</h1>
                <p>SIES Journal</p>
            </div>
            
            <div class='content'>
                <div class='field'>
                    <div class='label'>Full Name:</div>
                    <div class='value'>" . htmlspecialchars($fullName) . "</div>
                </div>
                
                <div class='field'>
                    <div class='label'>Email Address:</div>
                    <div class='value'>" . htmlspecialchars($email) . "</div>
                </div>
                
                <div class='field'>
                    <div class='label'>Phone Number:</div>
                    <div class='value'>" . (empty($phone) ? 'Not provided' : htmlspecialchars($phone)) . "</div>
                </div>
                
                <div class='field'>
                    <div class='label'>Institution:</div>
                    <div class='value'>" . (empty($institution) ? 'Not provided' : htmlspecialchars($institution)) . "</div>
                </div>
                
                <div class='field'>
                    <div class='label'>Subject:</div>
                    <div class='value'>" . htmlspecialchars($subject) . "</div>
                </div>
                
                <div class='field'>
                    <div class='label'>Message:</div>
                    <div class='value'>" . nl2br(htmlspecialchars($message)) . "</div>
                </div>
                
                <div class='field'>
                    <div class='label'>Submitted At:</div>
                    <div class='value'>" . $currentDateTime . "</div>
                </div>
            </div>
        </div>
    </body>
    </html>";
}
?>