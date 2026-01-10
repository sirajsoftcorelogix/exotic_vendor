<?php
// 1. PHP Logic & Data Fetching
$label_data = $data['form2'] ?? [];

if(empty($label_data) && isset($_GET['id'])) {
    is_login();
    require_once 'settings/database/database.php';
    $conn = Database::getConnection();
}

if(!isset($userDetails)) {
    require_once 'models/user/user.php';
    if(!isset($conn)) $conn = Database::getConnection();
    $usersModel = new User($conn);
    $user_id = $label_data['received_by_user_id'] ?? $_SESSION['user']['id'] ?? 0;
    $userDetails = $usersModel->getUserById($user_id);
    unset($usersModel);
}

function safe($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

$photoUrl = base_url(safe($label_data['product_photo'] ?? 'assets/images/placeholder.png'));
$currentUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

<div class="w-full flex items-center justify-center p-0 md:p-6 bg-gray-100 min-h-screen font-sans">
    <div class="w-full md:max-w-3xl bg-white md:rounded-2xl shadow-2xl overflow-hidden flex flex-col border border-gray-200">
        
        <div class="bg-[#d9822b] px-4 py-4 flex items-center justify-between text-white shadow-md">
            <button id="back-btn" class="p-2 hover:bg-white/20 rounded-full transition">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            </button>
            <h1 class="font-bold text-lg">Print Label (3x2)</h1>
            <button id="cancel-btn" class="bg-white/20 hover:bg-white/30 text-white font-bold py-1 px-4 rounded-full border border-white/30 text-sm">CLOSE</button>
        </div>

        <div class="p-8 bg-gray-50 flex flex-col items-center justify-center gap-4">
            <div class="w-[300px] h-[200px] border-2 border-black bg-white flex items-center justify-center shadow-lg">
                <span class="text-gray-500 font-bold">Preview (3x2 Inch)</span>
            </div>
            <p class="text-sm text-gray-500">Click the button below to generate the high-quality PDF.</p>
        </div>

        <div class="p-5 bg-white border-t border-gray-100">
            <button onclick="generatePDF()" class="w-full bg-[#d9822b] hover:bg-[#bf7326] text-white font-bold text-lg py-3 rounded-xl shadow-lg flex justify-center items-center gap-2 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                Download / Print Label
            </button>
        </div>
    </div>
</div>

<div id="high-res-print-area" class="fixed top-0 -left-[9999px] w-[1200px] h-[800px] bg-white border-[4px] border-black flex font-sans box-border z-[9999] text-black">
    
    <div class="w-[384px] border-r-[4px] border-black p-5 flex flex-col items-center justify-start">
        <div class="w-full h-[320px] border-[4px] border-black p-2.5 flex items-center justify-center mb-[15px] shrink-0">
            <img src="<?php echo $photoUrl; ?>" crossorigin="anonymous" class="max-w-full max-h-full object-contain">
        </div>
        
        <div class="w-full grow flex flex-col items-center justify-start pb-2.5">
            <div id="qrcode-highres" class="flex justify-center items-center mb-[5px]"></div>
            <div class="text-[40px] font-black mt-[5px] leading-none text-center">
                <?php echo safe($label_data['Item_code'] ?? $label_data['temp_code']); ?>
            </div>
            <div class="text-[36px] font-bold mt-[5px] text-center">
                <?php 
                    $dt = $label_data['gate_entry_date_time'] ?? '';
                    echo ($dt) ? date('d M Y', strtotime($dt)) : date('d M Y'); 
                ?>
            </div>
        </div>
    </div>

    <div class="w-[816px] flex flex-col">
        
        <div class="w-full h-[160px] border-b-[4px] border-black flex last:border-b-0">
            <div class="flex-1 border-r-[4px] border-black p-5 flex flex-col justify-center overflow-hidden last:border-r-0">
                <span class="text-[22px] font-extrabold uppercase mb-2 leading-none">SIZE:</span>
                <span class="text-[42px] font-bold whitespace-nowrap overflow-visible leading-[1.3]"><?php echo safe($label_data['size'] ?? '-'); ?></span>
            </div>
            <div class="flex-1 border-r-[4px] border-black p-5 flex flex-col justify-center overflow-hidden last:border-r-0">
                <span class="text-[22px] font-extrabold uppercase mb-2 leading-none">COLOR:</span>
                <span class="text-[42px] font-bold whitespace-nowrap overflow-visible leading-[1.3]"><?php echo safe($label_data['color'] ?? '-'); ?></span>
            </div>
        </div>

        <div class="w-full h-[160px] border-b-[4px] border-black flex last:border-b-0">
            <div class="flex-1 border-r-[4px] border-black p-5 flex flex-col justify-center overflow-hidden last:border-r-0">
                <span class="text-[22px] font-extrabold uppercase mb-2 leading-none">WxHxD:</span>
                <span class="text-[34px] font-bold whitespace-nowrap overflow-visible leading-[1.3]">
                    <?php echo safe($label_data['width'] ?? '-'); ?>x<?php echo safe($label_data['height'] ?? '-'); ?>x<?php echo safe($label_data['depth'] ?? '-'); ?>
                </span>
            </div>
            <div class="flex-1 border-r-[4px] border-black p-5 flex flex-col justify-center overflow-hidden last:border-r-0">
                <span class="text-[22px] font-extrabold uppercase mb-2 leading-none">WEIGHT:</span>
                <span class="text-[42px] font-bold whitespace-nowrap overflow-visible leading-[1.3]"><?php echo safe($label_data['weight'] ?? '0'); ?> kg</span>
            </div>
        </div>

        <div class="w-full h-[160px] border-b-[4px] border-black flex last:border-b-0">
            <div class="flex-1 border-r-[4px] border-black p-5 flex flex-col justify-center overflow-hidden last:border-r-0">
                <span class="text-[22px] font-extrabold uppercase mb-2 leading-none">CP:</span>
                <span class="text-[42px] font-bold whitespace-nowrap overflow-visible leading-[1.3]">₹ <?php echo safe($label_data['cp'] ?? '-'); ?></span>
            </div>
            <div class="flex-1 border-r-[4px] border-black p-5 flex flex-col justify-center overflow-hidden last:border-r-0">
                <span class="text-[22px] font-extrabold uppercase mb-2 leading-none">MATERIAL:</span>
                <span class="text-[34px] font-bold whitespace-nowrap overflow-visible leading-[1.3] pb-1">
                    <?php echo safe($label_data['material_name'] ?? '-'); ?>
                </span>
            </div>
        </div>

        <div class="w-full h-[160px] border-b-[4px] border-black flex last:border-b-0">
            <div class="flex-1 border-r-[4px] border-black p-5 flex flex-col justify-center overflow-hidden last:border-r-0">
                <span class="text-[22px] font-extrabold uppercase mb-2 leading-none">QTY:</span>
                <span class="text-[42px] font-bold whitespace-nowrap overflow-visible leading-[1.3]"><?php echo safe($label_data['quantity_received'] ?? '0'); ?></span>
            </div>
            <div class="flex-1 border-r-[4px] border-black p-5 flex flex-col justify-center overflow-hidden last:border-r-0">
                <span class="text-[22px] font-extrabold uppercase mb-2 leading-none">LOC:</span>
                <span class="text-[42px] font-bold whitespace-nowrap overflow-visible leading-[1.3]"><?php echo safe($label_data['location'] ?? 'Rack1'); ?></span>
            </div>
        </div>

        <div class="w-full h-[160px] border-b-[4px] border-black flex last:border-b-0">
            <div class="flex-1 border-r-[4px] border-black p-5 flex flex-col justify-center overflow-hidden last:border-r-0 border-r-0">
                <span class="text-[22px] font-extrabold uppercase mb-2 leading-none">VENDOR:</span>
                <span class="text-[34px] font-bold whitespace-nowrap overflow-visible leading-[1.3]">
                    <?php echo safe($label_data['vendor_name'] ?? 'Jagapoorani Arts'); ?>
                </span>
            </div>
        </div>

    </div>
</div>

<script>
    // 1. Navigation
    const recordId = "<?php echo isset($_GET['id']) ? $_GET['id'] : ''; ?>";
    document.getElementById("back-btn").addEventListener("click", () => {
        window.location.href = window.location.origin + "/index.php?page=inbounding&action=form3&id=" + recordId;
    });
    document.getElementById("cancel-btn").addEventListener("click", () => {
        window.location.href = window.location.origin + "/index.php?page=inbounding&action=list";
    });

    // 2. Generate High-Res QR Code on Load
    // Adjusted Size: 280x280 (High Res but fits better)
    window.addEventListener('load', function() {
        const qrContainer = document.getElementById("qrcode-highres");
        if(qrContainer) {
            qrContainer.innerHTML = "";
            new QRCode(qrContainer, {
                text: "<?php echo $currentUrl; ?>",
                width: 280,  
                height: 280, 
                colorDark : "#000000",
                colorLight : "#ffffff",
                correctLevel : QRCode.CorrectLevel.H 
            });
        }
    });

    // 3. Generate PDF Function
    function generatePDF() {
        const { jsPDF } = window.jspdf;
        const element = document.getElementById("high-res-print-area");

        html2canvas(element, { 
            scale: 2, 
            useCORS: true,
            backgroundColor: "#ffffff",
            logging: false,
            width: 1200, 
            height: 800,
            windowWidth: 2000, 
            onclone: (clonedDoc) => {
                // QR Canvas fix
                const originalCanvas = element.querySelector('canvas');
                const clonedCanvas = clonedDoc.querySelector('#qrcode-highres canvas');
                if (originalCanvas && clonedCanvas) {
                    const ctx = clonedCanvas.getContext('2d');
                    clonedCanvas.width = originalCanvas.width;
                    clonedCanvas.height = originalCanvas.height;
                    ctx.drawImage(originalCanvas, 0, 0);
                }
            }
        }).then(canvas => {
            const imgData = canvas.toDataURL("image/png", 1.0);
            
            const pdfWidth = 76.2; 
            const pdfHeight = 50.8; 
            
            const pdf = new jsPDF({
                orientation: "landscape",
                unit: "mm",
                format: [pdfWidth, pdfHeight]
            });

            pdf.addImage(imgData, "PNG", 0, 0, pdfWidth, pdfHeight);
            
            const fileName = "Label_3x2_<?php echo safe($label_data['temp_code'] ?? 'Item'); ?>.pdf";
            pdf.save(fileName);
        }).catch(err => {
            console.error("PDF Gen Error:", err);
            alert("Error generating label. Please check console.");
        });
    }
</script>