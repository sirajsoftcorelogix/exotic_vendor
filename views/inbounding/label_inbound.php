<?php
// 1. PHP Logic & Data Fetching
$label_data[0] = $data["form2"] ?? [];
$variations = $data["variations"] ?? [];
// $variations = $data['variation'];
if (isset($variations) && !empty($variations)) {
    foreach ($variations as $key => $value) {
        $key++;
        $label_data[$key] = $value;
        $label_data[$key]["product_photo"] = $value["variation_image"];
        $label_data[$key]["Item_code"] = $label_data[0]["Item_code"];
        $label_data[$key]["material_name"] = $label_data[0]["material_name"];
        $label_data[$key]["vendor_name"] = $label_data[0]["vendor_name"];
        $label_data[$key]["gate_entry_date_time"] =
            $label_data[0]["gate_entry_date_time"];
    }
}

if (empty($label_data) && isset($_GET["id"])) {
    is_login();
    require_once "settings/database/database.php";
    $conn = Database::getConnection();
}

if (!isset($userDetails)) {
    require_once "models/user/user.php";
    if (!isset($conn)) {
        $conn = Database::getConnection();
    }
    $usersModel = new User($conn);
    $user_id =
        $label_data["received_by_user_id"] ?? ($_SESSION["user"]["id"] ?? 0);
    $userDetails = $usersModel->getUserById($user_id);
    unset($usersModel);
}

function safe($value)
{
    return htmlspecialchars($value ?? "", ENT_QUOTES, "UTF-8");
}

function safeInt($value)
{
    return intval($value ?? 0);
}
$categoryName = "";
if (!empty($label_data[0]["group_name"])) {
    require_once "models/inbounding/Inbounding.php";
    $inboundingModel = new Inbounding($conn);
    $categoryData = $inboundingModel->getBycateId($label_data[0]["group_name"]);
    $categoryName = strtolower($categoryData["name"] ?? "");
}
// echo '<pre>' ; print_r($categoryName);exit;

$currentUrl =
    (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] === "on"
        ? "https"
        : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdn.tailwindcss.com"></script>

<div class="w-full flex items-center justify-center p-0 md:p-6 bg-gray-100 min-h-screen font-sans">
    <div class="w-full md:max-w-4xl bg-white md:rounded-2xl shadow-2xl overflow-hidden flex flex-col border border-gray-200">

        <div class="bg-[#d9822b] px-4 py-4 flex items-center justify-between text-white shadow-md">
            <button id="back-btn" class="p-2 hover:bg-white/20 rounded-full transition">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
            </button>
            <h1 class="font-bold text-lg">Print Labels (3x2)</h1>
            <button id="cancel-btn" class="bg-white/20 hover:bg-white/30 text-white font-bold py-1 px-4 rounded-full border border-white/30 text-sm">CLOSE</button>
        </div>

        <?php
        // UPDATED: Calculate total label count based on quantity sum
        $total_labels_to_print = 0;
        foreach ($label_data as $l) {
            $q = safeInt($l["quantity_received"] ?? 1);
            if ($q < 1) {
                $q = 1;
            }
            $total_labels_to_print += $q;
        }
        // 200px visual height per label in preview
        $preview_height = $total_labels_to_print * 200;
        ?>

        <div class="w-full bg-gray-50 flex justify-center py-10 overflow-auto" style="min-height: 400px;">
            <div id="preview-container" class="relative shadow-xl border border-gray-300 bg-white">
                <div class="p-10 text-gray-400">Loading Preview...</div>
            </div>
        </div>

        <div class="p-6 bg-white border-t border-gray-200">
            <button onclick="generatePDF()" class="w-full bg-[#d9822b] hover:bg-[#bf7326] text-white font-bold text-lg py-3 rounded-xl shadow-lg flex justify-center items-center gap-2 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                </svg>
                <span>Download Print File (PDF)</span>
            </button>
        </div>
    </div>
</div>

<div id="high-res-print-area" class="fixed top-0 -left-[9999px] w-[1200px] flex flex-col font-sans box-border z-[9999] text-black bg-white">

    <?php foreach ($label_data as $index => $current_label): ?>

        <?php
        // 1. Get Quantity for this specific variation
        $qtyToPrint = safeInt($current_label["quantity_received"] ?? 1);
        if ($qtyToPrint < 1) {
            $qtyToPrint = 1;
        }

        // 2. Prepare Data Variables
        $thisPhotoUrl = base_url(
            safe(
                $current_label["product_photo"] ??
                    "assets/images/placeholder.png"
            )
        );
        $itemCode = $current_label["Item_code"];

        $w = safeInt($current_label["width"]);
        $h = safeInt($current_label["height"]);
        $d = safeInt($current_label["depth"]);
        $dims = "{$w}x{$h}x{$d}";

        // 3. Loop: Print the label N times
        for ($i = 0; $i < $qtyToPrint; $i++): ?>

            <div class="single-label-item w-full h-[800px] border-[3px] border-black bg-white box-border mb-0 flex flex-col relative overflow-hidden">


                <?php if ($categoryName == "book") { ?>
                    <div class="flex flex-row w-full h-[450px] border-b-[3px] border-black">
                        <div class="w-[350px] h-full flex flex-col items-center justify-center p-6">
                            <div class="qrcode-highres" style="width: 300px; height: 300px;"></div>
                        </div>
                        <div class="w-[400px] h-full flex items-center justify-center p-4">
                            <img src="<?php echo $thisPhotoUrl; ?>" crossorigin="anonymous" class="object-contain max-w-full max-h-[95%] grayscale hover:grayscale-0 transition-all">
                        </div>
                        <div class="flex-1 h-full flex flex-col justify-center pl-10 pr-6 pt-6 space-y-6">
                            <div class="flex flex-col leading-none">
                                <span class="text-[32px] font-bold text-black uppercase mb-3">MRP:</span>
                                <span class="text-[54px] font-black text-black tracking-tight">
                                    ₹ <?php echo safe(
                                            $current_label["price_india_mrp"] ?? "0"
                                        ); ?>
                                </span>
                            </div>
                            <div class="flex flex-col leading-none">
                                <span class="text-[32px] font-bold text-black uppercase mb-2">QTY:</span>
                                <span class="text-[54px] font-black text-black">
                                    <?php echo safe(
                                        $current_label["quantity_received"] ?? "1"
                                    ); ?>
                                </span>
                            </div>
                            <div class="flex flex-col leading-none">
                                <span class="text-[32px] font-bold text-black uppercase mb-2">LANGUAGE:</span>
                                <span class="text-[42px] font-bold text-black leading-tight">
                                    <?php echo safe(
                                        $current_label["language"] ?? "N/A"
                                    ); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <!-- Row 1 -->
                    <div class="flex flex-row w-full h-[160px] items-center border-b-[3px] border-black">

                        <div class="w-[380px] pl-8 flex flex-col justify-center h-full">
                            <div class="text-[50px] font-black tracking-tight leading-none text-black">
                                <?php echo safe($current_label["Item_code"]); ?>
                            </div>
                            <div class="text-[30px] font-bold text-black leading-none">
                                <?php
                                $dt = $current_label['gate_entry_date_time'] ?? '';
                                echo ($dt) ? date('dS M Y', strtotime($dt)) : date('dS M Y');
                                ?>
                            </div>
                        </div>





                    </div>

                    <!-- Row 2 -->

                    <div class="flex-1 w-full flex flex-col justify-center pl-8">
                        <span class="text-[32px] font-bold uppercase text-black mb-1 leading-none">PUBLISHER:</span>
                        <span class="text-[48px] font-black text-black tracking-tight leading-tight block w-full pr-4 pb-2">

                        </span>
                    </div>


                <?php  } elseif ($categoryName == 'sculptures' || $categoryName == 'homeandliving') { ?>
                    <div class="flex flex-row w-full h-[450px] border-b-[3px] border-black">
                        <div class="w-[350px] h-full flex flex-col items-center justify-center p-6">
                            <div class="qrcode-highres" style="width: 300px; height: 300px;"></div>
                        </div>
                        <div class="w-[400px] h-full flex items-center justify-center p-4">
                            <img src="<?php echo $thisPhotoUrl; ?>" crossorigin="anonymous" class="object-contain max-w-full max-h-[95%] grayscale hover:grayscale-0 transition-all">
                        </div>
                        <div class="flex-1 h-full flex flex-col justify-center pl-10 pr-6 pt-6 space-y-6">
                            <div class="flex flex-col leading-none">
                                <span class="text-[32px] font-bold text-black uppercase mb-3">CP: <span class="text-[54px] font-black text-black tracking-tight">
                                    ₹ <?php echo safe($current_label['cp'] ?? '0'); ?>
                                </span></span>
                                
                            </div>
                            <div class="flex flex-col leading-none">
                                <span class="text-[32px] font-bold text-black uppercase mb-2">QTY:</span>
                                <span class="text-[54px] font-black text-black">
                                    <?php echo safe(
                                        $current_label["quantity_received"] ?? "1"
                                    ); ?>
                                </span>
                            </div>
                            <div class="flex flex-col leading-none">
                                <span class="text-[32px] font-bold text-black uppercase mb-2">COLOR:</span>
                                <span class="text-[42px] font-bold text-black leading-tight">
                                    <?php echo safe(
                                        $current_label["color"] ?? "N/A"
                                    ); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="flex flex-row w-full h-[160px] items-center border-b-[3px] border-black">

                        <div class="w-[380px] pl-8 flex flex-col justify-center h-full">
                            <div class="text-[50px] font-black tracking-tight leading-none mb-3 text-black">
                                <?php echo safe($current_label["Item_code"]); ?>
                            </div>
                            <div class="text-[30px] font-bold text-black leading-none">
                                <?php
                                $dt =
                                    $current_label["gate_entry_date_time"] ??
                                    "";
                                echo $dt
                                    ? date("dS M Y", strtotime($dt))
                                    : date("dS M Y");
                                ?>
                            </div>
                        </div>
                        <div class="w-[320px] pl-4 flex flex-col justify-center h-full">
                            <span class="text-[30px] font-bold uppercase text-black">WEIGHT:</span>
                            <span class="text-[42px] font-black text-black leading-none">
                                <?php echo safe(
                                    $current_label["weight"] ?? "-"
                                ); ?>
                                <?php echo safe(
                                    $current_label["weight_unit"] ?? "kg"
                                ); ?>
                            </span>
                        </div>
                        <div class="w-[320px] pl-4 flex flex-col justify-center h-full">
                            <span class="text-[30px] font-bold uppercase text-black">material:</span>
                            <span class="text-[42px] font-black text-black leading-none">
                                <?php if (!empty($current_label["material_name"])) {
                                    echo safe($current_label["material_name"]);
                                } ?>
                            </span>
                        </div>

                        <div class="w-[320px] pl-4 flex flex-col justify-center h-full">
                            <span class="text-[30px] font-bold uppercase text-black">SIZE:</span>
                            <span class="text-[42px] font-black text-black leading-none">
                                <?php if (!empty($current_label["size"])) {
                                    echo safe($current_label["size"]);
                                } ?>
                            </span>
                        </div>
                    </div>


                    <div class="flex-1 w-full flex flex-col justify-center pl-8">
                        <span class="text-[32px] font-bold uppercase text-black mb-1 leading-none">VENDOR:</span>
                        <span class="text-[48px] font-black text-black tracking-tight leading-tight block w-full pr-4 pb-2">
                            <?php echo safe($current_label['vendor_name'] ?? 'Jagapoorani Arts & Crafts'); ?>
                        </span>
                    </div>


                <?php } elseif ($categoryName == 'jewelry' || $categoryName == 'textile') { ?>
                    <div class="flex flex-row w-full h-[450px] border-b-[3px] border-black">
                        <div class="w-[350px] h-full flex flex-col items-center justify-center p-6">
                            <div class="qrcode-highres" style="width: 300px; height: 300px;"></div>
                        </div>
                        <div class="w-[400px] h-full flex items-center justify-center p-4">
                            <img src="<?php echo $thisPhotoUrl; ?>" crossorigin="anonymous" class="object-contain max-w-full max-h-[95%] grayscale hover:grayscale-0 transition-all">
                        </div>
                        <div class="flex-1 h-full flex flex-col justify-center pl-10 pr-6 pt-6 space-y-6">
                            <div class="flex flex-col leading-none">
                                <span class="text-[32px] font-bold text-black uppercase mb-3">COLOR:</span>
                                <span class="text-[42px] font-bold text-black leading-tight">
                                    <?php echo safe(
                                        $current_label["color"] ?? "N/A"
                                    ); ?>
                                </span>
                            </div>
                            <div class="flex flex-col leading-none">
                                <span class="text-[32px] font-bold text-black uppercase mb-3">DIMENSIONS:</span>
                                <span class="text-[54px] font-black text-black tracking-tight">
                                    <?php echo safe(
                                        $current_label["dimensions"] ?? " "
                                    ); ?>
                                </span>
                            </div>
                            <div class="flex flex-col leading-none">
                                <span class="text-[32px] font-bold text-black uppercase mb-2">LOCATION:</span>
                                <span class="text-[54px] font-black text-black">
                                    <?php echo safe(
                                        $current_label["store_location"] ?? " "
                                    ); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="flex flex-row w-full h-[160px] items-center ">

                        <div class="w-[380px] pl-8 flex flex-col justify-center h-full">
                            <div class="text-[50px] font-black tracking-tight leading-none text-black">
                                <?php echo safe($current_label["Item_code"]); ?>
                            </div>
                            <div class="text-[30px] font-bold text-black leading-none">
                                <?php
                                $dt = $current_label['gate_entry_date_time'] ?? '';
                                echo ($dt) ? date('dS M Y', strtotime($dt)) : date('dS M Y');
                                ?>
                            </div>
                        </div>
                    </div>


                <?php } else { ?>
                    <div class="flex flex-row w-full h-[450px] border-b-[3px] border-black">
                        <div class="w-[350px] h-full flex flex-col items-center justify-center p-6">
                            <div class="qrcode-highres" style="width: 300px; height: 300px;"></div>
                        </div>
                        <div class="w-[400px] h-full flex items-center justify-center p-4">
                            <img src="<?php echo $thisPhotoUrl; ?>" crossorigin="anonymous" class="object-contain max-w-full max-h-[95%] grayscale hover:grayscale-0 transition-all">
                        </div>
                        <div class="flex-1 h-full flex flex-col justify-center pl-10 pr-6 pt-6 space-y-6">
                            <div class="flex flex-col leading-none">
                                <span class="text-[32px] font-bold text-black uppercase mb-3">MRP:</span>
                                <span class="text-[54px] font-black text-black tracking-tight">
                                    ₹ <?php echo safe(
                                            $current_label["price_india_mrp"] ?? "0"
                                        ); ?>
                                </span>
                            </div>
                            <div class="flex flex-col leading-none">
                                <span class="text-[32px] font-bold text-black uppercase mb-2">QTY:</span>
                                <span class="text-[54px] font-black text-black">
                                    <?php echo safe(
                                        $current_label["quantity_received"] ?? "1"
                                    ); ?>
                                </span>
                            </div>
                            <div class="flex flex-col leading-none">
                                <span class="text-[32px] font-bold text-black uppercase mb-2">COLOR:</span>
                                <span class="text-[42px] font-bold text-black leading-tight">
                                    <?php echo safe(
                                        $current_label["color"] ?? "N/A"
                                    ); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="flex flex-row w-full h-[160px] items-center border-b-[3px] border-black">
                        <div class="w-[340px] pl-8 flex flex-col justify-center h-full">
                            <div class="text-[50px] font-black tracking-tight leading-none mb-3 text-black">
                                <?php echo $itemCode; ?>
                            </div>
                            <div class="text-[30px] font-bold text-black leading-none">
                                <?php
                                $dt = $current_label['gate_entry_date_time'] ?? '';
                                echo ($dt) ? date('dS M Y', strtotime($dt)) : date('dS M Y');
                                ?>
                            </div>
                        </div>
                        <div class="w-[240px] pl-4 flex flex-col justify-center h-full leading-none">
                            <span class="text-[30px] font-bold uppercase text-black mb-2">WEIGHT:</span>
                            <span class="text-[42px] font-black text-black">
                                <?php echo safe($current_label['weight'] ?? '0');
                                echo safe($current_label['weight_unit'] ?? 'kg'); ?>
                            </span>
                        </div>
                        <div class="w-[320px] pl-4 flex flex-col justify-center h-full leading-none">
                            <span class="text-[30px] font-bold uppercase text-black mb-2">WxHxD:</span>
                            <span class="text-[42px] font-black text-black whitespace-nowrap">
                                <?php echo $dims; ?>
                            </span>
                        </div>
                        <div class="flex-1 pl-4 flex flex-col justify-center h-full leading-none">
                            <span class="text-[30px] font-bold uppercase text-black mb-2">SIZE:</span>
                            <span class="text-[42px] font-black text-black">
                                <?php echo safe($current_label['size'] ?? '-'); ?>
                            </span>
                        </div>
                    </div>

                    <div class="flex-1 w-full flex flex-col justify-center pl-8">
                        <span class="text-[32px] font-bold uppercase text-black mb-1 leading-none">VENDOR:</span>
                        <span class="text-[48px] font-black text-black tracking-tight leading-tight block w-full pr-4 pb-2">
                            <?php echo safe($current_label['vendor_name'] ?? 'Jagapoorani Arts & Crafts'); ?>
                        </span>
                    </div>
                <?php  } ?>
            </div>

        <?php endfor;

        // End Loop for Printing N Copies
        ?>

    <?php endforeach;
    // End Loop for Variations
    ?>

</div>

<script>
    const recordId = "<?php echo isset($_GET["id"]) ? $_GET["id"] : ""; ?>";

    document.getElementById("back-btn").addEventListener("click", () => {
        window.history.back();
    });
    document.getElementById("cancel-btn").addEventListener("click", () => {
        window.location.href = window.location.origin + "/index.php?page=inbounding&action=list";
    });

    // Generate QRs
    window.addEventListener('load', function() {
        const qrContainers = document.querySelectorAll(".qrcode-highres");
        qrContainers.forEach(container => {
            container.innerHTML = "";
            new QRCode(container, {
                text: "<?php echo $currentUrl; ?>",
                width: 300,
                height: 300,
                colorDark: "#000000",
                colorLight: "#ffffff",
                correctLevel: QRCode.CorrectLevel.H
            });
        });
        setTimeout(updateLivePreview, 600);
    });

    function updateLivePreview() {
        const source = document.getElementById('high-res-print-area');
        const container = document.getElementById('preview-container');
        if (!source || !container) return;

        const clone = source.cloneNode(true);
        clone.removeAttribute('id');
        clone.classList.remove('fixed', 'top-0', '-left-[9999px]');
        clone.style.transform = "scale(0.25)";
        clone.style.transformOrigin = "top left";
        clone.style.position = "absolute";

        const originalCanvases = source.querySelectorAll('canvas');
        const clonedCanvases = clone.querySelectorAll('canvas');
        originalCanvases.forEach((orig, index) => {
            if (clonedCanvases[index]) {
                clonedCanvases[index].width = orig.width;
                clonedCanvases[index].height = orig.height;
                clonedCanvases[index].getContext('2d').drawImage(orig, 0, 0);
            }
        });

        container.innerHTML = '';
        // Calculate dynamic height based on actual printed items (children of source)
        const totalHeight = source.children.length * 800;
        container.style.width = (1200 * 0.25) + "px";
        container.style.height = (totalHeight * 0.25) + "px";
        container.appendChild(clone);
    }

    async function generatePDF() {
        const {
            jsPDF
        } = window.jspdf;
        // This selector will now pick up ALL copies (e.g. 5 copies of var A + 2 copies of var B)
        const labels = document.querySelectorAll('#high-res-print-area .single-label-item');

        if (labels.length === 0) {
            alert("No data to print.");
            return;
        }

        const pdf = new jsPDF({
            orientation: 'landscape',
            unit: 'mm',
            format: [76.2, 50.8]
        });

        const btn = document.querySelector("button[onclick='generatePDF()']");
        const originalHtml = btn.innerHTML;
        btn.innerHTML = "Processing...";
        btn.disabled = true;

        try {
            for (let i = 0; i < labels.length; i++) {
                if (i > 0) pdf.addPage([76.2, 50.8], 'landscape');
                const element = labels[i];
                const canvas = await html2canvas(element, {
                    scale: 2,
                    useCORS: true,
                    backgroundColor: "#ffffff",
                    logging: false,
                    windowWidth: 1200,
                    windowHeight: 800,
                    onclone: (clonedDoc) => {
                        const origQR = element.querySelector('.qrcode-highres canvas');
                        const clonedQR = clonedDoc.querySelector('.qrcode-highres canvas');
                        if (origQR && clonedQR) {
                            const ctx = clonedQR.getContext('2d');
                            clonedQR.width = origQR.width;
                            clonedQR.height = origQR.height;
                            ctx.drawImage(origQR, 0, 0);
                        }
                    }
                });
                const imgData = canvas.toDataURL("image/jpeg", 1.0);
                pdf.addImage(imgData, "JPEG", 0, 0, 76.2, 50.8);
            }
            pdf.save("Inbound_Labels.pdf");
        } catch (err) {
            console.error("PDF Error:", err);
            alert("An error occurred while generating the PDF.");
        } finally {
            btn.innerHTML = originalHtml;
            btn.disabled = false;
        }
    }
</script>