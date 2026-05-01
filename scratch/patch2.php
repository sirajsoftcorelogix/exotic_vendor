<?php
$file = 'c:\xampp\htdocs\exotic_vendor\models\product\product.php';
$content = file_get_contents($file);
$content = str_replace("\r\n", "\n", $content); // Normalize newlines

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

$bind_str_addon = "sssisiissssssssssssssiiiddiissss";

$parent_vars = "";
$variation_vars = "";
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

    $parent_vars .= "                    \$$field = isset(\$product['$field']) ? $cast\$product['$field'] : $def;\n";
    $variation_vars .= "                            \$$field = isset(\$variation['$field']) ? $cast\$variation['$field'] : (isset(\$product['$field']) ? $cast\$product['$field'] : $def);\n";
    
    $bind_vars .= "                        \$$field,\n";
    $insert_vars .= "                                '$field' => \$$field,\n";
}

// 2. Parent Variable Extraction & bind type string
$target2 = "\$hsn = self::vendorApiHsn(\$product);\n                    \$updated_at = \$now;\n                    \$bt = 'siss' . str_repeat('i', 9) . 's' . str_repeat('d', 9) . str_repeat('s', 7);";
$replacement2 = "\$hsn = self::vendorApiHsn(\$product);\n                    \$updated_at = \$now;\n" . rtrim($parent_vars) . "\n                    \$bt = 'siss' . str_repeat('i', 9) . 's' . str_repeat('d', 9) . str_repeat('s', 4) . '$bind_str_addon' . str_repeat('s', 3);";
$content = str_replace($target2, $replacement2, $content, $count2);
echo "Target2 replaced: $count2\n";

// 3. Parent bind parameter list
$target3 = "                        \$updated_at,\n                        \$sku,\n                        \$product['itemcode'],";
$replacement3 = "                        \$updated_at,\n                        \$sku,\n" . rtrim($bind_vars) . ",\n                        \$product['itemcode'],";
$content = str_replace($target3, $replacement3, $content, $count3);
echo "Target3 replaced: $count3\n";

// 4. Parent INSERT array
$target4 = "                                'length_unit' => (string)(\$product['length_unit'] ?? ''),\n                                'created_at' => \$now,\n                                'updated_at' => \$now,\n                            ]);";
$replacement4 = "                                'length_unit' => (string)(\$product['length_unit'] ?? ''),\n                                'created_at' => \$now,\n                                'updated_at' => \$now,\n" . rtrim($insert_vars) . "\n                            ]);";
$content = str_replace($target4, $replacement4, $content, $count4);
echo "Target4 replaced: $count4\n";

// 5. Variation Variable Extraction & bind type string
$target5 = "\$hsn = self::vendorApiHsn(\$product);\n                            \$updated_at = \$now;\n                            \$bt = 'siss' . str_repeat('i', 9) . 's' . str_repeat('d', 9) . str_repeat('s', 7);";
$replacement5 = "\$hsn = self::vendorApiHsn(\$product);\n                            \$updated_at = \$now;\n" . rtrim($variation_vars) . "\n                            \$bt = 'siss' . str_repeat('i', 9) . 's' . str_repeat('d', 9) . str_repeat('s', 4) . '$bind_str_addon' . str_repeat('s', 3);";
$content = str_replace($target5, $replacement5, $content, $count5);
echo "Target5 replaced: $count5\n";

// 6. Variation bind parameter list (indentation is different, it has 32 spaces)
$variation_bind_vars = str_replace("                        \$", "                                \$", rtrim($bind_vars));
$target6 = "                                \$updated_at,\n                                \$sku,\n                                \$product['itemcode'],";
$replacement6 = "                                \$updated_at,\n                                \$sku,\n" . $variation_bind_vars . ",\n                                \$product['itemcode'],";
$content = str_replace($target6, $replacement6, $content, $count6);
echo "Target6 replaced: $count6\n";

// 7. Variation INSERT array
$variation_insert_vars = str_replace("                                '", "                                        '", rtrim($insert_vars));
$target7 = "                                        'length_unit' => (string)(\$variation['length_unit'] ?? (\$product['length_unit'] ?? '')),\n                                        'created_at' => \$now,\n                                        'updated_at' => \$now,\n                                    ]);";
$replacement7 = "                                        'length_unit' => (string)(\$variation['length_unit'] ?? (\$product['length_unit'] ?? '')),\n                                        'created_at' => \$now,\n                                        'updated_at' => \$now,\n" . $variation_insert_vars . "\n                                    ]);";
$content = str_replace($target7, $replacement7, $content, $count7);
echo "Target7 replaced: $count7\n";

// Now we need to update createProduct
$target8 = "\n            \$data['created_at'],\n            \$data['updated_at']\n        );\n        if (\$this->executeVpProductsStmt(\$stmt)) {";

$createProduct_bind_vars = "";
foreach ($fields as $field => $type) {
    $createProduct_bind_vars .= "            \$data['$field'],\n";
}

$replacement8 = "\n            \$data['created_at'],\n            \$data['updated_at'],\n" . rtrim($createProduct_bind_vars) . "\n        );\n        if (\$this->executeVpProductsStmt(\$stmt)) {";
$content = str_replace($target8, $replacement8, $content, $count8);
echo "Target8 replaced: $count8\n";

$target9 = "product_weight, product_weight_unit, prod_height, prod_width, prod_length, length_unit, created_on, updated_at)\n                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)\";";

$createProduct_sql_addon = "";
$createProduct_placeholders = "";
foreach ($fields as $field => $type) {
    $createProduct_sql_addon .= ", $field";
    $createProduct_placeholders .= ", ?";
}
$replacement9 = "product_weight, product_weight_unit, prod_height, prod_width, prod_length, length_unit, created_on, updated_at" . $createProduct_sql_addon . ")\n                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?" . $createProduct_placeholders . ")\";";
$content = str_replace($target9, $replacement9, $content, $count9);
echo "Target9 replaced: $count9\n";

$target10 = "\$stmt->bind_param(\n            'ssssssdddsssisssssssssssssisddddddddddsiiisss',\n            \$data['item_code'],";
$replacement10 = "\$stmt->bind_param(\n            'ssssssdddsssisssssssssssssisddddddddddsiiisss" . $bind_str_addon . "',\n            \$data['item_code'],";
$content = str_replace($target10, $replacement10, $content, $count10);
echo "Target10 replaced: $count10\n";

file_put_contents($file, $content);
echo "Done replacing.\n";
?>
