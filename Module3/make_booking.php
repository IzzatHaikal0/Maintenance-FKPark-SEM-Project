<?php
ob_start();
session_start();
include('../Layout/student_layout.php');

/* =========================
   DATABASE CONNECTION
========================= */
$link = mysqli_connect("localhost", "root", "", "web_eng");
if (!$link) {
    die("Database connection failed: " . mysqli_connect_error());
}

/* =========================
   LOGIN CHECK
========================= */
if (!isset($_SESSION['STU_studentID'])) {
    die("Student not logged in");
}
$studentID = $_SESSION['STU_studentID'];

/* =========================
   GET PARKING SPACE INFO
========================= */
$parkingSpaceID = $_GET['id'] ?? '';
$location = $_GET['location'] ?? '';
$status = $_GET['status'] ?? '';
$type = $_GET['type'] ?? '';

if ($parkingSpaceID === '') {
    die("Invalid parking space");
}

/* =========================
   FETCH STUDENT VEHICLES
========================= */
$stmt = $link->prepare("SELECT V_vehicleID, V_plateNum FROM vehicle WHERE STU_studentID = ?");
$stmt->bind_param("i", $studentID);
$stmt->execute();
$vehicles = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

/* =========================
   HANDLE BOOKING
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $plateNum = $_POST['plateNum'];
    $startTime = $_POST['startTime'];
    $endTime = $_POST['endTime'];
    $parkingSpaceID = $_POST['parkingSpaceID'];

    /* =========================
       VALIDATE TIMES
    ========================== */
    if ($endTime <= $startTime) {
        echo "<script>alert('End time must be later than start time');</script>";
        exit();
    }

    /* =========================
       GET VEHICLE ID
    ========================== */
    $stmt = $link->prepare("SELECT V_vehicleID FROM vehicle WHERE V_plateNum = ? AND STU_studentID = ?");
    $stmt->bind_param("si", $plateNum, $studentID);
    $stmt->execute();
    $vehicle = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$vehicle) {
        echo "<script>alert('Invalid vehicle selected');</script>";
        exit();
    }
    $vehicleID = $vehicle['V_vehicleID'];

    /* =========================
       CHECK PARKING SPACE CLASH
       Formula: NOT (new_end <= existing_start OR new_start >= existing_end)
       Also handle B_endTime NULL (legacy data)
    ========================== */
    $stmt = $link->prepare("
        SELECT COUNT(*) AS clash
        FROM booking
        WHERE P_parkingSpaceID = ?
        AND (B_endTime IS NULL OR NOT (? >= B_endTime OR ? <= B_startTime))
    ");
    $stmt->bind_param("sss", $parkingSpaceID, $startTime, $endTime);
    $stmt->execute();
    $parkingClash = $stmt->get_result()->fetch_assoc()['clash'];
    $stmt->close();

    if ($parkingClash > 0) {
        echo "<script>alert('Cannot make booking. This parking space is already booked for the selected time.');</script>";
        exit();
    }

    /* =========================
       CHECK VEHICLE CLASH
    ========================== */
    $stmt = $link->prepare("
        SELECT COUNT(*) AS clash
        FROM booking
        WHERE V_vehicleID = ?
        AND (B_endTime IS NULL OR NOT (? >= B_endTime OR ? <= B_startTime))
    ");
    $stmt->bind_param("iss", $vehicleID, $startTime, $endTime);
    $stmt->execute();
    $vehicleClash = $stmt->get_result()->fetch_assoc()['clash'];
    $stmt->close();

    if ($vehicleClash > 0) {
        echo "<script>alert('This vehicle is already booked during this time');</script>";
        exit();
    }

    /* =========================
       INSERT BOOKING
    ========================== */
    $stmt = $link->prepare("
        INSERT INTO booking (B_startTime, B_endTime, P_parkingSpaceID, V_vehicleID)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->bind_param("sssi", $startTime, $endTime, $parkingSpaceID, $vehicleID);
    $stmt->execute();
    $bookingID = mysqli_insert_id($link);
    $stmt->close();

    $_SESSION['bookingID'] = $bookingID;
    header("Location: generate_qr_.php?bookingID=$bookingID");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Make Booking</title>
    <style>
        .content-container {
            max-width: 700px;
            margin: 40px auto;
            padding: 20px;
            background: #fff;
            border-radius: 10px;
            text-align: center;
        }
        select, input, button {
            padding: 8px;
            margin: 8px 0;
            width: 80%;
            max-width: 300px;
        }
    </style>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Make endTime >= startTime
            document.getElementById("startTime").addEventListener("change", function() {
                document.getElementById("endTime").min = this.value;
            });
        });
    </script>
</head>
<body>
<div class="content-container">
    <h2>Parking Booking</h2>
    <p><b>Parking ID:</b> <?= htmlspecialchars($parkingSpaceID) ?></p>
    <p><b>Location:</b> <?= htmlspecialchars($location) ?></p>

    <form method="POST">
        <input type="hidden" name="parkingSpaceID" value="<?= htmlspecialchars($parkingSpaceID) ?>">

        <label>Vehicle</label><br>
        <select name="plateNum" required>
            <?php foreach ($vehicles as $v): ?>
                <option value="<?= htmlspecialchars($v['V_plateNum']) ?>"><?= htmlspecialchars($v['V_plateNum']) ?></option>
            <?php endforeach; ?>
        </select><br>

        <label>Start Time</label><br>
        <input type="datetime-local" name="startTime" id="startTime" required><br>

        <label>End Time</label><br>
        <input type="datetime-local" name="endTime" id="endTime" required><br>

        <button type="submit">Confirm Booking</button>
    </form>
</div>
</body>
</html>
