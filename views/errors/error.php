<div class="flex items-center m-4 p-4 mb-4 text-red-800 rounded-lg bg-red-100" role="alert">
    <i class="fa-solid fa-circle-exclamation text-xl"></i>
    <span class="sr-only">Error</span>
    <span class="ml-2 text-sm">
    <?php
    $msg = $message ?? ($data['message'] ?? null);
    if ($msg !== null) {
        if (is_array($msg)) {
            $type = isset($msg['type']) ? $msg['type'] : 'success';
            $text = isset($msg['text']) ? $msg['text'] : 'Operation completed successfully.';
            echo flash_message($text, $type);
        } else {
            echo flash_message((string) $msg, 'error');
        }
    }
    ?>
    </span>
</div>