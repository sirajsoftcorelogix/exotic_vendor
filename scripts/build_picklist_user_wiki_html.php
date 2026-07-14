<?php
/**
 * Regenerate docs/picklist/user-wiki.html from views/picklist/partials/wiki_body.php
 * Run: php scripts/build_picklist_user_wiki_html.php
 */
$root = dirname(__DIR__);
$partial = $root . '/views/picklist/partials/wiki_body.php';
$out = $root . '/docs/picklist/user-wiki.html';

$body = file_get_contents($partial);
if ($body === false) {
    fwrite(STDERR, "Cannot read partial.\n");
    exit(1);
}
$body = preg_replace('/^<\?php.*?\?>\s*/s', '', $body);

$html = <<<'HTML'
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Picklist Module — User Guide | Exotic India Vendor Portal</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
body{font-family:Inter,system-ui,sans-serif;}
.prose h2{margin-top:0;}
.prose ul{margin:0.75em 0;padding-left:1.25em;}
.prose ol{margin:0.75em 0;padding-left:1.25em;}
.prose p{margin:0.75em 0;line-height:1.6;}
.prose code{background:#f3f4f6;padding:0.1em 0.35em;border-radius:0.25rem;font-size:0.9em;}
.prose h3{margin-top:1.25em;}
details summary::-webkit-details-marker{display:none;}
@media print{.no-print{display:none!important;}body{background:#fff;}}
</style>
</head>
<body class="bg-gray-100 text-gray-800 antialiased">
<div class="no-print bg-white border-b border-gray-200 px-4 py-3 text-center text-sm text-gray-600">
  Exotic India Vendor Portal · Picklist user guide ·
  <button type="button" onclick="window.print()" class="ml-2 text-amber-700 font-semibold hover:underline">Print this page</button>
</div>
<main class="max-w-4xl mx-auto px-4 py-8 sm:py-10">
HTML;
$html .= $body;
$html .= <<<'HTML'
</main>
<footer class="no-print text-center text-xs text-gray-400 py-6">&copy; Exotic India Pvt. Ltd.</footer>
</body>
</html>
HTML;

if (!is_dir(dirname($out))) {
    mkdir(dirname($out), 0755, true);
}
file_put_contents($out, $html);
echo "Wrote {$out} (" . strlen($html) . " bytes)\n";
