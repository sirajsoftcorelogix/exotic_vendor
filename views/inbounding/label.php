<?php
// 1. PHP Logic & Data Fetching
$label_data = $data['form2'] ?? []; 

// If accessing directly without previous data, try to fetch from DB based on ID
if(empty($label_data) && isset($_GET['id'])) {
    is_login();
    require_once 'settings/database/database.php';
    $conn = Database::getConnection();
    
    // Fetch logic here if needed, otherwise relying on $data passed from controller
    // Assuming $data is populated from controller for now as per previous context
}

// User model for "Received By" name
if(!isset($userDetails)) {
    require_once 'models/user/user.php';
    if(!isset($conn)) $conn = Database::getConnection();
    $usersModel = new User($conn);
    $user_id = $label_data['received_by_user_id'] ?? $_SESSION['user']['id'] ?? 0;
    $userDetails = $usersModel->getUserById($user_id);
    unset($usersModel);
}

// Safe output helper
function safe($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

// Data Preparation
$photoUrl = base_url(safe($label_data['product_photo'] ?? 'assets/images/placeholder.png'));
$currentUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

<div class="w-full flex items-center justify-center p-0 md:p-6 bg-gray-100 min-h-screen">

    <div class="w-full h-screen md:h-auto md:min-h-[700px] md:max-w-2xl bg-white md:rounded-2xl shadow-2xl overflow-hidden flex flex-col relative border border-gray-200">
        
        <div class="bg-[#d9822b] px-4 py-4 flex items-center justify-between text-white shadow-md z-20 flex-shrink-0">
            <button type="button" id="back-btn" class="p-2 hover:bg-white/20 rounded-full transition">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-6 h-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                </svg>
            </button>
            <h1 class="font-semibold text-lg tracking-wide">Print Label - Step: 4/4</h1>
            <div class="w-6"></div> 
        </div>

        <div class="flex-1 overflow-y-auto p-4 md:p-8 bg-gray-100 flex flex-col items-center justify-center">
            
            <div id="printArea" class="bg-white border-2 border-gray-800 p-4 w-full max-w-[400px] shadow-sm relative">
                
                <div class="flex gap-4 mb-4">
                    <div class="w-28 h-28 border border-gray-300 rounded-md flex-shrink-0 overflow-hidden bg-gray-50 flex items-center justify-center">
                         <img src="<?php echo $photoUrl; ?>" class="w-full h-full object-contain" crossorigin="anonymous">
                    </div>

                    <div class="flex-1 text-[11px] leading-relaxed text-gray-800">
                        <div class="mb-1">
                            <span class="font-bold">Gate Entry Date & Time:</span><br>
                            <span class="text-gray-600"><?php echo safe($label_data['gate_entry_date_time'] ?? '-'); ?></span>
                        </div>
                        <div class="mb-1">
                            <span class="font-bold">Received By:</span> 
                            <span class="text-gray-600"><?php echo safe($userDetails['name'] ?? 'Unknown'); ?></span>
                        </div>
                        <div class="mb-1">
                            <span class="font-bold">Category:</span> 
                            <span class="text-gray-600"><?php echo safe($label_data['category_code'] ?? '-'); ?></span>
                        </div>
                        <div class="mb-1">
                            <span class="font-bold">Material:</span> 
                            <span class="text-gray-600"><?php echo safe($label_data['material_code'] ?? '-'); ?></span>
                        </div>
                        <div>
                            <span class="font-bold">Quantity:</span> 
                            <span class="text-gray-600 font-semibold"><?php echo safe($label_data['quantity_received'] ?? '0'); ?> Nos</span>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-x-2 gap-y-1 text-[11px] font-bold text-gray-800 border-t border-b border-gray-200 py-2 mb-3">
                    <div>Height: <span class="font-normal"><?php echo safe($label_data['height'] ?? '-'); ?> cm</span></div>
                    <div>Width: <span class="font-normal"><?php echo safe($label_data['width'] ?? '-'); ?> cm</span></div>
                    <div>Depth: <span class="font-normal"><?php echo safe($label_data['depth'] ?? '-'); ?> cm</span></div>
                    <div>Weight: <span class="font-normal"><?php echo safe($label_data['weight'] ?? '-'); ?> kg</span></div>
                    <div>Size: <span class="font-normal">XL</span> </div>
                    <div>Color: <span class="font-normal"><?php echo safe($label_data['color'] ?? '-'); ?></span></div>
                </div>

                <div class="flex justify-between items-end">
                    <div class="text-[11px] font-bold text-gray-800 space-y-1">
                        <div>
                            Temp Code: <span class="font-normal"><?php echo safe($label_data['temp_code'] ?? 'N/A'); ?></span>
                        </div>
                        <div>
                            Vendor : <span class="font-normal"><?php echo safe($label_data['vendor_name'] ?? 'N/A'); ?></span>
                        </div>
                    </div>
                    
                    <div id="qrcode" class="border border-gray-200 p-1 bg-white"></div>
                </div>

            </div>
            <p class="text-xs text-gray-400 mt-4">Preview of Label (4x6 inch approx)</p>

        </div>

        <div class="p-5 bg-white border-t border-gray-100 mt-auto z-20">
            <button onclick="generatePDF()" class="w-full bg-[#d9822b] hover:bg-[#bf7326] text-white font-bold text-lg py-3 rounded-xl shadow-lg transform transition active:scale-[0.99] flex justify-center items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5zm-3 0h.008v.008H15V10.5z" />
                </svg>
                Print Label
            </button>
        </div>

    </div>
</div>

<script>
    // 1. Generate QR Code on Load
    // We put it inside a setTimeout to ensure DOM is ready
    setTimeout(() => {
        const qrContainer = document.getElementById("qrcode");
        // Clear previous if any
        qrContainer.innerHTML = "";
        
        // Generate QR
        new QRCode(qrContainer, {
            text: "<?php echo $currentUrl; ?>",
            width: 70,   // Small size to fit layout
            height: 70,
            colorDark : "#000000",
            colorLight : "#ffffff",
            correctLevel : QRCode.CorrectLevel.H
        });
    }, 100);

    // 2. Back Button Logic
    // Go back to Form 3
    const recordId = "<?php echo isset($_GET['id']) ? $_GET['id'] : ''; ?>";
    document.getElementById("back-btn").addEventListener("click", function () {
        window.location.href = window.location.origin + "/index.php?page=inbounding&action=form3&id=" + recordId;
    });

    // 3. PDF Generation Logic (Adjustable Sizes)
    function generatePDF() {
        const { jsPDF } = window.jspdf;
        const element = document.querySelector("#printArea");

        // High resolution capture
        html2canvas(element, { 
            scale: 3, // Higher scale for better text clarity
            useCORS: true // Ensure external images load (like the product photo)
        }).then(canvas => {
            const imgData = canvas.toDataURL("image/png");
            
            // PDF SETTINGS
            // 'p' = portrait, 'mm' = units
            // A6 is a common label size (105mm x 148mm). Adjust numbers below for custom label size.
            // For example: 4x6 inch label is approx 101.6mm x 152.4mm
            const pdfWidth = 100; 
            const pdfHeight = 150; 
            
            const pdf = new jsPDF("p", "mm", [pdfWidth, pdfHeight]);

            // Calculate ratios to fit image to PDF
            const imgProps = pdf.getImageProperties(imgData);
            const pdfWidthAvail = pdf.internal.pageSize.getWidth();
            const pdfHeightAvail = pdf.internal.pageSize.getHeight();
            
            // Add image with 5mm margin
            const margin = 5;
            const finalWidth = pdfWidthAvail - (margin * 2);
            const finalHeight = (imgProps.height * finalWidth) / imgProps.width;

            pdf.addImage(imgData, "PNG", margin, margin, finalWidth, finalHeight);
            
            // Save
            pdf.save("Item_Label_<?php echo safe($label_data['temp_code'] ?? 'ID'); ?>.pdf");

            // Optional: Auto Open Print Dialog
            window.open(pdf.output('bloburl'), '_blank');
        });
    }
</script>