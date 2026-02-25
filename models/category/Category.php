<?php 
class Category{
    private $conn;  
    public function __construct($conn) {
        $this->conn = $conn;
    }
    public function getCategoryData($value='') {
        // UPDATED: Join on 'c.category' instead of 'c.id'
        // This links the markup to the value (e.g., -2) instead of the row ID (e.g., 1)
        $sql = "SELECT c.*, m.markup_perct 
                FROM `category` c 
                LEFT JOIN `category_markup` m ON c.category = m.category_id 
                WHERE c.parent = 0";

        $category = $this->conn->query($sql)->fetch_all(MYSQLI_ASSOC);
        return ['category' => $category];
    }

    public function updateCategoryMarkups($markupsData) {
        // $markupsData comes from your form: [-2 => 10.00, -8 => 5.50]
        foreach ($markupsData as $cat_ref => $percent) {
            
            // $cat_ref will be the value from your 'category' column (e.g., -2)
            $cat_ref = intval($cat_ref); 
            $percent = floatval($percent);

            // We insert this 'category' value into the markup table
            $sql = "INSERT INTO `category_markup` (category_id, markup_perct) 
                    VALUES ($cat_ref, $percent) 
                    ON DUPLICATE KEY UPDATE markup_perct = $percent";
            
            if (!$this->conn->query($sql)) {
                return false;
            }
        }
        return true;
    }
public function getById($categoryRef)
{
    $stmt = $this->conn->prepare("SELECT * FROM category WHERE category = ?");
    $stmt->bind_param("i", $categoryRef);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}
}
?>