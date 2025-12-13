<?php
// Database connection using centralized config
require('../../db_config.php');

$location = $_POST['location'];
$status = $_POST['status'];
$reason = $_POST['reason'] ?? 'None';

$sql = "UPDATE parkingSpace SET P_status = ?, P_reason = ? WHERE P_location = ?";
$stmt = $link->prepare($sql);
$stmt->bind_param('sss', $status, $reason, $location);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update status: ' . $stmt->error]);
}

$stmt->close();
$link->close();
?>
