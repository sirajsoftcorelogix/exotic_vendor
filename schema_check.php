<?php
require_once 'models/comman/tables.php';
require_once 'config/database.php';
$conn = Database::getConnection();
$tables = ['vp_invoices', 'vp_customers', 'vp_invoice_items'];
foreach ($tables as $t) {
    echo "--- $t ---\n";
    $res = $conn->query("DESCRIBE $t");
    if ($res) {
        while($r = $res->fetch_assoc()) echo $r['Field'].', ';
        echo "\n";
    } else {
        echo "Failed\n";
    }
}
