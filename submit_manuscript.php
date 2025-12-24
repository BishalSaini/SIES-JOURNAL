<?php
require_once 'config.php';
require_once 'PHPMailer/PHPMailer.php';
require_once 'PHPMailer/SMTP.php';
require_once 'PHPMailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    echo json_encode(['success' => false, 'message' => 'Form not submitted properly']);
    exit;
}

$author_name = $_POST['author_name'];
$email = $_POST['email'];
$affiliation = $_POST['affiliation'];
$manuscript_title = $_POST['manuscript_title'];
$abstract = $_POST['abstract'];

// Check if all fields are filled
if (empty($author_name) || empty($email) || empty($affiliation) || empty($manuscript_title) || empty($abstract)) {
    echo json_encode(['success' => false, 'message' => 'Please fill all fields']);
    exit;
}

// Check if file was uploaded
if (!isset($_FILES['manuscript_file']) || $_FILES['manuscript_file']['error'] != 0) {
    echo json_encode(['success' => false, 'message' => 'Please upload a file']);
    exit;
}

// Get file info
$file = $_FILES['manuscript_file'];
$file_name = $file['name'];
$file_size = $file['size'];
$file_tmp = $file['tmp_name'];

// Check file size (max 5MB)
if ($file_size > 5000000) {
    echo json_encode(['success' => false, 'message' => 'File too large. Max 5MB']);
    exit;
}

// Check file type
$file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
if ($file_ext != 'pdf' && $file_ext != 'docx') {
    echo json_encode(['success' => false, 'message' => 'Only PDF and DOCX files allowed']);
    exit;
}

// Create uploads folder if not exists
$upload_dir = 'uploads/manuscripts/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Create unique file name
$new_file_name = date('Y-m-d_H-i-s') . '_' . rand(1000, 9999) . '.' . $file_ext;
$upload_path = $upload_dir . $new_file_name;

// Connect to database early
try {
    $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USERNAME, DB_PASSWORD);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Set timeout to 5 seconds
    $conn->setAttribute(PDO::ATTR_TIMEOUT, 5);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

// Move uploaded file
if (!move_uploaded_file($file_tmp, $upload_path)) {
    echo json_encode(['success' => false, 'message' => 'Failed to upload file']);
    exit;
}

// Insert data into database
try {
    $sql = "INSERT INTO manuscript_submissions (author_name, email, affiliation, manuscript_title, abstract, file_name, file_path, file_type, file_size) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        $author_name,
        $email, 
        $affiliation,
        $manuscript_title,
        $abstract,
        $file_name,
        $upload_path,
        $file_ext,
        $file_size
    ]);
    
    $submission_id = $conn->lastInsertId();
    
    // Send emails asynchronously
    ignore_user_abort(true);
    set_time_limit(0);
    
    // Send success response immediately
    echo json_encode([
        'success' => true, 
        'message' => 'Manuscript submitted successfully! You will receive confirmation email shortly.',
        'submission_id' => $submission_id
    ]);
    
    // Flush output buffer
    if (ob_get_length()) ob_end_flush();
    flush();
    
    // Now send emails in background
    if (function_exists('fastcgi_finish_request')) {
        fastcgi_finish_request();
    }
    
    // Send emails after response is sent
    sendAdminNotification($author_name, $email, $affiliation, $manuscript_title, $abstract, $file_name, $file_size, $submission_id);
    sendAuthorConfirmation($author_name, $email, $manuscript_title, $submission_id);
    
    exit;
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

/**
 * Send email notification to admin when a new manuscript is submitted
 */
function sendAdminNotification($author_name, $author_email, $affiliation, $manuscript_title, $abstract, $file_name, $file_size, $submission_id) {
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_ENCRYPTION;
        $mail->Port       = SMTP_PORT;

        // Recipients
        $mail->setFrom(SMTP_USERNAME, 'SIES Journal System');
        $mail->addAddress(ADMIN_EMAIL, ADMIN_NAME);
        $mail->addReplyTo($author_email, $author_name);

        // Add attachment - Using the correct upload path
        global $upload_path;  // Access the upload path from outside the function
        if (file_exists($upload_path)) {
            $mail->addAttachment($upload_path, $file_name);
        } else {
            error_log("File not found for attachment: " . $upload_path);
        }

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'New Manuscript Submission - ' . $manuscript_title;
        
        // Email body
        $mail->Body = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .header { background-color: #2c3e50; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; }
                .info-table { border-collapse: collapse; width: 100%; margin: 20px 0; }
                .info-table th, .info-table td { border: 1px solid #ddd; padding: 12px; text-align: left; }
                .info-table th { background-color: #f2f2f2; font-weight: bold; }
                .abstract-box { background-color: #f9f9f9; border-left: 4px solid #3498db; padding: 15px; margin: 15px 0; }
                .footer { background-color: #ecf0f1; padding: 15px; text-align: center; font-size: 12px; color: #7f8c8d; }
            </style>
        </head>
        <body>
            <div class='header'>
                <h2>New Manuscript Submission</h2>
                <p>Submission ID: #" . $submission_id . "</p>
            </div>
            
            <div class='content'>
                <h3>Manuscript Details</h3>
                <table class='info-table'>
                    <tr>
                        <th>Submission ID</th>
                        <td>#" . $submission_id . "</td>
                    </tr>
                    <tr>
                        <th>Author Name</th>
                        <td>" . htmlspecialchars($author_name) . "</td>
                    </tr>
                    <tr>
                        <th>Author Email</th>
                        <td>" . htmlspecialchars($author_email) . "</td>
                    </tr>
                    <tr>
                        <th>Affiliation</th>
                        <td>" . htmlspecialchars($affiliation) . "</td>
                    </tr>
                    <tr>
                        <th>Manuscript Title</th>
                        <td><strong>" . htmlspecialchars($manuscript_title) . "</strong></td>
                    </tr>
                    <tr>
                        <th>File Name</th>
                        <td>" . htmlspecialchars($file_name) . "</td>
                    </tr>
                    <tr>
                        <th>File Size</th>
                        <td>" . number_format($file_size / 1024, 2) . " KB</td>
                    </tr>
                    <tr>
                        <th>Submission Date</th>
                        <td>" . date('F j, Y \a\t g:i A') . "</td>
                    </tr>
                </table>
                
                <h3>Abstract</h3>
                <div class='abstract-box'>
                    " . nl2br(htmlspecialchars($abstract)) . "
                </div>
            </div>
            
            <div class='footer'>
                <p>This is an automated notification from SIES Journal submission system.</p>
                <p>Please do not reply to this email. Contact the author directly at: " . htmlspecialchars($author_email) . "</p>
            </div>
        </body>
        </html>";

        // Plain text version for email clients that don't support HTML
        $mail->AltBody = "
New Manuscript Submission - Submission ID: #" . $submission_id . "

Manuscript Details:
- Submission ID: #" . $submission_id . "
- Author Name: " . $author_name . "
- Author Email: " . $author_email . "
- Affiliation: " . $affiliation . "
- Manuscript Title: " . $manuscript_title . "
- File Name: " . $file_name . "
- File Size: " . number_format($file_size / 1024, 2) . " KB
- Submission Date: " . date('F j, Y \a\t g:i A') . "

Abstract:
" . $abstract . "

This is an automated notification from SIES Journal submission system.
Please do not reply to this email. Contact the author directly at: " . $author_email;

        $mail->send();
        return true;
        
    } catch (Exception $e) {
        error_log("Admin notification email failed: " . $mail->ErrorInfo);
        return false;
    }
}


function sendAuthorConfirmation($author_name, $author_email, $manuscript_title, $submission_id) {
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_ENCRYPTION;
        $mail->Port       = SMTP_PORT;

        // Recipients
        $mail->setFrom(SMTP_USERNAME, 'SIES Journal');
        $mail->addAddress($author_email, $author_name);
        $mail->addReplyTo(ADMIN_EMAIL, ADMIN_NAME);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Manuscript Submission Confirmation - ' . $manuscript_title;
        
        // Email body
        $mail->Body = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .header { background-color: #27ae60; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; }
                .info-box { background-color: #f8f9fa; border: 1px solid #dee2e6; border-radius: 5px; padding: 15px; margin: 15px 0; }
                .success-icon { color: #27ae60; font-size: 24px; }
                .footer { background-color: #ecf0f1; padding: 15px; text-align: center; font-size: 12px; color: #7f8c8d; }
            </style>
        </head>
        <body>
            <div class='header'>
                <h2><span class='success-icon'>✓</span> Submission Confirmed</h2>
                <p>Your manuscript has been successfully submitted</p>
            </div>
            
            <div class='content'>
                <h3>Dear " . htmlspecialchars($author_name) . ",</h3>
                
                <p>Thank you for submitting your manuscript to SIES Journal. We have successfully received your submission.</p>
                
                <div class='info-box'>
                    <h4>Submission Details:</h4>
                    <p><strong>Submission ID:</strong> #" . $submission_id . "</p>
                    <p><strong>Manuscript Title:</strong> " . htmlspecialchars($manuscript_title) . "</p>
                    <p><strong>Submission Date:</strong> " . date('F j, Y \a\t g:i A') . "</p>
                    <p><strong>Status:</strong> Under Review</p>
                </div>
                
                <p><strong>What happens next?</strong></p>
                <ul>
                    <li>Your submission will be reviewed by our editorial team</li>
                    <li>You will receive updates on the review process via email</li>
                    <li>Please keep your Submission ID (#" . $submission_id . ") for future reference</li>
                </ul>
                
                <p>If you have any questions about your submission, please contact us and include your Submission ID.</p>
                
                <p>Thank you for choosing SIES Journal.</p>
                
                <p>Best regards,<br>
                SIES Journal Editorial Team</p>
            </div>
            
            <div class='footer'>
                <p>This is an automated confirmation email from SIES Journal.</p>
                <p>For inquiries, please contact: " . ADMIN_EMAIL . "</p>
            </div>
        </body>
        </html>";

        // Plain text version
        $mail->AltBody = "
Dear " . $author_name . ",

Thank you for submitting your manuscript to SIES Journal. We have successfully received your submission.

Submission Details:
- Submission ID: #" . $submission_id . "
- Manuscript Title: " . $manuscript_title . "
- Submission Date: " . date('F j, Y \a\t g:i A') . "
- Status: Under Review

What happens next?
- Your submission will be reviewed by our editorial team
- You will receive updates on the review process via email
- Please keep your Submission ID (#" . $submission_id . ") for future reference

If you have any questions about your submission, please contact us and include your Submission ID.

Thank you for choosing SIES Journal.

Best regards,
SIES Journal Editorial Team

This is an automated confirmation email from SIES Journal.
For inquiries, please contact: " . ADMIN_EMAIL;

        $mail->send();
        return true;
        
    } catch (Exception $e) {
        error_log("Author confirmation email failed: " . $mail->ErrorInfo);
        return false;
    }
}
?>