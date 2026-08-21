<?php
/** @var array<string, mixed> $session */
/** @var array<string, array<string, mixed>> $forms */
/** @var array<string, string> $requiredDocs */
/** @var string $docFilter */

$commonData = $session['common_data'] ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Export Documents Preview - <?= htmlspecialchars($session['session_code']) ?></title>
    <!-- Tailwind CSS & FontAwesome -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @media print {
            body {
                background: white !important;
                color: black !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            .no-print {
                display: none !important;
            }
            .export-doc-page {
                box-shadow: none !important;
                border: none !important;
                padding: 0 !important;
                margin: 0 !important;
                page-break-after: always;
                page-break-inside: avoid;
            }
            .export-doc-page:last-child {
                page-break-after: auto;
            }
        }
        .export-doc-page {
            width: 210mm;
            min-height: 297mm;
            padding: 15mm;
            margin: 0 auto 20px auto;
            background: white;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            border: 1px solid #e5e7eb;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            box-sizing: border-box;
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen text-gray-900">

    <!-- Print Control Toolbar (hidden during printing) -->
    <div class="no-print bg-slate-900 text-white sticky top-0 z-50 shadow-md">
        <div class="max-w-7xl mx-auto px-4 py-3 flex flex-col md:flex-row items-center justify-between gap-3">
            <div class="flex items-center space-x-3">
                <a href="index.php?page=export_documents&action=generate&session_code=<?= urlencode($session['session_code']) ?>"
                   class="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 text-white rounded text-xs font-semibold flex items-center gap-1.5 transition-colors">
                    <i class="fas fa-arrow-left"></i> Back to Wizard
                </a>
                <span class="text-xs text-slate-300 font-mono">
                    Session: <strong class="text-white"><?= htmlspecialchars($session['session_code']) ?></strong>
                </span>
                <span class="text-xs text-slate-300">
                    Invoice: <strong class="text-white"><?= htmlspecialchars($session['invoice_number'] ?: 'N/A') ?></strong>
                </span>
            </div>

            <!-- Filter tabs -->
            <div class="flex items-center space-x-1 overflow-x-auto text-xs">
                <a href="index.php?page=export_documents&action=preview&session_code=<?= urlencode($session['session_code']) ?>&doc=all"
                   class="px-2.5 py-1 rounded font-medium transition-colors <?= $docFilter === 'all' ? 'bg-blue-600 text-white' : 'bg-slate-800 text-slate-300 hover:bg-slate-700' ?>">
                    All Documents
                </a>
                <?php foreach ($requiredDocs as $code => $title): ?>
                    <a href="index.php?page=export_documents&action=preview&session_code=<?= urlencode($session['session_code']) ?>&doc=<?= urlencode($code) ?>"
                       class="px-2.5 py-1 rounded font-medium whitespace-nowrap transition-colors <?= $docFilter === $code ? 'bg-blue-600 text-white' : 'bg-slate-800 text-slate-300 hover:bg-slate-700' ?>">
                        <?= htmlspecialchars($title) ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <!-- Print Actions -->
            <div class="flex items-center space-x-2">
                <button onclick="window.print()" class="px-4 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded text-xs font-bold shadow flex items-center gap-1.5 transition-colors">
                    <i class="fas fa-print"></i> Print Documents
                </button>
            </div>
        </div>
    </div>

    <!-- Document Pages Container -->
    <div class="py-8 px-4">
        <?php
            $docsToRender = ($docFilter === 'all')
                ? $requiredDocs
                : array_filter($requiredDocs, fn($k) => $k === $docFilter, ARRAY_FILTER_USE_KEY);
        ?>

        <?php if (empty($docsToRender)): ?>
            <div class="max-w-md mx-auto bg-white p-8 rounded-xl text-center shadow-sm border border-gray-200">
                <i class="fas fa-file-excel text-4xl text-gray-400 mb-3"></i>
                <p class="text-sm font-semibold text-gray-700">No documents match the selected filter.</p>
            </div>
        <?php else: ?>
            <?php foreach ($docsToRender as $docCode => $docTitle): ?>
                <?php
                    $form = $forms[$docCode] ?? [];
                    $templatePath = __DIR__ . '/templates/' . $docCode . '.php';
                ?>
                <?php if (file_exists($templatePath)): ?>
                    <?php include $templatePath; ?>
                <?php else: ?>
                    <div class="export-doc-page">
                        <h2 class="text-lg font-bold border-b border-black pb-2 mb-4"><?= htmlspecialchars($docTitle) ?></h2>
                        <p class="text-xs text-gray-600">Template [<?= htmlspecialchars($docCode) ?>.php] pending custom layout upload.</p>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

</body>
</html>
