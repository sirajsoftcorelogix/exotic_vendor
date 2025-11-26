<?php
if ($form2['vendor_code']) {
    is_login();
    require_once 'settings/database/database.php';
    $conn = Database::getConnection();
    require_once 'models/user/user.php';
    $usersModel = new User($conn);
    $currentuserDetails = $usersModel->getUserById($_SESSION['user']['id']);
    unset($usersModel);
}
?>
<div class="container">
<div class="bg-white p-4 md:p-8">
    <?php
    $record_id = $_GET['id'] ?? '';
    $form2 = $data['form2'] ?? [];
    $category = $form2['category_code'] ?? '';
    $photo    = $form2['product_photo'] ?? '';
    $temp_code    = $form2['temp_code'] ?? '';
    $vendor_name    = $form2['vendor_name'] ?? '';


    $gate_entry_date_time = $form2['gate_entry_date_time'] ?? '';
    $material_code = $form2['material_code'] ?? '';
    $height = $form2['height'] ?? '';
    $width = $form2['width'] ?? '';
    $depth = $form2['depth'] ?? '';
    $weight = $form2['weight'] ?? '';
    $color = $form2['color'] ?? '';
    $quantity_received = $form2['quantity_received'] ?? '';
    $Item_code = $form2['Item_code'] ?? '';
    $isEdit = (!empty($gate_entry_date_time) ||
               !empty($material_code) ||
               !empty($height) ||
               !empty($width) ||
               !empty($depth) ||
               !empty($weight) ||
               !empty($color) ||
               !empty($quantity_received) ||
               !empty($Item_code));
    $formAction = $isEdit
    ? base_url('?page=inbounding&action=updateform3&id=' . $record_id)
    : base_url('?page=inbounding&action=saveform3');
    ?>

<h1><?php echo $isEdit ? "Edit Form 3" : "Form 3"; ?></h1>
<div style="background:#fff; border-radius:6px; padding:10px; margin-top:12px; box-sizing:border-box; border:2px solid #cfcfcf;">
      <div style="display:flex; gap:10px; align-items:flex-start;">
        <!-- thumbnail -->
        <div style="flex:0 0 60px; height:60px; border:1px solid #ddd; display:flex; align-items:center; justify-content:center; padding:4px; box-sizing:border-box; background:#fafafa;">
          <img src="<?php echo base_url($photo); ?>" alt="thumb" style="max-width:100%; max-height:100%; display:block; object-fit:contain;">
        </div>

        <!-- details -->
        <div style="flex:1; font-size:12px; color:#222;">
          <div style="margin-bottom:6px;">
            <span style="display:inline-block; width:78px; font-weight:700;">Temp Code:</span>
            <span style="display:inline-block;"><?php echo htmlspecialchars($temp_code); ?></span>
          </div>

          <div style="margin-bottom:6px;">
            <span style="display:inline-block; width:78px; font-weight:700;">Category:</span>
            <span style="display:inline-block;"><?php echo htmlspecialchars($category); ?></span>
          </div>

          <div>
            <span style="display:inline-block; width:78px; font-weight:700;">Vendor :</span>
            <span style="display:inline-block;"><?php echo htmlspecialchars($vendor_name); ?></span>
          </div>
        </div>
      </div>
    </div>

<form action="<?php echo $formAction; ?>" 
      id="save_form2" 
      method="POST" 
      enctype="multipart/form-data">
    <!-- Gate Entry Date & Time -->
  <div style="margin-bottom:18px;">
    <label style="font-size:14px; font-weight:600; display:block; margin-bottom:6px;">
      Gate Entry Date & Time:
      <span style="border-bottom:1px solid #555; width:100%; display:block; margin-top:4px;"></span>
    </label>
    <div style="display:flex; align-items:center; gap:6px;">

    <?php if (!empty($gate_entry_date_time) && $gate_entry_date_time != "0000-00-00 00:00:00"): ?>

        <!-- READONLY FIELD — USE DB VALUE -->
        <input type="datetime-local"
               name="gate_entry_date_time"
               value="<?php echo date('Y-m-d\TH:i', strtotime($gate_entry_date_time)); ?>"
               readonly
               style="width:100%; padding:8px; border:1px solid #ccc; border-radius:4px;
                      font-size:14px; background:#e9e9e9; cursor:not-allowed;">
    <?php else: ?>
        <!-- EDITABLE FIELD — USE CURRENT DATETIME -->
        <input type="datetime-local"
               name="gate_entry_date_time"
               value="<?php echo date('Y-m-d\TH:i'); ?>"
               style="width:100%; padding:8px; border:1px solid #ccc; border-radius:4px;
                      font-size:14px;">
    <?php endif; ?>

</div>




  </div>

  <!-- Received By -->
  <div style="margin-bottom:18px;">
    <label style="font-size:14px; font-weight:600; display:block; margin-bottom:6px;">
      Received By:
      <span style="border-bottom:1px solid #555; width:100%; display:block; margin-top:4px;"></span>
    </label>
    <input type="hidden" name="record_id" value="<?php echo $record_id; ?>">
    <input type="hidden" name="received_by_user_id" value="<?php echo $_SESSION['user']['id']; ?>">
    <input type="text" name="received_by_name" value="<?php echo htmlspecialchars($currentuserDetails['name']); ?>" readonly 
      style="width:100%; padding:8px; border:1px solid #ccc; border-radius:4px; font-size:14px; background:#e9e9e9;">
  </div>

  <!-- Dimensions -->
  <div style="margin-bottom:12px;">
    <label style="font-size:14px; font-weight:600; display:block; margin-bottom:6px;">
      Dimensions:
      <span style="border-bottom:1px solid #555; width:100%; display:block; margin-top:4px;"></span>
    </label>
  </div>

  <!-- Material -->
  <div style="margin-bottom:12px;">
      <label><strong>Material</strong></label>
      <select name="material_code" style="width:100%; padding:8px; border: 1px solid;">
          <option value="">Select Material</option>
          <?php
          $materials = ["Brass","Copper","Bronze","Marble","Wood","Stone","Other"];
          foreach ($materials as $m) {
              $sel = ($material_code == $m) ? "selected" : "";
              echo "<option value='$m' $sel>$m</option>";
          }
          ?>
      </select>
  </div>


  <!-- Height -->
    <div style="margin-bottom:12px; display:flex; gap:6px;">
      <div style="flex:1;">
          <label><strong>Height</strong></label>
          <input type="text" name="height" value="<?php echo $height; ?>"
              style="width:100%; padding:8px; border: 1px solid;">
      </div>
      <span>cm</span>
  </div>

  <!-- Width -->
  <div style="margin-bottom:12px; display:flex; gap:6px;">
      <div style="flex:1;">
          <label><strong>Width</strong></label>
          <input type="text" name="width" value="<?php echo $width; ?>"
              style="width:100%; padding:8px; border: 1px solid;">
      </div>
      <span>cm</span>
  </div>

  <!-- Depth -->
  <div style="margin-bottom:12px; display:flex; gap:6px;">
    <div style="flex:1;">
        <label><strong>Depth</strong></label>
        <input type="text" name="depth" value="<?php echo $depth; ?>"
            style="width:100%; padding:8px; border: 1px solid;">
    </div>
    <span>cm</span>
</div>

  <!-- Weight -->
  <div style="margin-bottom:12px; display:flex; gap:6px;">
    <div style="flex:1;">
        <label><strong>Weight</strong></label>
        <input type="text" name="weight" value="<?php echo $weight; ?>"
            style="width:100%; padding:8px; border: 1px solid;">
    </div>
    <span>kg</span>
</div>

  <!-- Color -->
  <div style="margin-bottom:12px; display:flex; gap:6px;">
    <div style="flex:1;">
        <label><strong>Color</strong></label>
        <input type="text" name="color" value="<?php echo $color; ?>"
            style="width:100%; padding:8px; border: 1px solid;">
    </div>
</div>

  <!-- Quantity -->
  <div style="margin-bottom:12px; display:flex; gap:6px;">
    <div style="flex:1;">
        <label><strong>Quantity</strong></label>
        <input type="text" name="quantity_received" value="<?php echo $quantity_received; ?>"
            style="width:100%; padding:8px; border: 1px solid;">
    </div>
</div>

  <!-- Iteam Code -->
  <div style="margin-bottom:12px; display:flex; gap:6px;">
    <div style="flex:1;">
        <label><strong>Item Code</strong></label>
        <input type="text" name="Item_code" value="<?php echo $Item_code; ?>"
            style="width:100%; padding:8px; border: 1px solid;">
    </div>
</div>
  <button type="button" id="cancel-vendor-btn" class="action-btn cancel-btn">Back</button>
   <button type="submit" class="bg-blue-500 text-white font-semibold py-2 px-4 rounded-md">
            <?php echo $isEdit ? "Update & Next" : "Save & Next"; ?>
        </button>
</form>
</div>
</div>
<script>
var id = <?php echo json_encode($record_id); ?>;

document.getElementById("cancel-vendor-btn").addEventListener("click", function () {
    window.location.href = window.location.origin + "/index.php?page=inbounding&action=form2&id=" + id;
});

</script>