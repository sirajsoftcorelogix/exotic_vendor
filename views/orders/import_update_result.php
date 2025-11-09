<?php
foreach ($data['result'] as $result) {

    if (isset($result['success']) && $result['success']) {
        echo "<h2>Import Update Result</h2>";
        echo "<p>{$result['message']}</p>";
        echo "<p>Affected Rows: {$result['affected_rows']}</p>";
        echo "<p>Order Number: {$result['order_number']}</p>";
        echo "<p>Item Code: {$result['item_code']}</p>";
    } else {
        echo "<h2>Import Update Failed</h2>";
        echo "<p>{$result['message']}</p>";
    }
}
?>