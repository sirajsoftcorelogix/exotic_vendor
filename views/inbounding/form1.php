<?php
$form1 = $data['form1'] ?? [];
$isEdit = !empty($form1);
$id       = $form1['id'] ?? '';
$category = $form1['category_code'] ?? '';
$photo    = $form1['product_photo'] ?? '';
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
      <div id="category-error" style="color:red; font-size:14px; margin-top:5px;"></div>
      <br><br>
      <label for="photo">Upload Photo:</label><br>
      <input type="file" id="photo" name="photo" accept="image/*" capture="camera"><br><br>
      <div id="photo-error" style="color:red; font-size:14px; margin-top:5px;"></div>
      <?php if ($isEdit && !empty($photo)) { ?>
          <img id="preview" src="<?php echo base_url($photo); ?>"
               style="max-width:200px; margin-top:10px;">
      <?php } else { ?>
          <img id="preview" src="" style="display:none; max-width:200px; margin-top:10px;">
      <?php } ?>
      <br><br>
      <button type="button" id="cancel-vendor-btn" class="action-btn cancel-btn">Back</button>
      <button type="submit" class="bg-blue-500 text-white font-semibold py-2 px-4 rounded-md">
        <?php echo $isEdit ? "Update Changes" : "Save Changes"; ?>
      </button>
    </form>
</div>
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
<script>
document.getElementById("cancel-vendor-btn").addEventListener("click", function () {
    window.location.href = window.location.origin + "/index.php?page=inbounding&action=list";
});
</script>
<script>
document.getElementById("add_role").addEventListener("submit", function (e) {
    let isValid = true;

    // Reset errors
    document.getElementById("category-error").innerHTML = "";
    document.getElementById("photo-error").innerHTML = "";

    // Validate Category
    const categoryChecked = document.querySelector('input[name="category"]:checked');
    if (!categoryChecked) {
        document.getElementById("category-error").innerHTML = "Please select a category.";
        isValid = false;
    }

    // Validate Photo (required only on ADD)
    const isEdit = "<?php echo $isEdit ? '1' : '0'; ?>";
    const photoInput = document.getElementById("photo");
    const hasPhoto = photoInput.files.length > 0;

    if (isEdit === "0" && !hasPhoto) {
        document.getElementById("photo-error").innerHTML = "Please upload a product photo.";
        isValid = false;
    }

    // Stop submit if validation fails
    if (!isValid) {
        e.preventDefault();
    }
});

// Live Remove Error When User Fixes Input
document.querySelectorAll('input[name="category"]').forEach(radio => {
    radio.addEventListener("change", function () {
        document.getElementById("category-error").innerHTML = "";
    });
});

document.getElementById("photo").addEventListener("change", function () {
    if (this.files.length > 0) {
        document.getElementById("photo-error").innerHTML = "";
    }
});
</script>
