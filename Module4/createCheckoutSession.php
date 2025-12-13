<?php
session_start();

// Database connection using centralized config
require('../db_config.php');
// Use procedural style for consistency, or convert to OOP if needed
$conn = $link;

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['summon_id']) && isset($_POST['amount'])) {
    $summon_id = (int)$_POST['summon_id'];
    $amount = (float)$_POST['amount'];
    
    // Verify student
    if (!isset($_SESSION['STU_studentID'])) {
        die("Please login to make payment.");
    }
    
    // Get summon details
    $sql = "SELECT t.TF_summonID, v.V_plateNum, t.TF_violationType
            FROM trafficSummon t
            JOIN vehicle v ON t.V_vehicleID = v.V_vehicleID
            WHERE t.TF_summonID = ? AND v.STU_studentID = ? AND t.TF_status = 'Unpaid'";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $summon_id, $_SESSION['STU_studentID']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        die("Invalid summon.");
    }
    
    $summon = $result->fetch_assoc();
    $stmt->close();
    
    // Stripe Secret Key - Get from environment variable or config
    $stripe_secret_key = getenv('STRIPE_SECRET_KEY') ?: "YOUR_STRIPE_SECRET_KEY_HERE";
    
    // Amount in cents (Stripe uses cents)
    $amount_cents = (int)($amount * 100);
    
    // Create Stripe Checkout Session using cURL (since we don't have Stripe PHP library)
    $url = "https://api.stripe.com/v1/checkout/sessions";
    
    $data = [
        'payment_method_types[]' => 'card',
        'line_items[0][price_data][currency]' => 'myr',
        'line_items[0][price_data][product_data][name]' => 'Traffic Summon - ' . $summon['V_plateNum'],
        'line_items[0][price_data][product_data][description]' => 'Violation: ' . $summon['TF_violationType'],
        'line_items[0][price_data][unit_amount]' => $amount_cents,
        'line_items[0][quantity]' => 1,
        'mode' => 'payment',
        'success_url' => 'http://localhost/projectWeb/Mini-Project-Web-Eng/Module4/paymentSuccess.php?session_id={CHECKOUT_SESSION_ID}&summon_id=' . $summon_id . '&amount=' . $amount,
        'cancel_url' => 'http://localhost/projectWeb/Mini-Project-Web-Eng/Module4/paymentCancel.php?summon_id=' . $summon_id,
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
    header("Location: MySummon.php");
    exit();
}
?>
