<?php
session_start();
require_once("helpers/html_helpers.php");
is_login();
$currentUserId = $_SESSION["user"]['id'] ?? 1;
$apiToken = fetchAPIToken($currentUserId);
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Internal Chat</title>
  <link rel="stylesheet" href="public/assets/chat.css">
</head>
<body>
  <div id="app">
    <aside id="conversations"></aside>
    <main id="chat-window">
      <header id="chat-header">Select a conversation</header>
      <section id="messages"></section>
      <footer id="chat-input-area">
        <input id="message-input" placeholder="Type a message" />
        <button id="send-btn">Send</button>
      </footer>
    </main>
  </div>
<script>
window.CURRENT_USER = <?php echo json_encode($currentUserId); ?>;
window.API_TOKEN = <?php echo json_encode($apiToken); ?>;
//window.WS_URL = 'ws://' + window.location.hostname + ':8080/?token=' + encodeURIComponent(window.API_TOKEN);

window.WS_URL =
    (location.protocol === 'https:' ? 'wss://' : 'ws://') +
    location.hostname +
    ':8080/?token=' + encodeURIComponent(window.API_TOKEN);

console.log(window.WS_URL);
</script>
<script src="public/assets/chat.js"></script>
</body>
</html>