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
    public function getAllProducts($limit, $offset, $search = '') {
        $limit = (int)$limit;
        $offset = (int)$offset;
        $search = "%$search%";
        $stmt = $this->db->prepare("SELECT * FROM vp_products WHERE title LIKE ? OR item_code LIKE ? LIMIT ? OFFSET ?");
        if ($stmt === false) {
            return [];
        }
        $stmt->bind_param('ssii', $search, $search, $limit, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }
    public function countAllProducts($search = '') {
        $search = "%$search%";
        $stmt = $this->db->prepare("SELECT COUNT(*) AS cnt FROM vp_products WHERE title LIKE ?");
        if ($stmt === false) {
            return 0;
        }
        $stmt->bind_param('s', $search);
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
                $stmt = $this->db->prepare("UPDATE vp_products SET asin = ?, local_stock = ?, upc = ?, location = ?, fba_in = ?, fba_us = ?, leadtime = ?, instock_leadtime = ?, permanently_available = ?, numsold = ?, numsold_india = ?, numsold_global = ?, lastsold = ?, updated_at = ? WHERE item_code = ? AND color = ? AND size = ?");
                if ($stmt) {
                    // $title = isset($product['title']) ? $product['title'] : '';
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
                    $updated_at = date('Y-m-d H:i:s');
                    $stmt->bind_param(
                        'sissiissiiiisssss',                            
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
                        $updated_at,
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
                            $stmt = $this->db->prepare("UPDATE vp_products SET asin = ?, local_stock = ?, upc = ?, location = ?, fba_in = ?, fba_us = ?, leadtime = ?, instock_leadtime = ?, permanently_available = ?, numsold = ?, numsold_india = ?, numsold_global = ?, lastsold = ?, updated_at = ? WHERE item_code = ? AND color = ? AND size = ?");
                            if ($stmt) {
                                // $title = isset($product['title']) ? $product['title'] : '';
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
                                $updated_at = date('Y-m-d H:i:s');
                                $stmt->bind_param(
                                    'sissiissiiiisssss',                                    
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
                                    $updated_at,
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
}