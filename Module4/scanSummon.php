<?php
// Enable error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Code Scanner</title>
    <!-- Using html5-qrcode library (latest version) -->
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <style>
        /* Scanner overlay styles */
        .scanner-overlay {
            position: relative;
            display: inline-block;
        }
        .scan-line {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 2px;
            background: linear-gradient(to right, transparent, #00ff00, transparent);
            animation: scan 2s linear infinite;
            z-index: 10;
        }
        @keyframes scan {
            0% { top: 0; }
            100% { top: 100%; }
        }
        .scan-status {
            margin-top: 10px;
            padding: 10px;
            background-color: #e7f3ff;
            border-radius: 5px;
            display: none;
        }
        .scan-status.active {
            display: block;
            color: #0066cc;
        }
    </style>
    <style>
        body {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
        }
        .scanner-container {
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0px 0px 10px 0px rgba(0,0,0,0.1);
            text-align: center;
        }
        #reader {
            width: 100%;
            max-width: 600px;
            margin: 20px auto;
            position: relative;
        }
        
        /* Scanner viewfinder overlay */
        #reader video {
            width: 100%;
            height: auto;
            border: 2px solid #333;
            border-radius: 10px;
        }
        
        /* Scanning indicator */
        .scanning-indicator {
            position: absolute;
            top: 10px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            z-index: 100;
        }
        
        #preview {
            width: 100%;
            max-width: 600px;
            display: none;
            border: 2px solid #ddd;
            border-radius: 5px;
        }
        .error-message {
            color: #dc3545;
            margin-top: 10px;
            padding: 10px;
            background-color: #f8d7da;
            border-radius: 5px;
            display: none;
        }
        .info-message {
            color: #0c5460;
            margin-top: 10px;
            padding: 10px;
            background-color: #d1ecf1;
            border-radius: 5px;
        }
        h2 {
            margin-bottom: 20px;
            color: #333;
        }
    </style>
</head>
<body>
    <div class="scanner-container">
        <h2>QR Code Scanner</h2>
        <div class="info-message">Please allow camera access when prompted. Point the camera at the QR code.</div>
        <div id="reader"></div>
        <div class="scan-status" id="scanStatus">Scanning for QR code...</div>
        <div class="error-message" id="errorMessage"></div>
    </div>
    <script>
        let html5QrcodeScanner = null;

        function showError(message) {
            const errorDiv = document.getElementById('errorMessage');
            errorDiv.textContent = message;
            errorDiv.style.display = 'block';
        }

        function onScanSuccess(decodedText, decodedResult) {
            console.log('QR Code scanned successfully!', decodedText);
            
            // Show success message
            const statusDiv = document.getElementById('scanStatus');
            statusDiv.textContent = 'QR Code detected! Processing...';
            statusDiv.style.backgroundColor = '#d4edda';
            statusDiv.style.color = '#155724';
            statusDiv.classList.add('active');
            
            // Stop scanning
            if (html5QrcodeScanner) {
                html5QrcodeScanner.stop().then(() => {
                    html5QrcodeScanner.clear();
                }).catch(err => {
                    console.error('Error stopping scanner:', err);
                });
            }
            
            // Small delay before submitting to show the success message
            setTimeout(() => {
                // Create a form dynamically to post the scanned content
                let form = document.createElement('form');
                form.method = 'POST';
                form.action = 'process_qrcode.php';

                let input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'data';
                input.value = decodedText;

                form.appendChild(input);
                document.body.appendChild(form);
                form.submit();
            }, 500);
        }

        function onScanFailure(error) {
            // Log scan failures for debugging (happens frequently during scanning)
            // Only log if it's a meaningful error
            if (error && !error.includes('NotFoundException')) {
                console.log('Scan attempt:', error);
            }
        }

        function startScanner() {
            // Check if Html5Qrcode is available
            if (typeof Html5Qrcode === 'undefined') {
                showError('Error: QR Code library failed to load. Please check your internet connection or try refreshing the page.');
                return;
            }

            html5QrcodeScanner = new Html5Qrcode("reader");
            
            // Show scanning status
            const statusDiv = document.getElementById('scanStatus');
            statusDiv.classList.add('active');
            
            // Start scanner with improved configuration
            html5QrcodeScanner.start(
                { facingMode: "environment" }, // Use back camera (or "user" for front)
                {
                    fps: 30, // Higher FPS for better detection
                    qrbox: function(viewfinderWidth, viewfinderHeight) {
                        // Make scanning box larger (80% of viewfinder)
                        let minEdgePercentage = 0.8;
                        let minEdgeSize = Math.min(viewfinderWidth, viewfinderHeight);
                        let qrboxSize = Math.floor(minEdgeSize * minEdgePercentage);
                        return {
                            width: qrboxSize,
                            height: qrboxSize
                        };
                    },
                    aspectRatio: 1.0, // Square aspect ratio
                    disableFlip: false, // Allow flipping for better detection
                    videoConstraints: {
                        facingMode: "environment",
                        width: { ideal: 1280 },
                        height: { ideal: 720 }
                    }
                },
                onScanSuccess,
                onScanFailure
            ).then(() => {
                console.log('Scanner started successfully');
                statusDiv.textContent = 'Scanning for QR code...';
            }).catch(function (err) {
                console.error('Camera error:', err);
                statusDiv.classList.remove('active');
                showError('Error accessing camera: ' + err + '. Please ensure camera permissions are granted.');
            });
        }

        // Call startScanner() when the page loads
        document.addEventListener('DOMContentLoaded', startScanner);

        // Clean up scanner when page is unloaded
        window.addEventListener('beforeunload', function() {
            if (html5QrcodeScanner) {
                html5QrcodeScanner.clear().catch(err => {
                    console.error('Error stopping scanner:', err);
                });
            }
        });
    </script>
</body>
</html>
