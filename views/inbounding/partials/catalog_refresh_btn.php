<?php
/** @var string $btnId */
/** @var string $title */
/** @var string $srLabel */
/** @var string $iconType vendor|author|publisher|palette|refresh */
$btnId = $btnId ?? '';
$title = $title ?? 'Refresh from catalog';
$srLabel = $srLabel ?? 'Refresh';
$iconType = $iconType ?? 'refresh';
?>
<button type="button"
        id="<?php echo htmlspecialchars($btnId, ENT_QUOTES, 'UTF-8'); ?>"
        class="inline-flex items-center justify-center w-6 h-6 rounded border border-[#ccc] bg-white text-[#555] hover:border-[#d97824] hover:text-[#d97824] transition-colors"
        title="<?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?>">
    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <?php if ($iconType === 'vendor'): ?>
            <path d="M3 9h18v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V9z"></path>
            <path d="M3 9l2.45-4.9A2 2 0 0 1 7.24 3h9.52a2 2 0 0 1 1.8 1.1L21 9"></path>
            <path d="M12 3v6"></path>
        <?php elseif ($iconType === 'author'): ?>
            <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path>
            <circle cx="12" cy="7" r="4"></circle>
        <?php elseif ($iconType === 'publisher'): ?>
            <path d="M12 7v14"></path>
            <path d="M3 18a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h5a4 4 0 0 1 4 4 4 4 0 0 1 4-4h5a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1h-6a3 3 0 0 0-3 3 3 3 0 0 0-3-3z"></path>
        <?php elseif ($iconType === 'palette'): ?>
            <circle cx="13.5" cy="6.5" r=".5" fill="currentColor"></circle>
            <circle cx="17.5" cy="10.5" r=".5" fill="currentColor"></circle>
            <circle cx="8.5" cy="7.5" r=".5" fill="currentColor"></circle>
            <circle cx="6.5" cy="12.5" r=".5" fill="currentColor"></circle>
            <path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10c.926 0 1.648-.746 1.648-1.688 0-.437-.18-.835-.437-1.125-.29-.289-.438-.652-.438-1.125a1.64 1.64 0 0 1 1.668-1.668h1.996c3.051 0 5.555-2.503 5.555-5.554C21.965 6.012 17.461 2 12 2z"></path>
        <?php else: ?>
            <path d="M21 12a9 9 0 1 1-2.64-6.36"></path>
            <path d="M21 3v6h-6"></path>
            <path d="M3 12a9 9 0 0 1 14.36-6.36"></path>
            <path d="M3 21v-6h6"></path>
        <?php endif; ?>
    </svg>
    <span class="sr-only"><?php echo htmlspecialchars($srLabel, ENT_QUOTES, 'UTF-8'); ?></span>
</button>
