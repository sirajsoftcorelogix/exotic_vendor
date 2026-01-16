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
  const headerNameEl = document.getElementById('header-name');
  const headerRoleEl = document.getElementById('header-role');

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
  let pendingDeleteConversationId = null;
  let onlineUserIds = [];
  window.__onlineUserIds = onlineUserIds;
  
  // -- User mention variables
  let mentionActive = false;
  let mentionStartPos = -1;
  let mentionItems = [];
  let mentionIndex = -1;

  const mentionDropdown = document.getElementById("mention-dropdown");
  const placeholderAvatar =
  "data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAyNCAyNCI+PGNpcmNsZSBjeD0iMTIiIGN5PSIxMiIgcj0iMTIiIGZpbGw9IiNFNUU3RUIiLz48cGF0aCBkPSJNMTIgMTJhNSA1IDAgMSAwLTUtNSA1IDUgMCAwIDAgNSA1em0wIDJjLTUuMzMgMC04IDIuNjctOCA1djFoMTZ2LTFjMC0yLjMzLTIuNjctNS04LTV6IiBmaWxsPSIjOUNBM0FGIi8+PC9zdmc+";

  const urlParams = new URLSearchParams(window.location.search);
  const openConvParam = urlParams.get("conversation_id");
  // Emoji
  const EMOJIS = [
    "😀","😁","😂","🤣","😊","😍","😘","😎",
    "😢","😭","😡","👍","👎","🙏","🔥","❤️",
    "🎉","🚀","💯","📎"
  ];

  const emojiBtn = document.getElementById("emoji-btn");
  const emojiPicker = document.getElementById("emoji-picker");

  EMOJIS.forEach(e => {
      const span = document.createElement("span");
      span.textContent = e;
      span.onclick = () => insertEmoji(e);
      emojiPicker.appendChild(span);
  });

  emojiBtn.addEventListener("click", (e) => {
    e.stopPropagation();
    emojiPicker.classList.toggle("hidden");
  });

  document.addEventListener("click", () => {
    emojiPicker.classList.add("hidden");
  });

  function insertEmoji(emoji) {
    const input = inputEl; // your existing message input
    const start = input.selectionStart;
    const end = input.selectionEnd;

    const text = input.value;
    input.value =
        text.substring(0, start) +
        emoji +
        text.substring(end);

    const newPos = start + emoji.length;
    input.setSelectionRange(newPos, newPos);
    input.focus();
  }
  // END Emoji

  // Drag n Drop
  const chatInputWrapper = document.querySelector(".chat-input-wrapper");

  chatInputWrapper.addEventListener("dragover", e => {
    e.preventDefault();
    chatInputWrapper.classList.add("drag-active");
  });

  chatInputWrapper.addEventListener("dragleave", () => {
    chatInputWrapper.classList.remove("drag-active");
  });

  chatInputWrapper.addEventListener("drop", e => {
    e.preventDefault();
    chatInputWrapper.classList.remove("drag-active");

    const files = e.dataTransfer.files;
    if (files.length) {
        handleSelectedFile(files[0]);
    }
  });

  inputEl.addEventListener("paste", (e) => {
    const items = e.clipboardData.items;
    for (const item of items) {
        if (item.type.startsWith("image/")) {
            const file = item.getAsFile();
            handleSelectedFile(file);
            e.preventDefault();
            break;
        }
    }
  });

  function handleSelectedFile(file) {
    if (!file) return;

    // Only images for now
    if (!file.type.startsWith("image/")) {
        alert("Only image files are allowed");
        return;
    }

    showPreview(file);
  }

  function showPreview(file) {
    const preview = document.getElementById("file-preview");
    const img = document.getElementById("preview-img");
    const name = document.getElementById("preview-name");

    name.textContent = file.name;

    if (file.type.startsWith("image/")) {
        img.src = URL.createObjectURL(file);
        img.classList.remove("hidden");
    }

    preview.classList.remove("hidden");

    // Store file temporarily
    fileInput.dataset.pendingFile = "1";
    window.pendingUploadFile = file;
  }

  document.getElementById("preview-remove").onclick = () => {
    document.getElementById("file-preview").classList.add("hidden");
    window.pendingUploadFile = null;
  };

  function uploadFile(file) {
    const formData = new FormData();
    formData.append("file", file);

    fetch(window.API_BASE + "/upload_file.php", {
        method: "POST",
        body: formData,
        credentials: "include"
    })
    .then(r => r.json())
    .then(res => {
        if (res.error) {
            alert(res.error);
            return;
        }

        ws.send(JSON.stringify({
            type: "send_message",
            conversation_id: activeConversationId,
            file_path: res.path,
            original_name: res.original_name || file.name
        }));

        clearPreview();
    });
  }
  function clearPreview() {
    document.getElementById("file-preview").classList.add("hidden");
    window.pendingUploadFile = null;
  }
  // END

  const previewContainer = document.getElementById("image-preview-container");
  let pendingImageFile = null;

  function showImagePreview(file) {
      if (!file || !file.type.startsWith("image/")) return;

      previewContainer.innerHTML = "";
      previewContainer.classList.remove("hidden");

      const wrapper = document.createElement("div");
      wrapper.style.position = "relative";

      const img = document.createElement("img");
      img.src = URL.createObjectURL(file);

      const remove = document.createElement("span");
      remove.className = "remove-preview";
      remove.textContent = "×";
      remove.onclick = () => {
          pendingImageFile = null;
          previewContainer.classList.add("hidden");
          previewContainer.innerHTML = "";
      };

      wrapper.appendChild(img);
      wrapper.appendChild(remove);
      previewContainer.appendChild(wrapper);

      pendingImageFile = file;
  }
  // Search functionality
  const globalSearchEl = document.getElementById("global-search");

  if (globalSearchEl) {
      globalSearchEl.addEventListener("input", (e) => {
          const query = e.target.value.trim().toLowerCase();
          handleGlobalSearch(query);
      });
  }
  //----------------------------------
  ws.onopen = () => {
    console.log('\r\nWebSocket connected: '+ window.API_BASE);
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
    setTimeout(()=>{ window.location.reload(); }, 5000000);
  };

  sendBtn.addEventListener('click', sendMessage);

  inputEl.addEventListener("keydown", function (e) {

    const cursorPos = inputEl.selectionStart;
    const value = inputEl.value;

    const beforeCursor = value.slice(0, cursorPos);
    const match = beforeCursor.match(/(?:^|\s)@([A-Za-z0-9_]*)$/);

    if (match) {
        mentionActive = true;
        mentionStartPos = cursorPos - match[1].length - 1;
        showMentionDropdown(match[1]);   // ✅ CALLED HERE
    } else {
        hideMentionDropdown();
    }
    
    // SEND on Enter
    if (e.key === "Enter" && !e.shiftKey) {
      e.preventDefault();
      sendMessage();
      return;
    }

    // Allow multiline
    if (e.key === "Enter" && e.shiftKey) {
      return;
    }

    handleTyping();
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

    if (!mentionDropdown) {
      console.error("mention-dropdown element missing");
      return;
    }
    if (!users || users.length === 0) return;

    console.log("filterText: " + filterText)
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

      const beforeCursor = value.slice(0, cursorPos);
      const match = beforeCursor.match(/(?:^|\s)@([A-Za-z0-9_]*)$/);

      if (match) {
          mentionActive = true;
          mentionStartPos = cursorPos - match[1].length - 1;
          showMentionDropdown(match[1]);
      } else {
          hideMentionDropdown();
      }
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

  function handleGlobalSearch(query) {
    renderUserList(query);
    renderConversationList(query);
  }

  function playSound() {
      notificationSound.play().catch(() => {});
  }
  function loadUsers() {
    console.log("Loading users...");
    fetch(window.API_BASE + '/fetch_users.php', { credentials: 'include' })
      .then(r => r.json())
      .then(data => {
        users = data;
        //renderUserList('');
        applyOnlineStatus();
      })
      .catch(err => console.error('fetch_users error', err));
  }
  function renderUserList(filter = '') {
    const list = document.getElementById('user-list');
    if (!list) return;

    list.innerHTML = '';

    const q = filter.toLowerCase();

    users
        .filter(u => !q || u.name.toLowerCase().includes(q))
        .forEach(u => {

            const item = document.createElement('div');
            item.className = `
                flex items-center gap-3 px-4 py-3
                cursor-pointer transition
                hover:bg-gray-100
            `;

            item.onclick = () => openChatWithUser(u.id);

            item.innerHTML = `
                <div class="relative flex-shrink-0">
                    <div class="w-11 h-11 rounded-full bg-gray-300
                                flex items-center justify-center
                                text-sm font-semibold text-gray-700">
                        U
                    </div>

                    <span class="
                        absolute bottom-0 right-0
                        w-3 h-3 rounded-full
                        border-2 border-white
                        ${u.is_online ? 'bg-green-500' : 'bg-gray-400'}
                    "></span>
                </div>

                <div class="flex-1 min-w-0">
                    <div class="font-medium text-gray-900 truncate">
                        ${escapeHtml(u.name)}
                    </div>
                    <div class="text-xs text-gray-500">
                        ${u.is_online ? 'Online' : 'Offline'}
                    </div>
                </div>
            `;

            list.appendChild(item);
        });
  }
  function handleOnlineUsers(userIds) {
      onlineUserIds = userIds.map(id => parseInt(id, 10));
      window.__onlineUserIds = onlineUserIds; // 👈 add
      applyOnlineStatus();
  }
  function applyOnlineStatus() {
      if (!users || users.length === 0) return;

      users.forEach(u => {
          u.is_online = onlineUserIds.includes(parseInt(u.id, 10)) ? 1 : 0;
      });

      renderUserList();
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
    function renderConversationList(filter = '') {
      convListEl.innerHTML = '';

      const q = filter.toLowerCase();

      conversations
        .filter(conv =>
            !q || conv.display_name.toLowerCase().includes(q)
        )
        .forEach(conv => {
            const item = document.createElement('div');
            item.className =
                'conversation-item' +
                (conv.id === activeConversationId ? ' active' : '');

            // avatar
            const ava = document.createElement('div');
            ava.className = 'conv-avatar';
            ava.textContent = conv.type === 'group' ? '#' : 'U';

            // main
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

            // meta
            const meta = document.createElement('div');
            meta.className = 'conv-meta';

            if (conv.unread_count > 0) {
                const badge = document.createElement('div');
                badge.className = 'conv-unread';
                badge.textContent = conv.unread_count;
                meta.appendChild(badge);
            }

            // delete button (unchanged)
            const del = document.createElement('button');
            del.className = 'conv-delete-btn';
            del.innerHTML = '×';
            del.addEventListener('click', (e) => {
                e.stopPropagation();
                requestDeleteConversation(conv);
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
    function requestDeleteConversation(convOrId) {

      // Normalize argument
      const conv = (typeof convOrId === 'object')
          ? convOrId
          : conversations.find(c => c.id === convOrId);

      if (!conv) {
          console.error("Conversation not found:", convOrId);
          return;
      }

      pendingDeleteConversationId = conv.id;

      const modal = document.getElementById("delete-modal");
      const msg   = document.getElementById("delete-modal-msg");
      const confirmBtn = document.getElementById("delete-confirm-btn");

      console.log("Owner:", conv.owner_id);
      console.log("Current user:", window.CURRENT_USER);

      if (
          conv.type === 'group' &&
          Number(conv.owner_id) !== Number(window.CURRENT_USER)
      ) {
          msg.textContent = "Only the group owner can delete this group.";
          confirmBtn.classList.add("hidden");
      } else {
          msg.textContent = "Are you sure you want to delete this conversation?";
          confirmBtn.classList.remove("hidden");
      }

      modal.classList.remove("hidden");
    }


    document.getElementById("delete-cancel-btn").onclick = closeDeleteModal;
    document.getElementById("delete-confirm-btn").onclick = confirmDeleteConversation;

    function closeDeleteModal() {
        document.getElementById("delete-modal").classList.add("hidden");
        pendingDeleteConversationId = null;
    }
    function confirmDeleteConversation() {
      if (!pendingDeleteConversationId) return;

      fetch(window.API_BASE + '/delete_conversation.php', {
          method: "POST",
          credentials: "include",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ conversation_id: pendingDeleteConversationId })
      })
      .then(r => r.json())
      .then(res => {
          if (res.success) {
              handleConversationDeleted(pendingDeleteConversationId);
          } else if (res.error === "only_owner_can_delete") {
              alert("Permission denied. Only group owner can delete.");
          }
          closeDeleteModal();
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
          }else if(res.error == "only_owner_can_delete") {
            alert("Permission Denied: You do not have permission to delete groups owned by other users. You can only delete groups that you own.");
            return true;
          }
      });
  }
  function setActiveConversation(convId) {
    activeConversationId = convId;

    renderConversationList();

    const conv = conversations.find(c => c.id === convId);
    if (conv) {
      if (headerNameEl) headerNameEl.innerText = conv.display_name;
      if (headerRoleEl) {
          headerRoleEl.innerText = conv.type === 'group'
              ? 'Group chat'
              : 'Direct message';
      }
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
    });

    // Only send read when user scrolls / focuses
    messagesEl.addEventListener('scroll', () => {
        if (isAtBottom()) sendReadReceipt();
    });
  }
  window.setActiveConversation = setActiveConversation;
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
    const list = document.getElementById("chat-group-members-list");

    if (!panel || !list) {
        console.error("Group members panel/list not found");
        return;
    }

    list.innerHTML = '';

    if (!members || members.length === 0) {
        list.innerHTML = '<div class="text-gray-500 text-sm p-2">No members</div>';
        panel.classList.remove("hidden");
        return;
    }

    members.forEach(m => {
        const div = document.createElement("div");
        div.className = "flex items-center gap-2 px-3 py-2 text-sm";

        const dot = document.createElement("span");
        dot.className =
            "w-2 h-2 rounded-full " +
            (m.is_online ? "bg-green-500" : "bg-gray-400");

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
        messages[convId] = messages[convId] || [];

        data.forEach(msg => {
            if (!messages[convId].some(m => m.id === msg.id)) {
                messages[convId].push(msg);
            }
        });
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
      row.className =
        'message-row ' +
        (msg.sender_id == CURRENT_USER ? 'sent' : 'received');

      const bubble = document.createElement('div');
      bubble.className = 'bubble';

      // Group sender name
      if (msg.sender_id != CURRENT_USER && msg.sender_name) {
        const sender = document.createElement('div');
        sender.className = 'sender-name';
        sender.textContent = msg.sender_name;
        bubble.appendChild(sender);
      }

      // Deleted message
      if (msg.is_deleted) {
        bubble.innerHTML += `<i>Message deleted</i>`;
      } else {
        bubble.innerHTML += `
          <div class="message-text">${formatMessageText(msg.message || '')}</div>
        `;
      }

      if (msg.sender_id == CURRENT_USER && !msg.is_deleted) {
        const del = document.createElement('button');
        del.className = 'delete-btn';
        del.innerHTML = '🗑';
        del.onclick = () => deleteMessage(msg.id);
        bubble.appendChild(del);
      }

      if (msg.file_path) {

        // IMAGE PREVIEW
        if (isImageFile(msg.file_path, msg.original_name)) {

            const imgLink = document.createElement("a");
            imgLink.href = msg.file_path;
            imgLink.target = "_blank";

            const img = document.createElement("img");
            img.src = msg.file_path;
            img.className = "message-image";
            img.alt = msg.original_name || "Image";

            imgLink.appendChild(img);
            bubble.appendChild(imgLink);

        } 
        // NON-IMAGE FILE
        else {
            const fileDiv = document.createElement("div");
            fileDiv.className = "message-file";

            const link = document.createElement("a");
            link.href = msg.file_path;
            link.target = "_blank";
            link.textContent = msg.original_name || "Attachment";

            fileDiv.appendChild(link);
            bubble.appendChild(fileDiv);
        }
      }

      const meta = document.createElement('div');
      meta.className = 'message-meta';
      meta.textContent = msg.created_at;

      if (msg.sender_id == CURRENT_USER) {
        /*meta.innerHTML += `
          <span class="read ${msg.is_read ? 'read' : ''}">
            ${msg.is_read ? '✔✔' : '✔'}
          </span>
        `;*/
        meta.innerHTML = `
          ${msg.created_at}
          ${getTick(msg.delivery_status)}
        `;
      }

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
    if (!activeConversationId) {
      alert("Select a conversation first");
      return;
    }
    if (window.pendingUploadFile) {
        uploadFile(window.pendingUploadFile);
        return;
    }
    if (pendingImageFile) {
        uploadAndSendImage(pendingImageFile);
        return;
    }

    const text = inputEl.value.trim();
    const filePath = fileInput.dataset.uploadedPath || null;
    const originalName = fileInput.dataset.uploadedOriginalName || null;

    // Prevent empty send
    if (!text && !filePath) return;

    const payload = {
      type: "send_message",
      conversation_id: activeConversationId,
      message: text || null
    };

    if (filePath) {
      payload.file_path = filePath;
      payload.original_name = originalName;
    }

    if (ws.readyState === WebSocket.OPEN) {
      ws.send(JSON.stringify(payload));
    } else {
      console.warn("WS not open");
      return;
    }

    // Reset UI
    inputEl.value = "";
    clearAttachment();
  }
  function uploadAndSendImage(file) {
    const formData = new FormData();
    formData.append("file", file);

    fetch(window.API_BASE + "/upload_file.php", {
        method: "POST",
        body: formData,
        credentials: "include"
    })
    .then(r => r.json())
    .then(res => {
        if (res.error) {
            alert("Upload failed");
            return;
        }

        ws.send(JSON.stringify({
            type: "send_message",
            conversation_id: activeConversationId,
            file_path: res.path,
            original_name: file.name
        }));

        pendingImageFile = null;
        previewContainer.classList.add("hidden");
        previewContainer.innerHTML = "";
    });
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
        handlePresence(data);
        updateGroupMemberPresence(data);
        break;
      case 'read_receipt':
        console.log('read_receipt', data);
        messages[data.conversation_id].forEach(m => {
          if (m.id <= data.last_read_message_id) {
            m.delivery_status = 'read';
          }
        });
        renderMessages(activeConversationId);
        break;
      case 'message_deleted':
        handleDeletedMessage(data);
        break;
      case 'mention':
        handleMentionNotification(data);
        break;
      case 'conversation_deleted':
        handleConversationDeleted(data.conversation_id);
        break;
      case 'online_users':
          handleOnlineUsers(data.users);
          break;
    }
  }
  function handleOnlineUsers(userIds) {
      users.forEach(u => {
          u.is_online = userIds.includes(u.id) ? 1 : 0;
      });
      renderUserList();
  }
  function handleConversationDeleted(convId) {
      // Remove from memory
      conversations = conversations.filter(c => c.id != convId);

      // If currently open, clear UI
      if (activeConversationId == convId) {
          activeConversationId = null;
          messagesEl.innerHTML = "";
          titleEl.textContent = "Select a conversation";
          subtitleEl.textContent = "";
      }

      // Re-render chat list
      renderConversationList();
  }
  function handleMentionNotification(data) {
      let name = "Someone";

      if (data.sender_name) {
        name = data.sender_name;
      } else if (Array.isArray(users)) {
          const sender = users.find(u => u.id == data.sender_id);
          if (sender && sender.name) {
              name = sender.name;
          }
      }
      console.log("Name: " + name);
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
    console.log("Mag : " + Object.values(msg));
    // Show popup and browser notification for messages not from me
    if (msg.sender_id != window.CURRENT_USER) {
      playSound();
      showUiPopup(msg);
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
    if (data.conversation_id !== activeConversationId) return;

    typingIndicatorEl.classList.remove("hidden");

    clearTimeout(typingTimeout);
    typingTimeout = setTimeout(() => {
      typingIndicatorEl.classList.add("hidden");
    }, 1500);
  }
  function handlePresence(data) {
      // update other users in the list
      const uid = parseInt(data.user_id, 10);

      if (data.is_online) {
          if (!onlineUserIds.includes(uid)) {
              onlineUserIds.push(uid);
          }
      } else {
          onlineUserIds = onlineUserIds.filter(id => id !== uid);
      }
      window.__onlineUserIds = onlineUserIds;
      applyOnlineStatus();
  }
  function handleFileUpload() {
    if (!fileInput.files.length) return;
    const file = fileInput.files[0];

    const formData = new FormData();
    formData.append('file', file);
    fetch(window.API_BASE + '/upload_file.php', { method: 'POST', body: formData, credentials: 'include' })
      .then(r => r.json())
      .then(res => {
        if (res.error) return alert(res.error);

        fileInput.dataset.uploadedPath = res.path;
        fileInput.dataset.uploadedOriginalName = res.original_name;

        const preview = document.getElementById("attachment-preview");
        preview.innerHTML = `
          <i class="ph ph-paperclip text-2xl"></i> ${escapeHtml(res.original_name)}
          <button onclick="clearAttachment()">✕</button>
        `;
        preview.classList.remove("hidden");
      })
      .catch(err => console.error('upload error', err));
  }
  function clearAttachment() {
    delete fileInput.dataset.uploadedPath;
    delete fileInput.dataset.uploadedOriginalName;
    fileInput.value = "";

    const preview = document.getElementById("attachment-preview");
    if (preview) preview.classList.add("hidden");
  }
  function scrollToBottom() { messagesEl.scrollTop = messagesEl.scrollHeight; }
  function isAtBottom() { return messagesEl.scrollHeight - messagesEl.scrollTop - messagesEl.clientHeight < 50; }
  function sendReadReceipt() {
      if (!activeConversationId) return;
      if (!isAtBottom()) return;

      const list = messages[activeConversationId] || [];
      if (!list.length) return;

      const lastId = list[list.length - 1].id;

      ws.send(JSON.stringify({
          type: 'mark_read',
          conversation_id: activeConversationId,
          last_read_message_id: lastId
      }));
  }
  function showUiPopup(message) {
    const container = document.getElementById("ui-popup-container");
    if (!container) return;

    const div = document.createElement("div");
    div.className = "ui-popup";

    let senderName = message.sender_name;

    // Fallback: resolve from users list
    if (!senderName && message.sender_id) {
        const user = users.find(u => u.id == message.sender_id);
        senderName = user ? user.name : "New Message";
    }

    const preview = message.message
        ? message.message.substring(0, 70)
        : "📎 Attachment received";

    div.innerHTML = `
        <strong>${escapeHtml(senderName || "New Message")}</strong>
        <small>${escapeHtml(preview)}</small>
    `;

    div.addEventListener("click", () => {
        setActiveConversation(message.conversation_id);
        div.remove();
    });

    container.appendChild(div);

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

    if (!list) {
        console.error("group-members-list not found");
        return;
    }

    list.innerHTML = '';

    if (!users || users.length === 0) {
        list.innerHTML = '<div class="text-gray-400 p-2">No users found</div>';
        return;
    }

    users.forEach(u => {
        const label = document.createElement("label");
        label.className = "flex items-center gap-3 px-3 py-2 rounded hover:bg-gray-100 cursor-pointer";

        label.innerHTML = `
            <input 
                type="checkbox" 
                class="group-member w-4 h-4" 
                value="${u.id}"
            />
            <span class="text-sm text-gray-800">${escapeHtml(u.name)}</span>
        `;

        list.appendChild(label);
    });
  }
  window.renderGroupMemberSelector = renderGroupMemberSelector;

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
          if (res.error) {
              alert(res.error);
              return;
          }

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
  document.getElementById("create-group-btn").addEventListener("click", () => {
      console.log("GROUP MODAL OPEN CLICKED");
      groupModal.classList.remove("hidden");
      window.renderGroupMemberSelector();
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

  function getTick(status) {
    if (status === 'read') {
      return '<span class="tick read">✓✓</span>';
    }
    if (status === 'delivered') {
      return '<span class="tick delivered">✓✓</span>';
    }
    return '<span class="tick sent">✓</span>';
  }

  // small util
  function escapeHtml(s){ return (s+'').replace(/[&<>"']/g, function(c){ return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":"&#39;"}[c]; }); }
  function isImageFile(path, name = "") {
    const str = (name || path || "").toLowerCase();
    return /\.(jpg|jpeg|png|gif|webp)$/.test(str);
  }
})();
