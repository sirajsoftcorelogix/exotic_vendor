<?php
$fields = [
    'category' => 's', 'itemtype' => 's', 'snippet_description' => 's', 'india_net_qty' => 'i',
    'keywords' => 's', 'usblock' => 'i', 'indiablock' => 'i', 'hscode' => 's',
    'date_first_added' => 's', 'search_term' => 's', 'search_category' => 's',
    'long_description' => 's', 'long_description_india' => 's', 'aplus_content_ids' => 's',
    'item_level' => 's', 'marketplace_vendor' => 's', 'colormap' => 's', 'flex_status' => 's',
    'vendor_us' => 's', 'today_global' => 's', 'today_india' => 's', 'topurchase' => 'i',
    'backorder_percent' => 'i', 'backorder_weeks' => 'i', 'cp' => 'd', 'usd' => 'd',
    'amazon_sold' => 'i', 'amazon_leadtime' => 'i', 'amazon_itemcode_alias' => 's',
    'youtube_links' => 's', 'sketchfab_links' => 's', 'dimensions' => 's'
];

$vars = "";
$update_sql = "";
$bind_str = "";
$bind_vars = "";
$insert_vars = "";

foreach ($fields as $field => $type) {
    if ($type === 'i') {
        $cast = '(int)';
        $def = '0';
    } elseif ($type === 'd') {
        $cast = '(float)';
        $def = '0.0';
    } else {
        $cast = '';
        $def = "''";
    }

    $vars .= "\$$field = isset(\$product['$field']) ? $cast\$product['$field'] : $def;\n";
    
    if ($field === 'date_first_added') {
        $update_sql .= "$field = COALESCE(NULLIF(TRIM(?), ''), $field), ";
    } else {
        $update_sql .= "$field = ?, ";
    }
    
    $bind_str .= $type;
    $bind_vars .= "\$$field,\n";
    $insert_vars .= "'$field' => \$$field,\n";
}

echo "=== UPDATE SQL EXTENSION ===\n";
echo $update_sql . "\n\n";
echo "=== BIND STRING EXTENSION ===\n";
echo $bind_str . "\n\n";
echo "=== BIND VARS ===\n";
echo $bind_vars . "\n\n";
echo "=== EXTRACT VARS ===\n";
echo $vars . "\n\n";
echo "=== INSERT VARS ===\n";
echo $insert_vars . "\n\n";

?>
