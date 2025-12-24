(function(){
  const ws = new WebSocket(window.WS_URL);
  const messagesEl = document.getElementById('messages');
  const input = document.getElementById('message-input');
  const sendBtn = document.getElementById('send-btn');

  let activeConversation = 1;

  ws.onmessage = (ev) => {
    let data; 
    try { data = JSON.parse(ev.data); } 
    catch(e){ return; }
    if (data.type === 'new_message') renderMessage(data.message);
  };

  sendBtn.onclick = sendMessage;

  input.onkeydown = e => { 
    if(e.key === 'Enter') sendMessage(); 
  };

  function sendMessage(){
    const txt = input.value.trim();
    if(!txt) return;
    ws.send(JSON.stringify({
      type:'send_message', 
      conversation_id:activeConversation, 
      message:txt
    }));
    input.value = '';
  }

  function renderMessage(msg){
    const d = document.createElement('div');
    d.className = 'message';
    d.innerHTML =
      '<strong>' + 
      (msg.sender_id == window.CURRENT_USER ? 'You' : 'User ' + msg.sender_id) + 
      '</strong>: ' + msg.message + 
      '<div class=\"meta\">' + msg.created_at + '</div>';

    messagesEl.appendChild(d);
    messagesEl.scrollTop = messagesEl.scrollHeight;
  }
})();
