<?php
require_once 'bootstrap/init/init.php';
require_once("helpers/html_helpers.php");
is_login();
$currentUserId = $_SESSION["user"]['id'] ?? null;
$currentUserName = $_SESSION['user']['name'] ?? 'You';
$apiToken = fetchAPIToken($currentUserId);

require __DIR__ . '/config.php';
$config = require __DIR__ . '/config.php';

$env = $config['ENV'];

if($env === 'local') {
    $API_BASE = $config['LOCAL_API_BASE'];
    $WS_URL   = $config['LOCAL_WS_URL'];
}elseif ($env === 'test') {
    $API_BASE = $config['TEST_API_BASE'];
    $WS_URL   = $config['TEST_WS_URL'];
}elseif ($env === 'live') {
    $API_BASE = $config['LIVE_API_BASE'];
    $WS_URL   = $config['LIVE_WS_URL'];
}
$WS_PORT = $config['WS_PORT'];

global $domain, $root_path, $page, $action, $conn;
require_once 'models/user/user.php';
$usersModel = new User($conn);
$userDetails = $usersModel->getUserById($_SESSION['user']['id']);
unset($usersModel);
require_once 'controllers/NotificationController.php';
$notificationController = new NotificationController();
$msgCnt = $notificationController->getUnreadCount();
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Internal Chat</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  
  <!-- Tailwind CSS -->
  <script src="https://cdn.tailwindcss.com"></script>

  <!-- Phosphor Icons (for icons) -->
  <script src="https://unpkg.com/@phosphor-icons/web"></script>

  <!-- Google Fonts: Urbanist & Inter -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=Urbanist:wght@400;500;600;700&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="assets/chat.css">
  <link rel="stylesheet" href="style/style.css" />
  <!-- Custom Config & Styles -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['-apple-system', 'BlinkMacSystemFont', 'San Francisco', 'SF Pro Text', 'Segoe UI', 'Roboto', 'Helvetica Neue', 'sans-serif'],
                        urbanist: ['Urbanist', 'sans-serif'],
                    },
                    colors: {
                        primary: '#6366f1',
                        // Sidebar Colors
                        headerText: 'rgba(5, 19, 33, 1)',
                        iconColor: 'rgba(174, 186, 193, 1)',
                        tabUnselected: 'rgba(118, 118, 118, 1)',
                        sidebarName: 'rgba(5, 19, 33, 1)',
                        sidebarMsg: 'rgba(134, 150, 160, 1)',
                        selectedBg: 'rgba(208, 103, 6, 0.08)',

                        // Dark Theme Colors (Chat Area)
                        darkHeaderBg: 'rgba(5, 19, 33, 1)',      // Header & Footer Background
                        darkHeaderName: 'rgba(233, 237, 239, 1)',
                        darkHeaderSub: 'rgba(134, 150, 160, 1)',

                        // Message Colors
                        senderBubble: 'rgba(208, 103, 6, 1)',    // My Message Bg
                        senderText: 'rgba(255, 255, 255, 1)',    // My Message Text

                        receiverBubble: 'rgba(5, 19, 33, 1)',    // Their Message Bg
                        receiverText: 'rgba(233, 237, 239, 1)',  // Their Message Text

                        // Footer / Input Colors
                        inputIcon: 'rgba(134, 150, 160, 1)',
                        inputFieldBg: 'rgba(42, 57, 66, 1)',

                        // Attachment Popup (Updated)
                        popupBg: 'rgba(5, 19, 33, 1)',
                    },
                    fontSize: {
                        'header': ['22px', '32px'],
                        'tab': ['15px', '20px'],
                        'name': ['17px', '100%'],
                        'msg': ['14px', '20px'],
                        'chat-name': ['16px', '21px'],
                        'chat-sub': ['14px', '20px'],

                        // Specific Message Typography
                        'bubble-text': ['14.2px', '19px'],
                    },
                    animation: {
                        'scale-in': 'scaleIn 0.2s ease-out forwards',
                    },
                    keyframes: {
                        scaleIn: {
                            '0%': { transform: 'scale(0.9)', opacity: '0' },
                            '100%': { transform: 'scale(1)', opacity: '1' },
                        }
                    }
                }
            }
        }
    </script>
    <style>
        /* Custom Scrollbar for sleek look */
        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background-color: #cbd5e1;
            border-radius: 20px;
        }
        .chat-bg-pattern {
            background-color: #efeae2;
            background-image: radial-gradient(#d1d5db 1px, transparent 1px);
            background-size: 20px 20px;
        }
        .font-sf-pro {
            font-family: -apple-system, BlinkMacSystemFont, "SF Pro Text", "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        }

        /* Popup Arrow */
        .popup-arrow::after {
            content: '';
            position: absolute;
            bottom: -8px;
            left: 20px;
            width: 0;
            height: 0;
            border-left: 8px solid transparent;
            border-right: 8px solid transparent;
            border-top: 8px solid rgba(5, 19, 33, 1);
        }

        /* Typography Helper Class for Menu Items */
        .menu-item-text {
            font-family: 'Urbanist', sans-serif;
            font-weight: 500;
            font-size: 17px;
            line-height: 100%;
            color: rgba(255, 255, 255, 1);
        }
    </style>
</head>
<body>
<div class="flex-1 flex flex-col overflow-hidden">

  <div id="chat-app">
    <?php include 'views/layouts/left_menu.php'; ?>
    <aside id="sidebar" class="w-full md:w-[380px] lg:w-[420px] bg-white border-r border-gray-200 flex flex-col h-full z-10">

      <!-- Header -->
      <div class="h-16 px-5 flex items-center justify-between bg-white flex-shrink-0 mt-2">
          <!-- Title -->
          <h1 class="font-sf-pro font-bold text-header text-headerText tracking-normal">Chats</h1>

          <!-- Actions -->
          <!-- <div class="flex items-center gap-4">
              <button class="transition-colors focus:outline-none">
                  <i class="ph ph-note-pencil" style="font-size: 23px; color: rgba(174, 186, 193, 1);"></i>
              </button>
              <button class="transition-colors focus:outline-none">
                  <i class="ph ph-dots-three-vertical" style="font-size: 23px; color: rgba(174, 186, 193, 1);"></i>
              </button>
          </div> -->
      </div>

      <!-- Search Bar -->
      <div class="px-5 pb-2 bg-white">
          <div class="relative">
              <i class="ph ph-magnifying-glass absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 text-lg"></i>
              <input type="text" placeholder="Search" id="global-search"
                    class="w-full pl-10 pr-4 py-2 bg-gray-100 border-none   rounded-lg text-[15px] font-urbanist focus:outline-none focus:ring-1 focus:ring-gray-300 transition-all placeholder-gray-500 text-gray-700">
          </div>
      </div>

      <!-- Filter Tabs -->
      <div class="px-5 py-3 flex gap-3 items-center">
          <button class="px-4 py-1.5 bg-black text-white rounded-full font-urbanist font-normal text-tab transition-all">All</button>
          <!-- <button class="px-4 py-1.5 bg-gray-100 text-tabUnselected rounded-full font-urbanist font-normal text-tab hover:bg-gray-200 transition-all">Unread</button>
          <button class="px-4 py-1.5 bg-gray-100 text-tabUnselected rounded-full font-urbanist font-normal text-tab hover:bg-gray-200 transition-all">Groups</button> -->
      </div>

      <!-- Contact List (Scrollable) -->
      <div class="flex-1 overflow-y-auto custom-scrollbar px-3 space-y-1" id="users-section">
          <!-- Items injected by JS -->
          <div id="user-list"></div>
      </div>
      
      <div class="sidebar-header" style="margin-top:8px;">
        <h2>Recent Chats</h2>
      </div>
      <div id="conversation-list" class="flex-1 overflow-y-auto custom-scrollbar px-3 space-y-1"></div>
      <!-- Group chat members -->
      <div id="group-members-panel" class="group-members hidden">
        <div class="group-members-header">Group Members</div>
        <div id="chat-group-members-list"></div>
      </div>
      <!-- End -->
      <button id="create-group-btn">+ Create Group</button>

    </aside>
    <main id="chat-main" class="flex-1 flex flex-col h-full">
      <!-- Chat Header -->
      <header id="chat-header" class="h-16 px-5 flex items-center justify-between bg-darkHeaderBg flex-shrink-0">
          <div>
              <div id="chat-title" class="font-sf-pro font-semibold text-chat-name text-darkHeaderName">Select a conversation</div>
              <div id="chat-subtitle" class="font-sf-pro font-normal text-chat-sub text-darkHeaderSub"></div>
          </div>
          <div id="chat-presence" class="text-sm text-green-500" style="display: none;"></div>
      </header>
      <div class="h-16 px-4 flex items-center justify-between bg-darkHeaderBg z-20 shadow-md">
          <div class="flex flex-col">
              <h2 id="header-name" class="text-white font-medium"></h2>
              <span id="header-role" class="text-gray-400 text-sm"></span>
          </div>
      </div>

      <!-- Messages Section -->
      <section id="messages" class="flex-1 overflow-y-auto custom-scrollbar p-6 chat-bg-pattern">
          <!-- Messages injected by JS -->
            
      </section>

      <!-- Chat Footer -->
      <div class="h-16 px-4 flex items-center justify-between bg-darkHeaderBg z-20 shadow-md">
      <footer id="chat-footer" class="px-4 py-3 bg-darkHeaderBg border-t border-gray-800 relative z-20">
          <div id="typing-indicator" class="typing hidden">
            typing…
          </div>
          <div id="input-row" class="flex items-center w-full gap-3">
              <div class="flex gap-4 items-center">
                  <div id="attachment-preview" class="hidden attachment-preview"></div>
                  <input type="file" id="file-input" style="display:none;">
                  <button id="attach-btn" title="Attach file" class="text-inputIcon hover:text-white transition relative"><i class="ph ph-paperclip text-2xl"></i></button>
              </div>
              <div class="flex-1 bg-inputFieldBg rounded-full flex items-center px-4 py-2 border border-transparent focus-within:ring-1 focus-within:ring-white/20 transition-all">
                <input id="message-input" placeholder="Type a message" autocomplete="off"
                    class="flex-1 bg-inputFieldBg text-white rounded-full px-4 py-2 focus:outline-none focus:ring-1 focus:ring-gray-600 transition-all">
              </div>
                    <button id="send-btn" class="text-inputIcon hover:text-white transition flex items-center justify-center"><i class="ph-fill ph-paper-plane-right text-2xl"></i></button>
              <div id="mention-dropdown" class="mention-dropdown hidden"></div>
          </div>
      </footer>
       </div>
      <?php /*?>
          <aside id="sidebar">
          <div class="sidebar-header">
            <h2>Users</h2>
          </div>

          <div id="users-section">
            <!-- <input type="text" id="user-search" placeholder="Search users..." /> -->
            <div id="user-list"></div>
          </div>

          <div class="sidebar-header" style="margin-top:8px;">
            <h2>Recent Chats</h2>
          </div>
          <div id="conversation-list"></div>
          <!-- Group chat members -->
          <div id="group-members-panel" class="group-members hidden">
            <div class="group-members-header">Group Members</div>
            <div id="chat-group-members-list"></div>
          </div>
          <!-- End -->
          <button id="create-group-btn">+ Create Group</button>
        </aside>

        <main id="chat-main">
          <header id="chat-header">
            <div>
              <div id="chat-title">Select a conversation</div>
              <div id="chat-subtitle"></div>
            </div>
            <div id="chat-presence" style="display: none;"></div>
          </header>

          <section id="messages"></section>

          <footer id="chat-footer">
            <div id="typing-indicator" style="display:none;">Someone is typing...</div>
            <div id="input-row">
              <input type="file" id="file-input" style="display:none;">
              <button id="attach-btn" title="Attach file">📎</button>
              <input id="message-input" placeholder="Type a message" autocomplete="off">
              <button id="send-btn">Send</button>
              <div id="mention-dropdown" class="mention-dropdown hidden"></div>
            </div>
          </footer>
        </main>
      <?php */?>
  </div>

</div>
<div id="group-modal" class="modal hidden">
    <div class="modal-overlay"></div>

    <div class="modal-content">
      <div class="modal-header">
        <h2>Create Group</h2>
        <button class="modal-close" id="group-close-btn">&times;</button>
      </div>

      <div class="modal-body">
        <label for="group-name">Group Name</label>
        <input id="group-name" type="text" placeholder="Enter group name" />

        <label>Select Members</label>
        <div id="group-members-list"
             class="members-list border rounded p-2"
             style="max-height: 230px; overflow-y: auto; background: #fff;">
        </div>
      </div>

      <div class="modal-footer">
        <button class="btn btn-secondary" id="group-cancel-btn">Cancel</button>
        <button class="btn btn-primary" id="create-group-submit">Create Group</button>
      </div>
    </div>
</div>
<!-- Delete Conversation Modal -->
<div id="delete-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center">
    <div class="absolute inset-0 bg-black/50"></div>

    <div class="relative bg-white rounded-lg shadow-lg w-[360px] p-5">
        <h3 class="text-lg font-semibold mb-2">Delete Conversation</h3>

        <p id="delete-modal-msg" class="text-sm text-gray-600 mb-4">
            Are you sure you want to delete this conversation?
        </p>

        <div class="flex justify-end gap-2">
            <button
                id="delete-cancel-btn"
                class="px-4 py-2 text-sm bg-gray-200 rounded hover:bg-gray-300">
                Cancel
            </button>

            <button
                id="delete-confirm-btn"
                class="px-4 py-2 text-sm bg-red-600 text-white rounded hover:bg-red-700">
                Delete
            </button>
        </div>
    </div>
</div>
<script>
window.CURRENT_USER = <?php echo (int)$currentUserId; ?>;
window.API_TOKEN = <?php echo json_encode($apiToken); ?>;

window.API_BASE = "<?php echo $API_BASE ?>";

window.WS_URL = "<?php echo $WS_URL ?>";
console.log("URL: " + window.WS_URL + " Current User: " + window.CURRENT_USER);
</script>
<script src="assets/chat.js"></script>
<div id="ui-popup-container" style="position: fixed; bottom: 20px; right: 20px; z-index: 999999;"></div>
<div id="toast-container"></div>
</body>
</html>