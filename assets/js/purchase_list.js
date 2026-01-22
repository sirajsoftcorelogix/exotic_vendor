
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

        // Ensure thread exists & mark it as loaded so we can append immediately
        const thread = document.getElementById(`commentsThread_${purchaseListId}`);
        if (thread && !thread.dataset.loaded) {
            thread.dataset.loaded = "1";
            if (!thread.innerHTML.trim()) {
            thread.innerHTML = ""; // clear "Loading..." etc
            }
        }

        const payload = new URLSearchParams();
        payload.set("purchase_list_id", purchaseListId);
        payload.set("comment", text);

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
        const qty = qtyEl ? qtyEl.value : "";

        const eddEl = document.getElementById('edd_' + id);
        const edd = (eddEl && eddEl.value && eddEl.value.trim() !== '') ? eddEl.value : null;

        if (!btn) btn = {};
        btn.disabled = true;
        const originalText = btn.innerHTML || '';
        btn.innerHTML = 'Saving...';

        try {
            // ✅ 1) Save comment first (if any)
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
                expected_time_of_delivery: edd
            })
            });

            const data = await r.json();
            if (data && data.success) {
                //showAlertP('Saved successfully', 'success');
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
        wrap.classList.toggle('hidden');

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
          <span class="ml-1">commented on</span>
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

  async function addComment(purchaseListId, parentId = null,page='mpl') {
    
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

    // Append new comment
    const thread = document.getElementById(`commentsThread_${purchaseListId}`);
    const newHtml = commentHtml(data.comment);

    if (parentId) {
      // insert reply after parent node
      const parentNode = thread.querySelector(`[data-comment-id="${parentId}"]`);
      parentNode.insertAdjacentHTML("afterend", newHtml);      
    } else if(page==='mpl'){
      thread.insertAdjacentHTML("beforeend", newHtml);
    }

    input.value = "";
  }