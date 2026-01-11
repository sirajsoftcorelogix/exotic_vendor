<?php
// 1. PHP Logic & Data Fetching (Kept exactly the same)
$label_data[0] = $data['form2'] ?? [];
$variations = $data['variation'];
if (isset($variations) && !empty($variations)) {
    foreach ($variations as $key => $value) {
        $key++;    
        $label_data[$key] = $value;
        $label_data[$key]['product_photo'] = $value['variation_image'];
        $label_data[$key]['temp_code'] = $label_data[0]['temp_code'];
        $label_data[$key]['Item_code'] = $label_data[0]['Item_code'];
        $label_data[$key]['material_name'] = $label_data[0]['material_name'];
        $label_data[$key]['vendor_name'] = $label_data[0]['vendor_name'];
        $label_data[$key]['gate_entry_date_time'] = $label_data[0]['gate_entry_date_time'];
    }
}
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
            <h1 class="font-bold text-lg">Print Dual Label (3x4)</h1>
            <button id="cancel-btn" class="bg-white/20 hover:bg-white/30 text-white font-bold py-1 px-4 rounded-full border border-white/30 text-sm">CLOSE</button>
        </div>
        <?php 
            // Calculate dynamic height: 200px per label
            // If you have 1 label, height is 200. If 2, height is 400.
            $label_count = count($label_data); 
            $preview_height = $label_count * 200; 
        ?>
        <div id="preview-container" 
             class="w-[300px] border-2 border-black bg-white shadow-lg overflow-hidden relative"
             style="height: <?php echo $preview_height; ?>px;">
            <div class="w-full h-full flex items-center justify-center text-gray-400">
                Loading Preview...
            </div>
        </div>

        <div class="p-5 bg-white border-t border-gray-100">
            <button onclick="generatePDF()" class="w-full bg-[#d9822b] hover:bg-[#bf7326] text-white font-bold text-lg py-3 rounded-xl shadow-lg flex justify-center items-center gap-2 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                Download / Print Dual Label
            </button>
        </div>
    </div>
</div>

<div id="high-res-print-area" class="fixed top-0 -left-[9999px] w-[1200px] flex flex-col font-sans box-border z-[9999] text-black bg-white">
    
    <?php foreach($label_data as $index => $current_label): ?>
        
        <?php 
            // Calculate variables specifically for THIS label in the loop
            $thisPhotoUrl = base_url(safe($current_label['product_photo'] ?? 'assets/images/placeholder.png'));
        ?>

        <?php if($index > 0): ?>
            <div class="w-full h-[1px] bg-white"></div>
        <?php endif; ?>

        <div class="w-full h-[800px] border-[4px] border-black flex bg-white box-border">
            <div class="w-[384px] border-r-[4px] border-black p-5 flex flex-col items-center justify-start">
                <div class="w-full h-[320px] border-[4px] border-black p-2.5 flex items-center justify-center mb-[15px] shrink-0">
                    <img src="<?php echo $thisPhotoUrl; ?>" crossorigin="anonymous" class="max-w-full max-h-full object-contain">
                </div>
                
                <div class="w-full grow flex flex-col items-center justify-start pb-2.5">
                    <div class="qrcode-highres flex justify-center items-center mb-[5px]"></div>
                    <div class="text-[40px] font-black mt-[5px] leading-none text-center">
                        <?php echo safe($current_label['Item_code'] ?? $current_label['temp_code']); ?>
                    </div>
                    <div class="text-[36px] font-bold mt-[5px] text-center">
                        <?php 
                            $dt = $current_label['gate_entry_date_time'] ?? '';
                            echo ($dt) ? date('d M Y', strtotime($dt)) : date('d M Y'); 
                        ?>
                    </div>
                </div>
            </div>

            <div class="w-[816px] flex flex-col">
                
                <div class="w-full h-[160px] border-b-[4px] border-black flex last:border-b-0">
                    <div class="flex-1 border-r-[4px] border-black p-5 flex flex-col justify-center overflow-hidden last:border-r-0">
                        <span class="text-[22px] font-extrabold uppercase mb-2 leading-none">SIZE:</span>
                        <span class="text-[42px] font-bold whitespace-nowrap overflow-visible leading-[1.3]"><?php echo safe($current_label['size'] ?? '-'); ?></span>
                    </div>
                    <div class="flex-1 border-r-[4px] border-black p-5 flex flex-col justify-center overflow-hidden last:border-r-0">
                        <span class="text-[22px] font-extrabold uppercase mb-2 leading-none">COLOR:</span>
                        <span class="text-[42px] font-bold whitespace-nowrap overflow-visible leading-[1.3]"><?php echo safe($current_label['color'] ?? '-'); ?></span>
                    </div>
                </div>

                <div class="w-full h-[160px] border-b-[4px] border-black flex last:border-b-0">
                    <div class="flex-1 border-r-[4px] border-black p-5 flex flex-col justify-center overflow-hidden last:border-r-0">
                        <span class="text-[22px] font-extrabold uppercase mb-2 leading-none">WxHxD:</span>
                        <span class="text-[34px] font-bold whitespace-nowrap overflow-visible leading-[1.3]">
                            <?php echo safe($current_label['width'] ?? '-'); ?>x<?php echo safe($current_label['height'] ?? '-'); ?>x<?php echo safe($current_label['depth'] ?? '-'); ?>
                        </span>
                    </div>
                    <div class="flex-1 border-r-[4px] border-black p-5 flex flex-col justify-center overflow-hidden last:border-r-0">
                        <span class="text-[22px] font-extrabold uppercase mb-2 leading-none">WEIGHT:</span>
                        <span class="text-[42px] font-bold whitespace-nowrap overflow-visible leading-[1.3]"><?php echo safe($current_label['weight'] ?? '0'); ?> kg</span>
                    </div>
                </div>

                <div class="w-full h-[160px] border-b-[4px] border-black flex last:border-b-0">
                    <div class="flex-1 border-r-[4px] border-black p-5 flex flex-col justify-center overflow-hidden last:border-r-0">
                        <span class="text-[22px] font-extrabold uppercase mb-2 leading-none">CP:</span>
                        <span class="text-[42px] font-bold whitespace-nowrap overflow-visible leading-[1.3]">₹ <?php echo safe($current_label['cp'] ?? '-'); ?></span>
                    </div>
                    <div class="flex-1 border-r-[4px] border-black p-5 flex flex-col justify-center overflow-hidden last:border-r-0">
                        <span class="text-[22px] font-extrabold uppercase mb-2 leading-none">MATERIAL:</span>
                        <span class="text-[34px] font-bold whitespace-nowrap overflow-visible leading-[1.3] pb-1">
                            <?php echo safe($current_label['material_name'] ?? '-'); ?>
                        </span>
                    </div>
                </div>

                <div class="w-full h-[160px] border-b-[4px] border-black flex last:border-b-0">
                    <div class="flex-1 border-r-[4px] border-black p-5 flex flex-col justify-center overflow-hidden last:border-r-0">
                        <span class="text-[22px] font-extrabold uppercase mb-2 leading-none">QTY:</span>
                        <span class="text-[42px] font-bold whitespace-nowrap overflow-visible leading-[1.3]"><?php echo safe($current_label['quantity_received'] ?? '0'); ?></span>
                    </div>
                    <div class="flex-1 border-r-[4px] border-black p-5 flex flex-col justify-center overflow-hidden last:border-r-0">
                        <span class="text-[22px] font-extrabold uppercase mb-2 leading-none">LOC:</span>
                        <span class="text-[42px] font-bold whitespace-nowrap overflow-visible leading-[1.3]"><?php echo safe($current_label['location'] ?? 'Rack1'); ?></span>
                    </div>
                </div>

                <div class="w-full h-[160px] border-b-[4px] border-black flex last:border-b-0">
                    <div class="flex-1 border-r-[4px] border-black p-5 flex flex-col justify-center overflow-hidden last:border-r-0 border-r-0">
                        <span class="text-[22px] font-extrabold uppercase mb-2 leading-none">VENDOR:</span>
                        <span class="text-[34px] font-bold whitespace-nowrap overflow-visible leading-[1.3]">
                            <?php echo safe($current_label['vendor_name'] ?? 'Jagapoorani Arts'); ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

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

    // 2. Generate High-Res QR Code on Load & Update Preview
    window.addEventListener('load', function() {
        const qrContainers = document.querySelectorAll(".qrcode-highres");
        
        qrContainers.forEach(container => {
            container.innerHTML = "";
            new QRCode(container, {
                text: "<?php echo $currentUrl; ?>",
                width: 280,  
                height: 280, 
                colorDark : "#000000",
                colorLight : "#ffffff",
                correctLevel : QRCode.CorrectLevel.H 
            });
        });

        // Wait a moment for QRs to render before showing preview
        setTimeout(updateLivePreview, 500); 
    });

    // 3. Update Live Preview
    function updateLivePreview() {
        const source = document.getElementById('high-res-print-area');
        const container = document.getElementById('preview-container');
        
        if (!source || !container) return;
        
        const clone = source.cloneNode(true);
        clone.removeAttribute('id');
        
        const originalCanvases = source.querySelectorAll('canvas');
        const clonedCanvases = clone.querySelectorAll('canvas');
        
        originalCanvases.forEach((orig, index) => {
            if(clonedCanvases[index]) {
                clonedCanvases[index].width = orig.width;
                clonedCanvases[index].height = orig.height;
                clonedCanvases[index].getContext('2d').drawImage(orig, 0, 0);
            }
        });

        clone.classList.remove('fixed', 'top-0', '-left-[9999px]'); 
        clone.style.transform = "scale(0.25)"; 
        clone.style.transformOrigin = "top left"; 
        clone.style.position = "absolute";
        clone.style.top = "0";
        clone.style.left = "0";
        
        container.innerHTML = '';
        container.appendChild(clone);
    }

    // 4. Generate PDF Function (DYNAMIC FIX)
    function generatePDF() {
        const { jsPDF } = window.jspdf;
        const element = document.getElementById("high-res-print-area");

        // --- STEP A: Count the actual labels ---
        // We look for elements with the specific label height class
        const labelsFound = element.querySelectorAll('.h-\\[800px\\]').length;
        
        // Fallback: if class search fails, count the border boxes
        const count = labelsFound > 0 ? labelsFound : element.children.length;

        if (count === 0) { alert("No data to print"); return; }

        // --- STEP B: Calculate Dynamic Heights ---
        const singleHeight = 800; 
        const spacing = 1; // 1px white border (if used)
        
        // Calculate total pixels: (800 * count)
        const totalHeightPx = (singleHeight * count); 
        const totalWidthPx = 1200;

        html2canvas(element, { 
            scale: 2, 
            useCORS: true,
            backgroundColor: "#ffffff",
            logging: false,
            width: totalWidthPx, 
            height: totalHeightPx, // <--- DYNAMIC HEIGHT
            windowWidth: 2000, 
            onclone: (clonedDoc) => {
                const originalCanvases = element.querySelectorAll('.qrcode-highres canvas');
                const clonedCanvases = clonedDoc.querySelectorAll('.qrcode-highres canvas');
                
                originalCanvases.forEach((orig, index) => {
                    if (clonedCanvases[index]) {
                        const ctx = clonedCanvases[index].getContext('2d');
                        clonedCanvases[index].width = orig.width;
                        clonedCanvases[index].height = orig.height;
                        ctx.drawImage(orig, 0, 0);
                    }
                });
            }
        }).then(canvas => {
            const imgData = canvas.toDataURL("image/png", 1.0);
            
            const pdfWidth = 76.2; // 3 inches (fixed width)
            const singleLabelHeightMm = 50.8; // 2 inches per label
            
            // --- STEP C: Calculate PDF Page Height ---
            // If 1 label = 2 inches. If 3 labels = 6 inches.
            const pdfHeight = singleLabelHeightMm * count; 
            
            const pdf = new jsPDF({
                orientation: "portrait", 
                unit: "mm",
                format: [pdfWidth, pdfHeight] // <--- DYNAMIC PAGE SIZE
            });

            pdf.addImage(imgData, "PNG", 0, 0, pdfWidth, pdfHeight);
            
            // Use the code of the first item for the filename
            const fileName = "Labels_<?php echo safe($label_data[0]['temp_code'] ?? 'Batch'); ?>.pdf";
            pdf.save(fileName);
        }).catch(err => {
            console.error("PDF Gen Error:", err);
            alert("Error generating label. Please check console.");
        });
    }
</script>