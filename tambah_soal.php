<?php
session_start();

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Validate CSRF token on form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die('Invalid CSRF token');
    }
}

// Secure file upload handling
$allowedMIMETypes = ['image/jpeg', 'image/png', 'application/pdf'];
$maxFileSize = 10 * 1024 * 1024; // 10 MB
$uploadsDir = 'uploads/';

if ($_FILES['file_upload']['error'] == UPLOAD_ERR_OK) {
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $_FILES['file_upload']['tmp_name']);
    finfo_close($finfo);
    if (!in_array($mimeType, $allowedMIMETypes)) {
        die('Invalid file type');
    }
    if ($_FILES['file_upload']['size'] > $maxFileSize) {
        die('File size exceeds limit');
    }
    $randomFilename = bin2hex(random_bytes(8)) . '.' . pathinfo($_FILES['file_upload']['name'], PATHINFO_EXTENSION);
    move_uploaded_file($_FILES['file_upload']['tmp_name'], $uploadsDir . $randomFilename);
}

// Sanitize inputs
$matakuliah_id = intval($_POST['matakuliah_id']);
$tipe = mysqli_real_escape_string($db_connection, $_POST['tipe']);
$kunci = mysqli_real_escape_string($db_connection, $_POST['kunci']);

// Validate pilot and kunci options
if (!in_array($kunci, ['option1', 'option2', 'option3', 'option4'])) {
    die('Invalid answer option');
}

// Example SQL query using prepared statements for security
$stmt = $db_connection->prepare('INSERT INTO exam_entries (matakuliah_id, tipe, kunci) VALUES (?, ?, ?)');
$stmt->bind_param('iss', $matakuliah_id, $tipe, $kunci);
$stmt->execute();
$stmt->close();

// Proper error handling
if ($db_connection->error) {
    die('Database error: ' . $db_connection->error);
}
?>