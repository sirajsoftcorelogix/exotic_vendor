<?php
/** @var string $btnId */
/** @var string $title */
/** @var string $srLabel */
/** @var string $iconType vendor|author|publisher|palette|refresh */
/** @var string $size ''|'lg' */
$btnId = $btnId ?? '';
$title = $title ?? 'Refresh from catalog';
$srLabel = $srLabel ?? 'Refresh';
$iconType = $iconType ?? 'refresh';
$size = $size ?? '';
$iconPx = ($size === 'lg') ? 16 : 14;

if (empty($GLOBALS['catalog_refresh_btn_styles'])) {
    $GLOBALS['catalog_refresh_btn_styles'] = true;
    ?>
<style>
    .catalog-refresh-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        width: 1.875rem;
        height: 1.875rem;
        padding: 0;
        border-radius: 0.375rem;
        border: 2px solid #d97824;
        background: linear-gradient(180deg, #fff7ed 0%, #ffedd5 100%);
        color: #c66a1d;
        box-shadow: 0 1px 3px rgba(217, 120, 36, 0.25);
        cursor: pointer;
        transition: background 0.15s ease, color 0.15s ease, border-color 0.15s ease, box-shadow 0.15s ease, transform 0.1s ease;
    }
    .catalog-refresh-btn:hover:not(:disabled) {
        background: #d97824;
        color: #fff;
        border-color: #bf7326;
        box-shadow: 0 2px 8px rgba(217, 120, 36, 0.45);
        transform: translateY(-1px);
    }
    .catalog-refresh-btn:active:not(:disabled) {
        transform: translateY(0);
        box-shadow: 0 1px 3px rgba(217, 120, 36, 0.3);
    }
    .catalog-refresh-btn:focus-visible {
        outline: 2px solid #d97824;
        outline-offset: 2px;
    }
    .catalog-refresh-btn:disabled {
        opacity: 0.55;
        cursor: not-allowed;
        transform: none;
    }
    .catalog-refresh-btn--lg {
        width: 2.5rem;
        height: 2.5rem;
        border-radius: 0.75rem;
    }
</style>
    <?php
}
$sizeClass = ($size === 'lg') ? ' catalog-refresh-btn--lg' : '';
?>
<button type="button"
        id="<?php echo htmlspecialchars($btnId, ENT_QUOTES, 'UTF-8'); ?>"
        class="catalog-refresh-btn<?php echo $sizeClass; ?>"
        title="<?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?>">
    <svg xmlns="http://www.w3.org/2000/svg" width="<?php echo (int) $iconPx; ?>" height="<?php echo (int) $iconPx; ?>" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
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
