<div class="mrg-3">
<?php
if (isset($data['message'])) {
    $message = $data['message'];    
    $type = isset($message['type']) ? $message['type'] : 'success';
    $text = isset($message['text']) ? $message['text'] : 'Operation completed successfully.';
    echo flash_message($text, $type);
}
?>
</div>