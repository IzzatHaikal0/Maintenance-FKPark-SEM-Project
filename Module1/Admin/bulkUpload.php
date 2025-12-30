<?php
// Include header file
require('../../Layout/admin_layout.php');

// Database connection using centralized config
require('../../db_config.php');

// Include database connection file
mysqli_select_db($link, "web_eng");

// Process bulk upload
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['bulk_upload'])) {
    if (isset($_FILES['csvFile']) && $_FILES['csvFile']['error'] == 0) {
        $fileName = $_FILES['csvFile']['tmp_name'];
        
        // Check if file is CSV
        $fileExtension = strtolower(pathinfo($_FILES['csvFile']['name'], PATHINFO_EXTENSION));
        if ($fileExtension != 'csv') {
            echo "<div class='alert alert-danger' role='alert'>Please upload a CSV file only!</div>";
        } else {
            // Read CSV file
            $file = fopen($fileName, 'r');
            
            // Skip header row
            $header = fgetcsv($file);
            
            // Initialize counters
            $successCount = 0;
            $errorCount = 0;
            $errors = array();
            
            // Default password
            $defaultPassword = "FK123";
            
            // Start transaction
            mysqli_autocommit($link, false);
            
            $rowNumber = 1;
            while (($row = fgetcsv($file)) !== false) {
                $rowNumber++;
                
                // Skip empty rows
                if (empty(array_filter($row))) {
                    continue;
                }
                
                // Check if row has enough columns
                if (count($row) < 7) {
                    $errors[] = "Row $rowNumber: Insufficient data columns";
                    $errorCount++;
                    continue;
                }
                
                // Extract data
                $username = trim($row[0]);
                $studentName = trim($row[1]);
                $studentPhoneNum = trim($row[2]);
                $studentAddress = trim($row[3]);
                $studentYear = trim($row[4]);
                $studentType = trim($row[5]);
                $studentEmail = trim($row[6]);
                
                // Validate required fields
                if (empty($username) || empty($studentName) || empty($studentEmail)) {
                    $errors[] = "Row $rowNumber: Missing required fields (Username, Name, or Email)";
                    $errorCount++;
                    continue;
                }
                
                // Validate email format
                if (!filter_var($studentEmail, FILTER_VALIDATE_EMAIL)) {
                    $errors[] = "Row $rowNumber: Invalid email format - $studentEmail";
                    $errorCount++;
                    continue;
                }
                
                // Validate student type
                if (!in_array($studentType, ['Undergraduate', 'Postgraduate'])) {
                    $errors[] = "Row $rowNumber: Invalid Level of Study (must be Undergraduate or Postgraduate)";
                    $errorCount++;
                    continue;
                }
                
                // Check for duplicate username
                $checkQuery = "SELECT STU_username FROM student WHERE STU_username = ?";
                $checkStmt = $link->prepare($checkQuery);
                $checkStmt->bind_param("s", $username);
                $checkStmt->execute();
                $checkResult = $checkStmt->get_result();
                
                if ($checkResult->num_rows > 0) {
                    $errors[] = "Row $rowNumber: Username '$username' already exists";
                    $errorCount++;
                    $checkStmt->close();
                    continue;
                }
                $checkStmt->close();
                
                // Insert student
                $queryStudent = "INSERT INTO student (STU_username, STU_name, STU_type, STU_phoneNum, STU_yearStudy, STU_address, STU_email, STU_password)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $stmtStudent = $link->prepare($queryStudent);
                $stmtStudent->bind_param("sssissss", $username, $studentName, $studentType, $studentPhoneNum, $studentYear, $studentAddress, $studentEmail, $defaultPassword);
                
                if ($stmtStudent->execute()) {
                    $successCount++;
                } else {
                    $errors[] = "Row $rowNumber: Database error - " . $stmtStudent->error;
                    $errorCount++;
                }
                
                $stmtStudent->close();
            }
            
            fclose($file);
            
            // Commit or rollback
            if ($errorCount == 0) {
                mysqli_commit($link);
                echo "<div class='alert alert-success' role='alert'>
                    <strong>Success!</strong> All $successCount students registered successfully!
                </div>";
            } else {
                mysqli_commit($link); // Still commit successful ones
                echo "<div class='alert alert-warning' role='alert'>
                    <strong>Partial Success:</strong> $successCount students registered, $errorCount failed.
                </div>";
                
                // Display errors
                if (!empty($errors)) {
                    echo "<div class='alert alert-danger' role='alert'>";
                    echo "<strong>Errors:</strong><ul class='mb-0'>";
                    foreach ($errors as $error) {
                        echo "<li>$error</li>";
                    }
                    echo "</ul></div>";
                }
            }
            
            mysqli_autocommit($link, true);
        }
    } else {
        echo "<div class='alert alert-danger' role='alert'>Please select a file to upload!</div>";
    }
}

// Close the database connection
$link->close();
?>

<link rel="stylesheet" href="addUser.css">

<div class="card">
    <div class="card-header">
        Bulk Student Registration (CSV Upload)
    </div>
    <div class="card-body">
        <div class="alert alert-info">
            <strong>Instructions:</strong>
            <ul class="mb-0">
                <li>Download the CSV template below</li>
                <li>Fill in student data following the format: Username, Full Name, Phone, Address, Year, Level, Email</li>
                <li>Level of Study must be either "Undergraduate" or "Postgraduate"</li>
                <li>Upload the completed CSV file</li>
                <li>Default password "FK123" will be assigned to all students</li>
            </ul>
        </div>
        
        <!-- Download Template Button -->
        <a href="downloadTemplate.php" class="btn btn-primary mb-3">
            <i class="fas fa-download"></i> Download CSV Template
        </a>
        
        <!-- Upload Form -->
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group mb-3">
                <label for="csvFile">Select CSV File</label>
                <input type="file" required class="form-control" id="csvFile" name="csvFile" accept=".csv">
                <small class="form-text text-muted">Maximum file size: 2MB</small>
            </div>
            <button type="submit" name="bulk_upload" class="btn btn-success">
                Upload and Register Students
            </button>
        </form>
    </div>
</div>

<hr>

<?php
// Include footer and scripts
include('../../footer/footer.php');
?>