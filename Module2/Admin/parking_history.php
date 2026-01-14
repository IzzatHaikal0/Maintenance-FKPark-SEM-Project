<?php
include('../../Layout/admin_layout.php');
require('../../db_config.php');

// Fetch all parking history
$sql = "SELECT * FROM parkingClosureHistory ORDER BY action_datetime DESC";
$result = $link->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Parking Closure History</title>

    <style>
        .content-container {
            max-width: 1000px;
            margin: 50px auto;
            padding: 20px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0px 0px 10px rgba(0,0,0,0.1);
        }

        h2 {
            text-align: center;
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: center;
        }

        th {
            background-color: #333;
            color: white;
        }

        .back-btn {
            margin-bottom: 15px;
        }

        .back-btn button {
            background-color: #333;
            color: white;
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .back-btn button:hover {
            background-color: #555;
        }
    </style>
</head>
<body>

<div class="content-container">

    <div class="back-btn">
        <a href="manage_parking_area.php">
            <button>← Back</button>
        </a>
    </div>

    <h2>Parking Closure History</h2>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Location</th>
                <th>Action Type</th>
                <th>Reason</th>
                <th>Start Time</th>
                <th>End Time</th>
                <th>Action Date</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if ($result->num_rows > 0) {
                $no = 1;
                while ($row = $result->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>{$no}</td>";
                    echo "<td>{$row['P_location']}</td>";
                    echo "<td>{$row['action_type']}</td>";
                    echo "<td>{$row['reason']}</td>";
                    echo "<td>{$row['start_time']}</td>";
                    echo "<td>{$row['end_time']}</td>";
                    echo "<td>{$row['action_datetime']}</td>";
                    echo "</tr>";
                    $no++;
                }
            } else {
                echo "<tr><td colspan='7'>No history found</td></tr>";
            }
            ?>
        </tbody>
    </table>

</div>

</body>
</html>
