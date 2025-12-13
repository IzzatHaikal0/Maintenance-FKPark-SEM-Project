<?php
session_start();

// Database connection using centralized config
require('../db_config.php');
// Use procedural style for consistency, or convert to OOP if needed
$conn = $link;

// Initialize variables
$summons = [];

// Check if student ID is available in the session (assuming student ID is stored in session when student logs in)
if (isset($_SESSION['STU_studentID'])) {
    $student_id = $_SESSION['STU_studentID'];

    // Fetch all vehicles registered by the student
    $sql_vehicles = "SELECT V_vehicleID, V_plateNum FROM vehicle WHERE STU_studentID = ?";
    $stmt_vehicles = $conn->prepare($sql_vehicles);
    if ($stmt_vehicles) {
        $stmt_vehicles->bind_param("i", $student_id);
        $stmt_vehicles->execute();
        $result_vehicles = $stmt_vehicles->get_result();

        // For each vehicle, fetch the latest summon details
        while ($vehicle = $result_vehicles->fetch_assoc()) {
            $vehicle_id = $vehicle['V_vehicleID'];
            $plate_number = $vehicle['V_plateNum'];

            $sql_summon = "SELECT TF_summonID, TF_date, TF_status, TF_violationType, TF_demeritPoint
                           FROM trafficSummon
                           WHERE V_vehicleID = ?
                           ORDER BY TF_date DESC
                           LIMIT 1";  // Assuming you want the latest summon per vehicle

            $stmt_summon = $conn->prepare($sql_summon);
            if ($stmt_summon) {
                $stmt_summon->bind_param("i", $vehicle_id);
                $stmt_summon->execute();
                $result_summon = $stmt_summon->get_result();

                if ($result_summon->num_rows > 0) {
                    $summon = $result_summon->fetch_assoc();
                    $summon['plate_number'] = $plate_number;
                    $summon['enforcement_type'] = getEnforcementType((int)$summon['TF_demeritPoint']);
                    $summon['amount'] = calculateSummonAmount((int)$summon['TF_demeritPoint'], $summon['TF_violationType']);
                    $summons[] = $summon;
                }
                $stmt_summon->close();
            }
        }
        $stmt_vehicles->close();
    }
}

$conn->close();

// Function to get enforcement type based on demerit points
function getEnforcementType($demerit_points) {
    if ($demerit_points < 20) {
        return "Warning given";
    } elseif ($demerit_points < 50) {
        return "Revoke of in-campus vehicle permission for 1 semester";
    } elseif ($demerit_points < 80) {
        return "Revoke of in-campus vehicle permission for 2 semesters";
    } else {
        return "Revoke of in-campus vehicle permission for the entire study duration";
    }
}

// Function to calculate summon amount based on demerit points and violation type
function calculateSummonAmount($demerit_points, $violation_type) {
    $base_amount = 0;
    
    // Base amount based on violation type
    switch ($violation_type) {
        case 'Parking Violation':
            $base_amount = 50;
            break;
        case 'Not Complying with Campus Traffic Regulations':
            $base_amount = 75;
            break;
        case 'Accident Caused':
            $base_amount = 100;
            break;
        default:
            $base_amount = 50;
    }
    
    // Add additional amount based on demerit points
    $additional = $demerit_points * 2; // RM2 per demerit point
    
    return $base_amount + $additional;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Summon</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f4f4;
            color: #333;
            line-height: 1.6;
        }
        .content-container {
            max-width: 1400px;
            margin: 20px auto;
            margin-left: 280px;
            padding: 30px;
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        .content-container h2 {
            margin-bottom: 30px;
            color: #2c3e50;
            font-size: 28px;
            font-weight: 600;
            text-align: left;
        }
        .table-wrapper {
            overflow-x: auto;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            background-color: white;
            border-radius: 8px;
            overflow: hidden;
        }
        thead {
            background-color: #000000 !important;
            background: #000000 !important;
            color: white;
        }
        th {
            padding: 16px 12px;
            text-align: left;
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border: none;
        }
        th:first-child {
            border-top-left-radius: 8px;
        }
        th:last-child {
            border-top-right-radius: 8px;
        }
        tbody tr {
            transition: all 0.2s ease;
            border-bottom: 1px solid #e9ecef;
        }
        tbody tr:hover {
            background-color: #f8f9fa;
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        tbody tr:last-child {
            border-bottom: none;
        }
        td {
            padding: 16px 12px;
            border: none;
            vertical-align: middle;
            font-size: 14px;
        }
        .status-badge {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            white-space: nowrap;
        }
        .status-paid {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .status-unpaid {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .amount {
            font-weight: 700;
            color: #2c3e50;
            font-size: 15px;
        }
        .amount::before {
            content: "RM ";
            font-weight: 600;
            color: #6c757d;
        }
        .btn-pay {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(40, 167, 69, 0.3);
        }
        .btn-pay:hover {
            background: linear-gradient(135deg, #218838 0%, #1ea080 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(40, 167, 69, 0.4);
        }
        .btn-pay:active {
            transform: translateY(0);
        }
        .paid-indicator {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: #28a745;
            font-weight: 600;
            font-size: 14px;
        }
        .paid-indicator::before {
            content: "✓";
            background-color: #28a745;
            color: white;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: bold;
        }
        .plate-number {
            font-weight: 600;
            color: #2c3e50;
            font-family: 'Courier New', monospace;
            font-size: 15px;
        }
        .violation-type {
            color: #495057;
            font-size: 13px;
        }
        .demerit-points {
            font-weight: 600;
            color: #dc3545;
        }
        .enforcement-type {
            color: #6c757d;
            font-size: 13px;
            line-height: 1.5;
            max-width: 300px;
        }
        .date-cell {
            color: #495057;
            font-size: 13px;
        }
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #6c757d;
        }
        .empty-state p {
            font-size: 16px;
            margin: 0;
        }
    </style>
</head>
<body>
    <?php include('../Layout/student_layout.php'); ?>
    <div class="content-container">
        <h2>My Summon</h2>
        <?php
        if (count($summons) > 0) {
            echo "<div class='table-wrapper'>";
            echo "<table>";
            echo "<thead>";
            echo "<tr>";
            echo "<th>Plate Number</th>";
            echo "<th>Date</th>";
            echo "<th>Status</th>";
            echo "<th>Violation Type</th>";
            echo "<th>Demerit Points</th>";
            echo "<th>Amount</th>";
            echo "<th>Enforcement Type</th>";
            echo "<th>Action</th>";
            echo "</tr>";
            echo "</thead>";
            echo "<tbody>";
            foreach ($summons as $summon) {
                echo "<tr>";
                echo "<td><span class='plate-number'>" . htmlspecialchars($summon['plate_number']) . "</span></td>";
                echo "<td class='date-cell'>" . htmlspecialchars($summon['TF_date']) . "</td>";
                $status_class = ($summon['TF_status'] == 'Paid') ? 'status-paid' : 'status-unpaid';
                echo "<td><span class='status-badge " . $status_class . "'>" . htmlspecialchars($summon['TF_status']) . "</span></td>";
                echo "<td><span class='violation-type'>" . htmlspecialchars($summon['TF_violationType']) . "</span></td>";
                echo "<td><span class='demerit-points'>" . htmlspecialchars($summon['TF_demeritPoint']) . "</span></td>";
                echo "<td class='amount'>" . number_format($summon['amount'], 2) . "</td>";
                echo "<td><span class='enforcement-type'>" . htmlspecialchars($summon['enforcement_type']) . "</span></td>";
                echo "<td>";
                if ($summon['TF_status'] == 'Unpaid') {
                    echo "<a href='Module4/processPayment.php?summon_id=" . htmlspecialchars($summon['TF_summonID']) . "&amount=" . $summon['amount'] . "' class='btn-pay'>Pay Now</a>";
                } else {
                    echo "<span class='paid-indicator'>Paid</span>";
                }
                echo "</td>";
                echo "</tr>";
            }
            echo "</tbody>";
            echo "</table>";
            echo "</div>";
        } else {
            echo "<div class='empty-state'>";
            echo "<p>No summon details found.</p>";
            echo "</div>";
        }
        ?>
    </div>
</body>
</html>
