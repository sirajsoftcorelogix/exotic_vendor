<?php 

class DashboardController {
        public function index() {
            is_login();
            global $addonsModel;
            $data = [];
            renderTemplate('views/dashboard/index.php', $data, 'Dashboard');
        }
    }
?>