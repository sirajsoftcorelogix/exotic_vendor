<?php
// Assuming controller is loaded and $_GET['id'] contains edit ID if editing
$isEdit = isset($_GET['id']) && !empty($_GET['id']);
$currency = null;
$errors = [];
$successMessage = '';

if ($isEdit) {
    $currency = $currencyController->getCurrencyById($_GET['id']);
    if (!$currency) {
        die('Currency not found');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'currency_code' => trim($_POST['currency_code'] ?? ''),
        'currency_name' => trim($_POST['currency_name'] ?? ''),
        'currency_unit' => trim($_POST['currency_unit'] ?? ''),
        'rate_import' => floatval($_POST['rate_import'] ?? 0),
        'rate_export' => floatval($_POST['rate_export'] ?? 0)
    ];
    
    $errors = $currencyController->validate($data, $isEdit);
    
    if (empty($errors)) {
        if ($isEdit) {
            $result = $currencyController->updateCurrency($_GET['id'], $data);
        } else {
            $result = $currencyController->addCurrency($data);
        }
        
        if ($result['success']) {
            $successMessage = $result['message'];
            if (!$isEdit) {
                $currency = null;
                header('Location: currency_list.php?success=1');
                exit;
            } else {
                $currency = $currencyController->getCurrencyById($_GET['id']);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $isEdit ? 'Edit' : 'Add'; ?> Currency</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f5f5f5; padding: 20px; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { margin-bottom: 20px; color: #333; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; color: #555; }
        input, textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; }
        input:focus { outline: none; border-color: #4CAF50; box-shadow: 0 0 5px rgba(76,175,80,0.3); }
        .form-row { display: flex; gap: 20px; }
        .form-row .form-group { flex: 1; }
        .error { color: #d32f2f; font-size: 12px; margin-top: 5px; }
        .success { color: #388e3c; padding: 10px; background: #e8f5e9; border-radius: 4px; margin-bottom: 20px; }
        .errors { color: #d32f2f; padding: 10px; background: #ffebee; border-radius: 4px; margin-bottom: 20px; }
        .errors ul { margin-left: 20px; }
        button { padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; }
        .btn-submit { background: #4CAF50; color: white; width: 100%; }
        .btn-submit:hover { background: #45a049; }
        .btn-back { background: #757575; color: white; text-decoration: none; display: inline-block; margin-top: 20px; }
        .btn-back:hover { background: #616161; }
        .currency-code-note { font-size: 12px; color: #999; margin-top: 3px; }
    </style>
</head>
<body>
    <div class="container">
        <h1><?php echo $isEdit ? 'Edit' : 'Add'; ?> Currency</h1>
        
        <?php if (!empty($successMessage)): ?>
            <div class="success"><?php echo htmlspecialchars($successMessage); ?></div>
        <?php endif; ?>
        
        <?php if (!empty($errors)): ?>
            <div class="errors">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-row">
                <div class="form-group">
                    <label for="currency_code">Currency Code *</label>
                    <input type="text" id="currency_code" name="currency_code" maxlength="3" 
                           value="<?php echo $currency ? strtoupper($currency['currency_code']) : ''; ?>" 
                           <?php echo $isEdit ? 'readonly' : ''; ?> required>
                    <div class="currency-code-note">3-letter code (e.g., USD, INR, EUR)</div>
                </div>
                <div class="form-group">
                    <label for="currency_name">Currency Name *</label>
                    <input type="text" id="currency_name" name="currency_name" 
                           value="<?php echo $currency ? htmlspecialchars($currency['currency_name']) : ''; ?>" required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="currency_unit">Currency Unit *</label>
                <input type="text" id="currency_unit" name="currency_unit" placeholder="e.g., 1 USD, 100 JPY"
                       value="<?php echo $currency ? htmlspecialchars($currency['currency_unit']) : ''; ?>" required>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="rate_import">Import Rate *</label>
                    <input type="number" id="rate_import" name="rate_import" step="0.000001" min="0"
                           value="<?php echo $currency ? $currency['rate_import'] : ''; ?>" required>
                </div>
                <div class="form-group">
                    <label for="rate_export">Export Rate *</label>
                    <input type="number" id="rate_export" name="rate_export" step="0.000001" min="0"
                           value="<?php echo $currency ? $currency['rate_export'] : ''; ?>" required>
                </div>
            </div>
            
            <button type="submit" class="btn-submit">
                <?php echo $isEdit ? 'Update Currency' : 'Add Currency'; ?>
            </button>
        </form>
        
        <a href="currency_list.php" class="btn-back">Back to List</a>
    </div>
</body>
</html>
