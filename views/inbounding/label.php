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

<style>
    /* 1. Fonts */
    @font-face {
        font-family: 'DejaVuSans';
        src: url('/fonts/DejaVuSans.ttf') format('truetype');
        font-weight: normal;
        font-style: normal;
    }
    @font-face {
        font-family: 'DejaVuSans';
        src: url('/fonts/DejaVuSans-Bold.ttf') format('truetype');
        font-weight: bold;
        font-style: normal;
    }

    /* 2. Print Area Defaults */
    #printArea {
        font-family: 'DejaVuSans', sans-serif !important;
        background-color: #ffffff !important;
        color: #000000 !important;
        box-sizing: border-box;
    }
    
    #printArea * {
        box-sizing: border-box; 
        color: #000000 !important; 
        border-color: #000000 !important; 
    }

    #printArea img {
        image-rendering: pixelated; 
    }
</style>

<div class="w-full flex items-center justify-center p-0 md:p-6 bg-gray-100 min-h-screen">

    <div class="w-full h-screen md:h-auto md:min-h-[600px] md:max-w-3xl bg-white md:rounded-2xl shadow-2xl overflow-hidden flex flex-col relative border border-gray-200">
        
        <div class="bg-[#d9822b] px-4 py-4 flex items-center justify-between text-white shadow-md z-20 flex-shrink-0">
            <button type="button" id="back-btn" class="p-2 hover:bg-white/20 rounded-full transition">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-6 h-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                </svg>
            </button>
            <h1 class="font-semibold text-lg tracking-wide">Print Label (3x2 inch)</h1>
            <button type="button" id="cancel-btn" class="bg-white/20 hover:bg-white/30 text-white text-sm font-bold py-1 px-4 rounded-full transition border border-white/30 shadow-sm uppercase tracking-wider">
                Close
            </button>
        </div>

        <div id="previewContainer" class="flex-1 overflow-hidden bg-gray-100 flex items-center justify-center w-full p-2 relative">
            
            <div id="previewWrapper" class="relative shadow-xl bg-white overflow-hidden transition-all duration-200">
                
                <div id="printArea" class="bg-white flex gap-4 overflow-hidden" 
                     style="width: 600px; height: 400px; padding: 20px; border: 4px solid #000000; 
                            position: absolute; top: 50%; left: 50%; transform-origin: center center;">
    
                    <div class="w-[150px] flex flex-col justify-between h-full flex-shrink-0 border-r-2 border-black pr-3">
                        <div class="w-full h-[170px] border-2 border-black rounded p-1 flex items-center justify-center bg-white mb-2">
                             <img src="<?php echo $photoUrl; ?>" class="max-w-full max-h-full object-contain filter grayscale contrast-125" crossorigin="anonymous">
                        </div>
                        
                        <div class="flex flex-col items-center justify-center flex-grow">
                            <div id="qrcode" class="border-2 border-black p-1 bg-white rounded"></div>
                            
                            <div class="text-[24px] font-bold mt-2 tracking-widest text-center break-all leading-none">
                                <?php echo safe($label_data['Item_code'] ?? $label_data['temp_code']); ?>
                            </div>
                            <div class="text-[18px] font-bold mt-1 tracking-widest text-center break-all leading-none">
                                <?php 
                                    $dt = $label_data['gate_entry_date_time'] ?? '';
                                    echo substr($dt, 0, 10); 
                                ?>
                            </div>
                        </div>
                    </div>

                    <div class="flex-1 flex flex-col h-full pl-1">
                        
                        <div class="border-b-2 border-black pb-2 mb-2 flex items-center gap-2">
                            <div class="text-[12px] font-bold uppercase tracking-wider whitespace-nowrap">Material : </div>
                            <div class="text-[18px] font-bold leading-none truncate flex-1">
                                <?php echo safe($label_data['material_name'] ?? ''); ?>
                            </div>
                        </div>

                        <div class="flex-1 flex flex-col justify-between">
                            
                            <div class="grid grid-cols-2 gap-4 border-b-2 border-black pb-2">
                                <div class="flex flex-col justify-center">
                                    <span class="text-[14px] font-bold uppercase">Size</span>
                                    <span class="text-[22px] font-bold truncate leading-normal pb-1">
                                        <?php echo safe($label_data['size'] ?? '-'); ?>
                                    </span>
                                </div>
                                <div class="flex flex-col justify-center border-l-2 border-black pl-4">
                                    <span class="text-[14px] font-bold uppercase">Color</span>
                                    <span class="text-[22px] font-bold truncate leading-normal pb-1">
                                        <?php echo safe($label_data['color'] ?? '-'); ?>
                                    </span>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4 border-b-2 border-black pb-2 pt-2">
                                <div class="flex flex-col justify-center">
                                    <span class="text-[14px] font-bold uppercase">Dim(WxHxD)</span>
                                    <span class="text-[20px] font-bold truncate leading-normal pb-1">
                                        <?php echo safe($label_data['width'] ?? '-'); ?>x<?php echo safe($label_data['height'] ?? '-'); ?>x<?php echo safe($label_data['depth'] ?? '-'); ?>
                                    </span>
                                </div>
                                <div class="flex flex-col justify-center border-l-2 border-black pl-4">
                                    <span class="text-[14px] font-bold uppercase">Weight</span>
                                    <span class="text-[20px] font-bold truncate leading-normal pb-1">
                                        <?php echo safe($label_data['weight'] ?? '-'); ?> kg
                                    </span>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4 pt-2">
                                <div class="flex flex-col justify-center">
                                    <span class="text-[14px] font-bold uppercase">Qty</span>
                                    <span class="text-[22px] font-bold truncate leading-normal pb-1">
                                        <?php echo safe($label_data['quantity_received'] ?? '0'); ?>
                                    </span>
                                </div>
                                <div class="flex flex-col justify-center border-l-2 border-black pl-4">
                                    <span class="text-[14px] font-bold uppercase">Loc</span>
                                    <span class="text-[22px] font-bold truncate leading-normal pb-1">
                                        <?php echo safe($label_data['location'] ?? '-'); ?>
                                    </span>
                                </div>
                            </div>

                        </div>
                        
                        <div class="mt-2 pt-2 border-t-2 border-black">
                            <div class="w-full">
                                <div class="text-[10px] font-bold uppercase mb-1">Vendor</div>
                                <div class="font-bold text-[14px] leading-snug w-full truncate pb-1">
                                    <?php echo safe($label_data['vendor_name'] ?? 'N/A'); ?>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
            
            <p class="absolute bottom-2 text-xs text-gray-400 text-center w-full">Preview (3x2 inch Landscape)</p>
        </div>

        <div class="p-5 bg-white border-t border-gray-100 mt-auto z-20">
            <button onclick="generatePDF()" class="w-full bg-[#d9822b] hover:bg-[#bf7326] text-white font-bold text-lg py-3 rounded-xl shadow-lg transform transition active:scale-[0.99] flex justify-center items-center gap-2">
                Print Label (3x2)
            </button>
        </div>

    </div>
</div>

<script>
    // 1. Generate QR Code
    window.addEventListener('load', function() {
        const qrContainer = document.getElementById("qrcode");
        if(qrContainer) {
            qrContainer.innerHTML = "";
            new QRCode(qrContainer, {
                text: "<?php echo $currentUrl; ?>",
                width: 80,  
                height: 80,
                colorDark : "#000000",
                colorLight : "#ffffff",
                correctLevel : QRCode.CorrectLevel.L
            });
        }
        // Initial Fit
        setTimeout(fitLabelToScreen, 100);
    });

    // 2. Responsive Scaling Logic (Fixed)
    function fitLabelToScreen() {
        const container = document.getElementById('previewContainer');
        const wrapper = document.getElementById('previewWrapper');
        const label = document.getElementById('printArea');
        
        if (!container || !wrapper || !label) return;

        // Base Label Dimensions
        const baseWidth = 600;
        const baseHeight = 400;

        // Available space (minus padding)
        const availableWidth = container.offsetWidth - 32; // 1rem padding each side
        const availableHeight = container.offsetHeight - 40; // Space for text/padding

        // Calculate Scale to fit within view
        let scale = Math.min(
            availableWidth / baseWidth,
            availableHeight / baseHeight,
            1 // Max scale 1 (don't make it bigger than actual 600px)
        );

        // Apply Size to Wrapper (It shrinks to fit)
        wrapper.style.width = `${baseWidth * scale}px`;
        wrapper.style.height = `${baseHeight * scale}px`;

        // Center and Scale the Inner Label
        // We use translate(-50%, -50%) because position is absolute top 50% left 50%
        label.style.transform = `translate(-50%, -50%) scale(${scale})`;
    }

    window.addEventListener('resize', fitLabelToScreen);

    // 3. Navigation
    const recordId = "<?php echo isset($_GET['id']) ? $_GET['id'] : ''; ?>";
    document.getElementById("back-btn").addEventListener("click", function () {
        window.location.href = window.location.origin + "/index.php?page=inbounding&action=form3&id=" + recordId;
    });
    document.getElementById("cancel-btn").addEventListener("click", function () {
        window.location.href = window.location.origin + "/index.php?page=inbounding&action=list";
    });

    // 4. PDF Generation (Maintained your Working Version)
    function generatePDF() {
        const { jsPDF } = window.jspdf;
        const originalElement = document.querySelector("#printArea");

        // Clone element
        const clone = originalElement.cloneNode(true);
        
        // Manually copy QR Canvas (Fix for missing QR)
        const originalCanvases = originalElement.querySelectorAll('canvas');
        const clonedCanvases = clone.querySelectorAll('canvas');
        originalCanvases.forEach((orig, index) => {
            const dest = clonedCanvases[index];
            const ctx = dest.getContext('2d');
            dest.width = orig.width;   
            dest.height = orig.height;
            ctx.drawImage(orig, 0, 0); 
        });

        // Setup Clone Styles for Capture
        clone.style.transform = "none";
        clone.style.position = "fixed"; 
        clone.style.top = "-10000px";   
        clone.style.left = "0";
        clone.style.zIndex = "-9999"; 
        
        document.body.appendChild(clone);

        html2canvas(clone, { 
            scale: 2, 
            useCORS: true,
            backgroundColor: "#ffffff",
            logging: false,
            windowWidth: 1200, 
            windowHeight: 800
        }).then(canvas => {
            const imgData = canvas.toDataURL("image/png");
            
            // 3 inch x 2 inch
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
            window.open(pdf.output('bloburl'), '_blank');
            
            document.body.removeChild(clone);
        }).catch(err => {
            console.error("PDF Generation Error:", err);
            alert("Error generating PDF. Please try again.");
            document.body.removeChild(clone);
        });
    }
</script>