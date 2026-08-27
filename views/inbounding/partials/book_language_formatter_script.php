<?php
require_once __DIR__ . '/../../../helpers/book_language_formatter.php';
$bookLanguageRoleDefinitionsJson = json_encode(
    BookLanguageFormatter::roleDefinitionsForJs(),
    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
);
?>
<script>
window.BookLanguageFormatter = (function () {
    const roleDefinitions = <?php echo $bookLanguageRoleDefinitionsJson; ?>;

    function formatLanguageList(names) {
        const cleaned = (Array.isArray(names) ? names : [])
            .map(function (name) { return String(name || '').trim(); })
            .filter(Boolean);
        const count = cleaned.length;
        if (count === 0) return '';
        if (count === 1) return cleaned[0];
        if (count === 2) return cleaned[0] + ' and ' + cleaned[1];
        const last = cleaned.pop();
        return cleaned.join(', ') + ' and ' + last;
    }

    function formatRoleSegment(languageNames, singleTemplate, multipleTemplate) {
        const cleaned = (Array.isArray(languageNames) ? languageNames : [])
            .map(function (name) { return String(name || '').trim(); })
            .filter(Boolean);
        if (!cleaned.length) return '';
        const languages = formatLanguageList(cleaned);
        const template = cleaned.length === 1 ? singleTemplate : multipleTemplate;
        return template.replace('{languages}', languages);
    }

    function joinRoleSegments(segments) {
        const cleaned = (Array.isArray(segments) ? segments : [])
            .map(function (segment) { return String(segment || '').trim(); })
            .filter(Boolean);
        if (!cleaned.length) return '';
        let result = cleaned.shift();
        let withCount = 0;
        cleaned.forEach(function (segment) {
            if (segment.indexOf('in ') === 0) {
                result += ' ' + segment;
                return;
            }
            if (segment.indexOf('with ') === 0) {
                if (withCount === 0) {
                    result += ' ' + segment;
                } else {
                    result += ' and ' + segment.substring(5);
                }
                withCount++;
                return;
            }
            result += ' and ' + segment;
        });
        return result;
    }

    function formatFromRoleSelections(roleIdLists, nameById) {
        const roleNamesByKey = {};
        let hasOriginal = false;
        let hasOtherRoles = false;

        roleDefinitions.forEach(function (role) {
            const ids = Array.isArray(roleIdLists[role.key]) ? roleIdLists[role.key] : [];
            const names = ids.map(function (id) {
                return nameById[id] || nameById[String(id)] || '';
            }).filter(Boolean);
            roleNamesByKey[role.key] = names;

            if (names.length > 0) {
                if (role.key === 'original_languages') {
                    hasOriginal = true;
                } else {
                    hasOtherRoles = true;
                }
            }
        });

        const onlyOriginal = hasOriginal && !hasOtherRoles;
        const segments = [];

        roleDefinitions.forEach(function (role) {
            const names = roleNamesByKey[role.key] || [];
            if (!names.length) return;

            let singleTemplate = role.single_template;
            let multipleTemplate = role.multiple_template;

            if (role.key === 'original_languages' && onlyOriginal) {
                singleTemplate = '{languages}';
                multipleTemplate = '{languages}';
            }

            const segment = formatRoleSegment(names, singleTemplate, multipleTemplate);
            if (segment) segments.push(segment);
        });

        return joinRoleSegments(segments);
    }

    return {
        roleDefinitions: roleDefinitions,
        formatLanguageList: formatLanguageList,
        formatFromRoleSelections: formatFromRoleSelections
    };
})();
</script>
