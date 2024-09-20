import { call as fetchMany } from 'core/ajax';
import { exception as displayException } from 'core/notification';
import { getString } from 'core/str';

const Selectors = {
  container: '#moodlechatbot-container',
  messages: '#moodlechatbot-messages',
  input: {
    textarea: '#moodlechatbot-textarea',
    send: '#moodlechatbot-send',
  },
};

const addMessageToChat = (sender, message) => {
  const messagesContainer = document.querySelector(Selectors.messages);
  const messageElement = document.createElement('div');
  messageElement.classList.add('message', sender);
  messageElement.textContent = message;
  messagesContainer.appendChild(messageElement);
  messagesContainer.scrollTop = messagesContainer.scrollHeight;
};

const getBotResponse = (userMessage) => {
  return fetchMany([{
    methodname: 'mod_moodlechatbot_get_bot_response',
    args: { message: userMessage },
  }])[0]
    .then(response => {
      console.log('Raw server response:', response);
      return response;
    })
    .catch(error => {
      console.error('Error fetching bot response:', error);
      displayException(error);
      return getString('error', 'mod_moodlechatbot');
    });
};

const sendMessage = () => {
  const textarea = document.querySelector(Selectors.input.textarea);
  const userMessage = textarea.value.trim();
  if (userMessage) {
    addMessageToChat('user', userMessage);
    textarea.value = '';
    textarea.style.height = 'auto';
    getBotResponse(userMessage)
      .then(response => {
        console.log('Parsed server response:', response);
        const [botMessage, toolResult] = response.split('\n\nTool Result: ');
        addMessageToChat('bot', botMessage || 'Error: No bot message');
        if (toolResult) {
          addMessageToChat('tool', `Tool Result: ${toolResult}`);
        }
      })
      .catch(error => {
        console.error('Error processing response:', error);
        addMessageToChat('bot', 'There was an error processing your request.');
      });
  }
};

const initEventListeners = () => {
  const sendButton = document.querySelector(Selectors.input.send);
  const textarea = document.querySelector(Selectors.input.textarea);
  sendButton.addEventListener('click', sendMessage);
  textarea.addEventListener('keypress', (e) => {
    if (e.key === 'Enter' && !e.shiftKey) {
      e.preventDefault();
      sendMessage();
    }
  });
};

export const init = () => {
  initEventListeners();
};
