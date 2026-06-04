<?php
/** @var string $btnId */
/** @var string $title */
/** @var string $srLabel */
$btnId = $btnId ?? '';
$title = $title ?? 'Refresh from catalog';
$srLabel = $srLabel ?? 'Refresh';
?>
<button type="button"
        id="<?php echo htmlspecialchars($btnId, ENT_QUOTES, 'UTF-8'); ?>"
        class="inline-flex items-center justify-center w-6 h-6 rounded border border-[#ccc] bg-white text-[#555] hover:border-[#d97824] hover:text-[#d97824] transition-colors"
        title="<?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?>">
    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M12 20h9"></path>
        <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path>
    </svg>
    <span class="sr-only"><?php echo htmlspecialchars($srLabel, ENT_QUOTES, 'UTF-8'); ?></span>
</button>
