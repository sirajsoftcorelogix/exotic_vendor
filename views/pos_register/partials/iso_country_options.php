<?php
/**
 * ISO 3166-1 alpha-2 country options for POS address selects.
 *
 * @var array<string, string> $country_list code => name
 * @var string|null $selected_iso pre-selected 2-letter code (default IN)
 */
$selectedIso = isset($selected_iso) ? strtoupper(substr(trim((string)$selected_iso), 0, 2)) : 'IN';
if (!isset($country_list) || !is_array($country_list)) {
    $country_list = ['IN' => 'India'];
}
foreach ($country_list as $iso => $name):
    $iso = strtoupper(substr(trim((string)$iso), 0, 2));
    if ($iso === '') {
        continue;
    }
    $isSelected = ($selectedIso !== '' && $selectedIso === $iso);
?>
<option value="<?= htmlspecialchars($iso, ENT_QUOTES, 'UTF-8') ?>"<?= $isSelected ? ' selected' : '' ?>><?= htmlspecialchars((string)$name, ENT_QUOTES, 'UTF-8') ?></option>
<?php endforeach; ?>
