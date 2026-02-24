// purchase_list.js
function openImagePopup(imageUrl) {
    popupImage.src = imageUrl;
    document.getElementById('imagePopup').classList.remove('hidden');
}

function closeImagePopup(event) {
    document.getElementById('imagePopup').classList.add('hidden');
    document.getElementById('popupImage').src = '';
}

// Prevent typing negative numbers
document.addEventListener('keydown', (e) => {
    if (e.target.classList.contains('no-negative')) {
        // Block '-' key, 'e' for scientific notation
        if (e.key === '-' || e.key === 'e' || e.key === '+') {
            e.preventDefault();
        }
    }
});

// Prevent pasting negative numbers
document.addEventListener('paste', (e) => {
    const t = e.target;
    if (t.classList.contains('no-negative')) {
        const paste = (e.clipboardData || window.clipboardData).getData('text');
        if (paste.trim().startsWith('-')) {
            e.preventDefault();
        }
    }
});

// Prevent using scroll to go negative
document.addEventListener('input', (e) => {
    if (e.target.classList.contains('no-negative')) {
        if (e.target.value < 0) e.target.value = '';
    }
});


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

async function saveMainCommentIfAny(purchaseListId,sku,orderID) {
    const text = getMainCommentText(purchaseListId);
    if (!text) return { success: true, skipped: true };

    // Ensure thread exists & mark it as loaded so we can append immediately
    const thread = document.getElementById(`commentsThread_${purchaseListId}`);
    if (thread && !thread.dataset.loaded) {
        thread.dataset.loaded = "1";
        if (!thread.innerHTML.trim()) {
            thread.innerHTML = ""; // clear "Loading..." etc
        }
    }

    const payload = new URLSearchParams();
    payload.set("sku", sku);
    payload.set("purchase_list_id", purchaseListId);
    payload.set("comment", text);
    payload.set("orderID", orderID);

    let res;
    try {
        res = await fetch("?page=products&action=addComment", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: payload.toString(),
        });
    } catch (e) {
        return { success: false, message: "Network error while saving comment" };
    }

    let data;
    try {
        data = await res.json();
    } catch (e) {
        return { success: false, message: "Invalid server response for comment" };
    }

    if (!data.success) {
        return { success: false, message: data.message || "Failed to add comment" };
    }

    // ✅ Append new comment to HTML (create thread area if needed)
    if (thread) {
        // if it said "No comments yet." replace it
        if (thread.textContent.includes("No comments yet")) thread.innerHTML = "";
        thread.insertAdjacentHTML("beforeend", commentHtml(data.comment));
    }

    clearMainComment(purchaseListId);
    return { success: true, skipped: false, comment: data.comment };
}

async function savePurchaseItem(id, btn) {
  const qtyEl = document.getElementById('quantity_' + id);
  const qty = qtyEl ? String(qtyEl.value ?? "") : ""; 

  const eddEl = document.getElementById('edd_' + id);
  const eddRaw = eddEl ? String(eddEl.value ?? "") : "";
  const edd = eddRaw.trim() !== "" ? eddRaw : null;

  const statusEl = document.getElementById('status_' + id);
  const status = statusEl ? String(statusEl.value ?? "") : "";

  const prodcutEl = document.getElementById('productId_' + id);
  const productId = prodcutEl ? String(prodcutEl.value ?? "") : "";

  const skuEl = document.getElementById('sku_' + id);
  const sku = skuEl ? String(skuEl.value ?? "") : "";

  const orderIDEl = document.getElementById('orderId_' + id);
  const orderID = orderIDEl ? String(orderIDEl.value ?? "") : "";

  // ✅ Compare against original values (stored in data-original)
  //const originalQty = qtyEl?.dataset?.original ?? "";
  const originalEddRaw = eddEl?.dataset?.original ?? "";
  const originalEdd = originalEddRaw.trim() !== "" ? originalEddRaw : null;
  const originalStatus = statusEl?.dataset?.original ?? "";

  const changed = 
    edd !== originalEdd ||
    status !== String(originalStatus);

  if (!btn) btn = {};
  btn.disabled = true;
  const originalText = btn.innerHTML || '';
  btn.innerHTML = 'Saving...';

  try {
    // ✅ 1) Save comment first (if any)
    const c = await saveMainCommentIfAny(id, sku, orderID);
    if (!c.success) {
      showAlertP("Comment not saved: " + (c.message || "Error"), "error");
      return;
    }

    // ✅ 2) Only call API if qty/edd/status changed
    if (!changed) {
      showAlertP("No changes to save.", "success");
      return;
    }

    const r = await fetch('?page=products&action=update_purchase_item', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        id: id,
        quantity: qty,
        expected_time_of_delivery: edd,
        status: status,
        product_id: productId,
        sku: sku,
        orderID: orderID
      })
    });

    const data = await r.json();
    if (data && data.success) {
      // update originals so next save doesn't re-fire unnecessarily
      if (qtyEl) qtyEl.dataset.original = qty;
      if (eddEl) eddEl.dataset.original = (edd ?? "");
      if (statusEl) statusEl.dataset.original = status;

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
            body: JSON.stringify({
                purchase_list_id: id
            })
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
            body: JSON.stringify({
                purchase_list_id: id
            })
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


function toggleComments(purchaseListId) {
    const wrap = document.getElementById(`commentsWrap_${purchaseListId}`);
    const thread = document.getElementById(`commentsThread_${purchaseListId}`);

    if (!wrap) return; // safety

    // toggle visibility
    thread.classList.toggle('hidden');

    // load only once
    if (!thread.dataset.loaded) {
        loadComments(purchaseListId).then(() => {
            thread.dataset.loaded = "1";
        });
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
          <span class="ml-1">-</span>
          <span class="ml-1">${escapeHtml(when)}</span>
        </div>
        <div class="mt-1 text-sm text-gray-900">${text}</div>        
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

    const res = await fetch(`?page=products&action=comment_list&purchase_list_id=${purchaseListId}`);
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

async function addComment(purchaseListId, parentId = null, page = 'mpl',orderID=null) {

    let inputId = parentId
        ? `replyInput_${purchaseListId}_${parentId}`
        : `commentInput_${purchaseListId}`;

    const input = document.getElementById(inputId);
    if (!input) return; // input missing (UI closed etc.)

    const text = (input.value || "").trim();   console.log(text); 

    // 🚫 If comment is empty or whitespace → cancel
    if (!text.length) return;

    input.disabled = true;

    const payload = new URLSearchParams();
    payload.set("purchase_list_id", purchaseListId);
    payload.set("comment", text);
    payload.set("orderID", orderID);
    
    if (parentId) payload.set("parent_id", parentId);

    const res = await fetch("?page=products&action=addComment", {
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

    // Inject new comment
    const thread = document.getElementById(`commentsThread_${purchaseListId}`);
    const newHtml = commentHtml(data.comment);

    if (parentId) {
        const parentNode = thread.querySelector(`[data-comment-id="${parentId}"]`);
        parentNode.insertAdjacentHTML("afterend", newHtml);
    } else if (page === 'mpl') {
        thread.insertAdjacentHTML("beforeend", newHtml);
    }

    input.value = "";
}

function checkMinStock(id) {
    const qtyInput = document.getElementById(`quantity_${id}`);
    const minStock = parseInt(document.getElementById(`minStock_${id}`).value || 0);
    const qty = parseInt(qtyInput.value || 0);

    console.log('Checking min stock:', { qty, minStock });

    if (qty > 0 && qty < minStock) {
        document.getElementById('errorMessage').innerText =
            `Entered quantity (${qty}) is less than minimum stock (${minStock}).`;

        document.getElementById('errorModal').classList.remove('hidden');
        qtyInput.value = '';
        qtyInput.focus();
    }
}

function closeErrorModal() {
    document.getElementById('errorModal').classList.add('hidden');
}

function initMasterPurchaseTable() {
    const tableEl = document.getElementById('master-purchase-table');
    if (!tableEl) return;

    tableEl.addEventListener('click', (e) => {
        const btn = e.target.closest('button.details-btn');
        if (!btn) return;

        const tr = btn.closest('tr');
        const detailRow = tr ? tr.nextElementSibling : null;
        if (!detailRow || !detailRow.classList.contains('detail-row')) return;

        detailRow.classList.toggle('hidden');
        const icon = btn.querySelector('.details-icon');
        if (icon) icon.textContent = detailRow.classList.contains('hidden') ? '+' : '-';
    });
}

document.addEventListener('DOMContentLoaded', () => {
    initMasterPurchaseTable();
});
// End of purchase_list.js
