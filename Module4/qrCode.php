<?php
session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include QR code library
$qrlib_path = __DIR__ . '/../phpqrcode/qrlib.php';
if (!file_exists($qrlib_path)) {
    die("Error: QR code library not found at: " . $qrlib_path);
}
include($qrlib_path);

if (!isset($_SESSION['summon'])) {
    echo "No summon data found. Please create a summon first.";
    exit();
}

$summon = $_SESSION['summon'];
$plate_number = $summon['plate_number'];
$date = $summon['date'];
$status = $summon['status'];
$violation_type = $summon['violation_type'];
$demerit_points = $summon['demerit_points'];

// Create an associative array with the summon data
$data = [
    'plate_number' => $plate_number,
    'date' => $date,
    'status' => $status,
    'violation_type' => $violation_type,
    'demerit_points' => $demerit_points
];

// Convert the array to JSON
$json_data = json_encode($data);

if ($json_data === false) {
    echo "Error encoding summon data to JSON.";
    exit();
}

// Generate the QR code with a dynamic filename
// Sanitize plate number for filename (remove spaces and special characters)
$safe_plate = preg_replace('/[^a-zA-Z0-9]/', '_', $plate_number);
$filename = "QRCodeS/qrcode_" . $safe_plate . ".png";
$full_path = __DIR__ . '/' . $filename;

// Check if the directory exists, if not create it with write permissions
$qr_dir = __DIR__ . '/QRCodeS';
if (!is_dir($qr_dir)) {
    // Try to create directory with full permissions
    $old_umask = umask(0);
    if (!@mkdir($qr_dir, 0777, true)) {
        umask($old_umask);
        die("Error: Could not create QRCodeS directory. Please manually create it with write permissions.");
    }
    umask($old_umask);
}

// Check if directory is writable
// Note: We suppress chmod warnings since PHP may not have permission to change directory permissions
if (!is_writable($qr_dir)) {
    // Try to set permissions silently (suppress errors if we don't have permission)
    @chmod($qr_dir, 0777);
    
    // Check again after attempting chmod
    if (!is_writable($qr_dir)) {
        die("Error: QRCodeS directory is not writable. Please contact your system administrator to set permissions to 777 for: " . $qr_dir);
    }
}

// Generate the QR code and save to file
$error_correction_level = 'L'; // Error correction level: L, M, Q, H
$matrix_point_size = 4; // Size of QR code

// Use output buffering to prevent phpqrcode library from writing error files
// This avoids permission issues with the phpqrcode directory
ob_start();
QRcode::png($json_data, false, $error_correction_level, $matrix_point_size, 2);
$qr_image_data = ob_get_contents();
ob_end_clean();

// Write the QR code image data to file
if (file_put_contents($full_path, $qr_image_data) === false) {
    die("Error: Could not write QR code file. Directory: " . $qr_dir . " Please check permissions.");
}

// Verify file was created
if (!file_exists($full_path)) {
    die("Error: QR code file was not created. Check directory permissions.");
}

// Use relative path for web display (relative to Module4 directory)
$web_path = "Module4/QRCodeS/qrcode_" . $safe_plate . ".png";

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Summon QR Code</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            color: #333;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
        }

        .qr-container {
            text-align: center;
            background-color: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0px 0px 10px 0px rgba(0,0,0,0.1);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: space-between;
            height: 50%;
            max-width: 600px;
            margin: 20px;
        }

        .qr-container h2 {
            margin-bottom: 20px;
        }

        .qr-container img {
            margin-top: 20px;
            max-width: 100%;
            height: auto;
        }

        .back-button {
            padding: 10px 20px;
            background-color: #800000;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            transition: background-color 0.3s;
        }

        .back-button:hover {
            background-color: #575757;
        }
    </style>
</head>
<body>
    <div class="qr-container">
        <h2>Summon QR Code</h2>
        <h3>Scan QR code below to get your summon details</h3>
        <?php 
        // Check if file exists before displaying
        if (file_exists($full_path)) {
            // Use relative path from web root
            $img_path = "QRCodeS/qrcode_" . $safe_plate . ".png";
            echo '<img src="' . htmlspecialchars($img_path) . '" alt="Summon QR Code" style="max-width: 400px; height: auto;">';
        } else {
            echo '<p style="color: red;">Error: QR code file could not be generated. Please try again.</p>';
            echo '<p style="font-size: 12px; color: #666;">Debug: File path: ' . htmlspecialchars($full_path) . '</p>';
            echo '<p style="font-size: 12px; color: #666;">Directory exists: ' . (is_dir(__DIR__ . '/QRCodeS') ? 'Yes' : 'No') . '</p>';
            echo '<p style="font-size: 12px; color: #666;">Directory writable: ' . (is_writable(__DIR__ . '/QRCodeS') ? 'Yes' : 'No') . '</p>';
        }
        ?>
        <a href="applySummon.php" class="back-button">Back to Apply Summon</a>
    </div>
</body>
</html>
