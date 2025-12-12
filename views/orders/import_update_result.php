<?php
//count
$imported = isset($data['imported']) ? $data['imported'] : 0;
$total = isset($data['total']) ? $data['total'] : 0;
echo "<div class='mrg-3'>";
if ($imported > 0) {
    $message = sprintf('%d orders updated successfully out of %d total orders.', $imported, $total);
    echo flash_message($message, 'success');
} else {
    $message = 'No orders were updated. Please check the errors below.';
    echo flash_message($message, 'error');
}
echo "<h1>Import Update Summary</h1>";
//total affected rows, order number, item code
foreach ($data['result'] as $result) {

    if (isset($result['success']) && $result['success']) {
        echo "<h2>Import Update Result</h2>";
        echo "<p>{$result['message']}</p>";
        echo "<p>Affected Rows: {$result['affected_rows']}</p>";
        echo "<p>Order Number: {$result['order_number']}</p>";
        echo "<p>Item Code: {$result['item_code']}</p>";
    } else {
        echo "<h2>Import Update Failed</h2>";
		echo isset($result['message'])?  "<p>{$result['message']}</p>" : '';
    }
}
?>