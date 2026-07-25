<?php
/**
 * Phone dial-code options for POS address confirm.
 *
 * @var array<string, string> $country_list ISO => name
 * @var array<string, string> $pos_country_phone_codes ISO => dial digits
 * @var string $selected_phone_iso Default selected ISO (e.g. IN)
 */
declare(strict_types=1);

if (!isset($country_list) || !is_array($country_list)) {
    $country_list = ['IN' => 'India'];
}
$phoneCodes = isset($pos_country_phone_codes) && is_array($pos_country_phone_codes)
    ? $pos_country_phone_codes
    : ['IN' => '91'];
$selectedIso = strtoupper(substr(trim((string)($selected_phone_iso ?? 'IN')), 0, 2));
if ($selectedIso === '') {
    $selectedIso = 'IN';
}

foreach ($country_list as $iso => $name):
    $code = strtoupper(substr(trim((string)$iso), 0, 2));
    if ($code === '') {
        continue;
    }
    $dial = preg_replace('/\D+/', '', (string)($phoneCodes[$code] ?? ''));
    if ($dial === '') {
        continue;
    }
    $label = '+' . $dial;
    $title = htmlspecialchars((string)$name, ENT_QUOTES, 'UTF-8') . ' (+' . $dial . ')';
    $selected = ($code === $selectedIso) ? ' selected' : '';
    ?>
<option value="<?= htmlspecialchars($code, ENT_QUOTES, 'UTF-8') ?>" title="<?= $title ?>"<?= $selected ?>><?= $label ?></option>
<?php endforeach; ?>
