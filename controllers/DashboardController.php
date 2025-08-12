<?php 

class DashboardController {
        public function index() {
            global $addonsModel;
            $data = [];
            renderTemplate('views/dashboard/index.php', $data, 'Dashboard');
        }
    }
?>