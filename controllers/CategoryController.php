 <?php
require_once 'models/category/Category.php';
$categoryModel = new Category($conn);
class CategoryController {
    public function index() {
        is_login();
        global $categoryModel;

        // Check if the URL has action=updateMarkup
        if (isset($_GET['action']) && $_GET['action'] == 'updateMarkup') {
            $this->updateMarkup(); // Call the update function
            return; // Stop here so we don't render the index page twice
        }

        $data = $categoryModel->getCategoryData();
        renderTemplate('views/category/index.php', $data, 'Manage Category');
    }

    public function updateMarkup() {
        global $categoryModel;

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['markup'])) {
            $status = $categoryModel->updateCategoryMarkups($_POST['markup']);
            
            if($status) {
                // Redirect back to the main list (remove the action parameter)
                header("Location: " . base_url('?page=category')); 
                exit;
            } else {
                echo "Error saving data.";
            }
        }
    }
}
?>