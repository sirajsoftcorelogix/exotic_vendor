<?php
is_login();
require_once 'settings/database/database.php';
$conn = Database::getConnection();
require_once 'models/user/user.php';
$usersModel = new User($conn);
$userDetails = $usersModel->getUserById($_SESSION['user']['id']);
unset($usersModel);

$record_id = $_GET['id'] ?? ''; // get id from URL if editing
?>
<div class="bg-white p-4 md:p-8">
    <?php
    $form1 = $data['form1'] ?? [];
    $category = $form1['category_code'] ?? '';
    $photo    = $form1['product_photo'] ?? '';

    $vendor = $form1['vendor_code'];
    $invoiceImg = $form1['invoice_image'];

    $isEdit  = (!empty($form1['vendor_code']) || !empty($form1['invoice_image']));

    $formAction = $isEdit
    ? base_url('?page=inbounding&action=updateform2&id=' . $record_id)
    : base_url('?page=inbounding&action=saveform2');

    ?>

    <h1><?php echo $isEdit ? "Edit Form 2" : "Form 2"; ?></h1>

<div style="background:#fff; border-radius:6px; padding:10px; margin-top:12px; box-sizing:border-box; border:2px solid #cfcfcf;">
      <div style="display:flex; gap:10px; align-items:flex-start;">
        <!-- thumbnail -->
        <div style="flex:0 0 60px; height:60px; border:1px solid #ddd; display:flex; align-items:center; justify-content:center; padding:4px; box-sizing:border-box; background:#fafafa;">
          <img src="<?php echo base_url($photo); ?>" alt="thumb" style="max-width:100%; max-height:100%; display:block; object-fit:contain;">
        </div>

        <!-- details -->
        <div style="flex:1; font-size:12px; color:#222;">

          <div style="margin-bottom:6px;">
            <span style="display:inline-block; width:78px; font-weight:700;">Category:</span>
            <span style="display:inline-block;"><?php echo htmlspecialchars($category); ?></span>
          </div>

        </div>
      </div>
    </div>

  

<!-- <form action="<?php echo base_url('?page=inbounding&action=saveform2'); ?>" 
      id="save_form2" 
      method="POST" 
      enctype="multipart/form-data"> -->
 <form action="<?php echo $formAction; ?>" method="POST" enctype="multipart/form-data">
    <label for="name">Vendor:</label><br>
    <input type="hidden" name="record_id" value="<?php echo $record_id; ?>">
    <select id="vendor_id" name="vendor_id" style="width:250px;">
            <?php foreach ($data['vendors'] as $v) { ?>
                <option value="<?php echo $v['id']; ?>"
                    <?php echo ($vendor == $v['id']) ? 'selected' : ''; ?>>
                    <?php echo $v['vendor_name']; ?>
                </option>
            <?php } ?>
        </select>
    <br>
    <br>
    <label for="invoice">invoice</label><label for="invoice">Upload invoice:</label><br>
    <input type="file" id="invoice" name="invoice" accept="image/*" capture="camera"><br><br>

        <!-- SHOW OLD INVOICE IMAGE IF EDIT -->
        <?php if ($isEdit && !empty($invoiceImg)) { ?>
            <img id="preview" 
                 src="<?php echo base_url($invoiceImg); ?>" 
                 style="max-width:200px; margin-top:10px;">
        <?php } else { ?>
            <img id="preview" src="" style="display:none; max-width:200px; margin-top:10px;">
        <?php } ?><br><br>
    <button type="button" id="cancel-vendor-btn" class="action-btn cancel-btn">Back</button>
    <button type="submit" class="bg-blue-500 text-white font-semibold py-2 px-4 rounded-md">
            <?php echo $isEdit ? "Update & Next" : "Save & Next"; ?>
        </button>
</form>
</div>
<script>
  const invoiceInput = document.getElementById('invoice');
  const previewImg = document.getElementById('preview');

  invoiceInput.addEventListener('change', function(event) {
    const file = event.target.files[0];
    if (file) {
      const reader = new FileReader();
      reader.onload = function(e) {
        previewImg.src = e.target.result;
        previewImg.style.display = 'block';
      }
      reader.readAsDataURL(file);
    } else {
      previewImg.src = '';
      previewImg.style.display = 'none';
    }
  });
</script>
<script>
  var id = <?php echo json_encode($record_id); ?>;
document.getElementById("cancel-vendor-btn").addEventListener("click", function () {
     window.location.href = window.location.origin + "?page=inbounding&action=form1&id="+id;
});
</script>