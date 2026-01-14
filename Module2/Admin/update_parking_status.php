<?php
require('../../db_config.php');

if (!$link) {
    echo json_encode(['success' => false]);
    exit;
}

date_default_timezone_set('Asia/Kuala_Lumpur');

$location = $_POST['location'];
$status = $_POST['status'];
$reason = $_POST['reason'] ?? 'None';
$start = $_POST['start_time'] ?? null;
$end = $_POST['end_time'] ?? null;

if ($start) {
    $start = date('Y-m-d H:i:s', strtotime($start));
}
if ($end) {
    $end = date('Y-m-d H:i:s', strtotime($end));
}

if ($status === 'Temporary Closed') {
    $sql = "UPDATE parkingSpace 
            SET P_status = ?, 
                P_reason = ?, 
                P_closeStartTime = ?, 
                P_closeEndTime = ?
            WHERE P_location = ?";
    $stmt = $link->prepare($sql);
    $stmt->bind_param("sssss", $status, $reason, $start, $end, $location);

    // INSERT HISTORY - CLOSED
$historySql = "INSERT INTO parkingClosureHistory
    (P_location, action_type, reason, start_time, end_time)
    VALUES (?, 'Closed', ?, ?, ?)";

$historyStmt = $link->prepare($historySql);
$historyStmt->bind_param(
    "ssss",
    $location,
    $reason,
    $start,
    $end
);
$historyStmt->execute();
$historyStmt->close();

} else {
    // Manual open
    $sql = "UPDATE parkingSpace 
            SET P_status = 'Available',
                P_reason = 'None',
                P_closeStartTime = NULL,
                P_closeEndTime = NULL
            WHERE P_location = ?";
    $stmt = $link->prepare($sql);
    $stmt->bind_param("s", $location);

    // INSERT HISTORY - OPENED (MANUAL)
$historySql = "INSERT INTO parkingClosureHistory
    (P_location, action_type, reason)
    VALUES (?, 'Opened (Manual)', 'Manual Open')";

$historyStmt = $link->prepare($historySql);
$historyStmt->bind_param("s", $location);
$historyStmt->execute();
$historyStmt->close();

}

$stmt->execute();



// AUTO OPEN
$now = date('Y-m-d H:i:s');

// STEP A: detect dulu
$auto = $link->query("
    SELECT P_location, P_closeStartTime, P_closeEndTime
    FROM parkingSpace
    WHERE P_status = 'Temporary Closed'
      AND P_closeEndTime IS NOT NULL
      AND P_closeEndTime <= '$now'
");

if ($auto && $auto->num_rows > 0) {

    while ($row = $auto->fetch_assoc()) {

        // history - auto open
        $h = $link->prepare("
            INSERT INTO parkingClosureHistory
            (P_location, action_type, reason, start_time, end_time)
            VALUES (?, 'Opened (Auto)', 'Auto open', ?, ?)
        ");
        $h->bind_param(
            "sss",
            $row['P_location'],
            $row['P_closeStartTime'],
            $row['P_closeEndTime']
        );
        $h->execute();
    }

    // STEP B: baru update
    $link->query("
        UPDATE parkingSpace
        SET P_status = 'Available',
            P_reason = 'None',
            P_closeStartTime = NULL,
            P_closeEndTime = NULL
        WHERE P_status = 'Temporary Closed'
          AND P_closeEndTime <= '$now'
    ");
}

echo json_encode(['success' => true]);

