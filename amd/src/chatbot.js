import { call as fetchMany } from 'core/ajax';
import { exception as displayException } from 'core/notification';
import { getString } from 'core/str';

/**
 * Selectors used in this module.
 *
 * @type {Object}
 */
const Selectors = {
  container: '#moodlechatbot-container',
  messages: '#moodlechatbot-messages',
  input: {
    textarea: '#moodlechatbot-textarea',
    send: '#moodlechatbot-send',
  },
};

/**
 * Add a message to the chat interface.
 *
 * @param {string} sender The sender of the message ('user', 'bot', or 'tool').
 * @param {string} message The message content.
 */
const addMessageToChat = (sender, message) => {
  const messagesContainer = document.querySelector(Selectors.messages);
  const messageElement = document.createElement('div');
  messageElement.classList.add('message', `${sender}-message`);
  messageElement.textContent = message;
  messagesContainer.appendChild(messageElement);
  messagesContainer.scrollTop = messagesContainer.scrollHeight;
};

/**
 * Fetch the bot's response from the server.
 *
 * @param {string} userMessage The user's message.
 * @return {Promise}
 */
const getBotResponse = (userMessage) => {
  return fetchMany([{
    methodname: 'mod_moodlechatbot_get_bot_response',
    args: { message: userMessage },
  }])[0]
    .then(response => {
      // Log the raw response for debugging.
      console.log('Raw server response:', response);
      return response;
    })
    .catch(error => {
      // Log the error and display it.
      console.error('Error fetching bot response:', error);
      displayException(error);
      return getString('error', 'mod_moodlechatbot');
    });
};

/**
 * Send a message and get the bot's response.
 */
const sendMessage = () => {
  const textarea = document.querySelector(Selectors.input.textarea);
  const userMessage = textarea.value.trim();
  if (userMessage) {
    addMessageToChat('user', userMessage);
    textarea.value = '';
    textarea.style.height = 'auto';

    getBotResponse(userMessage)
      .then(response => {
        // Log the response for debugging purposes.
        console.log('Parsed server response:', response);

        // Split the response if it contains a tool result.
        const [botMessage, toolResult] = response.split('\n\nTool Result: ');
        addMessageToChat('bot', botMessage || 'Error: No bot message');

        // Check if there's a tool result and add it to the chat.
        if (toolResult) {
          addMessageToChat('tool', `Tool Result: ${toolResult}`);
        }
      })
      .catch(error => {
        // Log and show an error message in case of failure.
        console.error('Error processing response:', error);
        addMessageToChat('bot', 'There was an error processing your request.');
      });
  }
};

/**
 * Initialize event listeners.
 */
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

/**
 * Initialize the chat bot.
 */
export const init = () => {
  initEventListeners();
};
