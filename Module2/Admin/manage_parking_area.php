<?php
include('../../Layout/admin_layout.php');

require('../../db_config.php');

$now = date('Y-m-d H:i:s');

$link->query("
    UPDATE parkingSpace 
    SET P_status = 'Available',
        P_reason = 'None',
        P_closeStartTime = NULL,
        P_closeEndTime = NULL
    WHERE P_status = 'Temporary Closed'
      AND P_closeEndTime IS NOT NULL
      AND P_closeEndTime <= '$now'
");

date_default_timezone_set('Asia/Kuala_Lumpur');


// Fetch parking spaces status
$fixed_locations = ['A1', 'A2', 'A3', 'A4', 'B1', 'B2', 'B3'];
$parking_statuses = [];

// Updated query to also fetch the reason
$parking_sql = "SELECT P_location, P_status, P_reason FROM parkingSpace WHERE P_location IN ('" . implode("','", $fixed_locations) . "')";
$parking_result = $link->query($parking_sql);

while ($parking_row = $parking_result->fetch_assoc()) {
    $parking_statuses[$parking_row['P_location']] = [
        'status' => $parking_row['P_status'],
        'reason' => $parking_row['P_reason']
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Parking Area</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .content-container {
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0px 0px 10px 0px rgba(0,0,0,0.1);
            text-align: center;
        }
        .content-container h2 {
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        table th, table td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: center;
        }
        table th {
            background-color: #333;
            color: white;
        }
        button {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            background-color: #333;
            color: white;
            cursor: pointer;
        }
        button:hover {
            background-color: #555;
        }
    </style>
    <script>
        function updateParkingStatus(location, status, reason = 'None', start = '', end = '') {
    const xhr = new XMLHttpRequest();
    xhr.open("POST", "update_parking_status.php", true);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

    xhr.send(
        "location=" + location +
        "&status=" + status +
        "&reason=" + encodeURIComponent(reason) +
        "&start_time=" + start +
        "&end_time=" + end
    );
    closeModal();
}

        function openCloseModal(location) {
    document.getElementById('modalLocation').value = location;
    document.getElementById('closeModal').style.display = 'block';
}

function closeModal() {
    document.getElementById('closeModal').style.display = 'none';
}

function submitClose() {
    const location = document.getElementById('modalLocation').value;
    const reason = document.getElementById('modalReason').value;
    const start = document.getElementById('modalStart').value;
    const end = document.getElementById('modalEnd').value;

    if (!reason || !start || !end) {
        alert("Please fill all fields");
        return;
    }
    updateParkingStatus(location, 'Temporary Closed', reason, start, end);
}

setInterval(() => {
    fetch('update_parking_status.php', {
        method: 'POST'
    });
}, 30000); // setiap 30 saat
    </script>
</head>
<body>
<div class="content-container">
    <h2>Manage Parking Area</h2>


    <div style="text-align:right; margin-bottom:15px;">
    <a href="parking_history.php">
        <button>View Parking History</button>
    </a>
    </div>




    <table>
        <thead>
            <tr>
                <th>Location</th>
                <th>Status</th>
                <th>Reason</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php
            foreach ($fixed_locations as $location) {
                $status = isset($parking_statuses[$location]['status']) ? $parking_statuses[$location]['status'] : 'Unknown';
                $reason = isset($parking_statuses[$location]['reason']) ? $parking_statuses[$location]['reason'] : '';
                echo "<tr>";
                echo "<td>{$location}</td>";
                echo "<td id='status-{$location}'>{$status}</td>";
                echo "<td id='reason-{$location}'>{$reason}</td>";
                echo "<td>
                        <button onclick=\"updateParkingStatus('{$location}', 'Available')\">Open</button>
                        <button onclick=\"openCloseModal('{$location}')\">Close</button>
                      </td>";
                echo "</tr>";
            }
            ?>
        </tbody>
    </table>

<div id="closeModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5);">
  <div style="background:#fff; padding:20px; width:400px; margin:10% auto; border-radius:10px;">
    <h3>Close Parking Area</h3>

    <input type="hidden" id="modalLocation">

    <label>Reason</label>
    <input type="text" id="modalReason" style="width:100%; margin-bottom:10px;">

    <label>Start Time</label>
    <input type="datetime-local" id="modalStart" style="width:100%; margin-bottom:10px;">

    <label>End Time</label>
    <input type="datetime-local" id="modalEnd" style="width:100%; margin-bottom:10px;">

    <button onclick="submitClose()">Confirm</button>
    <button onclick="closeModal()">Cancel</button>
  </div>
</div>


</div>
</body>
</html>
