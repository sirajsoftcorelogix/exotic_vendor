<?php
class product{
    private $db;    
    public function __construct($db) {
        $this->db = $db;
    }
    public function getProduct($id) {
        $stmt = $this->db->prepare("SELECT * FROM vp_products WHERE id = ?");
        if ($stmt === false) {
            return null;
        }
        $id = (int)$id;
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result ? $result->fetch_assoc() : null;
    }
    public function getAllProducts($limit, $offset, $filters = []) {
        $limit = (int)$limit;
        $offset = (int)$offset;
        //$search = "%$search%";
        
        $search = "";
        if (!empty($filters['item_code'])) {
            $search .= "AND vp_products.item_code like '%" . $filters['item_code'] . "%'";
        }
        if (!empty($filters['title'])) {
           $search .= "AND vp_products.title like '%" . $filters['title'] . "%'";
        }
        if (!empty($filters['vendor_name'])) {
           $search .= "AND vp_products.vendor like '%" . $filters['vendor_name'] . "%'";
        }
        

        $stmt = $this->db->prepare("SELECT * FROM vp_products WHERE 1=1 $search LIMIT ? OFFSET ?");
        if ($stmt === false) {
            return [];
        }
        $stmt->bind_param('ii', $limit, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }
    public function countAllProducts($filters = []) {
        $search = "";
        if (!empty($filters['item_code'])) {
            $search .= "AND vp_products.item_code like '%" . $filters['item_code'] . "%'";
        }
        if (!empty($filters['title'])) {
           $search .= "AND vp_products.title like '%" . $filters['title'] . "%'";
        }
        if (!empty($filters['vendor_name'])) {
           $search .= "AND vp_products.vendor like '%" . $filters['vendor_name'] . "%'";
        }
        $stmt = $this->db->prepare("SELECT COUNT(*) AS cnt FROM vp_products WHERE 1=1 $search");
        if ($stmt === false) {
            return 0;
        }
        //$stmt->bind_param('s', $search);
        $stmt->execute();
        $result = $stmt->get_result();  
        if ($result === false) {
            return 0;
        }
        $row = $result->fetch_assoc();
        return isset($row['cnt']) ? (int)$row['cnt'] : 0;
    }
    public function getProductItems($search = '') {
        $searchTerm = "%$search%";
        $sql = "SELECT * FROM vp_products WHERE (item_code LIKE ? OR title LIKE ?)";
        $stmt = $this->db->prepare($sql);
        if ($stmt === false) {
            return [];
        }
        $stmt->bind_param('ss', $searchTerm, $searchTerm);
        $stmt->execute();
        $result = $stmt->get_result();
        $orderItems = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $orderItems[] = [
                    'id' => $row['id'],
                    'sku' => $row['sku'],                                     
                    'item_code' => $row['item_code'],
                    'title' => $row['title'],
                    'color' => $row['color'],
                    'size' => $row['size'],
                    'cost_price' => $row['cost_price'],
                    'gst' => $row['gst'],
                    'hsn' => $row['hsn'],
                    'description' => $row['description'],
                    'image' => $row['image'],
                    'local_stock' => $row['local_stock'],
                    'itemprice' => $row['itemprice'],
                    'leadtime' => $row['leadtime'],
                    'numsold' => $row['numsold'],
                    'numsold_india' => $row['numsold_india'],
                    'numsold_global' => $row['numsold_global'],
                    'lastsold' => $row['lastsold'],
                    'instock_leadtime' => $row['instock_leadtime'],
                    'fba_in' => $row['fba_in'],
                    'fba_us' => $row['fba_us']
                ];
            }
        }
        return $orderItems;
    }
    public function getProductItemsByCode($item_code = '') {
        $searchTerm = "%$item_code%";
        $sql = "SELECT * FROM vp_products WHERE item_code LIKE ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('s', $searchTerm);
        $stmt->execute();
        $result = $stmt->get_result();
        $orderItems = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $orderItems[] = [
                    'id' => $row['id'], 
                    'sku' => $row['sku'],                   
                    'item_code' => $row['item_code'],
                    'title' => $row['title'],
                    'color' => $row['color'],
                    'size' => $row['size'],
                    'cost_price' => $row['cost_price'],
                    'gst' => $row['gst'],
                    'hsn' => $row['hsn'],
                    'description' => $row['description'],
                    'image' => $row['image']
                    
                ];
            }
        }
        return $orderItems;
    }
    public function updateProductFromApi($productData) {
        $updatedCount = 0;
        // print_array($productData);
        // exit;
        if (isset($productData) && is_array($productData)) {            
            foreach ($productData as $product) {  
                //echo "Updating single itemcode: ".$product['itemcode']."<br/>";           
                $stmt = $this->db->prepare("UPDATE vp_products SET asin = ?, local_stock = ?, upc = ?, location = ?, fba_in = ?, fba_us = ?, leadtime = ?, instock_leadtime = ?, permanently_available = ?, numsold = ?, numsold_india = ?, numsold_global = ?, lastsold = ?, vendor = ?, shippingfee = ?, sourcingfee = ?, price = ?, price_india = ?, price_india_suggested = ?, mrp_india = ?, permanent_discount = ?, discount_global = ?, discount_india = ?, updated_at = ?, sku = ? WHERE item_code = ? AND color = ? AND size = ?");
                if ($stmt) {
                    // $title = isset($product['title']) ? $product['title'] : '';
                        $sku = isset($product['sku']) && !empty($product['sku']) ? $product['sku'] : $product['itemcode'];
                        $color = isset($product['color']) ? $product['color'] : '';
                        $size = isset($product['size']) ? $product['size'] : '';
                    // $costPrice = isset($product['cost_price']) ? (float)$product['cost_price'] : 0.0;
                    // $gst = isset($product['gst']) ? (float)$product['gst'] : 0.0;
                    // $hsn = isset($product['hsn']) ? $product['hsn'] : '';
                    // $description = isset($product['description']) ? $product['description'] : '';
                    // $image = isset($product['image']) ? $product['image'] : '';
                    // $stockQuantity = isset($product['stock_quantity']) ? (int)$product['stock_quantity'] : 0;
                    $asin = isset($product['asin']) ? $product['asin'] : '';
                    $localStock = isset($product['local_stock']) ? (int)$product['local_stock'] : 0;
                    $upc = isset($product['upc']) ? $product['upc'] : '';
                    $location = isset($product['location']) ? $product['location'] : '';
                    $fba_in = isset($product['fba_in']) ? (int)$product['fba_in'] : 0;
                    $fba_us = isset($product['fba_us']) ? (int)$product['fba_us'] : 0;
                    $leadtime = isset($product['leadtime']) ? $product['leadtime'] : '';
                    $instock_leadtime = isset($product['instock_leadtime']) ? $product['instock_leadtime'] : '';
                    $permanently_available = isset($product['permanently_available']) ? (int)$product['permanently_available'] : 0;
                    $numsold = isset($product['numsold']) ? (int)$product['numsold'] : 0;
                    $numsold_india = isset($product['numsold_india']) ? (int)$product['numsold_india'] : 0;
                    $numsold_global = isset($product['numsold_global']) ? (int)$product['numsold_global'] : 0;
                    $lastsold = isset($product['lastsold']) ? $product['lastsold'] : '';
                    $vendor = isset($product['vendor']) ? $product['vendor'] : '';
                    $shippingfee = isset($product['shippingfee']) ? (float)$product['shippingfee'] : 0.0;
                    $sourcingfee = isset($product['sourcingfee']) ? (float)$product['sourcingfee'] : 0.0;
                    $price = isset($product['price']) ? (float)$product['price'] : 0.0;
                    $price_india = isset($product['price_india']) ? (float)$product['price_india'] : 0.0;
                    $price_india_suggested = isset($product['price_india_suggested']) ? (float)$product['price_india_suggested'] : 0.0;
                    $mrp_india = isset($product['mrp_india']) ? (float)$product['mrp_india'] : 0.0;
                    $permanent_discount = isset($product['permanent_discount']) ? (float)$product['permanent_discount'] : 0.0;
                    $discount_global = isset($product['discount_global']) ? (float)$product['discount_global'] : 0.0;
                    $discount_india = isset($product['discount_india']) ? (float)$product['discount_india'] : 0.0;
                    $updated_at = date('Y-m-d H:i:s');
                    $stmt->bind_param(
                        'sissiissiiiissdddddddddsssss',                            
                        $asin,
                        $localStock,
                        $upc,
                        $location,
                        $fba_in,
                        $fba_us,
                        $leadtime,
                        $instock_leadtime,
                        $permanently_available,
                        $numsold,
                        $numsold_india,
                        $numsold_global,
                        $lastsold,
                        $vendor,
                        $shippingfee,
                        $sourcingfee,
                        $price,
                        $price_india,
                        $price_india_suggested,
                        $mrp_india,
                        $permanent_discount,
                        $discount_global,
                        $discount_india,
                        $updated_at,
                        $sku,
                        $product['itemcode'],$color,$size
                    );  
                    //echo "Executing update for itemcode: ".$product['itemcode']."<br/>";                          
                    if ($stmt->execute()) {
                        $updatedCount++;
                    }
                    if ($stmt->error) {
                        return ['success' => false, 'message' => 'Database error: ' . $stmt->error];
                    }                        
                    $stmt->close();
                }
                if (isset($product['variations'])) {
                    foreach ($product['variations'] as $variation) {                            
                            //echo "Updating variations itemcode: ".$product['itemcode']."<br/>";
                            $stmt = $this->db->prepare("UPDATE vp_products SET asin = ?, local_stock = ?, upc = ?, location = ?, fba_in = ?, fba_us = ?, leadtime = ?, instock_leadtime = ?, permanently_available = ?, numsold = ?, numsold_india = ?, numsold_global = ?, lastsold = ?, vendor = ?, shippingfee = ?, sourcingfee = ?, price = ?, price_india = ?, price_india_suggested = ?, mrp_india = ?, permanent_discount = ?, discount_global = ?, discount_india = ?, updated_at = ?, sku = ? WHERE item_code = ? AND color = ? AND size = ?");
                            if ($stmt) {
                                // $title = isset($product['title']) ? $product['title'] : '';
                                 $sku = isset($variation['sku']) && !empty($variation['sku']) ? $variation['sku'] : $product['itemcode'];
                                 $color = isset($variation['color']) ? $variation['color'] : '';
                                 $size = isset($variation['size']) ? $variation['size'] : '';
                                // $costPrice = isset($product['cost_price']) ? (float)$product['cost_price'] : 0.0;
                                // $gst = isset($product['gst']) ? (float)$product['gst'] : 0.0;
                                // $hsn = isset($product['hsn']) ? $product['hsn'] : '';
                                // $description = isset($product['description']) ? $product['description'] : '';
                                // $image = isset($product['image']) ? $product['image'] : '';
                                // $stockQuantity = isset($product['stock_quantity']) ? (int)$product['stock_quantity'] : 0;
                                $asin = isset($variation['asin']) ? $variation['asin'] : '';
                                $localStock = isset($variation['local_stock']) ? (int)$variation['local_stock'] : 0;
                                $upc = isset($variation['upc']) ? $variation['upc'] : '';
                                $location = isset($variation['location']) ? $variation['location'] : '';
                                $fba_in = isset($variation['fba_in']) ? (int)$variation['fba_in'] : 0;
                                $fba_us = isset($variation['fba_us']) ? (int)$variation['fba_us'] : 0;
                                $leadtime = isset($variation['leadtime']) ? $variation['leadtime'] : '';
                                $instock_leadtime = isset($variation['instock_leadtime']) ? $variation['instock_leadtime'] : '';
                                $permanently_available = isset($variation['permanently_available']) ? (int)$variation['permanently_available'] : 0;
                                $numsold = isset($variation['numsold']) ? (int)$variation['numsold'] : 0;
                                $numsold_india = isset($variation['numsold_india']) ? (int)$variation['numsold_india'] : 0;
                                $numsold_global = isset($variation['numsold_global']) ? (int)$variation['numsold_global'] : 0;
                                $lastsold = isset($variation['lastsold']) ? $variation['lastsold'] : '';
                                $vendor = isset($product['vendor']) ? $product['vendor'] : '';
                                $shippingfee = isset($product['shippingfee']) ? (float)$product['shippingfee'] : 0.0;
                                $sourcingfee = isset($product['sourcingfee']) ? (float)$product['sourcingfee'] : 0.0;
                                $price = isset($product['price']) ? (float)$product['price'] : 0.0;
                                $price_india = isset($product['price_india']) ? (float)$product['price_india'] : 0.0;
                                $price_india_suggested = isset($product['price_india_suggested']) ? (float)$product['price_india_suggested'] : 0.0;
                                $mrp_india = isset($product['mrp_india']) ? (float)$product['mrp_india'] : 0.0;
                                $permanent_discount = isset($product['permanent_discount']) ? (float)$product['permanent_discount'] : 0.0;
                                $discount_global = isset($product['discount_global']) ? (float)$product['discount_global'] : 0.0;
                                $discount_india = isset($product['discount_india']) ? (float)$product['discount_india'] : 0.0;
                                $updated_at = date('Y-m-d H:i:s');
                                $stmt->bind_param(
                                    'sissiissiiiissdddddddddsssss',                                    
                                    $asin,
                                    $localStock,
                                    $upc,
                                    $location,
                                    $fba_in,
                                    $fba_us,
                                    $leadtime,
                                    $instock_leadtime,
                                    $permanently_available,
                                    $numsold,
                                    $numsold_india,
                                    $numsold_global,
                                    $lastsold,
                                    $vendor,
                                    $shippingfee,
                                    $sourcingfee,
                                    $price,
                                    $price_india,
                                    $price_india_suggested,
                                    $mrp_india,
                                    $permanent_discount,
                                    $discount_global,
                                    $discount_india,
                                    $updated_at,
                                    $sku,
                                    $product['itemcode'],$color,$size
                                );                                   
                                if ($stmt->execute()) {
                                    $updatedCount++;
                                }
                                if ($stmt->error) {
                                    return ['success' => false, 'message' => 'Database error: ' . $stmt->error];
                                }  
                                $stmt->close();
                            }
                    }                    
                } 
                  
            }
        }
        return ['success' => true, 'updated_count' => $updatedCount, 'message' => 'Products updated successfully.'];       
    }
     public function findByItemCodeSizeColor($code, $size, $color) {
        $sql = "SELECT * FROM vp_products WHERE item_code = ? AND size = ? AND color = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('sss', $code, $size, $color);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        return $res;
    }
    public function findBySku($sku) {
        $sql = "SELECT * FROM vp_products WHERE sku = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('s', $sku);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        return $res;
    }   
    public function createProduct($data) {
        $sql = "INSERT INTO vp_products (item_code, sku, size, color, title, image, local_stock, itemprice, finalprice,  groupname, material, cost_price, gst, hsn, description, asin, upc, location, fba_in, fba_us, leadtime, instock_leadtime, permanently_available, numsold, numsold_india, numsold_global, lastsold, vendor, shippingfee, sourcingfee, price, price_india, price_india_suggested, mrp_india, permanent_discount, discount_global, discount_india, product_weight, product_weight_unit, prod_height, prod_width, prod_length, length_unit, created_on, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql); 
        $stmt->bind_param('ssssssdddsssisssssssssssssisddddddddddsiiisss',
            $data['item_code'],
            $data['sku'],
            $data['size'],
            $data['color'],
            $data['title'],
            $data['image'],
            $data['local_stock'],
            $data['itemprice'],
            $data['finalprice'],            
            $data['groupname'],
            $data['material'],
            $data['cost_price'],
            $data['gst'],
            $data['hsn'],
            $data['description'],
            $data['asin'],
            $data['upc'],
            $data['location'],
            $data['fba_in'],
            $data['fba_us'],
            $data['leadtime'],
            $data['instock_leadtime'],
            $data['permanently_available'],
            $data['numsold'],
            $data['numsold_india'],
            $data['numsold_global'],
            $data['lastsold'],
            $data['vendor'],
            $data['shippingfee'],
            $data['sourcingfee'],
            $data['price'],
            $data['price_india'],
            $data['price_india_suggested'],
            $data['mrp_india'],
            $data['permanent_discount'],
            $data['discount_global'],
            $data['discount_india'],
            $data['product_weight'],
            $data['product_weight_unit'],
            $data['prod_height'],
            $data['prod_width'],
            $data['prod_length'],
            $data['length_unit'],
            $data['created_at'],
            $data['updated_at']
        );
        if ($stmt->execute()) return $this->db->insert_id;
        return false;
    }
    public function updateProduct($id, $data) {
        $sql = "UPDATE vp_products SET title=?, image=?, local_stock=?, itemprice=?, finalprice=?,  groupname=?, material=?, cost_price=?, gst=?, hsn=?, description=?, asin=?, upc=?, location=?, fba_in=?, fba_us=?, leadtime=?, instock_leadtime=?, permanently_available=?, numsold=?, numsold_india=?, numsold_global=?, lastsold=?, vendor=?, shippingfee=?, sourcingfee=?, price=?, price_india=?, price_india_suggested=?, mrp_india=?, permanent_discount=?, discount_global=?, discount_india=?, product_weight=?, product_weight_unit=?, prod_height=?, prod_width=?, prod_length=?, length_unit=?, updated_at=? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ssiddssddsssssiissiiiiisddddddddddsdddssi',
            $data['title'],
            $data['image'],
            $data['local_stock'],
            $data['itemprice'],
            $data['finalprice'],            
            $data['groupname'],
            $data['material'],
            $data['cost_price'],
            $data['gst'],
            $data['hsn'],
            $data['description'],
            $data['asin'],
            $data['upc'],
            $data['location'],
            $data['fba_in'],
            $data['fba_us'],
            $data['leadtime'],
            $data['instock_leadtime'],
            $data['permanently_available'],
            $data['numsold'],
            $data['numsold_india'],
            $data['numsold_global'],
            $data['lastsold'],
            $data['vendor'],
            $data['shippingfee'],
            $data['sourcingfee'],   
            $data['price'],
            $data['price_india'],
            $data['price_india_suggested'],
            $data['mrp_india'],
            $data['permanent_discount'],
            $data['discount_global'],
            $data['discount_india'], 
            $data['product_weight'],
            $data['product_weight_unit'],              
            $data['prod_height'],
            $data['prod_width'],
            $data['prod_length'],
            $data['length_unit'],
            $data['updated_at'],
            $id
        );
        return $stmt->execute();
    }
    public function getProductByItemCode($item_code) {
        $sql = "SELECT * FROM vp_products WHERE item_code = ?";
        $stmt = $this->db->prepare($sql);   
        $stmt->bind_param('s', $item_code);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            // If only one row is found, return an associative array for backward compatibility,
            // otherwise return all matching rows as an array of associative arrays.
            /*if ($result->num_rows === 1) {
                return $result->fetch_assoc();
            }*/
            return $result->fetch_all(MYSQLI_ASSOC);
        }
        return null;
    }
}