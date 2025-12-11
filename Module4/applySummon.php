<?php
session_start();

// Start output buffering
ob_start();

include('../Layout/staff_layout.php');

// Database connection using centralized config
require('../db_config.php');

$show_unregistered_popup = false;
$show_vehicle_details_form = false;
$unregistered_data = null;

// Debug: Check if we should show popup from previous submission
if (isset($_POST['apply-summon']) && !isset($_POST['proceed-unregistered'])) {
    // This will be set in the POST processing below
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
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

    // Check if this is a request to proceed with unregistered vehicle (after vehicle details form)
    if (isset($_POST['create-unregistered-vehicle'])) {
        $vehicle_brand = $_POST['vehicle_brand'];
        $vehicle_type = $_POST['vehicle_type'];
        $vehicle_color = $_POST['vehicle_color'];
        
        // Create unregistered vehicle record with provided details
        $sql_insert_vehicle = "INSERT INTO vehicle (V_plateNum, V_vehigrant, V_vehicleType, V_brand, V_colour, V_status, STU_studentID) 
                               VALUES (?, NULL, ?, ?, ?, 'Unregistered', NULL)";
        $stmt_insert_vehicle = $link->prepare($sql_insert_vehicle);
        $stmt_insert_vehicle->bind_param("ssss", $plate_number, $vehicle_type, $vehicle_brand, $vehicle_color);
        
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
    } else {
        // Normal flow - check if vehicle exists
        $sql = "SELECT V_vehicleID, V_plateNum FROM vehicle WHERE V_plateNum = ?";
        $stmt = $link->prepare($sql);
        $stmt->bind_param("s", $plate_number);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // Vehicle exists - proceed normally
            $row = $result->fetch_assoc();
            $vehicle_id = $row['V_vehicleID'];

            $sql = "SELECT * FROM trafficSummon WHERE V_vehicleID = ?";
            $stmt = $link->prepare($sql);
            $stmt->bind_param("i", $vehicle_id);
            $stmt->execute();
            $result_summon = $stmt->get_result();

            if ($result_summon->num_rows > 0) {
                $row_summon = $result_summon->fetch_assoc();
                $current_demerit_points = (int)$row_summon['TF_demeritPoint'];
                $new_demerit_points = $current_demerit_points + $demerit_points;

                $sql_update = "UPDATE trafficSummon SET TF_date = ?, TF_status = ?, TF_violationType = ?, TF_demeritPoint = ? WHERE V_vehicleID = ?";
                $stmt_update = $link->prepare($sql_update);
                $stmt_update->bind_param("sssii", $date, $status, $violation_type, $new_demerit_points, $vehicle_id);

                if ($stmt_update->execute()) {
                    $_SESSION['summon'] = [
                        'plate_number' => $plate_number,
                        'date' => $date,
                        'status' => $status,
                        'violation_type' => $violation_type,
                        'demerit_points' => $new_demerit_points
                    ];
                    $stmt_update->close();
                    $stmt->close();
                    mysqli_close($link);
                    header("Location: qrCode.php");
                    exit();
                } else {
                    echo "<div class='alert alert-danger' role='alert'>Error updating traffic summon: " . $link->error . "</div>";
                    $stmt_update->close();
                }
            } else {
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
                    $stmt->close();
                    mysqli_close($link);
                    header("Location: qrCode.php");
                    exit();
                } else {
                    echo "<div class='alert alert-danger' role='alert'>Error adding traffic summon: " . $link->error . "</div>";
                    $stmt_insert->close();
                }
            }
            $stmt->close();
        } else {
            // Vehicle not found - show popup
            $show_unregistered_popup = true;
            $unregistered_data = [
                'plate_number' => $plate_number,
                'date' => $date,
                'status' => $status,
                'violation_type' => $violation_type,
                'demerit_points' => $demerit_points
            ];
            $stmt->close();
            // Don't exit - let the page render with popup
        }
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
    <title>Add Summon</title>
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
            margin-bottom: 20px; 
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
            background-color: #800000; 
            border: none; 
            border-radius: 4px; 
            cursor: pointer; 
            transition: background-color 0.3s; 
        }
        button:hover { 
            background-color: #575757; 
        }
        
        /* Modal/Popup Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 10000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.5);
        }
        .modal.show {
            display: block !important;
        }
        .modal-content {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 30px;
            border: 2px solid #dc3545;
            border-radius: 10px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 4px 8px 0 rgba(0,0,0,0.2);
        }
        .modal-header {
            color: #dc3545;
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 20px;
            text-align: center;
        }
        .modal-body {
            margin-bottom: 20px;
            line-height: 1.8;
        }
        .modal-body p {
            margin-bottom: 10px;
        }
        .modal-footer {
            display: flex;
            justify-content: space-around;
            gap: 10px;
        }
        .btn-cancel {
            background-color: #6c757d;
            padding: 10px 20px;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        .btn-cancel:hover {
            background-color: #5a6268;
        }
        .btn-proceed {
            background-color: #dc3545;
            padding: 10px 20px;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        .btn-proceed:hover {
            background-color: #c82333;
        }
    </style>
</head>
<body>
    <div class="content-container">
        <h2>Add Summon</h2>
        <p style="color: #666; font-size: 14px; margin-bottom: 15px;">
            Enter the vehicle plate number and summon details below.
        </p>
        
        <form action="Module4/applySummon.php" method="post" id="summonForm">
            <div class="form-group">
                <label for="plate_number">Plate Number:</label>
                <input type="text" id="plate_number" name="plate_number" required placeholder="Enter plate number (e.g., ABC1234)" value="<?php echo isset($_POST['plate_number']) ? htmlspecialchars($_POST['plate_number']) : ''; ?>">
            </div>
            <div class="form-group">
                <label for="date">Date:</label>
                <input type="date" id="date" name="date" required value="<?php echo isset($_POST['date']) ? htmlspecialchars($_POST['date']) : date('Y-m-d'); ?>">
            </div>
            <div class="form-group">
                <label for="status">Status:</label>
                <select id="status" name="status">
                    <option value="Paid" <?php echo (isset($_POST['status']) && $_POST['status'] == 'Paid') ? 'selected' : ''; ?>>Paid</option>
                    <option value="Unpaid" <?php echo (isset($_POST['status']) && $_POST['status'] == 'Unpaid' || !isset($_POST['status'])) ? 'selected' : ''; ?>>Unpaid</option>
                </select>
            </div>
            <div class="form-group">
                <label for="violation_type">Violation Type:</label>
                <select id="violation_type" name="violation_type">
                    <option value="Parking Violation" <?php echo (isset($_POST['violation_type']) && $_POST['violation_type'] == 'Parking Violation') ? 'selected' : ''; ?>>Parking Violation</option>
                    <option value="Not Complying with Campus Traffic Regulations" <?php echo (isset($_POST['violation_type']) && $_POST['violation_type'] == 'Not Complying with Campus Traffic Regulations') ? 'selected' : ''; ?>>Not Complying with Campus Traffic Regulations</option>
                    <option value="Accident Caused" <?php echo (isset($_POST['violation_type']) && $_POST['violation_type'] == 'Accident Caused') ? 'selected' : ''; ?>>Accident Caused</option>
                </select>
            </div>
            <button type="submit" name="apply-summon">Apply Summon</button>
        </form>
    </div>

    <!-- Unregistered Vehicle Popup Modal -->
    <?php if ($show_unregistered_popup && isset($unregistered_data) && $unregistered_data !== null): ?>
    <div id="unregisteredModal" class="modal" style="display: block !important;">
        <div class="modal-content">
            <div class="modal-header">
                ⚠️ Vehicle Not Registered
            </div>
            <div class="modal-body">
                <p><strong>Plate Number:</strong> <?php echo htmlspecialchars($unregistered_data['plate_number']); ?></p>
                <p>This vehicle is <strong>not registered</strong> in the system.</p>
                <p>Would you like to proceed with issuing a summon for this unregistered vehicle?</p>
                <p style="color: #856404; font-size: 14px; margin-top: 15px;">
                    <strong>Note:</strong> You will need to provide vehicle details (brand, type, color) to proceed.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button>
                <button type="button" class="btn-proceed" onclick="showVehicleDetailsForm()">Proceed with Unregistered Vehicle</button>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Vehicle Details Form Modal -->
    <div id="vehicleDetailsModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                Enter Vehicle Details
            </div>
            <div class="modal-body">
                <p style="margin-bottom: 15px;"><strong>Plate Number:</strong> <span id="modalPlateNumber"><?php echo isset($unregistered_data) ? htmlspecialchars($unregistered_data['plate_number']) : ''; ?></span></p>
                <p style="color: #856404; font-size: 14px; margin-bottom: 20px;">
                    Please provide the following vehicle information to proceed with the summon.
                </p>
                <form id="vehicleDetailsForm" action="Module4/applySummon.php" method="post">
                    <input type="hidden" name="plate_number" id="formPlateNumber" value="<?php echo isset($unregistered_data) ? htmlspecialchars($unregistered_data['plate_number']) : ''; ?>">
                    <input type="hidden" name="date" id="formDate" value="<?php echo isset($unregistered_data) ? htmlspecialchars($unregistered_data['date']) : ''; ?>">
                    <input type="hidden" name="status" id="formStatus" value="<?php echo isset($unregistered_data) ? htmlspecialchars($unregistered_data['status']) : ''; ?>">
                    <input type="hidden" name="violation_type" id="formViolationType" value="<?php echo isset($unregistered_data) ? htmlspecialchars($unregistered_data['violation_type']) : ''; ?>">
                    
                    <div style="margin-bottom: 15px; text-align: left;">
                        <label for="vehicle_brand" style="display: block; margin-bottom: 5px; font-weight: bold;">Vehicle Brand:</label>
                        <input type="text" id="vehicle_brand" name="vehicle_brand" required placeholder="e.g., Toyota, Honda, Proton" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 16px;">
                    </div>
                    
                    <div style="margin-bottom: 15px; text-align: left;">
                        <label for="vehicle_type" style="display: block; margin-bottom: 5px; font-weight: bold;">Vehicle Type:</label>
                        <select id="vehicle_type" name="vehicle_type" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 16px;">
                            <option value="">Select vehicle type</option>
                            <option value="Car">Car</option>
                            <option value="Motorcycle">Motorcycle</option>
                            <option value="Van">Van</option>
                            <option value="Truck">Truck</option>
                            <option value="SUV">SUV</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    
                    <div style="margin-bottom: 15px; text-align: left;">
                        <label for="vehicle_color" style="display: block; margin-bottom: 5px; font-weight: bold;">Vehicle Color:</label>
                        <input type="text" id="vehicle_color" name="vehicle_color" required placeholder="e.g., Red, Blue, Black, White" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 16px;">
                    </div>
                    
                    <div class="modal-footer" style="margin-top: 20px;">
                        <button type="button" class="btn-cancel" onclick="closeVehicleDetailsForm()">Cancel</button>
                        <button type="submit" name="create-unregistered-vehicle" class="btn-proceed">Create Vehicle & Issue Summon</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Store unregistered data in JavaScript
        var unregisteredData = <?php echo isset($unregistered_data) ? json_encode($unregistered_data) : 'null'; ?>;
        
        // Show modal on page load if it exists
        window.onload = function() {
            var modal = document.getElementById('unregisteredModal');
            if (modal && unregisteredData) {
                modal.style.display = 'block';
                modal.classList.add('show');
            }
        };

        function closeModal() {
            var modal = document.getElementById('unregisteredModal');
            if (modal) {
                modal.style.display = 'none';
                modal.classList.remove('show');
                // Clear form or redirect
                window.location.href = 'Module4/applySummon.php';
            }
        }

        function showVehicleDetailsForm() {
            // Hide unregistered modal
            var unregisteredModal = document.getElementById('unregisteredModal');
            if (unregisteredModal) {
                unregisteredModal.style.display = 'none';
            }
            
            // Show vehicle details form
            var vehicleModal = document.getElementById('vehicleDetailsModal');
            if (vehicleModal && unregisteredData) {
                // Populate form fields
                document.getElementById('formPlateNumber').value = unregisteredData.plate_number;
                document.getElementById('formDate').value = unregisteredData.date;
                document.getElementById('formStatus').value = unregisteredData.status;
                document.getElementById('formViolationType').value = unregisteredData.violation_type;
                document.getElementById('modalPlateNumber').textContent = unregisteredData.plate_number;
                
                vehicleModal.style.display = 'block';
                vehicleModal.classList.add('show');
            }
        }

        function closeVehicleDetailsForm() {
            var vehicleModal = document.getElementById('vehicleDetailsModal');
            if (vehicleModal) {
                vehicleModal.style.display = 'none';
                vehicleModal.classList.remove('show');
            }
            // Show unregistered modal again or redirect
            closeModal();
        }

        // Close modal when clicking outside of it
        window.onclick = function(event) {
            var unregisteredModal = document.getElementById('unregisteredModal');
            var vehicleModal = document.getElementById('vehicleDetailsModal');
            
            if (event.target == unregisteredModal) {
                closeModal();
            }
            if (event.target == vehicleModal) {
                closeVehicleDetailsForm();
            }
        }
    </script>
</body>
</html>
