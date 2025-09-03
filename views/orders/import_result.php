<?php
//print_array($data);
if(isset($data['imported']) && isset($data['total'])) {
    $imported = $data['imported'];
    $total = $data['total'];
?>
<div class="mrg-3">
    <?php   
    if ($imported > 0) {
        $message = sprintf('%d orders imported successfully out of %d total orders.', $imported, $total);
        echo flash_message($message, 'success');
    }
    else {
        $message = 'No orders were imported. Please check the API response or your import criteria.';
        echo flash_message($message, 'error');
    }
    ?>
</div>
<?php
}?>
<div class="mrg-3">
    <h3>Import Result</h3>
    
<?php
if(isset($data['result']) && is_array($data['result'])) {
    foreach ($data['result'] as $order) {
        if (is_array($order) && isset($order['success'])) {
            if ($order['success'] === false) {
                echo flash_message($order['message'], 'error');
            }
            else {  
                echo flash_message('Order imported successfully.', 'success');
            }
        } else {
            echo flash_message('Unexpected result format.', 'error');
        }
    }
    
}
?>
</div>