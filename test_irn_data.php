<?php
$conn = new mysqli('127.0.0.1', 'root', '');
$res = $conn->query("SHOW DATABASES");
while ($row = $res->fetch_row()) {
    $db = $row[0];
    $c2 = new mysqli('127.0.0.1', 'root', '', $db);
    $r2 = $c2->query("SHOW TABLES LIKE 'vp_invoices'");
    if ($r2 && $r2->num_rows > 0) {
        echo "Found vp_invoices in DB: $db\n";
    }
}
