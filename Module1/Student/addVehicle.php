<?php
// Include header file
session_start();
require('../../Layout/student_layout.php');

?>

<style>
/* Card styling */
.card {
    border-radius: 0.25rem;
    width: 100%;
    max-width: 600px;
    margin-left: 280px;
}

.card-header {
    background-color: #007bff;
    color: #fff;
    font-weight: bold;
    padding: 1rem;
    border-bottom: 1px solid #ddd;
    border-top-left-radius: 0.25rem;
    border-top-right-radius: 0.25rem;
    text-align: center;
}

/* Card body styling */
.card-body {
    padding: 2rem;
    width: auto;
    height: auto;
    padding-bottom: auto;
}

/* Form styling */
.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    font-weight: bold;
    margin-bottom: 0.5rem;
}

.form-control {
    border: 1px solid #ddd;
    border-radius: 0.25rem;
    padding: 0.5rem;
    width: 100%;
    box-sizing: border-box;
}

/* Button styling */
.btn-success {
    background-color: #28a745;
    border-color: #28a745;
    color: #fff;
    padding: 0.5rem 1rem;
    border-radius: 0.25rem;
    font-weight: bold;
    text-transform: uppercase;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

.btn-success:hover {
    background-color: #218838;
    border-color: #1e7e34;
}

.btn-warning {
    background-color: #f0ad4e;
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 0.25rem;
    font-weight: bold;
    text-transform: uppercase;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

.btn-warning:hover {
    background-color: #ec971f;
    border-color: #ec971f;
}

.btn-warning:active {
    background-color: #d58512;
}

/* Styling for success message */
.alert-success {
    margin-left: 280px;
    margin-right: 20px;
    width: fit-content;
    margin-bottom: 10px;
    padding: 10px;
    background-color: #d4edda;
    border-color: #c3e6cb;
    color: #155724;
    border-radius: 0.25rem;
}

/* Styling for error message */
.alert-danger {
    margin-left: 280px;
    margin-right: 20px;
    margin-bottom: 10px;
    padding: 10px;
    background-color: #f8d7da;
    border-color: #f5c6cb;
    color: #721c24;
    border-radius: 0.25rem;
}

/* Modal Overlay */
.modal-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.7);
    z-index: 9998;
    animation: fadeIn 0.3s ease;
}

.modal-overlay.active {
    display: block;
}

/* Modal Container */
.summon-modal {
    display: none;
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 90%;
    max-width: 700px;
    max-height: 85vh;
    overflow-y: auto;
    background-color: white;
    border-radius: 12px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
    z-index: 9999;
    animation: slideDown 0.4s ease;
}

.summon-modal.active {
    display: block;
}

/* Modal Header */
.modal-header {
    background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
    color: white;
    padding: 20px 25px;
    border-radius: 12px 12px 0 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h4 {
    margin: 0;
    font-size: 20px;
    font-weight: bold;
    display: flex;
    align-items: center;
    gap: 10px;
}

.modal-close {
    background: transparent;
    border: none;
    color: white;
    font-size: 28px;
    cursor: pointer;
    padding: 0;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: background-color 0.3s ease;
}

.modal-close:hover {
    background-color: rgba(255, 255, 255, 0.2);
}

/* Modal Body */
.modal-body {
    padding: 25px;
    background-color: #fff3cd;
}

.plate-info {
    background-color: white;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
    border-left: 4px solid #ffc107;
}

.plate-info strong {
    font-size: 18px;
    color: #333;
}

.plate-info .plate-number {
    font-size: 24px;
    color: #dc3545;
    font-family: 'Courier New', monospace;
    font-weight: bold;
    margin-left: 10px;
}

.warning-message {
    background-color: #fff;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
    border: 1px solid #ffc107;
}

.warning-message p {
    margin: 0;
    color: #856404;
    font-size: 15px;
    line-height: 1.6;
}

.summon-details {
    background-color: white;
    padding: 18px;
    border-radius: 8px;
    margin-bottom: 15px;
    border-left: 4px solid #dc3545;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.summon-details h5 {
    margin-top: 0;
    margin-bottom: 12px;
    color: #dc3545;
    font-size: 16px;
    font-weight: bold;
}

.summon-item {
    margin-bottom: 10px;
    font-size: 14px;
    display: flex;
    justify-content: space-between;
    padding: 5px 0;
}

.summon-item strong {
    color: #333;
    font-weight: 600;
}

.summon-item .value {
    color: #495057;
    text-align: right;
}

.summon-item .value.highlight {
    color: #dc3545;
    font-weight: bold;
    font-size: 15px;
}

.total-amount {
    background: linear-gradient(135deg, #fff 0%, #ffe8e8 100%);
    padding: 15px;
    border-radius: 8px;
    font-size: 22px;
    font-weight: bold;
    color: #dc3545;
    margin-top: 15px;
    text-align: center;
    border: 2px solid #dc3545;
}

/* Modal Footer */
.modal-footer {
    padding: 20px 25px;
    background-color: #f8f9fa;
    border-radius: 0 0 12px 12px;
    display: flex;
    gap: 10px;
    justify-content: center;
}

.btn-pay-now {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    color: white;
    padding: 14px 28px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
    font-size: 15px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    transition: all 0.3s ease;
    box-shadow: 0 4px 6px rgba(40, 167, 69, 0.3);
}

.btn-pay-now:hover {
    background: linear-gradient(135deg, #218838 0%, #1ea080 100%);
    transform: translateY(-2px);
    box-shadow: 0 6px 12px rgba(40, 167, 69, 0.4);
}

.btn-cancel {
    background-color: #6c757d;
    color: white;
    padding: 14px 28px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
    font-size: 15px;
    font-weight: 600;
    text-transform: uppercase;
    transition: all 0.3s ease;
}

.btn-cancel:hover {
    background-color: #5a6268;
    transform: translateY(-2px);
}

/* Animations */
@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translate(-50%, -60%);
    }
    to {
        opacity: 1;
        transform: translate(-50%, -50%);
    }
}

/* Scrollbar styling for modal */
.summon-modal::-webkit-scrollbar {
    width: 8px;
}

.summon-modal::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
}

.summon-modal::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 10px;
}

.summon-modal::-webkit-scrollbar-thumb:hover {
    background: #555;
}
</style>

<?php

require('../../db_config.php');

// Variables to store summon information
$hasSummons = false;
$summonDetails = [];
$totalAmount = 0;

// Check if form is submitted and the add_user button is clicked
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_user'])) {
    // Get form data
    $plateNum = strtoupper(trim($_POST['plate'])); // Normalize plate number
    $vehicleType = $_POST['type'];
    $vehicleGrant = $_POST['grant'];
    $colour = $_POST['V_colour'];
    $brand = $_POST['V_brand'];

    // Get the STU_studentID from the session
    if (isset($_SESSION['STU_studentID'])) {
        $studentID = $_SESSION['STU_studentID'];

        // First, check if this plate number already exists with "Unregistered" status
        $checkQuery = "SELECT V_vehicleID FROM vehicle WHERE V_plateNum = ? AND V_status = 'Unregistered'";
        $checkStmt = $link->prepare($checkQuery);
        $checkStmt->bind_param("s", $plateNum);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();

        if ($checkResult->num_rows > 0) {
            // Vehicle exists with unregistered status - check for unpaid summons
            $vehicleData = $checkResult->fetch_assoc();
            $vehicleID = $vehicleData['V_vehicleID'];
            
            // Get detailed summon information
            $summonQuery = "SELECT TF_summonID, TF_date, TF_violationType, TF_demeritPoint 
                           FROM trafficsummon 
                           WHERE V_vehicleID = ? AND TF_status = 'Unpaid'
                           ORDER BY TF_date DESC";
            $summonStmt = $link->prepare($summonQuery);
            $summonStmt->bind_param("i", $vehicleID);
            $summonStmt->execute();
            $summonResult = $summonStmt->get_result();
            
            if ($summonResult->num_rows > 0) {
                // Store summon details
                $hasSummons = true;
                while ($summon = $summonResult->fetch_assoc()) {
                    $amount = calculateSummonAmount((int)$summon['TF_demeritPoint'], $summon['TF_violationType']);
                    $summon['amount'] = $amount;
                    $totalAmount += $amount;
                    $summonDetails[] = $summon;
                }
            }
            $summonStmt->close();
        }
        $checkStmt->close();

        // If no unpaid summons, proceed with registration checks
        if (!$hasSummons) {
            // First check if this vehicle exists as "Unregistered" (staff created it)
            $unregQuery = "SELECT V_vehicleID FROM vehicle WHERE V_plateNum = ? AND V_status = 'Unregistered'";
            $unregStmt = $link->prepare($unregQuery);
            $unregStmt->bind_param("s", $plateNum);
            $unregStmt->execute();
            $unregResult = $unregStmt->get_result();

            if ($unregResult->num_rows > 0) {
                // Vehicle exists as unregistered - UPDATE it instead of INSERT
                $vehicleData = $unregResult->fetch_assoc();
                $vehicleID = $vehicleData['V_vehicleID'];
                $unregStmt->close();

                // Update the existing vehicle record with student's information
                $updateQuery = "UPDATE vehicle 
                               SET V_vehigrant = ?, 
                                   V_vehicleType = ?, 
                                   V_brand = ?, 
                                   V_colour = ?, 
                                   V_status = 'pending', 
                                   STU_studentID = ?
                               WHERE V_vehicleID = ?";
                $updateStmt = $link->prepare($updateQuery);
                $updateStmt->bind_param("ssssii", $vehicleGrant, $vehicleType, $brand, $colour, $studentID, $vehicleID);

                if ($updateStmt->execute()) {
                    echo "<div class='alert-success' role='alert'>
                            Vehicle registered successfully! Your previous summons have been paid and the vehicle is now linked to your account.
                          </div>";
                } else {
                    echo "<div class='alert-danger' role='alert'>Error: " . $updateStmt->error . "</div>";
                }
                $updateStmt->close();
            } else {
                $unregStmt->close();

                // Check if vehicle is already registered by this or another student
                $duplicateQuery = "SELECT * FROM vehicle WHERE V_plateNum = ?";
                $dupStmt = $link->prepare($duplicateQuery);
                $dupStmt->bind_param("s", $plateNum);
                $dupStmt->execute();
                $dupResult = $dupStmt->get_result();

                if ($dupResult->num_rows > 0) {
                    echo "<div class='alert-danger' role='alert'>
                            Error: This vehicle is already registered in the system.
                          </div>";
                    $dupStmt->close();
                    $link->close();
                    exit();
                }
                $dupStmt->close();

                // If no issues, proceed with NEW registration (INSERT)
                $query = "INSERT INTO vehicle (V_plateNum, V_vehigrant, V_vehicleType, V_brand, V_colour, V_status, STU_studentID)
                          VALUES (?, ?, ?, ?, ?, 'pending', ?)";
                $stmt = $link->prepare($query);
                $stmt->bind_param("sssssi", $plateNum, $vehicleGrant, $vehicleType, $brand, $colour, $studentID);

                if ($stmt->execute()) {
                    echo "<div class='alert-success' role='alert'>Vehicle registered successfully!</div>";
                } else {
                    echo "<div class='alert-danger' role='alert'>Error: " . $stmt->error . "</div>";
                }

                // Close the statement
                $stmt->close();
            }
        }
    } else {
        echo "<div class='alert-danger' role='alert'>Error: User is not logged in.</div>";
    }
}

// Function to calculate summon amount based on demerit points and violation type
function calculateSummonAmount($demerit_points, $violation_type) {
    $base_amount = 0;
    
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
    
    $additional = $demerit_points * 2;
    return $base_amount + $additional;
}

// Close the database connection
$link->close();
?>

<!-- Modal Overlay -->
<div class="modal-overlay" id="modalOverlay"></div>

<!-- Summon Modal -->
<?php if ($hasSummons): ?>
<div class="summon-modal" id="summonModal">
    <div class="modal-header">
        <h4>⚠️ VEHICLE REGISTRATION BLOCKED</h4>
        <button class="modal-close" onclick="closeModal()">&times;</button>
    </div>
    
    <div class="modal-body">
        <div class="plate-info">
            <strong>Vehicle Plate Number:</strong>
            <span class="plate-number"><?php echo htmlspecialchars($plateNum); ?></span>
        </div>
        
        <div class="warning-message">
            <p><strong>⚠️ Unpaid Summons Detected!</strong></p>
            <p>This vehicle has <strong><?php echo count($summonDetails); ?> unpaid traffic summon(s)</strong>. You must pay all outstanding summons before registering this vehicle.</p>
        </div>
        
        <?php foreach ($summonDetails as $index => $summon): ?>
            <div class="summon-details">
                <h5>📋 Summon #<?php echo $index + 1; ?></h5>
                <div class="summon-item">
                    <strong>Date:</strong>
                    <span class="value"><?php echo htmlspecialchars($summon['TF_date']); ?></span>
                </div>
                <div class="summon-item">
                    <strong>Violation Type:</strong>
                    <span class="value"><?php echo htmlspecialchars($summon['TF_violationType']); ?></span>
                </div>
                <div class="summon-item">
                    <strong>Demerit Points:</strong>
                    <span class="value highlight"><?php echo htmlspecialchars($summon['TF_demeritPoint']); ?> points</span>
                </div>
                <div class="summon-item">
                    <strong>Amount Due:</strong>
                    <span class="value highlight">RM <?php echo number_format($summon['amount'], 2); ?></span>
                </div>
            </div>
        <?php endforeach; ?>
        
        <div class="total-amount">
            💰 Total Amount Due: RM <?php echo number_format($totalAmount, 2); ?>
        </div>
    </div>
    
    <div class="modal-footer">
        <?php if (!empty($summonDetails)): ?>
            <!-- One or more summons - direct payment -->
            <a href="/Mini-Project-Web-Eng/Module4/processPayment.php?summon_id=<?php echo htmlspecialchars($summonDetails[0]['TF_summonID']); ?>&amount=<?php echo $summonDetails[0]['amount']; ?>" class="btn-pay-now">
                💳 Pay Summon Now (RM <?php echo number_format($summonDetails[0]['amount'], 2); ?>)
            </a>
        <?php else: ?>
            <!-- No summons found - link to My Summons page -->
            <a href="/Mini-Project-Web-Eng/Module4/mySummon.php?plate=<?php echo urlencode($plateNum); ?>" class="btn-pay-now">
                💳 Go to My Summons to Pay
            </a>
        <?php endif; ?>
        
        <button onclick="closeModal()" class="btn-cancel">Cancel Registration</button>
    </div>

</div>
<?php endif; ?>

<div class="card">
    <div class="card-header" style="margin-top:50px;">
        Vehicle Registration
    </div>
    <div class="card-body">
        <!-- Add User Form -->
        <form method="POST">
            <div class="form-group mb-3">
                <label for="plate">Plate Number</label>
                <input type="text" required class="form-control" id="plate" name="plate" 
                       placeholder="e.g., 14JJG8398" style="text-transform: uppercase;"
                       value="<?php echo isset($plateNum) && !$hasSummons ? htmlspecialchars($plateNum) : ''; ?>">
            </div>
            <div class="form-group mb-3">
                <label for="type">Type:</label>
                <select name="type" id="type" class="form-control" required>
                    <option value="Car">Car</option>
                    <option value="Motorcycle">Motorcycle</option>
                </select>
            </div>
            <div class="form-group mb-3">
                <label for="grant">Grant</label>
                <input type="file" class="form-control" id="grant" name="grant" required>
            </div>
            <div class="form-group mb-3">
                <label for="V_brand">Brand</label>
                <select class="form-control" required id="V_brand" name="V_brand">
                    <option value="Toyota">Toyota</option>
                    <option value="Honda">Honda</option>
                    <option value="Perodua">Perodua</option>
                    <option value="Proton">Proton</option>
                    <option value="Volkswagen">Volkswagen</option>
                    <option value="BMW">BMW</option>
                    <!-- Add more options as needed -->
                </select>
            </div>
            <div class="form-group mb-3">
                <label for="V_colour">Color</label>
                <select class="form-control" required id="V_colour" name="V_colour">
                    <option value="Black">Black</option>
                    <option value="White">White</option>
                    <option value="Silver">Silver</option>
                    <option value="Gray">Gray</option>
                    <option value="Red">Red</option>
                    <!-- Add more options as needed -->
                </select>
            </div>
            <button type="submit" name="add_user" class="btn btn-success">Register Vehicle</button>
            <button type="reset" name="reset" class="btn btn-warning">Reset</button>
        </form>
        <!-- End Form -->
    </div>
</div>

<script>
<?php if ($hasSummons): ?>
    // Show modal when page loads
    window.onload = function() {
        showModal();
        // Also show browser alert
        alert('⚠️ WARNING: UNPAID SUMMONS DETECTED\n\nVehicle Plate: <?php echo $plateNum; ?>\nTotal Unpaid Summons: <?php echo count($summonDetails); ?>\nTotal Amount Due: RM <?php echo number_format($totalAmount, 2); ?>\n\nPlease pay all outstanding summons before registering this vehicle.');
    };
<?php endif; ?>

function showModal() {
    document.getElementById('summonModal').classList.add('active');
    document.getElementById('modalOverlay').classList.add('active');
    document.body.style.overflow = 'hidden'; // Prevent background scrolling
}

function closeModal() {
    document.getElementById('summonModal').classList.remove('active');
    document.getElementById('modalOverlay').classList.remove('active');
    document.body.style.overflow = 'auto';
    // Redirect back or clear form
    window.location.href = window.location.pathname;
}

// Close modal when clicking overlay
document.getElementById('modalOverlay')?.addEventListener('click', closeModal);
</script>

<?php
// Include footer and scripts
include('../../footer/footer.php');
?>