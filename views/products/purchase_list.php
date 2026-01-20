<style>
/* Hide native date icon without breaking click behavior (Chrome/Edge/Safari) */
input[type="date"]::-webkit-calendar-picker-indicator {
  opacity: 0;
  width: 2.5rem;      /* keep click area */
  height: 100%;
  cursor: pointer;
}

/* Optional: avoid weird default styling */
input[type="date"] {
  -webkit-appearance: none;
  appearance: none;
}
</style>    
<div class="container mx-auto p-4">
    <div class="mb-4 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div class="flex justify-between items-center">
            <h1 class="text-2xl font-bold">Purchase List</h1>
            <div class="text-sm text-gray-600 ml-auto px-2">Showing <strong><?php echo isset($data['total_records']) ? (int)$data['total_records'] : 0; ?></strong> items</div>
        </div>
        <form method="GET" action="" class="flex items-center gap-3">
            <input type="hidden" name="page" value="products">
            <input type="hidden" name="action" value="purchase_list">
            <div class=" gap-2 w-1/2 flex-1">
                <label class="text-xs text-gray-600">Category</label><br>
                <select name="category" class="text-sm border rounded px-2 py-1 bg-white" onchange="this.form.submit()">
                    <option value="all">All</option>
                    <?php foreach (($data['categories'] ?? []) as $cat): ?>
                        <option value="<?= htmlspecialchars($cat) ?>" <?= (isset($data['selected_filters']['category']) && $data['selected_filters']['category'] === $cat) ? 'selected' : '' ?>><?= htmlspecialchars($cat) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class=" gap-2 w-1/2 ml-2 flex-2">
                <label class="text-xs text-gray-600">Status</label><br>
                <select name="status" class="text-sm border rounded px-2 py-1 bg-white w-full" onchange="this.form.submit()">
                    <option value="all" <?= (isset($data['selected_filters']['status']) && $data['selected_filters']['status'] === 'all') ? 'selected' : '' ?>>All</option>
                    <option value="pending" <?= (isset($data['selected_filters']['status']) && $data['selected_filters']['status'] === 'pending') ? 'selected' : '' ?> <?= (!isset($data['selected_filters']['status']) ? 'selected' : '') ?>>Pending</option>
                    <option value="purchased" <?= (isset($data['selected_filters']['status']) && $data['selected_filters']['status'] === 'purchased') ? 'selected' : '' ?>>Purchased</option>
                    <option value="item_ordered" <?= (isset($data['selected_filters']['status']) && $data['selected_filters']['status'] === 'item_ordered') ? 'selected' : '' ?>>Item Ordered</option>
                    <option value="item_not_available" <?= (isset($data['selected_filters']['status']) && $data['selected_filters']['status'] === 'item_not_available') ? 'selected' : '' ?>> Item not available</option>
                </select>
            </div>
            <!-- <div class="flex items-center gap-2">
                <button type="submit" class="bg-amber-600 text-white px-3 py-1 rounded">Filter</button>
                <a href="?page=products&action=purchase_list" class="px-3 py-1 border rounded text-sm">Clear</a>
            </div> -->
        </form>
    </div>

    <?php if (!empty($data['purchase_list'])): ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php
            foreach ($data['purchase_list'] as $pl):
                //$product = $pl['product'] ?? null;
                $image = $pl['image'] ?? 'https://placehold.co/100x140/e2e8f0/4a5568?text=No+Image';
                $title = $pl['title'] ?? ($pl['item_code'] ?? 'Product');
                $item_code = $pl['item_code'] ?? ($pl['sku'] ?? '');
                $cost = isset($pl['cost_price']) ? '₹' . number_format((float)$pl['cost_price']) : '';
                $status = str_replace("_"," ",$pl['status']) ?? '';
                $agent_name = $pl['agent_name'] ?? '';
                $date_added = $pl['date_added_readable'] ?? ($pl['date_added'] ? date('d M Y', strtotime($pl['date_added'])) : '');
                $date_purchased = $pl['date_purchased_readable'] ?? ($pl['date_purchased'] ? date('d M Y', strtotime($pl['date_purchased'])) : '');

                // Build WhatsApp share text
                $waText = "Product Details:%0A";
                //$waText .= "Item: " . urlencode($title) . "%0A";
                $waText .= "SKU: " . urlencode($pl['sku'] ?? '') . "%0A";
                $waText .= "Color: " . urlencode($pl['color'] ?? '') . "%0A";
                $waText .= "Size: " . urlencode($pl['size'] ?? '') . "%0A";
                $waText .= "Dimensions (HxWxD): " . urlencode(($pl['prod_height'] ?? '') . ' x ' . ($pl['prod_width'] ?? '') . ' x ' . ($pl['prod_length'] ?? '')) . "%0A";
                $waText .= "Weight: " . urlencode(($pl['product_weight'] ?? '') . ' ' . ($pl['product_weight_unit'] ?? '')) . "%0A";
                $waText .= "Image: " . urlencode($image) . "%0A";
            ?>
                <div class="bg-white border border-gray-300 rounded-3xl shadow-lg p-4">
                    <div class="mt-0 flex justify-end">
                        <a href="https://wa.me/?text=<?= $waText; ?>"
                            target="_blank"
                            class="text-yellow-900 hover:text-yellow-1000 flex items-center space-x-1 text-sm">

                            <!-- Share Icon (arrow) -->
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path d="M4 12v7a1 1 0 001 1h14a1 1 0 001-1v-7"/>
                                <path d="M12 3l5 5h-3v6h-4V8H7l5-5z"/>
                            </svg>
                        </a>
                    </div>

                    <div class="flex space-x-4">
                        <img src="<?php echo htmlspecialchars($image); ?>" alt="<?php echo htmlspecialchars($title); ?>" class="w-24 h-32 object-cover rounded-md flex-shrink-0">
                        <div class="flex-1">
                            <div class="flex items-start justify-between">
                                <div>
                                    <div class="text-sm font-semibold text-gray-800"><?php echo htmlspecialchars($title); ?></div>
                                    <div class="text-xs text-gray-500 mt-1">Item Code: <strong><?php echo htmlspecialchars($item_code); ?></strong></div>
                                    <div class="text-xs text-gray-500">Status: <span class="font-medium px-2 bg-<?php echo $status === 'purchased' ? 'green' : 'yellow'; ?>-100 text-<?php echo $status === 'purchased' ? 'green' : 'yellow'; ?>-800"><?php echo ucwords(htmlspecialchars($status)); ?></span></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div>
                        <div class="mt-3 grid grid-cols-2 gap-2 text-xs text-gray-600">
                            <div>Assigned Agent : <strong><?php echo htmlspecialchars($agent_name); ?></strong></div>
                            <?php if (!empty($pl['vendor']) && strtoupper(trim($pl['vendor'])) !== 'N/A'): ?>
                                <div>
                                    Vendor : <strong><?php echo ucwords($pl['vendor']); ?></strong>
                                </div>
                            <?php endif; ?>

                            <div>Date Added : <strong><?php echo htmlspecialchars($date_added); ?></strong></div>
                            <?php if (!empty($date_purchased) && $date_purchased != 'N/A') { ?>
                                <div>
                                    Date Purchased :
                                    <strong><?= htmlspecialchars($date_purchased) ?></strong>
                                </div>
                            <?php } ?>

                            <div>SKU : <strong><?php echo htmlspecialchars($pl['sku'] ?? ''); ?></strong></div>
                            <div>Color : <strong><?php echo htmlspecialchars($pl['color'] ?? ''); ?></strong></div>
                            <div>Size : <strong><?php echo htmlspecialchars($pl['size'] ?? ''); ?></strong></div>
                            <div>Material : <strong><?php echo htmlspecialchars($pl['material'] ?? ''); ?></strong></div>
                            <div>Dimensions : <strong><?php echo htmlspecialchars($pl['prod_height'] ?? ''); ?> x <?php echo htmlspecialchars($pl['prod_width'] ?? ''); ?> x <?php echo htmlspecialchars($pl['prod_length'] ?? ''); ?></strong></div>
                            <div>Weight : <strong><?php echo htmlspecialchars($pl['product_weight'] ?? '') . ' ' . htmlspecialchars($pl['product_weight_unit'] ?? ''); ?></strong></div>

                            <label class="block">
                                Quantity to be Purchased:
                                <span class="inline-block bg-gray-100 border rounded px-2 py-1 mt-1 w-16 text-center">
                                    <?php echo htmlspecialchars($pl['quantity'] ?? '0'); ?>
                                </span>
                            </label>

                            <label class="block">Quantity Purchased: <input type="number" id="quantity_<?php echo (int)$pl['id']; ?>" value="" class="border rounded px-2 py-1 mt-1 w-16"></label>

                        </div>

                        <?php if (isset($pl['status']) && $pl['status'] === 'item_ordered'): ?>
                            <div class="mt-3">
                                <label for="edd_<?php echo (int)$pl['id']; ?>" class="block text-xs text-gray-600">
                                    Expected Delivery Date
                                </label>

                                <div class="mt-1 relative w-full md:w-48">
                                    <input
                                    type="date"
                                    id="edd_<?php echo (int)$pl['id']; ?>"
                                    name="edd"
                                    value="<?php echo htmlspecialchars($pl['expected_time_of_delivery'] ?? ''); ?>"
                                    class="border rounded px-3 py-2 pr-10 text-sm w-full bg-white focus:outline-none"
                                    />

                                    <!-- Clickable yellow calendar icon -->
                                    <button
                                    type="button"
                                    class="absolute inset-y-0 right-2 flex items-center px-2 text-amber-500"
                                    onclick="(function(btn){
                                        const input = btn.parentElement.querySelector('input[type=date]');
                                        if (!input) return;
                                        if (typeof input.showPicker === 'function') input.showPicker();
                                        else input.focus();
                                    })(this)"
                                    aria-label="Open calendar"
                                    >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    </button>
                                </div>
                                </div>

                        <?php endif; ?>

                        <!--<div class="mt-3 grid grid-cols-1 gap-2 text-xs text-gray-600">
                            <label class="block">Remarks: <textarea id="remarks_<?php //echo (int)$pl['id']; ?>" class="border rounded px-2 py-1 mt-1 w-full" rows="2"><?php //echo htmlspecialchars($pl['remarks'] ?? ''); ?></textarea></label>
                        </div>-->

                        <div class="mt-3">
                            <button
                                type="button"
                                class="text-sm text-blue-600 hover:underline"
                                onclick="toggleComments(<?= (int)$pl['id']; ?>)">
                                Comments
                            </button>

                            <div id="commentsWrap_<?= (int)$pl['id']; ?>" class="mt-3 hidden">
                                <!-- Thread list -->
                                <div id="commentsThread_<?= (int)$pl['id']; ?>" class="space-y-3"></div>

                                <!-- Add comment -->
                                <div class="mt-3 flex gap-2">
                                <input
                                    id="commentInput_<?= (int)$pl['id']; ?>"
                                    class="w-full border rounded px-3 py-2 text-sm"
                                    placeholder="Write a comment..."
                                />
                                </div>
                            </div>
                        </div>


                        <div class="mt-4 flex items-center justify-end space-x-2">
                            <button onclick="savePurchaseItem(<?= (int)$pl['id']; ?>, this)" class="px-3 py-1 bg-blue-600 text-white rounded text-sm">
                                Save
                            </button>

                            <?php if ($pl['status'] === 'pending'): ?>
                                <button onclick="markAsPurchased(<?= (int)$pl['id']; ?>)" class="px-3 py-1 bg-amber-600 text-white rounded text-sm">
                                    Mark Purchased
                                </button>
                            <?php else: ?>
                                <button onclick="markUnpurchased(<?= (int)$pl['id']; ?>)" class="px-3 py-1 bg-red-600 text-white rounded text-sm">
                                    Mark Unpurchased
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <div class="mt-6 flex items-center justify-center space-x-2">
            <?php $page_no = $data['page_no'] ?? 1;
            $total_pages = $data['total_pages'] ?? 1;
            $limit = $data['limit'] ?? 50;
            $query_string = '';
            // preserve existing filters in query string (if any)
            $qs = $_GET;
            unset($qs['page_no'], $qs['limit']);
            $query_string = http_build_query($qs);
            $query_string = $query_string ? '&' . $query_string : '';
            ?>
            <?php if ($page_no > 1): ?>
                <a href="?page=products&action=purchase_list&page_no=<?php echo max(1, $page_no - 1); ?>&limit=<?php echo $limit . $query_string; ?>" class="px-3 py-1 border rounded">&laquo; Prev</a>
            <?php endif; ?>
            <span class="px-3 py-1 text-sm">Page <?php echo $page_no; ?> of <?php echo $total_pages; ?></span>
            <?php if ($page_no < $total_pages): ?>
                <a href="?page=products&action=purchase_list&page_no=<?php echo min($total_pages, $page_no + 1); ?>&limit=<?php echo $limit . $query_string; ?>" class="px-3 py-1 border rounded">Next &raquo;</a>
            <?php endif; ?>
        </div>

    <?php else: ?>
        <div class="bg-white rounded-lg shadow-sm p-6 text-center text-gray-600">No items in purchase list.</div>
    <?php endif; ?>
</div>

<script>
    // Use global helpers when available (defined in layout). Fallbacks included.
    function showAlertP(message, type = 'info', timeout = 3000) {
        if (window.showGlobalToast) return window.showGlobalToast(message, type, timeout);
        alert(message);
    }

    function getMainCommentText(purchaseListId) {
        const el = document.getElementById(`commentInput_${purchaseListId}`);
        if (!el) return "";
        return (el.value || "").trim();
    }

    function clearMainComment(purchaseListId) {
        const el = document.getElementById(`commentInput_${purchaseListId}`);
        if (el) el.value = "";
    }

    async function saveMainCommentIfAny(purchaseListId) {
    const text = getMainCommentText(purchaseListId);
    if (!text) return { success: true, skipped: true };

    const payload = new URLSearchParams();
    payload.set("purchase_list_id", purchaseListId);
    payload.set("comment", text);

    const res = await fetch("?page=purchase_list_comments&action=list", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: payload.toString(),
    });

    const data = await res.json();

    if (!data.success) {
        return { success: false, message: data.message || "Failed to add comment" };
    }

    // Append immediately if thread is loaded, else just clear input
    const thread = document.getElementById(`commentsThread_${purchaseListId}`);
    if (thread && thread.dataset.loaded === "1") {
        thread.insertAdjacentHTML("beforeend", commentHtml(data.comment));
    } else {
        // next time user opens comments, it will load from server
        // (optional) you can auto-load here if you want:
        // await loadComments(purchaseListId);
    }

    clearMainComment(purchaseListId);
    return { success: true, skipped: false };
    }


    async function savePurchaseItem(id, btn) {
        const qtyEl = document.getElementById('quantity_' + id);
        const qty = qtyEl ? qtyEl.value : "";

        //const remarksEl = document.getElementById('remarks_' + id);
        //const remarks = remarksEl ? remarksEl.value : ""; // avoid crash if remarks is removed

        const eddEl = document.getElementById('edd_' + id);
        const edd = (eddEl && eddEl.value && eddEl.value.trim() !== '') ? eddEl.value : null;

        if (!btn) btn = {};
        btn.disabled = true;
        const originalText = btn.innerHTML || '';
        btn.innerHTML = 'Saving...';

        try {
            // ✅ 1) Save comment if user typed something
            const c = await saveMainCommentIfAny(id);
            if (!c.success) {
                showAlertP("Comment not saved: " + (c.message || "Error"), "error");
                return;
            }

            // ✅ 2) Save purchase item
            const r = await fetch('?page=products&action=update_purchase_item', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                id: id,
                quantity: qty,
                //remarks: remarks,
                expected_time_of_delivery: edd
            })
            });

            const data = await r.json();
            if (data && data.success) {
                showAlertP('Saved successfully', 'success');
                setTimeout(() => location.reload(), 800);
            } else {
                showAlertP('Failed: ' + (data.message || 'Error'), 'error');
            }
        } catch (err) {
            showAlertP('Network error', 'error');
        } finally {
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    }


    async function markAsPurchased(id) {
        const confirmed = window.customConfirm
        ? await window.customConfirm('Mark this item as purchased?')
        : confirm('Mark this item as purchased?');
        if (!confirmed) return;

        try {
            // ✅ Save comment if typed
            const c = await saveMainCommentIfAny(id);
            if (!c.success) {
            showAlertP("Comment not saved: " + (c.message || "Error"), "error");
            return;
            }

            const r = await fetch('?page=products&action=mark_purchased', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id })
            });

            const data = await r.json();
            if (data && data.success) {
            showAlertP('Marked as purchased', 'success');
            setTimeout(() => location.reload(), 900);
            } else {
            showAlertP('Failed: ' + (data.message || 'Error'), 'error');
            }
        } catch (err) {
            showAlertP('Network error', 'error');
        }
    }

    async function markUnpurchased(id) {
        const confirmed = window.customConfirm
            ? await window.customConfirm('Mark this item as unpurchased?')
            : confirm('Mark this item as unpurchased?');
        if (!confirmed) return;

        try {
            // ✅ Save comment if typed
            const c = await saveMainCommentIfAny(id);
            if (!c.success) {
            showAlertP("Comment not saved: " + (c.message || "Error"), "error");
            return;
            }

            const r = await fetch('?page=products&action=mark_unpurchased', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id })
            });

            const data = await r.json();
            if (data && data.success) {
            showAlertP('Marked as unpurchased', 'success');
            setTimeout(() => location.reload(), 900);
            } else {
            showAlertP('Failed: ' + (data.message || 'Error'), 'error');
            }
        } catch (err) {
            showAlertP('Network error', 'error');
        }
    }
  
    async function toggleComments(purchaseListId) {
        const wrap = document.getElementById(`commentsWrap_${purchaseListId}`);
        wrap.classList.toggle("hidden");

        // Load once (basic guard)
        const thread = document.getElementById(`commentsThread_${purchaseListId}`);
        if (!thread.dataset.loaded) {
            await loadComments(purchaseListId);
            thread.dataset.loaded = "1";
        }
    }

  function formatDateTime(dateStr) {
    // Expects ISO-ish "YYYY-MM-DD HH:MM:SS" from PHP
    const d = new Date(dateStr.replace(" ", "T"));
    if (isNaN(d.getTime())) return dateStr;

    return d.toLocaleString(undefined, {
      day: "2-digit",
      month: "short",
      year: "numeric",
      hour: "2-digit",
      minute: "2-digit",
      hour12: true,
    });
  }

  function escapeHtml(str) {
    return String(str)
      .replaceAll("&", "&amp;")
      .replaceAll("<", "&lt;")
      .replaceAll(">", "&gt;")
      .replaceAll('"', "&quot;")
      .replaceAll("'", "&#039;");
  }

  function commentHtml(c) {
    const user = escapeHtml(c.user_name || "User");
    const when = formatDateTime(c.created_at);
    const text = c.is_deleted ? "<em class='text-gray-500'>Comment deleted</em>" : escapeHtml(c.comment);

    // indent replies
    const indent = c.parent_id ? "ml-8 border-l pl-4" : "";

    return `
      <div class="rounded-lg border p-3 ${indent}" data-comment-id="${c.id}">
        <div class="text-xs text-gray-600">
          <span class="font-semibold">${user}</span>
          <span class="ml-1">commented on</span>
          <span class="ml-1">${escapeHtml(when)}</span>
        </div>
        <div class="mt-1 text-sm text-gray-900">${text}</div>

        <div class="mt-2 flex items-center gap-3 text-xs">
          <button class="text-blue-600 hover:underline" onclick="showReplyBox(${c.purchase_list_id}, ${c.id})">Reply</button>
        </div>

        <div id="replyBox_${c.purchase_list_id}_${c.id}" class="mt-2 hidden">
          <div class="flex gap-2">
            <input id="replyInput_${c.purchase_list_id}_${c.id}" class="w-full border rounded px-3 py-2 text-sm" placeholder="Write a reply..." />
            <button class="px-3 py-2 rounded bg-gray-900 text-white text-sm"
              onclick="addComment(${c.purchase_list_id}, ${c.id})"
            >Send</button>
          </div>
        </div>
      </div>
    `;
  }

  function showReplyBox(purchaseListId, commentId) {
    const el = document.getElementById(`replyBox_${purchaseListId}_${commentId}`);
    el.classList.toggle("hidden");
  }

  async function loadComments(purchaseListId) {
    const thread = document.getElementById(`commentsThread_${purchaseListId}`);
    thread.innerHTML = "<div class='text-sm text-gray-500'>Loading...</div>";

    const res = await fetch(`purchase_list_comments?action=list&purchase_list_id=${purchaseListId}`);
    const data = await res.json();

    if (!data.success) {
      thread.innerHTML = `<div class="text-sm text-red-600">${escapeHtml(data.message || "Failed to load")}</div>`;
      return;
    }

    if (!data.comments.length) {
      thread.innerHTML = "<div class='text-sm text-gray-500'>No comments yet.</div>";
      return;
    }

    // Render in order (server already orders)
    thread.innerHTML = data.comments.map(commentHtml).join("");
  }

  async function addComment(purchaseListId, parentId = null) {
    let inputId = parentId
      ? `replyInput_${purchaseListId}_${parentId}`
      : `commentInput_${purchaseListId}`;

    const input = document.getElementById(inputId);
    const text = (input.value || "").trim();
    if (!text) return;

    input.disabled = true;

    const payload = new URLSearchParams();
    payload.set("purchase_list_id", purchaseListId);
    payload.set("comment", text);
    if (parentId) payload.set("parent_id", parentId);

    const res = await fetch("?page=purchase_list_comments&action=add", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: payload.toString(),
    });

    const data = await res.json();
    input.disabled = false;

    if (!data.success) {
      alert(data.message || "Failed to add comment");
      return;
    }

    // Append new comment
    const thread = document.getElementById(`commentsThread_${purchaseListId}`);
    const newHtml = commentHtml(data.comment);

    if (parentId) {
      // insert reply after parent node
      const parentNode = thread.querySelector(`[data-comment-id="${parentId}"]`);
      parentNode.insertAdjacentHTML("afterend", newHtml);
      // hide reply box
      document.getElementById(`replyBox_${purchaseListId}_${parentId}`).classList.add("hidden");
    } else {
      thread.insertAdjacentHTML("beforeend", newHtml);
    }

    input.value = "";
  }
</script>
