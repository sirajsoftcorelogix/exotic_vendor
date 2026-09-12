<?php
/**
 * Standalone UI for scripts/sync_order_statuses.php
 *
 * @var string $scriptUrl
 * @var string $jsUrl
 * @var int $prefillLimit
 * @var string $prefillMode all|partial|specific
 * @var bool $prefillDryRun
 * @var string $prefillOrders
 */
$scriptUrl = (string) ($scriptUrl ?? '');
$jsUrl = (string) ($jsUrl ?? '');
$prefillLimit = (int) ($prefillLimit ?? 250);
$prefillMode = in_array(($prefillMode ?? 'partial'), ['all', 'partial', 'specific'], true) ? $prefillMode : 'partial';
$prefillDryRun = !empty($prefillDryRun);
$prefillOrders = (string) ($prefillOrders ?? '');
$allowedLimits = [50, 100, 250, 500, 1000, 2500, 5000];
if (!in_array($prefillLimit, $allowedLimits, true)) {
    $allowedLimits[] = $prefillLimit;
    sort($allowedLimits);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Sync order statuses</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root {
            --bg: #f4f7f5;
            --card: #ffffff;
            --ink: #1f2937;
            --muted: #6b7280;
            --line: #e5e7eb;
            --emerald: #059669;
            --emerald-dark: #047857;
            --red: #dc2626;
            --amber: #d97706;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, sans-serif;
            background: var(--bg);
            color: var(--ink);
        }
        .oss-wrap { max-width: 920px; margin: 32px auto; padding: 0 16px 48px; }
        .oss-hero {
            background: linear-gradient(135deg, #059669 0%, #0f766e 100%);
            color: #fff;
            border-radius: 16px;
            padding: 24px 28px;
            box-shadow: 0 10px 30px rgba(5, 150, 105, 0.18);
        }
        .oss-hero h1 { margin: 0 0 8px; font-size: 1.45rem; }
        .oss-hero p { margin: 0; opacity: 0.92; font-size: 0.92rem; line-height: 1.45; }
        .oss-count {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 14px;
            background: rgba(255,255,255,0.14);
            border-radius: 999px;
            padding: 6px 12px;
            font-size: 0.82rem;
            font-weight: 700;
        }
        .oss-card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 16px;
            padding: 22px;
            margin-top: 18px;
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.04);
        }
        .oss-modes { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; }
        @media (max-width: 720px) { .oss-modes { grid-template-columns: 1fr; } }
        .oss-mode {
            border: 2px solid var(--line);
            border-radius: 12px;
            padding: 14px;
            cursor: pointer;
            display: block;
        }
        .oss-mode:has(input:checked) {
            border-color: var(--emerald);
            background: #ecfdf5;
        }
        .oss-mode strong { display: block; margin-bottom: 4px; }
        .oss-mode span { color: var(--muted); font-size: 0.8rem; line-height: 1.35; }
        .oss-mode input { margin-right: 6px; }
        .oss-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-top: 16px; }
        @media (max-width: 720px) { .oss-grid { grid-template-columns: 1fr; } }
        label.oss-label { display: block; font-size: 0.78rem; font-weight: 700; margin-bottom: 6px; }
        select, textarea, input[type="text"] {
            width: 100%;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            padding: 10px 12px;
            font-size: 0.9rem;
        }
        .oss-check { display: flex; align-items: center; gap: 8px; margin-top: 14px; font-size: 0.9rem; font-weight: 700; }
        .oss-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 18px; }
        .oss-btn {
            border: 0;
            border-radius: 10px;
            padding: 10px 16px;
            font-weight: 700;
            font-size: 0.88rem;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .oss-btn:disabled { opacity: 0.55; cursor: not-allowed; }
        .oss-btn-start { background: var(--emerald); color: #fff; }
        .oss-btn-start:hover:not(:disabled) { background: var(--emerald-dark); }
        .oss-btn-stop { background: var(--red); color: #fff; }
        .hidden { display: none !important; }
        .oss-progress-top { display: flex; justify-content: space-between; font-size: 0.82rem; font-weight: 700; margin-bottom: 8px; }
        .oss-track { height: 10px; background: #e5e7eb; border-radius: 999px; overflow: hidden; }
        .oss-bar { height: 100%; width: 0; background: var(--emerald); transition: width .25s ease; }
        .oss-log, .oss-details {
            max-height: 240px;
            overflow: auto;
            background: #0f172a;
            color: #e2e8f0;
            border-radius: 12px;
            padding: 12px;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 12px;
            line-height: 1.5;
        }
        .oss-details { background: #f8fafc; color: #111827; border: 1px solid var(--line); }
        .oss-log-time { color: #94a3b8; }
        .oss-log-ok { color: #86efac; }
        .oss-log-warn { color: #fcd34d; }
        .oss-log-error { color: #fca5a5; }
        .oss-badges { display: flex; flex-wrap: wrap; gap: 8px; margin: 12px 0; }
        .oss-badge { font-size: 0.75rem; font-weight: 800; border-radius: 999px; padding: 4px 10px; }
        .oss-badge-status { background: #ecfdf5; color: #047857; }
        .oss-badge-blue { background: #dbeafe; color: #1d4ed8; }
        .oss-badge-green { background: #dcfce7; color: #166534; }
        .oss-badge-gray { background: #f3f4f6; color: #374151; }
        .oss-badge-amber { background: #fef3c7; color: #92400e; }
        .oss-old { color: #dc2626; }
        .oss-new { color: #059669; font-weight: 800; }
        .oss-muted { color: var(--muted); font-style: italic; }
        h2.oss-h { margin: 0 0 10px; font-size: 1rem; }
    </style>
</head>
<body>
    <div class="oss-wrap">
        <div class="oss-hero">
            <h1><i class="fas fa-sync-alt"></i> Sync order statuses</h1>
            <p>
                Updates local orders that are not Cancelled, Returned, or Shipped.
                Vendor API is queried oldest-first (`order_date ASC`) using <code>only_status=1</code>.
                Choose All or a partial set, watch the progress bar, and stop at any time.
            </p>
            <div class="oss-count">Pending non-terminal orders: <span id="ossPendingCount">…</span></div>
        </div>

        <form id="ossForm" class="oss-card">
            <div class="oss-modes">
                <label class="oss-mode">
                    <input class="oss-field" type="radio" name="oss_mode" value="all" <?= $prefillMode === 'all' ? 'checked' : '' ?>>
                    <strong>All pending</strong>
                    <span>Queue every non-terminal order, oldest first.</span>
                </label>
                <label class="oss-mode">
                    <input class="oss-field" type="radio" name="oss_mode" value="partial" <?= $prefillMode === 'partial' ? 'checked' : '' ?>>
                    <strong>Partial</strong>
                    <span>Only the oldest N orders. Use this for a controlled run.</span>
                </label>
                <label class="oss-mode">
                    <input class="oss-field" type="radio" name="oss_mode" value="specific" <?= $prefillMode === 'specific' ? 'checked' : '' ?>>
                    <strong>Specific orders</strong>
                    <span>Sync only the order numbers you enter.</span>
                </label>
            </div>

            <div class="oss-grid">
                <div id="ossPartialFields" <?= $prefillMode === 'partial' ? '' : 'hidden' ?>>
                    <label class="oss-label" for="ossLimit">Partial size (oldest first)</label>
                    <select id="ossLimit" class="oss-field">
                        <?php foreach ($allowedLimits as $n): ?>
                            <option value="<?= (int) $n ?>" <?= (int) $n === $prefillLimit ? 'selected' : '' ?>><?= (int) $n ?> orders</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="oss-label" for="ossBatchSize">API batch size</label>
                    <select id="ossBatchSize" class="oss-field">
                        <option value="20">20 orders / request</option>
                        <option value="50" selected>50 orders / request</option>
                        <option value="100">100 orders / request</option>
                    </select>
                </div>
            </div>

            <div id="ossSpecificFields" <?= $prefillMode === 'specific' ? '' : 'hidden' ?> style="margin-top:14px;">
                <label class="oss-label" for="ossOrderIds">Order numbers</label>
                <textarea id="ossOrderIds" class="oss-field" rows="3" placeholder="3114463, 3114147"><?= htmlspecialchars($prefillOrders, ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>

            <label class="oss-check">
                <input id="ossDryRun" class="oss-field" type="checkbox" <?= $prefillDryRun ? 'checked' : '' ?>>
                Dry run — preview status changes without writing to the database
            </label>

            <div class="oss-actions">
                <button type="button" id="ossStopBtn" class="oss-btn oss-btn-stop" disabled>
                    <i class="fas fa-stop"></i> Stop
                </button>
                <button type="submit" id="ossStartBtn" class="oss-btn oss-btn-start">
                    <i class="fas fa-play"></i> Start sync
                </button>
            </div>
        </form>

        <div id="ossProgressWrap" class="oss-card hidden">
            <div class="oss-progress-top">
                <span id="ossProgressLabel">Waiting…</span>
                <span><span id="ossProgressCount">0 / 0</span> · <span id="ossProgressPercent">0%</span></span>
            </div>
            <div class="oss-track"><div id="ossProgressBar" class="oss-bar"></div></div>
            <div id="ossLog" class="oss-log" style="margin-top:14px;"></div>
        </div>

        <div id="ossResultWrap" class="oss-card hidden">
            <h2 class="oss-h">Results</h2>
            <div id="ossBadges" class="oss-badges"></div>
            <div id="ossDetails" class="oss-details"></div>
        </div>
    </div>
    <script src="<?= htmlspecialchars($jsUrl, ENT_QUOTES, 'UTF-8') ?>"></script>
    <script>
        createOrderStatusSyncController({
            endpoint: <?= json_encode($scriptUrl) ?>,
            formSelector: '.oss-field',
            modeSelector: 'input[name="oss_mode"]',
            ids: {
                form: 'ossForm',
                startBtn: 'ossStartBtn',
                stopBtn: 'ossStopBtn',
                limit: 'ossLimit',
                batchSize: 'ossBatchSize',
                orderIds: 'ossOrderIds',
                dryRun: 'ossDryRun',
                pendingCount: 'ossPendingCount',
                progressWrap: 'ossProgressWrap',
                progressBar: 'ossProgressBar',
                progressPercent: 'ossProgressPercent',
                progressLabel: 'ossProgressLabel',
                progressCount: 'ossProgressCount',
                log: 'ossLog',
                badges: 'ossBadges',
                details: 'ossDetails',
                resultWrap: 'ossResultWrap',
                partialFields: 'ossPartialFields',
                specificFields: 'ossSpecificFields'
            }
        }).bind();
    </script>
</body>
</html>
