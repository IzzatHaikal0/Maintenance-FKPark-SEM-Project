<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Cancelled</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }
        .cancel-container {
            background-color: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0px 0px 10px 0px rgba(0,0,0,0.1);
            max-width: 500px;
            width: 100%;
            text-align: center;
        }
        .cancel-icon {
            font-size: 64px;
            color: #dc3545;
            margin-bottom: 20px;
        }
        h2 {
            color: #dc3545;
            margin-bottom: 20px;
        }
        .btn-back {
            background-color: #800000;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin-top: 20px;
        }
        .btn-back:hover {
            background-color: #575757;
        }
    </style>
</head>
<body>
    <?php include('../Layout/student_layout.php'); ?>
    <div class="cancel-container">
        <div class="cancel-icon">✗</div>
        <h2>Payment Cancelled</h2>
        <p>Your payment was cancelled. No charges were made.</p>
        <a href="MySummon.php" class="btn-back">Back to My Summon</a>
    </div>
</body>
</html>
