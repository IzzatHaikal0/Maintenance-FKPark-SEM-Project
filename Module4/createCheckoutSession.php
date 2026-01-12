<?php
session_start();

// Database connection using centralized config
require('../db_config.php');
// Use procedural style for consistency, or convert to OOP if needed
$conn = $link;

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['summon_id']) && isset($_POST['amount'])) {
    $summon_id = (int)$_POST['summon_id'];
    $amount = (float)$_POST['amount'];
    $is_unregistered = isset($_POST['is_unregistered']) ? (int)$_POST['is_unregistered'] : 0;
    
    // Verify student
    if (!isset($_SESSION['STU_studentID'])) {
        die("Please login to make payment.");
    }
    
    $student_id = $_SESSION['STU_studentID'];
    
    // Get summon details - allow payment for both registered and unregistered vehicles
    $sql = "SELECT t.TF_summonID, v.V_plateNum, t.TF_violationType, v.V_status, v.V_vehicleID, v.STU_studentID
            FROM trafficSummon t
            JOIN vehicle v ON t.V_vehicleID = v.V_vehicleID
            WHERE t.TF_summonID = ? 
            AND t.TF_status = 'Unpaid'";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $summon_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Check if summon exists
    if ($result->num_rows == 0) {
        $stmt->close();
        $conn->close();
        die("Invalid summon or summon already paid. Summon ID: " . $summon_id);
    }
    
    // Fetch summon data (ONLY ONCE!)
    $summon = $result->fetch_assoc();
    $stmt->close(); // Close statement immediately after fetching
    
    // Verify data was fetched
    if ($summon === null || !is_array($summon)) {
        $conn->close();
        die("Error: Unable to fetch summon details.");
    }
    
    // Verify ownership: either the vehicle belongs to this student OR it's unregistered (NULL)
    if ($summon['STU_studentID'] !== null && $summon['STU_studentID'] != $student_id) {
        $conn->close();
        die("You are not authorized to pay this summon.");
    }
    
    // Stripe Secret Key - Get from environment variable or config
   // Stripe Secret Key - load from environment
    $stripe_secret_key = getenv('STRIPE_SECRET_KEY');

    if (!$stripe_secret_key) {
        die("Stripe secret key not configured.");
    }


    
    // Amount in cents (Stripe uses cents)
    $amount_cents = (int)($amount * 100);
    
    // Determine if this is an unregistered vehicle
    $is_unregistered_vehicle = ($summon['V_status'] == 'Unregistered') ? 1 : 0;
    
    // Create Stripe Checkout Session using cURL (since we don't have Stripe PHP library)
    $url = "https://api.stripe.com/v1/checkout/sessions";
    
    // Build success URL with all necessary parameters
    $success_url = 'http://localhost/Mini-Project-Web-Eng/Module4/paymentSuccess.php?' . http_build_query([
        'session_id' => '{CHECKOUT_SESSION_ID}',
        'summon_id' => $summon_id,
        'amount' => $amount,
        'vehicle_id' => $summon['V_vehicleID'],
        'is_unregistered' => $is_unregistered_vehicle
    ]);
    
    $cancel_url = 'http://localhost/Mini-Project-Web-Eng/Module4/paymentCancel.php?' . http_build_query([
        'summon_id' => $summon_id,
        'is_unregistered' => $is_unregistered_vehicle
    ]);
    
    // Add vehicle status indicator to product name
    $product_name = 'Traffic Summon - ' . $summon['V_plateNum'];
    if ($is_unregistered_vehicle) {
        $product_name .= ' (Unregistered Vehicle)';
    }
    
    $data = [
        'payment_method_types[]' => 'card',
        'line_items[0][price_data][currency]' => 'myr',
        'line_items[0][price_data][product_data][name]' => $product_name,
        'line_items[0][price_data][product_data][description]' => 'Violation: ' . $summon['TF_violationType'],
        'line_items[0][price_data][unit_amount]' => $amount_cents,
        'line_items[0][quantity]' => 1,
        'mode' => 'payment',
        'success_url' => $success_url,
        'cancel_url' => $cancel_url,
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $stripe_secret_key,
        'Content-Type: application/x-www-form-urlencoded',
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code == 200) {
        $session = json_decode($response, true);
        if (isset($session['url'])) {
            // Redirect to Stripe Checkout
            header("Location: " . $session['url']);
            exit();
        } else {
            die("Error creating payment session. Please check your Stripe API key.");
        }
    } else {
        // If API call fails, show error
        $error = json_decode($response, true);
        die("Error: " . (isset($error['error']['message']) ? $error['error']['message'] : 'Failed to create payment session. Please check your Stripe API key.'));
    }
    
    $conn->close();
} else {
    header("Location: ../Module1/Student/mySummon.php");
    exit();
}
?>