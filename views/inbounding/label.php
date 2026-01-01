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
    /* Load DejaVu Sans Font */
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

    /* Apply Font Globally to the Print Area */
    #printArea {
        font-family: 'DejaVuSans', sans-serif !important;
    }
    
    /* Ensure no scrollbars inside the label */
    #printArea * {
        box-sizing: border-box; 
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
            <h1 class="font-semibold text-lg tracking-wide">Print Label (3x2 inch Landscape)</h1>
            <button type="button" id="cancel-btn" class="bg-white/20 hover:bg-white/30 text-white text-sm font-bold py-1 px-4 rounded-full transition border border-white/30 shadow-sm uppercase tracking-wider">
                Close
            </button>
        </div>

        <div class="flex-1 overflow-y-auto p-4 bg-gray-100 flex flex-col items-center justify-center w-full">
            
            <div id="previewWrapper" class="w-full flex justify-center items-center relative">
                
                <div id="printArea" class="bg-white relative flex gap-4 shadow-xl text-gray-900 overflow-hidden" 
     style="width: 600px; height: 400px; padding: 20px; border: 2px solid #333; transform-origin: top center;">
    
    <div class="w-[150px] flex flex-col justify-between h-full flex-shrink-0 border-r-2 border-gray-200 pr-3">
        <div class="w-full h-[170px] border border-gray-300 rounded p-1 flex items-center justify-center bg-white mb-2">
             <img src="<?php echo $photoUrl; ?>" class="max-w-full max-h-full object-contain" crossorigin="anonymous">
        </div>
        
        <div class="flex flex-col items-center justify-center flex-grow">
            <div id="qrcode" class="border border-gray-300 p-1 bg-white rounded"></div>
            <div class="text-[13px] font-bold text-gray-600 mt-2 tracking-widest text-center break-all">
                <?php echo safe($label_data['temp_code'] ?? '0'); ?>
            </div>
        </div>
    </div>

    <div class="flex-1 flex flex-col h-full pl-1">
        
        <div class="border-b-2 border-gray-800 pb-2 mb-2">
             <div class="text-[11px] text-gray-500 font-bold uppercase tracking-wider mb-1">Material Name</div>
             <div class="text-[20px] font-bold leading-snug text-gray-900 line-clamp-2 pb-1">
                <?php echo safe($label_data['material_name'] ?? 'Material Name'); ?>
             </div>
        </div>

        <div class="flex-1 flex flex-col justify-between">
            
            <div class="grid grid-cols-2 gap-4 border-b border-gray-200 pb-2">
                <div class="flex flex-col justify-center">
                    <span class="text-[16px] font-bold text-gray-500 uppercase">Size</span>
                    <span class="text-[25px] font-bold text-gray-900 truncate leading-normal pb-1">
                        <?php echo safe($label_data['size'] ?? '-'); ?>
                    </span>
                </div>
                <div class="flex flex-col justify-center border-l border-gray-100 pl-4">
                    <span class="text-[16px] font-bold text-gray-500 uppercase">Color</span>
                    <span class="text-[25px] font-bold text-gray-900 truncate leading-normal pb-1">
                        <?php echo safe($label_data['color'] ?? '-'); ?>
                    </span>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4 border-b border-gray-200 pb-2 pt-2">
                <div class="flex flex-col justify-center">
                    <span class="text-[16px] font-bold text-gray-500 uppercase">Dimensions</span>
                    <span class="text-[25px] font-bold text-gray-900 truncate leading-normal pb-1">
                        <?php echo safe($label_data['width'] ?? '-'); ?> x <?php echo safe($label_data['height'] ?? '-'); ?> x <?php echo safe($label_data['depth'] ?? '-'); ?>
                    </span>
                </div>
                <div class="flex flex-col justify-center border-l border-gray-100 pl-4">
                    <span class="text-[16px] font-bold text-gray-500 uppercase">Weight</span>
                    <span class="text-[25px] font-bold text-gray-900 truncate leading-normal pb-1">
                        <?php echo safe($label_data['weight'] ?? '-'); ?> g
                    </span>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4 pt-2">
                <div class="flex flex-col justify-center">
                    <span class="text-[16px] font-bold text-gray-500 uppercase">Quantity</span>
                    <span class="text-[25px] font-bold text-gray-900 truncate leading-normal pb-1">
                        <?php echo safe($label_data['quantity_received'] ?? '0'); ?>
                    </span>
                </div>
                <div class="flex flex-col justify-center border-l border-gray-100 pl-4">
                    <span class="text-[16px] font-bold text-gray-500 uppercase">Location</span>
                    <span class="text-[25px] font-bold text-gray-900 truncate leading-normal pb-1">
                        <?php echo safe($label_data['location'] ?? '-'); ?>
                    </span>
                </div>
            </div>

        </div>
        
        <div class="mt-2 pt-2 border-t-2 border-gray-800">
            <div class="flex justify-between items-end">
                <div class="w-2/3 pr-2">
                    <div class="text-[14px] text-gray-400 font-bold uppercase">Vendor</div>
                    <div class="font-bold text-[18px] truncate leading-normal text-gray-700 pb-1">
                        <?php echo safe($label_data['vendor_name'] ?? 'N/A'); ?>
                    </div>
                </div>
                <div class="text-right w-1/3 flex-shrink-0">
                    <div class="text-[14px] text-gray-400 font-bold uppercase">Date</div>
                    <div class="font-bold text-[18px] whitespace-nowrap text-gray-700 leading-normal pb-1">
                        <?php 
                            $dt = $label_data['gate_entry_date_time'] ?? '';
                            echo substr($dt, 0, 10); 
                        ?>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
            </div>
            
            <p class="text-xs text-gray-400 mt-4 text-center">Preview (3x2 inch Landscape)</p>
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
    setTimeout(() => {
        const qrContainer = document.getElementById("qrcode");
        qrContainer.innerHTML = "";
        
        new QRCode(qrContainer, {
            text: "<?php echo $currentUrl; ?>",
            width: 80,  
            height: 80,
            colorDark : "#000000", 
            colorLight : "#ffffff",
            correctLevel : QRCode.CorrectLevel.L
        });
    }, 100);

    // 2. Responsive Scaling
    function fitLabelToScreen() {
        const wrapper = document.getElementById('previewWrapper');
        const label = document.getElementById('printArea');
        
        // Landscape Base Dimensions
        const baseWidth = 600;
        const baseHeight = 400;
        
        const availableWidth = wrapper.offsetWidth;
        
        let scale = 1;
        
        if (availableWidth < baseWidth) {
            scale = availableWidth / baseWidth;
        }

        label.style.transform = `scale(${scale - 0.05})`;
        wrapper.style.height = `${baseHeight * scale}px`;
    }

    window.addEventListener('load', fitLabelToScreen);
    window.addEventListener('resize', fitLabelToScreen);


    // 3. Navigation
    const recordId = "<?php echo isset($_GET['id']) ? $_GET['id'] : ''; ?>";
    document.getElementById("back-btn").addEventListener("click", function () {
        window.location.href = window.location.origin + "/index.php?page=inbounding&action=form3&id=" + recordId;
    });
    
    document.getElementById("cancel-btn").addEventListener("click", function () {
        window.location.href = window.location.origin + "/index.php?page=inbounding&action=list";
    });


    // 4. PDF Generation (Landscape 3x2)
    function generatePDF() {
        const { jsPDF } = window.jspdf;
        const originalElement = document.querySelector("#printArea");

        const clone = originalElement.cloneNode(true);
        
        clone.style.transform = "none";
        clone.style.position = "absolute";
        clone.style.top = "-9999px";
        clone.style.left = "-9999px";
        document.body.appendChild(clone);

        html2canvas(clone, { 
            scale: 2, 
            useCORS: true,
            backgroundColor: "#ffffff"
        }).then(canvas => {
            const imgData = canvas.toDataURL("image/png");
            
            // 3 inch Width x 2 inch Height
            const pdfWidth = 76.2; 
            const pdfHeight = 50.8; 
            
            // "l" for Landscape
            const pdf = new jsPDF("l", "mm", [pdfWidth, pdfHeight]);

            pdf.addImage(imgData, "PNG", 0, 0, pdfWidth, pdfHeight);
            
            pdf.save("Label_3x2_<?php echo safe($label_data['temp_code'] ?? 'Item'); ?>.pdf");
            window.open(pdf.output('bloburl'), '_blank');
            
            document.body.removeChild(clone);
        });
    }
</script>