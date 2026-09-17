document.addEventListener('DOMContentLoaded', () => {
  const assistantEndpoint = 'https://mbayomitpoint-ary2m40ri-mbayom-it-point.vercel.app/api/ai-agent';
  const chat = document.getElementById('ai-chat');
  const toggle = document.getElementById('ai-chat-toggle');
  const close = document.getElementById('ai-chat-close');
  const panel = document.getElementById('ai-chat-panel');
  const form = document.getElementById('ai-chat-form');
  const input = document.getElementById('ai-chat-input');
  const messages = document.getElementById('ai-chat-messages');

  if (!chat || !toggle || !close || !panel || !form || !input || !messages) return;

  const addMessage = (text, type) => {
    const message = document.createElement('div');
    message.className = `ai-chat__message ai-chat__message--${type}`;
    message.textContent = text;
    messages.appendChild(message);
    messages.scrollTop = messages.scrollHeight;
    return message;
  };

  const setOpen = (isOpen) => {
    chat.classList.toggle('is-open', isOpen);
    toggle.setAttribute('aria-expanded', String(isOpen));
    panel.setAttribute('aria-hidden', String(!isOpen));
    if (isOpen) input.focus();
  };

  toggle.addEventListener('click', () => setOpen(!chat.classList.contains('is-open')));
  close.addEventListener('click', () => setOpen(false));

  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    const message = input.value.trim();
    if (!message) return;

    addMessage(message, 'user');
    input.value = '';
    input.disabled = true;
    form.querySelector('button').disabled = true;
    const pending = addMessage('Thinking...', 'assistant');

    try {
      if (assistantEndpoint.includes('YOUR-VERCEL-PROJECT')) {
        throw new Error('Please connect the chat to your Vercel endpoint in assets/javascripts/ai-chat.js.');
      }
      const response = await fetch(assistantEndpoint, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ message })
      });
      const responseText = await response.text();
      if (responseText.trimStart().startsWith('<?php')) {
        throw new Error('The assistant backend is not enabled on this hosting plan. Please contact us on WhatsApp.');
      }
      let data;
      try {
        data = JSON.parse(responseText);
      } catch {
        throw new Error('The assistant backend returned an invalid response. Please contact us on WhatsApp.');
      }
      if (!response.ok) throw new Error(data.error || 'The assistant is unavailable right now.');
      pending.textContent = data.reply;
    } catch (error) {
      pending.textContent = error.message || 'The assistant is unavailable right now. Please contact us on WhatsApp.';
    } finally {
      input.disabled = false;
      form.querySelector('button').disabled = false;
      input.focus();
    }
  });
});
