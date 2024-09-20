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
  messageElement.classList.add('message', `${sender}-message`);
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
      // Log the raw response for debugging.
      console.log('Raw server response:', response);

      // Ensure the response is parsed correctly
      if (typeof response === 'string') {
        try {
          return JSON.parse(response); // Ensure it's valid JSON
        } catch (error) {
          console.error('Failed to parse JSON response:', error);
          throw new Error('Invalid response format');
        }
      }
      return response; // Return the parsed response if it's already an object
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
        // Log the parsed response for debugging purposes.
        console.log('Parsed server response:', response);

        // Assuming the response is an object with 'botMessage' and 'toolResult'
        const botMessage = response.botMessage || 'Error: No bot message';
        const toolResult = response.toolResult;

        addMessageToChat('bot', botMessage);

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

