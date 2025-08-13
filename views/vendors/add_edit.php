<div class="container">
<?php
if (isset($data['vendor']['id'])) {
    echo "<h3>Edit Vendor: " . htmlspecialchars($data['vendor']['contact_name']) . "</h3>";

?>

<form id="addVendorForm">
  <div class="row mb-3">
    <div class="col-md-6">
      <label for="inputName4" class="form-label">Contact Name</label>
      <input type="text" id="contact_name" name="contact_name" class="form-control" value="<?= htmlspecialchars($data['vendor']['contact_name'] ?? '') ?>" required>
    </div>
    <div class="col-md-6">
      <label for="inputEmail4" class="form-label">Vendor Email</label>
      <input type="email" id="email" name="vendor_email" class="form-control" value="<?= htmlspecialchars($data['vendor']['vendor_email'] ?? '') ?>" required>
    </div>
  </div>
  <div class="row mb-3">
    <div class="col-md-6">
      <label for="inputCompany4" class="form-label">Company Name</label>
      <input type="text" id="company_name" name="company_name" class="form-control" value="<?= htmlspecialchars($data['vendor']['company_name'] ?? '') ?>" required>
    </div>
    <div class="col-md-6">
      <label for="inputPhone4" class="form-label ">Vendor Phone</label>
        <input type="text" id="phone" name="phone" class="form-control" value="<?= htmlspecialchars($data['vendor']['vendor_phone'] ?? '') ?>" required>
    </div>
  </div>
  <div class="row mb-3">
    <div class="col-md-6">
        <lable for="gst_number" class="form-label">GST Number</lable>
        <input type="text" id="gst_number" name="gst_number" class="form-control" value="<?= htmlspecialchars($data['vendor']['gst_number'] ?? '') ?>" required>
    </div>  
    <div class="col-md-6">
        <label for="pan_number" class="form-label ">PAN Number</label>
        <input type="text" id="pan_number" name="pan_number" class="form-control" value="<?= htmlspecialchars($data['vendor']['pan_number'] ?? '') ?>" required>
    </div>
    </div>  
    <div class="row mb-3">
    <div class="col-md-6">  
        <label for="address" class="form-label">Address</label>
        <textarea id="address" name="address" class="form-control" rows="3" required><?= htmlspecialchars($data['vendor']['address'] ?? '') ?></textarea>
    </div>
    <div class="col-md-6">
        <label for="city" class="form-label">City</label>
        <input type="text" id="city" name="city" class="form-control" value="<?= htmlspecialchars($data['vendor']['city'] ?? '') ?>" required>
    </div>
    </div>
    <div class="row mb-3">
    <div class="col-md-6">
        <label for="state" class="form-label">State</label>
        <input type="text" id="state" name="state" class="form-control" value="<?= htmlspecialchars($data['vendor']['state'] ?? '') ?>" required>   
    </div>  
    <div class="col-md-6">
        <label for="country" class="form-label">Country</label>
        <input type="text" id="country" name="country" class="form-control" value="<?= htmlspecialchars($data['vendor']['country'] ?? '') ?>" required>
    </div>
    </div>
    <div class="row mb-3">
    <div class="col-md-6">
        <lable for="postal_code" class="form-label">Postal Code</lable>
        <input type="text" id="postal_code" name="postal_code" class="form-control" value="<?= htmlspecialchars($data['vendor']['postal_code'] ?? '') ?>" required>
    </div>
    <div class="col-md-6">
        <label for="business_type" class="form-label">Business Type</label>
        <select id="business_type" name="business_type" class="form-select" required>
            <option value="manufacturer" <?= isset($data['vendor']['business_type']) && $data['vendor']['business_type'] == 'manufacturer' ? 'selected' : '' ?>>Manufacturer</option>
            <option value="wholesaler" <?= isset($data['vendor']['business_type']) && $data['vendor']['business_type'] == 'wholesaler' ? 'selected' : '' ?>>Wholesaler</option>
            <option value="retailer" <?= isset($data['vendor']['business_type']) && $data['vendor']['business_type'] == 'retailer' ? 'selected' : '' ?>>Retailer</option>
            <option value="service_provider" <?= isset($data['vendor']['business_type']) && $data['vendor']['business_type'] == 'service_provider' ? 'selected' : '' ?>>Service Provider</option>
        </select>
    </div>
    </div>
    <div class="row mb-3">
    <div class="col-md-6">
        <label for="document_path" class="form-label">Document Path</label>
        <input type="text" id="document_path" name="document_path" class="form-control" value="<?= htmlspecialchars($data['vendor']['document_path'] ?? '') ?>" required>
    </div>
    <div class="col-md-6">
        <label for="logo_path" class="form-label">Logo Path</label>
        <input type="text" id="logo_path" name="logo_path" class="form-control" value="<?= htmlspecialchars($data['vendor']['logo_path'] ?? '') ?>" required>
    </div>
    </div>
    <div class="row mb-3">
    <div class="col-md-6">
        <lable for="email_verified_at" class="form-label">Email Verified At</lable>
        <input type="datetime-local" id="email_verified_at" name="email_verified_at" class="form-control" value="<?= isset($data['vendor']['email_verified_at']) ? date('Y-m-d\TH:i', strtotime($data['vendor']['email_verified_at'])) : '' ?>">
    </div>
    <div class="col-md-6">
        <label for="is_active" class="form-label">Active</label>
        <select id="is_active" name="is_active" class="form-select">
            <option value="1" <?= isset($data['vendor']['is_active']) && $data['vendor']['is_active'] ? 'selected' : '' ?>>Yes</option> 
            <option value="0" <?= isset($data['vendor']['is_active']) && !$data['vendor']['is_active'] ? 'selected' : '' ?>>No</option>
        </select>
    </div>
    </div>
    <input type="hidden" name="id" value="<?= $data['vendor']['id'] ?? '' ?>">
    <button type="submit" class="btn btn-secondary">Save Vendor</button>
</form>

<script>
document.getElementById('addVendorForm').addEventListener('submit', function(event) {
    event.preventDefault();
    const formData = new FormData(this);
    fetch('index.php?page=vendors&action=addPost', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            window.location.href = 'index.php?page=vendors&action=list';
        } else {
            alert(data.message);
        }
    })  
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while saving the vendor.');
    });

});
</script>

</div>
<?php
} else {
    echo "<h3>Add New Vendor</h3>";
?>
<form id="addVendorForm">
  <div class="row mb-3">
    <div class="col-md-6">
      <label for="inputName4" class="form-label">Contact Name</label>
      <input type="text" id="contact_name" name="contact_name" class="form-control" required>
    </div>
    <div class="col-md-6">
        <label for="inputEmail4" class="form-label">Vendor Email</label>
        <input type="email" id="email" name="vendor_email" class="form-control" required>
    </div>
    </div>
    <div class="row mb-3">
    <div class="col-md-6">
        <label for="inputCompany4" class="form-label">Company Name</label>
        <input type="text" id="company_name" name="company_name" class="form-control" required>
    </div>
    <div class="col-md-6">
        <label for="inputPhone4" class="form-label">Vendor Phone</label>
        <input type="text" id="phone" name="phone" class="form-control" required>
    </div>
    </div>
    <div class="row mb-3">
    <div class="col-md-6">
        <lable for="gst_number" class="form-label">GST Number</lable>
        <input type="text" id="gst_number" name="gst_number" class="form-control" required> 
    </div>
    <div class="col-md-6">
        <label for="pan_number" class="form-label">PAN Number</label>
        <input type="text" id="pan_number" name="pan_number" class="form-control" required>
    </div>
    </div>
    <div class="row mb-3">
    <div class="col-md-6">
        <label for="address" class="form-label">Address</label>
        <textarea id="address" name="address" class="form-control" rows="3" required></textarea>
    </div>
    <div class="col-md-6">
        <label for="city" class="form-label">City</label>
        <input type="text" id="city" name="city" class="form-control" required>
    </div>
    </div>
    <div class="row mb-3">
    <div class="col-md-6">
        <label for="state" class="form-label">State</label>
        <input type="text" id="state" name="state" class="form-control" required>
    </div>
    <div class="col-md-6">
        <label for="country" class="form-label">Country</label>

        <input type="text" id="country" name="country" class="form-control" required>
    </div>
    </div>
    <div class="row mb-3">
    <div class="col-md-6">
        <lable for="postal_code" class="form-label">Postal Code</lable>
        <input type="text" id="postal_code" name="postal_code" class="form-control" required>
    </div>
    <div class="col-md-6">
        <label for="business_type" class="form-label">Business Type</label>
        <select id="business_type" name="business_type" class="form-select" required>
            <option value="manufacturer">Manufacturer</option>
            <option value="wholesaler">Wholesaler</option>
            <option value="retailer">Retailer</option>
            <option value="service_provider">Service Provider</option>
        </select>
    </div>
    </div>
    <div class="row mb-3">
    <div class="col-md-6">
        <label for="document_path" class="form-label">Document Path</label>
        <input type="text" id="document_path" name="document_path" class="form-control" required>
    </div>
    <div class="col-md-6">
        <label for="logo_path" class="form-label">Logo Path</label>
        <input type="text" id="logo_path" name="logo_path" class="form-control" required>
    </div>
    </div>
    <div class="row mb-3">
    <div class="col-md-6">
        <lable for="email_verified_at" class="form-label">Email Verified At</lable>
        <input type="datetime-local" id="email_verified_at" name="email_verified_at" class="form-control">
    </div>  
    <div class="col-md-6">
        <label for="is_active" class="form-label">Active</label>
        <select id="is_active" name="is_active" class="form-select">
            <option value="1">Yes</option>
            <option value="0">No</option>
        </select>
    </div>
    </div>
    <button type="submit" class="btn btn-secondary">Add Vendor</button>
</form>
<script>
document.getElementById('addVendorForm').addEventListener('submit', function(event) {
    event.preventDefault(); 
    const formData = new FormData(this);
    // Optionally log form data keys for debugging
    //for (let pair of formData.entries()) { console.log(pair[0]+ ': ' + pair[1]); }
    
    fetch('index.php?page=vendors&action=addPost', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(text => {
        try {
            const data = JSON.parse(text);
            if (data.success) {
                alert(data.message);
                window.location.href = 'index.php?page=vendors&action=list';
            } else {
                alert(data.message);
            }
        } catch (e) {
            console.error('Response is not valid JSON:', text);
            alert('Server error: ' + text);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while saving the vendor.');
    });
    
});     
</script>
<?php
}
?>
</div>


    