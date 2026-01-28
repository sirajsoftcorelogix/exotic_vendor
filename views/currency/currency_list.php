<?php
$currencies = $currencyController->getAllCurrencies();
$selectedCurrency = null;
$rateHistory = [];

if (isset($_GET['view_history']) && !empty($_GET['view_history'])) {
    $selectedCurrency = $currencyController->getCurrencyById($_GET['view_history']);
    $rateHistory = $currencyController->getRateHistory($selectedCurrency['currency_code'], 30);
}

if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $result = $currencyController->deactivateCurrency($_GET['delete']);
    if ($result['success']) {
        header('Location: currency_list.php?success=1');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Currency Management</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f5f5f5; padding: 20px; }
        .container { max-width: 1000px; margin: 0 auto; }
        h1 { color: #333; margin-bottom: 20px; }
        .btn-add { background: #4CAF50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; display: inline-block; margin-bottom: 20px; }
        .btn-add:hover { background: #45a049; }
        .success { background: #e8f5e9; color: #388e3c; padding: 12px; border-radius: 4px; margin-bottom: 20px; }
        table { width: 100%; background: white; border-collapse: collapse; box-shadow: 0 2px 4px rgba(0,0,0,0.1); border-radius: 4px; overflow: hidden; }
        th { background: #f5f5f5; padding: 15px; text-align: left; font-weight: bold; border-bottom: 2px solid #ddd; }
        td { padding: 12px 15px; border-bottom: 1px solid #eee; }
        tr:hover { background: #fafafa; }
        .btn { padding: 6px 12px; border: none; border-radius: 3px; cursor: pointer; font-size: 12px; text-decoration: none; display: inline-block; }
        .btn-edit { background: #2196F3; color: white; }
        .btn-edit:hover { background: #0b7dda; }
        .btn-delete { background: #f44336; color: white; }
        .btn-delete:hover { background: #da190b; }
        .btn-history { background: #FF9800; color: white; }
        .btn-history:hover { background: #e68900; }
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); }
        .modal.active { display: flex; align-items: center; justify-content: center; }
        .modal-content { background: white; padding: 30px; border-radius: 8px; max-width: 600px; width: 90%; max-height: 80vh; overflow-y: auto; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .close-modal { background: none; border: none; font-size: 28px; cursor: pointer; color: #999; }
        .history-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .history-table th, .history-table td { padding: 10px; text-align: left; border-bottom: 1px solid #eee; }
        .history-table th { background: #f5f5f5; }
        .empty { text-align: center; color: #999; padding: 40px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Currency Management</h1>
        
        <?php if (isset($_GET['success'])): ?>
            <div class="success">Operation completed successfully!</div>
        <?php endif; ?>
        
        <a href="currency_form.php" class="btn-add">+ Add New Currency</a>
        
        <?php if (empty($currencies)): ?>
            <div class="empty">No currencies found. <a href="currency_form.php">Add one now</a></div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Unit</th>
                        <th>Import Rate</th>
                        <th>Export Rate</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($currencies as $curr): ?>
                        <tr>
                            <td><strong><?php echo strtoupper($curr['currency_code']); ?></strong></td>
                            <td><?php echo htmlspecialchars($curr['currency_name']); ?></td>
                            <td><?php echo htmlspecialchars($curr['currency_unit']); ?></td>
                            <td><?php echo number_format($curr['rate_import'], 6); ?></td>
                            <td><?php echo number_format($curr['rate_export'], 6); ?></td>
                            <td>
                                <a href="currency_form.php?id=<?php echo $curr['id']; ?>" class="btn btn-edit">Edit</a>
                                <button class="btn btn-history" onclick="openHistory(<?php echo htmlspecialchars(json_encode($curr)); ?>)">History</button>
                                <a href="?delete=<?php echo $curr['id']; ?>" class="btn btn-delete" onclick="return confirm('Deactivate this currency?')">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    
    <!-- History Modal -->
    <div id="historyModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="historyTitle">Rate History</h2>
                <button class="close-modal" onclick="closeHistory()">&times;</button>
            </div>
            <table class="history-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Import Rate</th>
                        <th>Export Rate</th>
                    </tr>
                </thead>
                <tbody id="historyBody">
                </tbody>
            </table>
        </div>
    </div>
    
    <script>
        function openHistory(currency) {
            document.getElementById('historyTitle').textContent = currency.currency_code + ' - Rate History';
            
            // Fetch history via AJAX
            fetch('get_rate_history.php?code=' + currency.currency_code)
                .then(response => response.json())
                .then(data => {
                    const tbody = document.getElementById('historyBody');
                    tbody.innerHTML = '';
                    
                    if (data.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="3" style="text-align:center;">No history found</td></tr>';
                    } else {
                        data.forEach(record => {
                            const row = `<tr>
                                <td>${record.rate_date}</td>
                                <td>${parseFloat(record.rate_import).toFixed(6)}</td>
                                <td>${parseFloat(record.rate_export).toFixed(6)}</td>
                            </tr>`;
                            tbody.innerHTML += row;
                        });
                    }
                    
                    document.getElementById('historyModal').classList.add('active');
                });
        }
        
        function closeHistory() {
            document.getElementById('historyModal').classList.remove('active');
        }
        
        window.onclick = function(event) {
            const modal = document.getElementById('historyModal');
            if (event.target === modal) {
                modal.classList.remove('active');
            }
        }
    </script>
</body>
</html>
