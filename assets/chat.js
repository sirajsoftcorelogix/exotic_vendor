(function(){
  const ws = new WebSocket(window.WS_URL);
  
  const userListEl = document.getElementById('user-list');
  const convListEl = document.getElementById('conversation-list');
  const messagesEl = document.getElementById('messages');
  const titleEl = document.getElementById('chat-title');
  const subtitleEl = document.getElementById('chat-subtitle');
  const presenceEl = document.getElementById('chat-presence');
  const typingIndicatorEl = document.getElementById('typing-indicator');
  const popupContainer = document.getElementById('chat-popup-container');
  const userSearchEl = document.getElementById('user-search');
  const PAGE_STARTED_AT = Date.now();

  const inputEl = document.getElementById('message-input');
  const sendBtn = document.getElementById('send-btn');
  const attachBtn = document.getElementById('attach-btn');
  const fileInput = document.getElementById('file-input');

  const groupModal = document.getElementById("group-modal");
  const groupCloseBtn = document.getElementById("group-close-btn");
  const groupCancelBtn = document.getElementById("group-cancel-btn");
  const groupOverlay = groupModal.querySelector(".modal-overlay");

  let users = [];
  let conversations = [];
  let messages = {};
  let activeConversationId = null;
  let typingTimeout = null;
  let lastTypingSentAt = 0;
  let notificationSound = new Audio("assets/message.mp3");

  let mentionActive = false;
  let mentionStartPos = -1;
  let mentionItems = [];
  let mentionIndex = -1;

  const mentionDropdown = document.getElementById("mention-dropdown");


  const urlParams = new URLSearchParams(window.location.search);
  const openConvParam = urlParams.get("conversation_id");

  ws.onopen = () => {
    console.log('WebSocket connected');
    loadUsers();
    loadConversations();
  };

  ws.onmessage = (ev) => {
    let data;
    try { console.log("Ev Data: " + ev.data); data = JSON.parse(ev.data); } catch(e) { return; }
    handleWsMessage(data);
  };

  ws.onerror = (e) => {
    console.error('WebSocket error', e);
  };

  ws.onclose = () => {
    console.warn('WebSocket closed');
    // try reconnect once after short delay
    setTimeout(()=>{ window.location.reload(); }, 2000);
  };

  sendBtn.addEventListener('click', sendMessage);

  inputEl.addEventListener("keydown", function (e) {

      const cursorPos = inputEl.selectionStart;
      const value = inputEl.value;

      /* =========================
        DETECT MENTION CONTEXT
      ========================= */
      const beforeCursor = value.slice(0, cursorPos);
      const match = beforeCursor.match(/(?:^|\s)@([A-Za-z0-9_]*)$/);

      if (match) {
          mentionActive = true;
          mentionStartPos = cursorPos - match[1].length - 1;
          showMentionDropdown(match[1]);
      } else {
          hideMentionDropdown();
      }

      /* =========================
        MENTION NAVIGATION
      ========================= */
      if (mentionActive) {

          if (e.key === "ArrowDown") {
              e.preventDefault();
              mentionIndex = (mentionIndex + 1) % mentionItems.length;
              updateMentionHighlight();
              return;
          }

          if (e.key === "ArrowUp") {
              e.preventDefault();
              mentionIndex =
                  (mentionIndex - 1 + mentionItems.length) % mentionItems.length;
              updateMentionHighlight();
              return;
          }

          if (e.key === "Enter" && mentionIndex >= 0) {
              e.preventDefault();
              const name = mentionItems[mentionIndex].textContent;
              insertMention(name);
              return;
          }

          if (e.key === "Escape") {
              hideMentionDropdown();
              return;
          }
      }

      /* =========================
        SEND MESSAGE
      ========================= */
      if (e.key === "Enter" && !e.shiftKey) {
          e.preventDefault();
          sendMessage();
          return;
      }

      if (e.key !== "Enter") {
          handleTyping();
      }
  });
  function updateMentionHighlight() {
      mentionItems.forEach((item, idx) => {
          if (idx === mentionIndex) {
              item.classList.add("active");
              item.scrollIntoView({ block: "nearest" });
          } else {
              item.classList.remove("active");
          }
      });
  }
  function showMentionDropdown(filterText) {
    const query = (filterText || "").toLowerCase();

    const filtered = users.filter(u =>
        u.name.toLowerCase().includes(query)
    );

    if (!filtered.length) {
        hideMentionDropdown();
        return;
    }

    mentionDropdown.innerHTML = "";
    mentionItems = [];
    mentionIndex = 0;

    filtered.forEach(u => {
        const div = document.createElement("div");
        div.className = "mention-item";
        div.textContent = u.name;

        div.addEventListener("click", () => insertMention(u.name));

        mentionDropdown.appendChild(div);
        mentionItems.push(div);
    });

    updateMentionHighlight();
    mentionDropdown.classList.remove("hidden");
}

  function insertMention(name) {
      const value = inputEl.value;
      const cursorPos = inputEl.selectionStart;

      const before = value.slice(0, mentionStartPos);
      const after = value.slice(cursorPos);

      inputEl.value = before + "@" + name + " " + after;

      const newPos = before.length + name.length + 2;
      inputEl.setSelectionRange(newPos, newPos);

      hideMentionDropdown();
  }
  document.addEventListener("click", function (e) {
      if (!mentionDropdown.contains(e.target)) {
          hideMentionDropdown();
      }
  });
  function hideMentionDropdown() {
      mentionActive = false;
      mentionStartPos = -1;
      mentionIndex = -1;
      mentionItems = [];
      mentionDropdown.classList.add("hidden");
  }
  // -- End User mentioned

  attachBtn.addEventListener('click', () => { fileInput.click(); });
  fileInput.addEventListener('change', handleFileUpload);
  messagesEl.addEventListener('scroll', () => { if (isAtBottom()) sendReadReceipt(); });
  window.addEventListener('focus', sendReadReceipt);

  /*userSearchEl.addEventListener('input', (e) => {
    renderUserList(userSearchEl.value.trim());
  });*/
  function playSound() {
      notificationSound.play().catch(() => {});
  }
  function loadUsers() {
    fetch(window.API_BASE + '/fetch_users.php', { credentials: 'include' })
      .then(r => r.json())
      .then(data => {
        users = data;
        renderUserList('');
      })
      .catch(err => console.error('fetch_users error', err));
  }
  function renderUserList(filter) {
    userListEl.innerHTML = '';
    const q = (filter || '').toLowerCase();
    users.filter(u => !q || u.name.toLowerCase().includes(q)).forEach(u => {
      const item = document.createElement('div');
      item.className = 'conversation-item';
      item.dataset.userId = u.id;
      item.innerHTML = `
          <div class="conv-avatar ${u.is_online ? 'online' : 'offline'}">U</div>
          <div class="conv-main">
              <div class="conv-title">${escapeHtml(u.name)}</div>
              <div class="conv-sub">${u.is_online ? 'Online' : 'Offline'}</div>
          </div>
      `;
      item.addEventListener('click', () => { openChatWithUser(u.id); });
      userListEl.appendChild(item);
    });
  }
  function openChatWithUser(userId) {
    // create or fetch single conversation via existing API
    fetch(window.API_BASE + '/create_conversation.php', {
      method: 'POST',
      credentials: 'include',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ type: 'single', members: [userId] })
    })
    .then(r => r.json())
    .then(res => {
      if (res.conversation_id) {
        // reload conversations then open
        loadConversations().then(() => {
          setActiveConversation(res.conversation_id);
        });
      }
    })
    .catch(err => console.error('create_conversation error', err));
  }
  function loadConversations() {
    return fetch(window.API_BASE + '/fetch_conversations.php', { credentials: 'include' })
      .then(r => r.json())
      .then(data => {
        conversations = data;
        renderConversationList();

        if (openConvParam) {
          setActiveConversation(parseInt(openConvParam, 10));
        } else if (!activeConversationId && conversations.length > 0) {
          setActiveConversation(conversations[0].id);
        }
        return Promise.resolve();
      })
      .catch(err => { console.error('fetch_conversations error', err); return Promise.reject(err); });
  }
  function renderConversationList() {
    convListEl.innerHTML = '';
    conversations.forEach(conv => {
      const item = document.createElement('div');
      item.className = 'conversation-item' + (conv.id === activeConversationId ? ' active' : '');
      item.dataset.id = conv.id;

      const ava = document.createElement('div');
      ava.className = 'conv-avatar';
      ava.textContent = (conv.type === 'group' ? '#' : 'U');

      const main = document.createElement('div');
      main.className = 'conv-main';

      const title = document.createElement('div');
      title.className = 'conv-title';
      title.textContent = conv.display_name;

      const sub = document.createElement('div');
      sub.className = 'conv-sub';
      sub.textContent = conv.last_message || '';

      main.appendChild(title);
      main.appendChild(sub);

      const meta = document.createElement('div');
      meta.className = 'conv-meta';

      const time = document.createElement('div');
      time.className = 'conv-time';
      time.textContent = conv.last_message_at || '';

      meta.appendChild(time);

      if (conv.unread_count && parseInt(conv.unread_count, 10) > 0) {
        const badge = document.createElement('div');
        badge.className = 'conv-unread';
        badge.textContent = conv.unread_count;
        meta.appendChild(badge);
      }

      const del = document.createElement('button');
      del.textContent = "×";
      del.className = "conv-delete-btn";
      del.addEventListener('click', (e) => {
          e.stopPropagation();
          deleteConversation(conv.id);
      });

      item.appendChild(ava);
      item.appendChild(main);
      item.appendChild(meta);
      item.appendChild(del);

      item.addEventListener('click', () => {
        setActiveConversation(conv.id);
      });

      convListEl.appendChild(item);
    });
  }
  function deleteConversation(convId) {
      if (!confirm("Delete this chat from your list?")) return;

      fetch(window.API_BASE + '/delete_conversation.php', {
          method: "POST",
          credentials: "include",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ conversation_id: convId })
      })
      .then(r => r.json())
      .then(res => {
          if (res.success) {
              // Remove from local array
              conversations = conversations.filter(c => c.id != convId);

              // If currently open, reset UI
              if (activeConversationId === convId) {
                  activeConversationId = null;
                  messagesEl.innerHTML = "";
                  titleEl.textContent = "Select a conversation";
                  subtitleEl.textContent = "";
              }

              // Re-render list
              renderConversationList();
          }
      });
  }
  function setActiveConversation(convId) {
    activeConversationId = convId;

    renderConversationList();

    const conv = conversations.find(c => c.id === convId);
    if (conv) {
      titleEl.textContent = conv.display_name;
      subtitleEl.textContent = conv.type === 'group' ? 'Group chat' : 'Direct chat';
    } else {
      titleEl.textContent = 'Conversation #' + convId;
      subtitleEl.textContent = '';
    }
    const groupPanel = document.getElementById("group-members-panel");
    if (conv && conv.type === 'group') {
        if (groupPanel) {
            loadGroupMembers(conv.id);
        }
    } else {
        if (groupPanel) {
            groupPanel.classList.add("hidden");
        }
    }

    messages[convId] = messages[convId] || [];
    renderMessages(convId);
    loadMessages(convId).then(() => {
      scrollToBottom();
      sendReadReceipt();
    });
  }
  function loadGroupMembers(conversationId) {
      fetch(
          window.API_BASE + '/fetch_group_members.php?conversation_id=' + conversationId,
          { credentials: 'include' }
      )
      .then(r => r.json())
      .then(data => {
          renderGroupMembers(data);
      })
      .catch(err => console.error(err));
  }
  function renderGroupMembers(members) {
      const panel = document.getElementById("group-members-panel");
      const list = document.getElementById("group-members-list");

      list.innerHTML = '';

      members.forEach(m => {
          const div = document.createElement("div");
          div.className = 'group-member';
          div.dataset.userId = m.id; // 👈 store user_id

          const dot = document.createElement("span");
          dot.className = 'status-dot ' + (m.is_online ? 'status-online' : 'status-offline');

          const name = document.createElement("span");
          name.textContent = m.name;

          div.appendChild(dot);
          div.appendChild(name);
          list.appendChild(div);
      });

      panel.classList.remove("hidden");
  }
  function updateGroupMemberPresence(data) {
      const el = document.querySelector(
          `.group-member[data-user-id="${data.user_id}"]`
      );

      if (!el) return;

      const dot = el.querySelector('.status-dot');
      if (!dot) return;

      dot.className = 'status-dot ' + (data.is_online ? 'status-online' : 'status-offline');
  }
  function loadMessages(convId) {
    return fetch(window.API_BASE + '/fetch_messages.php?conversation_id=' + encodeURIComponent(convId), {
      credentials: 'include'
    })
      .then(r => r.json())
      .then(data => {
        messages[convId] = data;
        if (convId === activeConversationId) {
          renderMessages(convId);
        }
      })
      .catch(err => console.error('fetch_messages error', err));
  }
  function renderMessages(convId) {
      messagesEl.innerHTML = '';

      (messages[convId] || []).forEach(msg => {
          const row = document.createElement('div');
          row.className = 'message-row' + (msg.sender_id == window.CURRENT_USER ? ' own' : '');

          const bubble = document.createElement('div');
          bubble.className = 'message-bubble';

          // If message is deleted
          if (msg.is_deleted == 1) {
              bubble.innerHTML = "<i>Message deleted</i>";
          } 
          else {
              // Normal text message
              if (msg.message) {
                  const text = document.createElement('div');
                  text.className = "message-text";
                  text.innerHTML = formatMessageText(msg.message);
                  bubble.appendChild(text);
              }

              // File attachment
              if (msg.file_path) {
                  const fileDiv = document.createElement('div');
                  fileDiv.className = 'message-file';

                  const link = document.createElement('a');
                  link.href = msg.file_path;
                  link.target = '_blank';
                  link.textContent = msg.original_name || "Attachment";

                  fileDiv.appendChild(link);
                  bubble.appendChild(fileDiv);
              }

              // DELETE BUTTON — only for your own messages
              if (msg.sender_id == window.CURRENT_USER) {
                  const delBtn = document.createElement("button");
                  delBtn.className = "delete-btn";
                  delBtn.innerHTML = "🗑";
                  delBtn.onclick = () => deleteMessage(msg.id);
                  bubble.appendChild(delBtn);
              }
          }

          // Timestamp
          const meta = document.createElement('div');
          meta.className = 'message-meta';
          meta.textContent = msg.created_at;
          bubble.appendChild(meta);

          row.appendChild(bubble);
          messagesEl.appendChild(row);
      });
  }
  function formatMessageText(text) {
      if (!text) return '';

      const baseUrl = window.location.origin + '/exotic_vendor/';

      // Escape HTML first (important for security)
      let safe = text
          .replace(/&/g, "&amp;")
          .replace(/</g, "&lt;")
          .replace(/>/g, "&gt;")
          .replace(/"/g, "&quot;")
          .replace(/'/g, "&#39;");

      // $12345 → Orders
      safe = safe.replace(/\$([0-9]+)/g, function (_, id) {
          return `<a href="${baseUrl}?page=orders&action=view&ord_id=${id}" target="_blank">$${id}</a>`;
      });

      // #12345 → Purchase Orders
      safe = safe.replace(/#([0-9]+)/g, function (_, id) {
          return `<a href="${baseUrl}?page=purchase_orders&action=view&po_id=${id}" target="_blank">#${id}</a>`;
      });

      // *12345 → Products
      safe = safe.replace(/\*([0-9]+)/g, function (_, id) {
          return `<a href="${baseUrl}?page=products&action=view&itm_id=${id}" target="_blank">*${id}</a>`;
      });

      // @mentions
      safe = safe.replace(/@([A-Za-z0-9_]+)/g,
          '<span class="mention">@$1</span>'
      );

      return safe;
  }
  function sendMessage() {
    const txt = inputEl.value.trim();
    if (!txt && !fileInput.dataset.uploadedPath) return;
    if (!activeConversationId) {
      alert('Select a conversation first');
      return;
    }

    const payload = {
      type: 'send_message',
      conversation_id: activeConversationId,
      message: txt
    };

    if (fileInput.dataset.uploadedPath) {
      payload.file_path = fileInput.dataset.uploadedPath;
    }
    
    if (fileInput.dataset.uploadedOriginalName) {
      payload.original_name = fileInput.dataset.uploadedOriginalName;
    }else{
      payload.original_name = null;
    }

    if (ws.readyState === WebSocket.OPEN) {
      ws.send(JSON.stringify(payload));
    } else {
      console.warn('WS not open, cannot send message');
    }
    inputEl.value = '';
    fileInput.value = '';
    delete fileInput.dataset.uploadedPath;
    delete fileInput.dataset.uploadedOriginalName;
  }
  function handleWsMessage(data) {
    switch (data.type) {
      case 'system':
        console.log('system', data);
        break;
      case 'error':
        console.error('Error message:', data.msg);
        break;
      case 'new_message':
        handleNewMessage(data.message);
        break;
      case 'typing':
        handleTypingIndicator(data);
        break;
      case 'presence':
        updateGroupMemberPresence(data);
        handlePresence(data);
        break;
      case 'read_receipt':
        console.log('read_receipt', data);
        break;
      case 'message_deleted':
        handleDeletedMessage(data);
        break;
      case 'mention':
        handleMentionNotification(data);
        break;
    }
  }
  function handleMentionNotification(data) {
      const sender = users.find(u => u.id == data.sender_id);

      const name = sender ? sender.name : "Someone";

      showUiPopup({
          sender_name: name,
          message: "mentioned you in a chat",
          conversation_id: data.conversation_id
      });

      // Optional: Blink tab or change favicon
      if (typeof startBlink === 'function') startBlink("Mention");
  }
  function handleNewMessage(msg) {
    const convId = msg.conversation_id;
    messages[convId] = messages[convId] || [];
    messages[convId].push(msg);

    const conv = conversations.find(c => c.id == convId);
    if (conv) {
      conv.last_message = msg.message || '[file]';
      conv.last_message_at = msg.created_at;
      if (msg.sender_id != window.CURRENT_USER && convId !== activeConversationId) {
        conv.unread_count = (parseInt(conv.unread_count || 0, 10) + 1);
      }
    }

    // If message is NOT from me, show popup/notification
    if (convId === activeConversationId) {
      renderMessages(convId);
      scrollToBottom();
      sendReadReceipt();
    }

    // Show popup and browser notification for messages not from me
    if (msg.sender_id != window.CURRENT_USER) {
      playSound();
      //showPopupNotification(msg);
      //showBottomBarNotification(msg);
      //showDesktopToast(msg); // Desktop-like toast notification
      showUiPopup(msg);
      /*if (typeof Notification !== "undefined" && Notification.permission === "granted") {
        const body = msg.message ? msg.message : "📎 Attachment received";
        const n = new Notification("New message", { body: body });
        n.onclick = function () { window.focus(); setActiveConversation(convId); this.close(); };
      }*/
    }

    renderConversationList();
  }
  function showDesktopToast(msg) {
      const container = document.getElementById("toast-container");
      if (!container) return;

      const div = document.createElement("div");
      div.className = "toast";

      const preview = msg.message ? msg.message.substring(0, 100) : "📎 Attachment received";
      const sender = msg.sender_name || ("User " + msg.sender_id);

      div.innerHTML = `
          <strong>${escapeHtml(sender)}</strong>
          <small>${escapeHtml(preview)}</small>
      `;

      div.addEventListener('click', () => {
          setActiveConversation(msg.conversation_id);
          div.remove();
      });

      container.appendChild(div);

      // Auto-remove after animation
      setTimeout(() => div.remove(), 5500);
  }
  function handleTyping() {
    const now = Date.now();
    if (now - lastTypingSentAt < 1500) return;
    lastTypingSentAt = now;

    if (!activeConversationId) return;

    if (ws.readyState === WebSocket.OPEN) {
      ws.send(JSON.stringify({ type: 'typing', conversation_id: activeConversationId }));
    }
  }
  function handleTypingIndicator(data) {
    if (data.conversation_id != activeConversationId) return;
    typingIndicatorEl.style.display = 'block';

    if (typingTimeout) clearTimeout(typingTimeout);
    typingTimeout = setTimeout(() => { typingIndicatorEl.style.display = 'none'; }, 1500);
  }
  function handlePresence(data) {
      // update my presence text
      if (data.user_id === window.CURRENT_USER) {
          presenceEl.textContent = data.is_online ? 'You are online' : 'You are offline';
      }

      // update other users in the list
      const user = users.find(u => u.id == data.user_id);
      if (user) {
          user.is_online = data.is_online;
          //renderUserList(userSearchEl.value.trim());
      }
  }
  function handleFileUpload() {
    if (!fileInput.files.length) return;
    const file = fileInput.files[0];

    const formData = new FormData();
    formData.append('file', file);
    fetch(window.API_BASE + '/upload_file.php', { method: 'POST', body: formData, credentials: 'include' })
      .then(r => r.json())
      .then(res => {
        if (res.error) { alert('Upload error: ' + res.error); fileInput.value = ''; return; }
        fileInput.dataset.uploadedPath = res.path;
        fileInput.dataset.uploadedOriginalName = res.original_name;
        sendMessage();
      })
      .catch(err => console.error('upload error', err));
  }
  function scrollToBottom() { messagesEl.scrollTop = messagesEl.scrollHeight; }
  function isAtBottom() { return messagesEl.scrollHeight - messagesEl.scrollTop - messagesEl.clientHeight < 50; }
  function sendReadReceipt() {
    if (!activeConversationId) return;
    const list = messages[activeConversationId] || [];
    if (!list.length) return;
    const lastId = list[list.length - 1].id;
    if (ws.readyState === WebSocket.OPEN) {
      ws.send(JSON.stringify({ type: 'mark_read', conversation_id: activeConversationId, last_read_message_id: lastId }));
    }

    const conv = conversations.find(c => c.id == activeConversationId);
    if (conv) { conv.unread_count = 0; renderConversationList(); }
  }
  function showPopupNotification(msg) {
    if (!popupContainer) return;
    const div = document.createElement('div');
    div.className = 'chat-popup';
    const preview = msg.message ? msg.message.substring(0, 80) : "📎 File received";
    div.innerHTML = "<strong>New message</strong>" + escapeHtml(preview);
    div.addEventListener('click', () => { window.focus(); setActiveConversation(msg.conversation_id); div.remove(); });
    popupContainer.appendChild(div);
    setTimeout(() => { div.remove(); }, 4000);
  }
  function showUiPopup(message) {
      const container = document.getElementById("ui-popup-container");
      const div = document.createElement("div");
      div.className = "ui-popup";

      const preview = message.message 
          ? message.message.substring(0, 70) 
          : "📎 Attachment received";

      div.innerHTML = `
          <strong>${escapeHtml(message.sender_name || "New Message")}</strong>
          <small>${escapeHtml(preview)}</small>
      `;

      div.addEventListener("click", () => {
          setActiveConversation(message.conversation_id);
          div.remove();
      });

      container.appendChild(div);

      // Auto-remove after 5 seconds
      setTimeout(() => div.remove(), 5000);
  }
  function showBottomBarNotification(msg) {
      // Remove old bar if exists
      let old = document.getElementById("bottom-bar-notify");
      if (old) old.remove();

      const bar = document.createElement("div");
      bar.id = "bottom-bar-notify";

      const preview = msg.message ? msg.message.substring(0, 80) : "📎 Attachment";
      const senderName = msg.sender_name || ("User " + msg.sender_id);

      bar.innerHTML = `
          <div><strong>${escapeHtml(senderName)}</strong>: ${escapeHtml(preview)}</div>
          <button id="bar-open-btn">Open</button>
      `;

      document.body.appendChild(bar);

      document.getElementById("bar-open-btn").onclick = () => {
          setActiveConversation(msg.conversation_id);
          bar.remove();
      };

      // Auto-hide after 6 seconds
      setTimeout(() => { try { bar.remove(); } catch(e){} }, 6000);
  }
  function renderGroupMemberSelector() {
    const list = document.getElementById("group-members-list");

    console.log("RENDER GROUP MEMBERS");
    console.log("USERS:", users);

    if (!list) {
        console.error("group-members-list not found");
        return;
    }

    list.innerHTML = '';

    if (!users || users.length === 0) {
        list.innerHTML = '<div style="padding:8px;color:#999;">No users found</div>';
        return;
    }

    users.forEach(u => {
        const label = document.createElement("label");
        label.style.display = "block";
        label.style.cursor = "pointer";

        const cb = document.createElement("input");
        cb.type = "checkbox";
        cb.value = u.id;
        cb.className = "group-member";

        label.appendChild(cb);
        label.appendChild(document.createTextNode(" " + u.name));

        list.appendChild(label);
    });
}


  document.getElementById("create-group-submit").addEventListener("click", submitGroup);

  function submitGroup() {
      const name = document.getElementById("group-name").value.trim();
      const memberEls = document.querySelectorAll(".group-member:checked");

      const members = Array.from(memberEls).map(el => parseInt(el.value, 10));

      fetch(window.API_BASE + "/create_group.php", {
          method: "POST",
          credentials: "include",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ name, members })
      })
      .then(r => r.json())
      .then(res => {
          if (res.conversation_id) {
              loadConversations().then(() => {
                  setActiveConversation(res.conversation_id);
              });
          }
          document.getElementById("group-modal").classList.add("hidden");
      });
  }
  function deleteMessage(id){
      if (!confirm("Delete this message?")) return;

      ws.send(JSON.stringify({
          type: 'delete_message',
          message_id: id
      }));
  }
  function handleDeletedMessage(data){
      const { message_id, conversation_id } = data;
      
      // Remove from local memory
      messages[conversation_id] = messages[conversation_id].map(m => {
          if (m.id == message_id) {
              m.is_deleted = 1;
              m.message = "Message deleted";
          }
          return m;
      });

      if (conversation_id === activeConversationId) {
          renderMessages(conversation_id);
      }
  }
  // Group Model
  // Open modal
  document.getElementById("create-group-btn").addEventListener("click", () => {
      console.log("GROUP MODAL OPEN CLICKED");
      groupModal.classList.remove("hidden");
      renderGroupMemberSelector();
  });

  // Close modal (X button)
  groupCloseBtn.addEventListener("click", closeGroupModal);

  // Cancel button
  groupCancelBtn.addEventListener("click", closeGroupModal);

  // Click outside modal
  groupOverlay.addEventListener("click", closeGroupModal);

  // ESC key closes modal
  document.addEventListener("keydown", (e) => {
      if (e.key === "Escape" && !groupModal.classList.contains("hidden")) {
          closeGroupModal();
      }
  });
  function closeGroupModal() {
      groupModal.classList.add("hidden");
      document.getElementById("group-name").value = "";
  }

  // small util
  function escapeHtml(s){ return (s+'').replace(/[&<>"']/g, function(c){ return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":"&#39;"}[c]; }); }

})();
