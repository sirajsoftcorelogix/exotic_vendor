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
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" xintegrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ96j3p6QhL6pG6L7Jz4uB4pB7I9fX6p5z5w2k4t5N6g9/5o5e+n8t1t2A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
  <link rel="stylesheet" href="assets/chat.css">
  <link rel="stylesheet" href="style/style.css" />
  <style>
    .bell-container {
      position: relative;
      display: inline-block;
    }

    .bell {
      width: 60px;
    }

    /* Badge that supports 1–3 digits */
    .notification-badge {
      position: absolute;
      top: -12px;
      right: -12px;
      background: red;
      color: white;
      padding: 4px 4px;
      min-width: 20px;
      height: 20px;
      border-radius: 12px; /* pill shape */
      font-size: 14px;
      font-weight: bold;
      display: flex;
      align-items: center;
      justify-content: center;
      white-space: nowrap;
    }
</style>
</head>
<body>
<div class="flex-1 flex flex-col overflow-hidden">

<div id="chat-app">
  <?php include 'views/layouts/left_menu.php'; ?>
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
<script>
window.CURRENT_USER = <?php echo (int)$currentUserId; ?>;
window.API_TOKEN = <?php echo json_encode($apiToken); ?>;

window.API_BASE = "<?php echo $API_BASE ?>";

window.WS_URL = "<?php echo $WS_URL ?>/" + encodeURIComponent(window.API_TOKEN);
console.log("URL: " + window.WS_URL + " Current User: " + window.CURRENT_USER);
</script>
<script src="assets/chat.js"></script>
<div id="ui-popup-container" style="position: fixed; bottom: 20px; right: 20px; z-index: 999999;"></div>
<div id="toast-container"></div>
</body>
</html>