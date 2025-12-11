<?php
session_start();

// Database connection using centralized config
require('../../db_config.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $V_plateNum = $_POST["V_plateNum"];

    $query = "DELETE FROM vehicle WHERE V_plateNum = ?";
    $stmt = $link->prepare($query);
    $stmt->bind_param("s", $V_plateNum);

    if ($stmt->execute()) {
        header("Location: infoVehicle.php?message=" . urlencode("Vehicle deleted successfully."));
    } else {
        header("Location: infoVehicle.php?message=" . urlencode("Error deleting vehicle: " . $stmt->error));
    }

    $stmt->close();
}

mysqli_close($link);
?>
