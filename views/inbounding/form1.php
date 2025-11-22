<?php
$form1 = $data['form1'] ?? [];
$isEdit = !empty($form1);

// Form variables
$id       = $form1['id'] ?? '';
$category = $form1['category_code'] ?? '';
$photo    = $form1['product_photo'] ?? '';  // Adjust if your key name differs

// Action URL (save or update)
$actionUrl = $isEdit
    ? base_url('?page=inbounding&action=updateform1&id=' . $id)
    : base_url('?page=inbounding&action=saveform1');
?>
<div class="bg-white p-4 md:p-8">
    <h1><?php echo $isEdit ? "Edit Inbounding" : "Add Inbounding"; ?></h1>

    <form action="<?php echo $actionUrl; ?>" 
          id="add_role" 
          method="POST" 
          enctype="multipart/form-data">

      <label for="Category">Category</label>
      <p>Select Category:</p>

      <!-- CATEGORY OPTIONS -->
      <?php
      $categories = [
          "technology" => "Jewelry",
          "business"   => "Painting",
          "education"  => "Statue",
          "health"     => "Clothing",
          "books"      => "Books",
          "homedecor"  => "Home Decor",
      ];

      foreach ($categories as $value => $label) { ?>
          <label>
              <input type="radio" name="category" value="<?= $value ?>"
                  <?php if ($category == $value) echo 'checked'; ?>>
              <?= $label ?>
          </label><br>
      <?php } ?>

      <br><br>

      <label for="photo">Upload Photo:</label><br>
      <input type="file" id="photo" name="photo" accept="image/*" capture="camera"><br><br>

      <!-- IMAGE PREVIEW -->
      <?php if ($isEdit && !empty($photo)) { ?>
          <img id="preview" src="<?php echo base_url($photo); ?>"
               style="max-width:200px; margin-top:10px;">
      <?php } else { ?>
          <img id="preview" src="" style="display:none; max-width:200px; margin-top:10px;">
      <?php } ?>

      <br><br>

      <!-- BUTTONS -->
      <button type="button" id="cancel-vendor-btn" class="action-btn cancel-btn">Back</button>

      <button type="submit" class="bg-blue-500 text-white font-semibold py-2 px-4 rounded-md">
        <?php echo $isEdit ? "Update Changes" : "Save Changes"; ?>
      </button>

    </form>
</div>

<!-- IMAGE PREVIEW SCRIPT -->
<script>
const photoInput = document.getElementById('photo');
const previewImg = document.getElementById('preview');

photoInput.addEventListener('change', function(event) {
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

<!-- BACK BUTTON GOES TO LIST ALWAYS -->
<script>
document.getElementById("cancel-vendor-btn").addEventListener("click", function () {
    window.location.href = window.location.origin + "/index.php?page=inbounding&action=list";
});
</script>
