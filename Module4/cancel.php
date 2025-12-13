<?php
// Database connection using centralized config
require('../db_config.php');
// Use procedural style for consistency, or convert to OOP if needed
$conn = $link;

if (isset($_GET['id'])) {
    $summonID = $_GET['id'];
    $sql = "UPDATE trafficSummon SET status='Cancelled' WHERE TF_summonID='$summonID'";

    if ($conn->query($sql) === TRUE) {
        echo "Record cancelled successfully";
        header("Location: Module4/trafficSummon.php"); // Redirect to the main page
    } else {
        echo "Error cancelling record: " . $conn->error;
    }
}

$conn->close();
?>
