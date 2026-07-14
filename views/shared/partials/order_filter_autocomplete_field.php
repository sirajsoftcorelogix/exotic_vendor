<?php
$fieldId = (string) ($field_id ?? '');
$fieldLabel = (string) ($field_label ?? '');
$fieldPlaceholder = (string) ($field_placeholder ?? 'Type at least 2 characters...');
$fieldValue = (string) ($field_value ?? '');
$fieldName = isset($field_name) ? (string) $field_name : '';
$suggestionsId = (string) ($suggestions_id ?? ($fieldId . '_suggestions'));
$searchUrl = (string) ($search_url ?? '');
$hiddenId = isset($hidden_id) ? (string) $hidden_id : '';
$hiddenName = isset($hidden_name) ? (string) $hidden_name : '';
$hiddenValue = (string) ($hidden_value ?? '');
$inputClass = (string) ($input_class ?? 'w-full px-2 py-2 text-xs border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-1 focus:ring-amber-500 focus:border-amber-500');
?>
<div class="relative">
    <label for="<?php echo htmlspecialchars($fieldId); ?>" class="block text-sm font-medium text-gray-600 mb-1">
        <?php echo htmlspecialchars($fieldLabel); ?>
    </label>
    <input
        type="text"
        id="<?php echo htmlspecialchars($fieldId); ?>"
        <?php if ($fieldName !== ''): ?>name="<?php echo htmlspecialchars($fieldName); ?>"<?php endif; ?>
        class="<?php echo htmlspecialchars($inputClass); ?>"
        placeholder="<?php echo htmlspecialchars($fieldPlaceholder); ?>"
        autocomplete="off"
        value="<?php echo htmlspecialchars($fieldValue); ?>"
        data-order-filter-autocomplete="1"
        data-search-url="<?php echo htmlspecialchars($searchUrl); ?>"
        data-suggestions-target="<?php echo htmlspecialchars($suggestionsId); ?>"
        <?php if ($hiddenId !== ''): ?>data-hidden-target="<?php echo htmlspecialchars($hiddenId); ?>"<?php endif; ?>>
    <?php if ($hiddenId !== ''): ?>
        <input
            type="hidden"
            id="<?php echo htmlspecialchars($hiddenId); ?>"
            name="<?php echo htmlspecialchars($hiddenName !== '' ? $hiddenName : $hiddenId); ?>"
            value="<?php echo htmlspecialchars($hiddenValue); ?>">
    <?php endif; ?>
    <div
        id="<?php echo htmlspecialchars($suggestionsId); ?>"
        class="absolute left-0 right-0 mt-1 z-50 bg-white border rounded-md shadow-lg max-h-48 overflow-auto"
        style="display:none; top:100%;"></div>
</div>
