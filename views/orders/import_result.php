<?php
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
}