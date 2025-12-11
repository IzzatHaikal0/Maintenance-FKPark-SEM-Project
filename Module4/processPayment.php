<?php
session_start();

// Database connection using centralized config
require('../db_config.php');
// Use procedural style for consistency, or convert to OOP if needed
$conn = $link;

// Get summon details
if (!isset($_GET['summon_id']) || !isset($_GET['amount'])) {
    die("Invalid payment request.");
}

$summon_id = (int)$_GET['summon_id'];
$amount = (float)$_GET['amount'];

// Verify summon belongs to student
if (!isset($_SESSION['STU_studentID'])) {
    die("Please login to make payment.");
}

$student_id = $_SESSION['STU_studentID'];

// Get summon details and verify ownership
$sql = "SELECT t.TF_summonID, t.TF_status, t.TF_violationType, t.TF_demeritPoint, t.TF_date, v.V_plateNum, v.STU_studentID
        FROM trafficSummon t
        JOIN vehicle v ON t.V_vehicleID = v.V_vehicleID
        WHERE t.TF_summonID = ? AND v.STU_studentID = ? AND t.TF_status = 'Unpaid'";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $summon_id, $student_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die("Summon not found or already paid.");
}

$summon = $result->fetch_assoc();
$stmt->close();

// Stripe API Configuration - Get from environment variables or config
$stripe_secret_key = getenv('STRIPE_SECRET_KEY') ?: "YOUR_STRIPE_SECRET_KEY_HERE";
$stripe_publishable_key = getenv('STRIPE_PUBLISHABLE_KEY') ?: "YOUR_STRIPE_PUBLISHABLE_KEY_HERE";

// For testing, you can use Stripe test keys
// Test card: 4242 4242 4242 4242, any future date, any CVC

// Include Stripe PHP library (you'll need to install it via Composer)
// For now, we'll use Stripe Checkout which doesn't require the PHP library

$amount_in_cents = (int)($amount * 100); // Stripe uses cents

// Create Stripe Checkout Session
$checkout_session_url = "https://checkout.stripe.com/pay/cs_test_";

// For production, you would use Stripe API to create a checkout session
// For now, we'll create a simple payment page that redirects to Stripe

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment - Summon</title>
    <script src="https://js.stripe.com/v3/"></script>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .payment-container {
            background-color: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            max-width: 600px;
            width: 100%;
            margin: 50px auto;
            margin-left: 280px;
            box-sizing: border-box;
        }
        .payment-container form,
        .payment-container a {
            display: block;
            width: 100%;
            box-sizing: border-box;
        }
        h2 {
            color: #2c3e50;
            margin-bottom: 30px;
            font-size: 28px;
            font-weight: 600;
            text-align: left;
        }
        .payment-details {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 25px;
            border-radius: 8px;
            margin-bottom: 25px;
            border: 1px solid #dee2e6;
        }
        .payment-details p {
            margin: 12px 0;
            font-size: 15px;
            color: #495057;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .payment-details strong {
            color: #2c3e50;
            font-weight: 600;
            min-width: 140px;
        }
        .payment-details span {
            color: #6c757d;
            font-weight: 500;
        }
        .amount-section {
            text-align: center;
            padding: 25px;
            background-color: #f8f9fa;
            border-radius: 8px;
            margin: 25px 0;
            border: 2px solid #000000;
        }
        .amount-label {
            font-size: 14px;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 8px;
            font-weight: 600;
        }
        .amount {
            font-size: 36px;
            font-weight: 700;
            color: #000000;
            margin: 0;
        }
        .btn-pay {
            background-color: #000000;
            color: white;
            padding: 16px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            margin-top: 20px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px rgba(0,0,0,0.2);
        }
        .btn-pay:hover {
            background-color: #333333;
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.3);
        }
        .btn-pay:active {
            transform: translateY(0);
        }
        .btn-cancel {
            background-color: #6c757d;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            width: 100%;
            margin-top: 15px;
            text-decoration: none;
            display: block;
            text-align: center;
            font-size: 15px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-sizing: border-box;
        }
        .btn-cancel:hover {
            background-color: #5a6268;
            transform: translateY(-1px);
        }
        .note {
            color: #856404;
            background-color: #fff3cd;
            padding: 15px;
            border-radius: 8px;
            margin-top: 25px;
            font-size: 13px;
            line-height: 1.6;
            border-left: 4px solid #ffc107;
        }
        .note strong {
            color: #856404;
            display: block;
            margin-bottom: 8px;
        }
    </style>
</head>
<body>
    <?php include('../Layout/student_layout.php'); ?>
    <div class="payment-container">
        <h2>Pay Summon</h2>
        <div class="payment-details">
            <p><strong>Plate Number:</strong> <span><?php echo htmlspecialchars($summon['V_plateNum']); ?></span></p>
            <p><strong>Date:</strong> <span><?php echo htmlspecialchars($summon['TF_date']); ?></span></p>
            <p><strong>Violation Type:</strong> <span><?php echo htmlspecialchars($summon['TF_violationType']); ?></span></p>
            <p><strong>Demerit Points:</strong> <span><?php echo htmlspecialchars($summon['TF_demeritPoint']); ?></span></p>
        </div>
        <div class="amount-section">
            <div class="amount-label">Total Amount</div>
            <div class="amount">RM <?php echo number_format($amount, 2); ?></div>
        </div>
        
        <form action="Module4/createCheckoutSession.php" method="POST">
            <input type="hidden" name="summon_id" value="<?php echo $summon_id; ?>">
            <input type="hidden" name="amount" value="<?php echo $amount; ?>">
            <button type="submit" class="btn-pay" id="checkout-button">Pay with Stripe</button>
        </form>
        
        <a href="Module4/MySummon.php" class="btn-cancel">Cancel</a>
    </div>
</body>
</html>
