<?php
session_start();

// Start output buffering
ob_start();

include('../Layout/staff_layout.php');

// Database connection using centralized config
require('../db_config.php');

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['apply-unregistered-summon'])) {
    $plate_number = $_POST['plate_number'];
    $date = $_POST['date'];
    $status = $_POST['status'];
    $violation_type = $_POST['violation_type'];
    $demerit_points = 0;

    switch ($violation_type) {
        case 'Parking Violation':
            $demerit_points = 10;
            break;
        case 'Not Complying with Campus Traffic Regulations':
            $demerit_points = 15;
            break;
        case 'Accident Caused':
            $demerit_points = 20;
            break;
    }

    // Check if vehicle already exists (might have been registered between page loads)
    $sql_check = "SELECT V_vehicleID FROM vehicle WHERE V_plateNum = ?";
    $stmt_check = $link->prepare($sql_check);
    $stmt_check->bind_param("s", $plate_number);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    
    if ($result_check->num_rows > 0) {
        // Vehicle exists - redirect to registered vehicle form
        $stmt_check->close();
        mysqli_close($link);
        $_SESSION['vehicle_exists_error'] = "This vehicle is already registered. Please use the 'Apply Summon (Registered Vehicle)' form instead.";
        header("Location: applySummon.php");
        exit();
    }
    $stmt_check->close();

    // Create unregistered vehicle record
    $sql_insert_vehicle = "INSERT INTO vehicle (V_plateNum, V_vehigrant, V_vehicleType, V_brand, V_colour, V_status, STU_studentID) 
                           VALUES (?, NULL, 'Unknown', 'Unknown', 'Unknown', 'Unregistered', NULL)";
    $stmt_insert_vehicle = $link->prepare($sql_insert_vehicle);
    $stmt_insert_vehicle->bind_param("s", $plate_number);
    
    if ($stmt_insert_vehicle->execute()) {
        $vehicle_id = $link->insert_id;
        $stmt_insert_vehicle->close();
        
        // Create new summon record
        $sql_insert = "INSERT INTO trafficSummon (V_vehicleID, TF_date, TF_status, TF_violationType, TF_demeritPoint) VALUES (?, ?, ?, ?, ?)";
        $stmt_insert = $link->prepare($sql_insert);
        $stmt_insert->bind_param("isssi", $vehicle_id, $date, $status, $violation_type, $demerit_points);

        if ($stmt_insert->execute()) {
            $_SESSION['summon'] = [
                'plate_number' => $plate_number,
                'date' => $date,
                'status' => $status,
                'violation_type' => $violation_type,
                'demerit_points' => $demerit_points
            ];
            $stmt_insert->close();
            mysqli_close($link);
            header("Location: qrCode.php");
            exit();
        } else {
            echo "<div class='alert alert-danger' role='alert'>Error adding traffic summon: " . $link->error . "</div>";
            $stmt_insert->close();
        }
    } else {
        echo "<div class='alert alert-danger' role='alert'>Error creating unregistered vehicle: " . $link->error . "</div>";
        $stmt_insert_vehicle->close();
    }
}

mysqli_close($link);

ob_end_flush();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Summon - Unregistered Vehicle</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: Arial, sans-serif; 
            background-color: #f4f4f4; 
            color: #333; 
            line-height: 1.6; 
        }
        .content-container { 
            max-width: 800px; 
            margin: 50px auto; 
            margin-left: 280px; 
            padding: 20px; 
            background-color: white; 
            border-radius: 10px; 
            box-shadow: 0px 0px 10px 0px rgba(0,0,0,0.1); 
            text-align: center; 
        }
        .content-container h2 { 
            margin-bottom: 10px; 
        }
        .warning-box {
            background-color: #fff3cd;
            border: 2px solid #ffc107;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 20px;
            color: #856404;
        }
        .warning-box strong {
            color: #dc3545;
        }
        form { 
            background: #fff; 
            padding: 20px; 
            border: 1px solid #ddd; 
            border-radius: 4px; 
            box-shadow: 0px 0px 10px 0px rgba(0,0,0,0.1); 
            margin-top: 20px; 
        }
        form label { 
            display: block; 
            margin-bottom: 10px; 
            font-weight: bold; 
        }
        .form-group { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            margin-bottom: 10px; 
        }
        .form-group label { 
            flex: 1; 
            text-align: left; 
            margin-right: 10px; 
        }
        .form-group input[type="text"], 
        .form-group input[type="date"], 
        .form-group select { 
            flex: 2; 
            width: calc(100% - 22px); 
            padding: 10px; 
            border: 1px solid #ddd; 
            border-radius: 4px; 
            font-size: 16px; 
        }
        button { 
            display: inline-block; 
            padding: 12px 24px; 
            font-size: 16px; 
            color: #fff; 
            background-color: #dc3545; 
            border: none; 
            border-radius: 4px; 
            cursor: pointer; 
            transition: background-color 0.3s; 
            margin-top: 10px;
        }
        button:hover { 
            background-color: #c82333; 
        }
        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #666;
            text-decoration: none;
        }
        .back-link:hover {
            color: #333;
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="content-container">
        <h2>Add Summon - Unregistered Vehicle</h2>
        <div class="warning-box">
            <strong>⚠️ Important:</strong> This form is for vehicles that are <strong>NOT registered</strong> in the system.
            The system will automatically create a vehicle record with status "Unregistered" when you submit this form.
            If the vehicle is already registered, please use the <a href="Module4/applySummon.php" style="color: #856404; text-decoration: underline;">registered vehicle form</a> instead.
        </div>
        
        <?php
        if (isset($_SESSION['vehicle_exists_error'])) {
            echo '<div class="alert alert-danger" style="background-color: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin-bottom: 20px; border: 1px solid #f5c6cb;">';
            echo htmlspecialchars($_SESSION['vehicle_exists_error']);
            echo '</div>';
            unset($_SESSION['vehicle_exists_error']);
        }
        ?>
        
        <form action="Module4/applyUnregisteredSummon.php" method="post">
            <div class="form-group">
                <label for="plate_number">Plate Number:</label>
                <input type="text" id="plate_number" name="plate_number" required placeholder="Enter plate number (e.g., XYZ9999)">
            </div>
            <div class="form-group">
                <label for="date">Date:</label>
                <input type="date" id="date" name="date" required>
            </div>
            <div class="form-group">
                <label for="status">Status:</label>
                <select id="status" name="status">
                    <option value="Paid">Paid</option>
                    <option value="Unpaid" selected>Unpaid</option>
                </select>
            </div>
            <div class="form-group">
                <label for="violation_type">Violation Type:</label>
                <select id="violation_type" name="violation_type">
                    <option value="Parking Violation">Parking Violation</option>
                    <option value="Not Complying with Campus Traffic Regulations">Not Complying with Campus Traffic Regulations</option>
                    <option value="Accident Caused">Accident Caused</option>
                </select>
            </div>
            <button type="submit" name="apply-unregistered-summon">Apply Summon (Unregistered Vehicle)</button>
        </form>
        
        <a href="Module4/applySummon.php" class="back-link">← Back to Registered Vehicle Form</a>
    </div>
</body>
</html>
