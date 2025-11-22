<?php 
$label_data = $data['form2'] ?? []; 

is_login();

// DB Connection
require_once 'settings/database/database.php';
$conn = Database::getConnection();

// User model
require_once 'models/user/user.php';
$usersModel = new User($conn);

// Safe user ID check
$user_id = $label_data['received_by_user_id'] ?? null;
$userDetails = $user_id ? $usersModel->getUserById($user_id) : [];

unset($usersModel);

// Safe output helper
function safe($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}
?>

<!-- Print Button -->
<div style="text-align:center; margin-top:20px;">
    <button onclick="generatePDF()" 
        style="padding:10px 18px; background:#d17125; border:none; color:white;
               border-radius:6px; font-size:16px; cursor:pointer;">
        Print / Save PDF
    </button>
</div>

<!-- Responsive Container -->
<div id="printArea"
    style="
        background:#fff;
        border-radius:25px;
        overflow:hidden;
        box-shadow:0 4px 12px rgba(0,0,0,0.2);
        margin:20px auto;
        padding-bottom:10px;
        width:95%;
        max-width:600px;
    ">

    <div style="background:#d17125; color:#fff; padding:12px; font-size:20px; 
         text-align:center; font-weight:bold;">
        Print Label - Step: 4/4
    </div>

    <div style="padding:20px; border:1px solid #ccc; margin:12px; background:#fff; border-radius:8px;">

        <div style="display:flex; gap:15px; flex-wrap:wrap;">
            
            <img src="<?php echo base_url(safe($label_data['product_photo'] ?? '')); ?>" 
                 style="width:120px; border-radius:6px; border:1px solid #ccc;">

            <div style="min-width:150px; flex:1;">

                <p style="margin:3px 0; font-size:14px;">
                    <strong>Gate Entry Date & Time:</strong><br>
                    <?php echo safe($label_data['gate_entry_date_time'] ?? ''); ?>
                </p>

                <p style="margin:3px 0; font-size:14px;">
                    <strong>Received By:</strong> 
                    <?php echo safe($userDetails['name'] ?? ''); ?>
                </p>

                <p style="margin:3px 0; font-size:14px;"><strong>Category:</strong> <?php echo safe($label_data['category_code'] ?? ''); ?></p>
                <p style="margin:3px 0; font-size:14px;"><strong>Material:</strong> <?php echo safe($label_data['material_code'] ?? ''); ?></p>
                <p style="margin:3px 0; font-size:14px;"><strong>Quantity:</strong> <?php echo safe($label_data['quantity_received'] ?? ''); ?> Nos</p>

            </div>
        </div>

        <div style="display:flex; justify-content:space-between; margin:10px 0; flex-wrap:wrap;">
            <div style="width:48%;">
                <p style="margin:4px 0; font-size:14px;"><strong>Height:</strong> <?php echo safe($label_data['height'] ?? ''); ?> cm</p>
                <p style="margin:4px 0; font-size:14px;"><strong>Depth:</strong> <?php echo safe($label_data['depth'] ?? ''); ?> cm</p>
                <p style="margin:4px 0; font-size:14px;"><strong>Size:</strong> <?php echo safe($label_data['size'] ?? ''); ?></p>
            </div>

            <div style="width:48%;">
                <p style="margin:4px 0; font-size:14px;"><strong>Width:</strong> <?php echo safe($label_data['width'] ?? ''); ?> cm</p>
                <p style="margin:4px 0; font-size:14px;"><strong>Weight:</strong> <?php echo safe($label_data['weight'] ?? ''); ?> kg</p>
                <p style="margin:4px 0; font-size:14px;"><strong>Color:</strong> <?php echo safe($label_data['color'] ?? ''); ?></p>
            </div>
        </div>

        <div>
            <p style="margin:4px 0; font-size:14px;"><strong>Temp Code:</strong> <?php echo safe($label_data['temp_code'] ?? ''); ?></p>
            <p style="margin:4px 0; font-size:14px;"><strong>Vendor:</strong> <?php echo safe($label_data['vendor_name'] ?? ''); ?></p>
        </div>

        <div style="text-align:right; margin-top:10px;">
            <img src="https://via.placeholder.com/100" style="width:100px; height:100px;">
        </div>

    </div>
</div>

<!-- JS: PDF Generator -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

<script>
function generatePDF() {
    const { jsPDF } = window.jspdf;

    html2canvas(document.querySelector("#printArea"), { scale: 2 }).then(canvas => {
        const imgData = canvas.toDataURL("image/png");
        const pdf = new jsPDF("p", "mm", "a4");

        let imgWidth = 180;
        let imgHeight = canvas.height * imgWidth / canvas.width;

        pdf.addImage(imgData, "PNG", 15, 15, imgWidth, imgHeight);
        pdf.save("label.pdf");

        pdf.autoPrint();
        window.open(pdf.output('bloburl'), '_blank');
    });
}
</script>
