<?php
/**
 * One-off: convert DIRECT_PURCHASE_USER_GUIDE.md → HTML for Word SaveAs.
 * Run: php docs/convert_direct_purchase_guide_to_docx.php
 */

$mdPath = __DIR__ . '/DIRECT_PURCHASE_USER_GUIDE.md';
$htmlPath = __DIR__ . '/DIRECT_PURCHASE_USER_GUIDE.html';

if (!is_readable($mdPath)) {
    fwrite(STDERR, "Markdown file not found: $mdPath\n");
    exit(1);
}

$md = file_get_contents($mdPath);
$html = markdownToHtml($md);

$fullHtml = '<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Direct Purchase &amp; Purchase Return — User Guide</title>
<style>
body { font-family: Calibri, Arial, sans-serif; font-size: 11pt; line-height: 1.35; margin: 1in; color: #222; }
h1 { font-size: 22pt; color: #1a365d; border-bottom: 2px solid #c9a227; padding-bottom: 6pt; }
h2 { font-size: 16pt; color: #1a365d; margin-top: 18pt; }
h3 { font-size: 13pt; color: #2d3748; margin-top: 14pt; }
p { margin: 6pt 0; }
ul, ol { margin: 6pt 0 6pt 18pt; }
li { margin: 3pt 0; }
table { border-collapse: collapse; width: 100%; margin: 10pt 0; font-size: 10pt; }
th, td { border: 1px solid #999; padding: 5pt 7pt; vertical-align: top; }
th { background: #f0f4f8; font-weight: bold; }
code { font-family: Consolas, monospace; font-size: 9.5pt; background: #f5f5f5; padding: 1pt 3pt; }
pre { font-family: Consolas, monospace; font-size: 9.5pt; background: #f5f5f5; padding: 8pt; border: 1px solid #ddd; white-space: pre-wrap; }
hr { border: none; border-top: 1px solid #ccc; margin: 16pt 0; }
.meta { color: #555; margin-bottom: 12pt; }
em { font-style: italic; }
strong { font-weight: bold; }
.diagram { background: #f8fafc; border: 1px solid #cbd5e0; padding: 10pt; font-family: Consolas, monospace; font-size: 9pt; white-space: pre-wrap; }
</style>
</head>
<body>
' . $html . '
</body>
</html>';

file_put_contents($htmlPath, $fullHtml);
echo $htmlPath;

function markdownToHtml(string $md): string
{
    $lines = preg_split('/\r\n|\r|\n/', $md);
    $out = [];
    $i = 0;
    $n = count($lines);

    while ($i < $n) {
        $line = $lines[$i];

        if (preg_match('/^```(\w*)\s*$/', $line, $m)) {
            $lang = $m[1];
            $i++;
            $block = [];
            while ($i < $n && !preg_match('/^```\s*$/', $lines[$i])) {
                $block[] = htmlspecialchars($lines[$i], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $i++;
            }
            $i++;
            $class = ($lang === 'mermaid') ? 'diagram' : '';
            $tag = ($lang === 'mermaid') ? 'div' : 'pre';
            $out[] = "<$tag class=\"$class\">" . implode("\n", $block) . "</$tag>";
            continue;
        }

        if (preg_match('/^\|(.+)\|\s*$/', $line) && $i + 1 < $n && preg_match('/^\|[\s\-:|]+\|\s*$/', $lines[$i + 1])) {
            $tableLines = [$line];
            $i++;
            $tableLines[] = $lines[$i];
            $i++;
            while ($i < $n && preg_match('/^\|(.+)\|\s*$/', $lines[$i])) {
                $tableLines[] = $lines[$i];
                $i++;
            }
            $out[] = tableToHtml($tableLines);
            continue;
        }

        if (preg_match('/^---+\s*$/', $line)) {
            $out[] = '<hr>';
            $i++;
            continue;
        }

        if (preg_match('/^# (.+)$/', $line, $m)) {
            $out[] = '<h1>' . inlineFormat($m[1]) . '</h1>';
            $i++;
            continue;
        }
        if (preg_match('/^## (.+)$/', $line, $m)) {
            $out[] = '<h2>' . inlineFormat($m[1]) . '</h2>';
            $i++;
            continue;
        }
        if (preg_match('/^### (.+)$/', $line, $m)) {
            $out[] = '<h3>' . inlineFormat($m[1]) . '</h3>';
            $i++;
            continue;
        }

        if (preg_match('/^[-*] (.+)$/', $line, $m)) {
            $items = [];
            while ($i < $n && preg_match('/^[-*] (.+)$/', $lines[$i], $lm)) {
                $items[] = '<li>' . inlineFormat($lm[1]) . '</li>';
                $i++;
            }
            $out[] = '<ul>' . implode('', $items) . '</ul>';
            continue;
        }

        if (preg_match('/^\d+\. (.+)$/', $line, $m)) {
            $items = [];
            while ($i < $n && preg_match('/^\d+\. (.+)$/', $lines[$i], $lm)) {
                $items[] = '<li>' . inlineFormat($lm[1]) . '</li>';
                $i++;
            }
            $out[] = '<ol>' . implode('', $items) . '</ol>';
            continue;
        }

        if (trim($line) === '') {
            $i++;
            continue;
        }

        $out[] = '<p>' . inlineFormat($line) . '</p>';
        $i++;
    }

    return implode("\n", $out);
}

function tableToHtml(array $tableLines): string
{
    $rows = [];
    foreach ($tableLines as $idx => $tl) {
        if ($idx === 1) {
            continue;
        }
        $cells = array_map('trim', explode('|', trim($tl, '|')));
        $rows[] = $cells;
    }
    if ($rows === []) {
        return '';
    }
    $html = '<table>';
    foreach ($rows as $rIdx => $cells) {
        $html .= '<tr>';
        foreach ($cells as $cell) {
            $tag = ($rIdx === 0) ? 'th' : 'td';
            $html .= "<$tag>" . inlineFormat($cell) . "</$tag>";
        }
        $html .= '</tr>';
    }
    $html .= '</table>';

    return $html;
}

function inlineFormat(string $text): string
{
    $text = htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $text = preg_replace('/`([^`]+)`/', '<code>$1</code>', $text);
    $text = preg_replace('/\*\*([^*]+)\*\*/', '<strong>$1</strong>', $text);
    $text = preg_replace('/\*([^*]+)\*/', '<em>$1</em>', $text);
    $text = str_replace('&gt;', '>', $text);

    return $text;
}
