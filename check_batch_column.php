<?php
// Check and add batch_no column if it doesn't exist
$config = require 'config.php';

// Create database connection
$conn = new mysqli(
    $config['db']['host'],
    $config['db']['user'],
    $config['db']['pass'],
    $config['db']['name'],
    $config['db']['port']
);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if batch_no column exists in vp_invoices table
$result = $conn->query("SHOW COLUMNS FROM vp_invoices LIKE 'batch_no'");

if ($result && $result->num_rows === 0) {
    // Column doesn't exist, add it
    $alter_sql = "ALTER TABLE vp_invoices ADD COLUMN batch_no VARCHAR(50) NULL AFTER total_amount";
    if ($conn->query($alter_sql)) {
        echo "✓ batch_no column added to vp_invoices table successfully\n";
    } else {
        echo "✗ Error adding batch_no column: " . $conn->error . "\n";
    }
} else {
    echo "✓ batch_no column already exists in vp_invoices table\n";
}

// Check vp_dispatch_details table for batch_no
$result2 = $conn->query("SHOW COLUMNS FROM vp_dispatch_details LIKE 'batch_no'");

if ($result2 && $result2->num_rows === 0) {
    // Column doesn't exist, add it
    $alter_sql2 = "ALTER TABLE vp_dispatch_details ADD COLUMN batch_no VARCHAR(50) NULL AFTER shipping_charges";
    if ($conn->query($alter_sql2)) {
        echo "✓ batch_no column added to vp_dispatch_details table successfully\n";
    } else {
        echo "✗ Error adding batch_no column to vp_dispatch_details: " . $conn->error . "\n";
    }
} else {
    echo "✓ batch_no column already exists in vp_dispatch_details table\n";
}

$conn->close();
?>
