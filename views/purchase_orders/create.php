<div class="container">
    <h4 class="">Create Purchase Order</h4>
    <div class="border-dotted my-3" style=""> </div>
    <form action="<?php echo base_url('purchase_orders/create_post'); ?>" id="create_po" method="post">
        <div class="row mb-3">
          <div class="col-sm-2">Vendor :</div>
          <div class="col-sm mr-3">            
            <select id="vendor" name="vendor" class="form-select"  required>
              <option value="">Select Vendor</option>
              <?php foreach ($vendors as $vendor): ?>
                <option value="<?= $vendor['id'] ?>"><?= htmlspecialchars($vendor['contact_name']) ?></option>
              <?php endforeach; ?>          
            </select>
          </div>
          
          <div class="col-sm text-end">            
            <label for="delivery_due_date" class="">Delivery Due Date</label>
          </div>
          <div class="col-sm">
            <input type="date" id="delivery_due_date" name="delivery_due_date" class="" >
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-sm-2">Delivery Address:</div>
            <div class="col-sm">
                <select id="delivery_address" name="delivery_address" class="form-select">
                    <option value="address1">Address 1</option>
                    <option value="address2">Address 2</option>
                    <option value="address3">Address 3</option>
                    <option value="address4">Address 4</option>
                </select>
            </div>
            <div class="col-sm text-end">                 
            </div>
            <div class="col-sm"></div>
        </div>
        <table class="table table-bordered" id="poTable">
          <thead class="table-light">
            <tr>
              <th>S.No</th>
              <th>Title</th>
              <th>HSN</th>
              <th>GST</th>
              <th>Quantity</th>
              <th>Rate</th>              
              <th>Amount</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($data as $index => $item): ?>
            <tr>
              <td><?= $index++ ?></td>
              <td><input type="hidden" name="title[]" value="<?= $item['title'] ?>" > <?= htmlspecialchars($item['title']) ?></td>  
              <td><input type="hidden" name="hsn[]" value="<?= $item['item_code'] ?>"><?= htmlspecialchars($item['item_code']) ?></td>
              <td><input type="number" name="gst[]" class="form-control gst" value="" oninput="calculateTotals()" required></td>
              <td><input type="number" name="quantity[]" class="form-control quantity" value="<?php echo $item['quantity'];  ?>" oninput="calculateTotals()" required></td>
              <td><input type="number" name="rate[]" class="form-control amount" value="" oninput="calculateTotals()" required></td>
              <td class="rowTotal"></td>
            </tr>
            <?php endforeach; ?>
         
          </tbody>
          <!-- <tfoot>
            <tr>
              <th colspan="4" class="text-end">Grand Total</th>
              <th id="grandTotal">0</th>
            </tr>
          </tfoot> -->
        </table>
        <div class="row mb-3">
            <div class="col-sm-8">
                <a href="javascript:void(0)" class="btn btn-primary" onclick="openPOPopup()">+Add Item</a>
            </div>
            <div class="col-sm text-end">
                <label for="subtotal" class="">Sub Total:</label>                
            </div>
            <div class="col-sm">
                <input type="number" name="subtotal" id="subtotal" class="form-control" value="" readonly>
            </div>
        </div>
        <div class="row mb-3 ">
            <div class="col-sm-8">
            </div>
            <div class="col-sm text-end">
                <lable for="GST" class="">Total GST:</lable>                
            </div>
            <div class="col-sm">
                <input type="number" name="total_gst" id="total_gst" class="form-control" value="" readonly>
            </div>
        </div>
        <div class="row mb-3 ">
            <div class="col-sm-8">
            </div>
            <div class="col-sm text-end bg-light">
                <label for="grandTotal" class="">Grand Total:</label>                
            </div> 
            <div class="col-sm bg-light">
                <input type="number" name="grand_total" id="grandTotal" class="form-control" value="" readonly>
            </div>           
            
        </div>    

            
        <div class="row mb-3">            
            <div class="col-sm">
            <label for="note" class="">Note:</label>
            <textarea name="note" class="form-control" rows="3"></textarea>
            </div>
        </div>
        <div class="row mb-3">
            <label for="terms_conditions"> Terms & Conditions :</label>
            <div class="col-sm">
                <input type="text" name="terms_conditions" class="form-control" placeholder="Enter Terms & Conditions" >
            </div>
            
        </div>

        <div class="text-end">
            <!-- <input type="hidden" name="total_amount" id="total_amount" value="">
            <input type="hidden" name="vendor_id" id="vendor_id" value="">
            <input type="hidden" name="delivery_due_date" id="delivery_due_date_hidden" value=""> -->
        </div>
        <button type="submit" class="btn btn-primary">Create Purchase Order</button>
    </form>
</div>
<!-- Create PO Modal -->
<div class="modal fade" id="createPOModal" tabindex="-1" aria-labelledby="createPOModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="createPOModalLabel">Add item to PO</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        
        <table class="table table-bordered" id="poTable">
          <thead>
            <tr>
              <th>SL No</th>
              <th>Title</th>
              <th>Amount</th>
              <th>GST</th>
              <th>Total</th>
            </tr>
          </thead>
          <tbody>
            <!-- Rows injected by JS -->
          </tbody>
          <tfoot>
            
          </tfoot>
        </table>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-success">Add</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>
<script>
function calculateTotals() {
    let subtotal = 0;
    let totalGST = 0;
    let grandTotal = 0;

    document.querySelectorAll("#poTable tbody tr").forEach(tr => {
        const amount = parseFloat(tr.querySelector(".amount").value) || 0;
        const gstPercent = parseFloat(tr.querySelector(".gst").value) || 0;
        const quantity = parseFloat(tr.querySelector(".quantity").value) || 0;

        const lineSubtotal = amount * quantity;
        const gstAmount = (lineSubtotal * gstPercent) / 100;
        const rowTotal = lineSubtotal + gstAmount;

        tr.querySelector(".rowTotal").innerText = rowTotal.toFixed(2);

        subtotal += lineSubtotal;
        totalGST += gstAmount;
        grandTotal += rowTotal;
    });

    document.getElementById("subtotal").value = subtotal.toFixed(2);
    document.getElementById("total_gst").value = totalGST.toFixed(2);
    document.getElementById("grandTotal").value = grandTotal.toFixed(2);
}
    document.querySelectorAll(".gst, .quantity, .amount").forEach(input => {
        input.addEventListener("input", calculateTotals);
    });


function openPOPopup() {
    // Show modal
    new bootstrap.Modal(document.getElementById("createPOModal")).show();
}
document.getElementById("create_po").addEventListener("submit", function(event) {
    event.preventDefault(); // Prevent default form submission
    const formData = new FormData(this);
    
    fetch(<?php echo "'".base_url('?page=purchase_orders&action=create_post')."'"; ?>, {
        method: "POST",
        body: formData
    })
    .then(response => response.json())  
    .then(data => {
        if (data.success) {
            alert("Purchase Order created successfully!");
            window.location.href = "<?php echo base_url('?page=purchase_orders&acton=list'); ?>"; // Redirect to the list page
        } else {
            alert("Error: " + data.message);  
        }
    })
    .catch(error => {
        console.error("Error:", error);
        alert("An error occurred while creating the Purchase Order.");
    });
});
</script>