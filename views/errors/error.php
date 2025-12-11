<div class="flex items-center m-4 p-4 mb-4 text-red-800 rounded-lg bg-red-100" role="alert">
    <i class="fa-solid fa-circle-exclamation text-xl"></i>
    <span class="sr-only">Error</span>
    <span class="ml-2 text-sm">
    <?php
    if (isset($data['message'])) {
        $message = $data['message'];    
        $type = isset($message['type']) ? $message['type'] : 'success';
        $text = isset($message['text']) ? $message['text'] : 'Operation completed successfully.';
        echo flash_message($text, $type);
    }
    ?>
    </span>
</div>