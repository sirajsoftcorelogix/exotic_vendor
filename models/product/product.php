<?php
class product
{
    private $db;
    public function __construct($db)
    {
        $this->db = $db;
    }
    public function getProduct($id)
    {
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
    public function getAllProducts($limit, $offset, $filters = [])
    {
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


        $stmt = $this->db->prepare("SELECT * FROM vp_products WHERE 1=1 $search order by vp_products.id DESC LIMIT ? OFFSET ?");
        if ($stmt === false) {
            return [];
        }
        $stmt->bind_param('ii', $limit, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }
    public function countAllProducts($filters = [])
    {
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
    public function getProductItems($search = '')
    {
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
    public function getProductItemsByCode($item_code = '')
    {
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
    public function updateProductFromApi($productData)
    {
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
                    $leadtime = isset($product['leadtime']) ? (int)$product['leadtime'] : '';
                    $instock_leadtime = isset($product['instock_leadtime']) ? (int)$product['instock_leadtime'] : '';
                    $permanently_available = isset($product['permanently_available']) ? (int)$product['permanently_available'] : 0;
                    $numsold = isset($product['numsold']) ? (int)$product['numsold'] : 0;
                    $numsold_india = isset($product['numsold_india']) ? (int)$product['numsold_india'] : 0;
                    $numsold_global = isset($product['numsold_global']) ? (int)$product['numsold_global'] : 0;
                    $lastsold = isset($product['lastsold']) ? (int)$product['lastsold'] : '';
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
                        'sissiiiiiiiiisdddddddddsssss',
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
                        $product['itemcode'],
                        $color,
                        $size
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
                                $product['itemcode'],
                                $color,
                                $size
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
    public function findByItemCodeSizeColor($code, $size, $color)
    {
        $sql = "SELECT * FROM vp_products WHERE item_code = ? AND size = ? AND color = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('sss', $code, $size, $color);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        return $res;
    }
    public function findBySku($sku)
    {
        $sql = "SELECT * FROM vp_products WHERE sku = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('s', $sku);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        return $res;
    }
    public function createProduct($data)
    {
        $sql = "INSERT INTO vp_products (item_code, sku, size, color, title, image, local_stock, itemprice, finalprice,  groupname, material, cost_price, gst, hsn, description, asin, upc, location, fba_in, fba_us, leadtime, instock_leadtime, permanently_available, numsold, numsold_india, numsold_global, lastsold, vendor, shippingfee, sourcingfee, price, price_india, price_india_suggested, mrp_india, permanent_discount, discount_global, discount_india, product_weight, product_weight_unit, prod_height, prod_width, prod_length, length_unit, created_on, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param(
            'ssssssdddsssisssssssssssssisddddddddddsiiisss',
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
    public function updateProduct($id, $data)
    {
        $sql = "UPDATE vp_products SET title=?, image=?, local_stock=?, itemprice=?, finalprice=?,  groupname=?, material=?, cost_price=?, gst=?, hsn=?, description=?, asin=?, upc=?, location=?, fba_in=?, fba_us=?, leadtime=?, instock_leadtime=?, permanently_available=?, numsold=?, numsold_india=?, numsold_global=?, lastsold=?, vendor=?, shippingfee=?, sourcingfee=?, price=?, price_india=?, price_india_suggested=?, mrp_india=?, permanent_discount=?, discount_global=?, discount_india=?, product_weight=?, product_weight_unit=?, prod_height=?, prod_width=?, prod_length=?, length_unit=?, updated_at=? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param(
            'ssiddssddsssssiissiiiiisddddddddddsdddssi',
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
    public function getProductByItemCode($item_code)
    {
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
    public function getVendorByItemCode($item_code)
    {
        $sql = "SELECT pvm.id as pvm_id, pvm.*, vv.* FROM product_vendor_map pvm 
            JOIN vp_vendors vv ON pvm.vendor_id = vv.id 
            WHERE pvm.item_code = ?  order by pvm.priority ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('s', $item_code);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            return $result->fetch_all(MYSQLI_ASSOC);
        }
        return null;
    }

    /**
     * Save or update product vendor mapping
     * @param string $item_code
     * @param int $vendor_id
     * @param string $vendor_code
     * @return bool
     */
    public function saveProductVendor($item_code, $vendor_id, $vendor_code = '')
    {
        $now = date('Y-m-d H:i:s');
        // check existing
        $sql = "SELECT id FROM product_vendor_map WHERE item_code = ? AND vendor_id = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) return false;
        $stmt->bind_param('si', $item_code, $vendor_id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && $res->num_rows > 0) {
            /*$row = $res->fetch_assoc();
            $id = (int)$row['id'];
            $sql = "UPDATE product_vendor_map SET vendor_id = ?, vendor_code = ?, updated_at = ? WHERE id = ?";
            $stmt2 = $this->db->prepare($sql);
            if (!$stmt2) return false;
            $stmt2->bind_param('issi', $vendor_id, $vendor_code, $now, $id);
            return $stmt2->execute();*/
            return true; // already exists
        } else {
            $sql = "INSERT INTO product_vendor_map (item_code, vendor_id, vendor_code, created_at, updated_at) VALUES (?, ?, ?, ?, ?)";
            $stmt2 = $this->db->prepare($sql);
            if (!$stmt2) return false;
            $stmt2->bind_param('sisss', $item_code, $vendor_id, $vendor_code, $now, $now);
            return $stmt2->execute();
        }
    }
    public function deleteProductVendor($id)
    {

        $sql = "DELETE FROM product_vendor_map WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) return false;
        $stmt->bind_param('i', $id);
        return $stmt->execute();
    }
    /**
     * Update only vendor priority
     * @param int $id
     * @param int $priority
     * @return array
     */
    public function updatePriority($id, $priority)
    {
        //select existing priority
        $sql = "SELECT * FROM product_vendor_map WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        if ($stmt === false) return ['success' => false, 'message' => 'Prepare failed: ' . $this->db->error];
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $sql2 = "SELECT * FROM product_vendor_map WHERE item_code = ? AND priority = ?";
            $stmt2 = $this->db->prepare($sql2);
            $stmt2->bind_param('si', $row['item_code'], $priority);
            $stmt2->execute();
            $result2 = $stmt2->get_result();
            if ($result2 && $result2->num_rows > 0) {
                //another vendor has same priority, reset that vendor priority to 0
                $row2 = $result2->fetch_assoc();
                $sql3 = "UPDATE product_vendor_map SET priority = 0 WHERE id = ?";
                $stmt3 = $this->db->prepare($sql3);
                if ($stmt3 === false) return ['success' => false, 'message' => 'Prepare failed: ' . $this->db->error];
                $vid = (int)$row2['id'];
                $stmt3->bind_param('i', $vid);
                $stmt3->execute();
            }
        }

        // Update the current vendor's priority
        $sql = "UPDATE product_vendor_map SET priority = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        if ($stmt === false) return ['success' => false, 'message' => 'Prepare failed: ' . $this->db->error];
        $p = (int)$priority;
        $i = (int)$id;
        $stmt->bind_param('ii', $p, $i);
        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'Priority updated successfully.'];
        }
        return ['success' => false, 'message' => 'Update failed: ' . $stmt->error];
    }

    public function createPurchaseList($data)
    {
        $sql = "
        INSERT INTO purchase_list (
            user_id,
            product_id,
            order_id,
            sku,
            date_added,
            date_purchased,
            status,
            quantity,
            edit_by,
            updated_at,
            created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return [
                'success' => false,
                'message' => 'Prepare failed: ' . $this->db->error
            ];
        }

        $now = date('Y-m-d H:i:s');

        $types = "iiissssiiss"; // ✔ corrected

        $stmt->bind_param(
            $types,
            $data['user_id'],
            $data['product_id'],
            $data['order_id'],
            $data['sku'],
            $now,                          // date_added
            $data['date_purchased'],       // date only is fine
            $data['status'],               // ✔ now string
            $data['quantity'],
            $data['edit_by'],
            $now,
            $now
        );

        if ($stmt->execute()) {
            return ['success' => true];
        }

        return [
            'success' => false,
            'message' => $stmt->error
        ];
    }


    public function getPurchaseListByUser($user_id, $limit = 100, $offset = 0, $filters = [])
    {
        $sql = "SELECT * FROM purchase_list WHERE user_id = ? AND status = ? ORDER BY created_at DESC LIMIT ? OFFSET ?";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) return [];
        $stmt->bind_param('isii', $user_id, $filters['status'], $limit, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            return $result->fetch_all(MYSQLI_ASSOC);
        }
        return [];
    }
    /*public function getPurchaseList($limit = 100, $offset = 0, $filters = [])
    {
        // Join with vp_products to allow filtering by product category/groupname and by user
        $where = [];
        $params = [];
        $types = '';

        if (!empty($filters['user_id'])) {
            $where[] = 'pl.user_id = ?';
            $params[] = (int)$filters['user_id'];
            $types .= 'i';
        }
        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $where[] = 'pl.status = ?';
            $params[] = $filters['status'];
            $types .= 's';
        }
        if (!empty($filters['category']) && $filters['category'] !== 'all') {
            $where[] = 'p.groupname = ?';
            $params[] = $filters['category'];
            $types .= 's';
        }
        if (!empty($filters['search'])) {
            $where[] = '(p.item_code LIKE ? OR p.title LIKE ?)';
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $types .= 'ss';
        }
        if (!empty($filters['added_by'])) {
            $where[] = 'pl.edit_by = ?';
            $params[] = (int)$filters['added_by'];
            $types .= 'i';
        }
        if (!empty($filters['asigned_to'])) {
            $where[] = 'pl.user_id = ?';
            $params[] = (int)$filters['asigned_to'];
            $types .= 'i';
        }

        $dateColumn = (!empty($filters['date_type']) && $filters['date_type'] === 'purchased')
            ? 'pl.date_purchased'
            : 'pl.date_added';

        if (!empty($filters['date_from'])) {
            $where[]  = "$dateColumn >= ?";
            $params[] = $filters['date_from'];
            $types   .= 's';
        }

        if (!empty($filters['date_to'])) {
            $where[]  = "$dateColumn <= ?";
            $params[] = $filters['date_to'];
            $types   .= 's';
        }

        //print_r($filters);

        $orderBy = '';
        if (!empty($filters['sort_by'])) {
            $orderBy = " ORDER BY pl.date_added $filters[sort_by]";
        } else {
            $orderBy = " ORDER BY pl.date_added DESC";
        }


        $whereSql = '';
        if (!empty($where)) {
            $whereSql = 'WHERE ' . implode(' AND ', $where);
        }
        //echo $whereSql."**********************";
        $sql = "SELECT pl.id,pl.user_id,pl.product_id,pl.order_id,pl.sku,pl.date_added,pl.date_purchased,pl.status,
		sum(pl.quantity) as quantity, pl.remarks,pl.edit_by,pl.updated_at,pl.created_at, 
		p.item_code, p.title, p.groupname AS category, p.cost_price, p.image 
		FROM purchase_list pl 
		LEFT JOIN vp_products p ON pl.product_id = p.id 
		$whereSql 
		GROUP BY pl.product_id 
		$orderBy LIMIT ? OFFSET ?";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) return [];

        // bind dynamic params followed by limit and offset
        if (!empty($params)) {
            $types_all = $types . 'ii';
            $bindParams = [$types_all];
            foreach ($params as $k => $v) {
                $bindParams[] = &$params[$k];
            }
            $bindParams[] = &$limit;
            $bindParams[] = &$offset;
            // convert to references for call_user_func_array
            $refs = [];
            foreach ($bindParams as $key => $val) {
                $refs[$key] = &$bindParams[$key];
            }
            call_user_func_array([$stmt, 'bind_param'], $refs);
        } else {
            $stmt->bind_param('ii', $limit, $offset);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            return $result->fetch_all(MYSQLI_ASSOC);
        }
        return [];
    }*/

    public function getPurchaseList($limit = 100, $offset = 0, $filters = [])
    {
        // -----------------------------
        // Build WHERE for purchase_list (subqueries + latest-row join)
        // NOTE: no alias here because we reuse it in multiple places.
        // -----------------------------
        $plWhere  = [];
        $plParams = [];
        $plTypes  = '';

        if (!empty($filters['user_id'])) {
            $plWhere[]  = 'user_id = ?';
            $plParams[] = (int)$filters['user_id'];
            $plTypes   .= 'i';
        }

        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $plWhere[]  = 'status = ?';
            $plParams[] = $filters['status'];
            $plTypes   .= 's';
        }

        if (!empty($filters['added_by'])) {
            $plWhere[]  = 'edit_by = ?';
            $plParams[] = (int)$filters['added_by'];
            $plTypes   .= 'i';
        }

        if (!empty($filters['asigned_to'])) {
            $plWhere[]  = 'user_id = ?';
            $plParams[] = (int)$filters['asigned_to'];
            $plTypes   .= 'i';
        }

        $dateColumn = (!empty($filters['date_type']) && $filters['date_type'] === 'purchased')
            ? 'date_purchased'
            : 'date_added';

        if (!empty($filters['date_from'])) {
            $plWhere[]  = "$dateColumn >= ?";
            $plParams[] = $filters['date_from'];
            $plTypes   .= 's';
        }

        if (!empty($filters['date_to'])) {
            $plWhere[]  = "$dateColumn <= ?";
            $plParams[] = $filters['date_to'];
            $plTypes   .= 's';
        }

        $plWhereSql = '';
        if (!empty($plWhere)) {
            $plWhereSql = ' WHERE ' . implode(' AND ', $plWhere);
        }

        // -----------------------------
        // Build OUTER filters for vp_products (category/search)
        // -----------------------------
        $outerWhere  = [];
        $outerParams = [];
        $outerTypes  = '';

        if (!empty($filters['category']) && $filters['category'] !== 'all') {
            $outerWhere[]  = 'p.groupname = ?';
            $outerParams[] = $filters['category'];
            $outerTypes   .= 's';
        }

        if (!empty($filters['search'])) {
            $outerWhere[]  = '(p.item_code LIKE ? OR p.title LIKE ?)';
            $searchTerm    = '%' . $filters['search'] . '%';
            $outerParams[] = $searchTerm;
            $outerParams[] = $searchTerm;
            $outerTypes   .= 'ss';
        }

        $outerWhereSql = '';
        if (!empty($outerWhere)) {
            $outerWhereSql = ' WHERE ' . implode(' AND ', $outerWhere);
        }

        // -----------------------------
        // ORDER BY (use pl_latest alias, not pl)
        // -----------------------------
        $sortDir = 'DESC';
        if (!empty($filters['sort_by'])) {
            $sortDir = (strtoupper($filters['sort_by']) === 'ASC') ? 'ASC' : 'DESC';
        }
        $orderBy = " ORDER BY pl_latest.date_added $sortDir";

        // -----------------------------
        // SQL: total quantity per product + latest row per product (by updated_at)
        // -----------------------------
        $sql = "
            SELECT
            pl_latest.id,
            pl_latest.user_id,
            pl_latest.product_id,
            pl_latest.order_id,
            pl_latest.sku,
            pl_latest.date_added,
            pl_latest.date_purchased,
            pl_latest.status,
            qty.quantity,
            pl_latest.remarks,
            pl_latest.edit_by,
            pl_latest.updated_at,
            pl_latest.created_at,
            pl_latest.expected_time_of_delivery,
            p.item_code,
            p.title,
            p.groupname AS category,
            p.cost_price,
            p.image
            FROM
            (
            SELECT product_id, SUM(quantity) AS quantity
            FROM purchase_list
            $plWhereSql
            GROUP BY product_id
            ) qty
            JOIN
            (
            SELECT pl.*
            FROM purchase_list pl
            JOIN (
                SELECT product_id, MAX(updated_at) AS max_updated_at
                FROM purchase_list
                $plWhereSql
                GROUP BY product_id
            ) latest
                ON latest.product_id = pl.product_id
            AND latest.max_updated_at = pl.updated_at
            $plWhereSql
            ) pl_latest
            ON pl_latest.product_id = qty.product_id
            LEFT JOIN vp_products p
            ON p.id = pl_latest.product_id
            $outerWhereSql
            $orderBy
            LIMIT ? OFFSET ?
        ";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) return [];

        // -----------------------------
        // Bind params (IMPORTANT ORDER)
        // We used $plWhereSql 3 times and $outerWhereSql once:
        // 1) qty subquery:        $plParams
        // 2) latest subquery:     $plParams
        // 3) pl_latest filter:    $plParams
        // 4) outer product filter $outerParams
        // 5) limit/offset
        // -----------------------------
        $bindTypes  = $plTypes . $plTypes . $plTypes . $outerTypes . 'ii';
        $bindValues = array_merge($plParams, $plParams, $plParams, $outerParams, [(int)$limit, (int)$offset]);

        // mysqli bind_param requires references
        $refs   = [];
        $refs[] = &$bindTypes;
        foreach ($bindValues as $k => $v) {
            $refs[] = &$bindValues[$k];
        }
        call_user_func_array([$stmt, 'bind_param'], $refs);

        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            return $result->fetch_all(MYSQLI_ASSOC);
        }
        return [];
    }

    public function countPurchaseList($filters = [])
    {
        // We want the count of DISTINCT product_id groups after applying filters.
        // With ONLY_FULL_GROUP_BY, do NOT "COUNT(*) ... GROUP BY product_id" and then fetch one row.
        // Instead: count distinct product_id using a subquery (or COUNT(DISTINCT ...)).

        // -----------------------------
        // Build WHERE for purchase_list fields
        // -----------------------------
        $plWhere  = [];
        $plParams = [];
        $plTypes  = '';

        if (!empty($filters['user_id'])) {
            $plWhere[]  = 'pl.user_id = ?';
            $plParams[] = (int)$filters['user_id'];
            $plTypes   .= 'i';
        }

        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $plWhere[]  = 'pl.status = ?';
            $plParams[] = $filters['status'];
            $plTypes   .= 's';
        }

        if (!empty($filters['added_by'])) {
            $plWhere[]  = 'pl.edit_by = ?';
            $plParams[] = (int)$filters['added_by'];
            $plTypes   .= 'i';
        }

        if (!empty($filters['asigned_to'])) {
            $plWhere[]  = 'pl.user_id = ?';
            $plParams[] = (int)$filters['asigned_to'];
            $plTypes   .= 'i';
        }

        $dateColumn = (!empty($filters['date_type']) && $filters['date_type'] === 'purchased')
            ? 'pl.date_purchased'
            : 'pl.date_added';

        if (!empty($filters['date_from'])) {
            $plWhere[]  = "$dateColumn >= ?";
            $plParams[] = $filters['date_from'];
            $plTypes   .= 's';
        }

        if (!empty($filters['date_to'])) {
            $plWhere[]  = "$dateColumn <= ?";
            $plParams[] = $filters['date_to'];
            $plTypes   .= 's';
        }

        $plWhereSql = '';
        if (!empty($plWhere)) {
            $plWhereSql = ' WHERE ' . implode(' AND ', $plWhere);
        }

        // -----------------------------
        // Build WHERE for vp_products fields (category/search)
        // -----------------------------
        $pWhere  = [];
        $pParams = [];
        $pTypes  = '';

        if (!empty($filters['category']) && $filters['category'] !== 'all') {
            $pWhere[]  = 'p.groupname = ?';
            $pParams[] = $filters['category'];
            $pTypes   .= 's';
        }

        if (!empty($filters['search'])) {
            $pWhere[]  = '(p.item_code LIKE ? OR p.title LIKE ?)';
            $searchTerm = '%' . $filters['search'] . '%';
            $pParams[] = $searchTerm;
            $pParams[] = $searchTerm;
            $pTypes   .= 'ss';
        }

        $pWhereSql = '';
        if (!empty($pWhere)) {
            // if purchase_list WHERE exists already, append with AND, else start WHERE
            $pWhereSql = ($plWhereSql ? ' AND ' : ' WHERE ') . implode(' AND ', $pWhere);
        }

        // -----------------------------
        // Count distinct grouped products
        // -----------------------------
        $sql = "
            SELECT COUNT(DISTINCT pl.product_id) AS cnt
            FROM purchase_list pl
            LEFT JOIN vp_products p ON pl.product_id = p.id
            $plWhereSql
            $pWhereSql
        ";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) return 0;

        $allParams = array_merge($plParams, $pParams);
        $allTypes  = $plTypes . $pTypes;

        if (!empty($allParams)) {
            // mysqli bind_param requires references
            $bindParams = [];
            $bindParams[] = &$allTypes;
            foreach ($allParams as $k => $v) {
                $bindParams[] = &$allParams[$k];
            }
            call_user_func_array([$stmt, 'bind_param'], $bindParams);
        }

        $stmt->execute();
        $result = $stmt->get_result();

        if ($result) {
            $row = $result->fetch_assoc();
            return isset($row['cnt']) ? (int)$row['cnt'] : 0;
        }

        return 0;
    }
 

    // Return distinct product categories (groupname) for filter dropdown
    public function getCategories()
    {
        $sql = "SELECT DISTINCT COALESCE(NULLIF(groupname, ''), '-') AS groupname FROM vp_products WHERE groupname IS NOT NULL ORDER BY groupname ASC";
        $res = $this->db->query($sql);
        $cats = [];
        if ($res && $res->num_rows > 0) {
            while ($row = $res->fetch_assoc()) {
                $cats[] = $row['groupname'];
            }
        }
        return $cats;
    }

    public function updatePurchaseListStatus($id, $status, $date_purchased = null)
    {
        $sql = "UPDATE purchase_list SET status = ?, date_purchased = ?, updated_at = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) return ['success' => false, 'message' => 'Prepare failed: ' . $this->db->error];
        $date_purchased = $date_purchased ? $date_purchased : date('Y-m-d H:i:s');
        $updatedAt = date('Y-m-d H:i:s');
        $id = (int)$id;
        $stmt->bind_param('sssi', $status, $date_purchased, $updatedAt, $id);
        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'Status updated'];
        }
        return ['success' => false, 'message' => 'Update failed: ' . $stmt->error];
    }

    // Update quantity and remarks for a purchase list item
    public function updatePurchaseItem($id, $quantity, $remarks, $status, $expected_time_of_delivery = null)
    {
        $sql = "UPDATE purchase_list 
                SET quantity = ?, 
                    remarks = ?, 
                    status = ?, 
                    expected_time_of_delivery = ?, 
                    updated_at = ? 
                WHERE id = ?";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return ['success' => false, 'message' => 'Prepare failed: ' . $this->db->error];
        }

        $updatedAt = date('Y-m-d H:i:s');
        $id  = (int) $id;

        // quantity can be NULL
        $qty = ($quantity === '' || $quantity === null) ? null : (int)$quantity;

        // normalize date (NULL allowed)
        if (!empty($expected_time_of_delivery)) {
            $dt = date_create($expected_time_of_delivery);
            $expected_time_of_delivery = $dt ? $dt->format('Y-m-d') : null;
        } else {
            $expected_time_of_delivery = null;
        }

        // ✅ FIXED bind_param
        $stmt->bind_param(
            'issssi',
            $qty,
            $remarks,
            $status,
            $expected_time_of_delivery,
            $updatedAt,
            $id
        );

        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'Updated successfully'];
        }

        return ['success' => false, 'message' => 'Update failed: ' . $stmt->error];
    }

    public function deletePurchaseItem($id)
    {
        $sql = "DELETE FROM purchase_list WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) return ['success' => false, 'message' => 'Prepare failed: ' . $this->db->error];
        $id = (int)$id;
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'Deleted successfully'];
        }
        return ['success' => false, 'message' => 'Delete failed: ' . $stmt->error];
    }
    public function getPurchaseItemById($id)
    {
        $sql = "SELECT p.*, pl.*,  u.name as agent_name, vu.name as added_by_name FROM purchase_list pl 
        LEFT JOIN vp_products p ON pl.product_id = p.id 
        LEFT JOIN vp_users u ON pl.user_id = u.id
        LEFT JOIN vp_users vu ON pl.edit_by = vu.id
        WHERE pl.id = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) return null;
        $id = (int)$id;
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        return null;
    }
    public function getProductByskuExact($sku)
    {
        $sql = "SELECT * FROM vp_products WHERE sku = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('s', $sku);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        return null;
    }
}
