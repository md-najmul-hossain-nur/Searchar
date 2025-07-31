const chatWindow = document.getElementById('chatWindow');
const chatInput = document.getElementById('chatInput');
const sendBtn = document.getElementById('sendBtn');

sendBtn.addEventListener('click', () => {
  const msg = chatInput.value.trim();
  if(msg === '') return;

  const msgDiv = document.createElement('div');
  msgDiv.classList.add('message', 'sent');
  msgDiv.textContent = msg;

  chatWindow.appendChild(msgDiv);
  chatInput.value = '';
  chatWindow.scrollTop = chatWindow.scrollHeight;
});

chatInput.addEventListener('keypress', (e) => {
  if(e.key === 'Enter') {
    sendBtn.click();
  }
});
