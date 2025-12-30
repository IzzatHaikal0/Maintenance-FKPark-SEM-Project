<?php
// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=student_registration_template.csv');

// Create file pointer
$output = fopen('php://output', 'w');

// Add header row
fputcsv($output, array('Username', 'Full Name', 'Phone Number', 'Address', 'Year of Study', 'Level of Study', 'Email'));

// Add sample data rows
fputcsv($output, array('STD001', 'John Doe', '0123456789', '123 Main Street, KL', '1', 'Undergraduate', 'john.doe@example.com'));
fputcsv($output, array('STD002', 'Jane Smith', '0198765432', '456 Park Avenue, KL', '2', 'Postgraduate', 'jane.smith@example.com'));
fputcsv($output, array('STD003', 'Ahmad Ali', '0111234567', '789 Lake View, KL', '1', 'Undergraduate', 'ahmad.ali@example.com'));

fclose($output);
exit();
?>