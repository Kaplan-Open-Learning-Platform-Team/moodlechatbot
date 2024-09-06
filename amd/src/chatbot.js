import {call as fetchMany} from 'core/ajax';
import {exception as displayException} from 'core/notification';
import {getString} from 'core/str';

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
 * @param {string} sender The sender of the message ('user' or 'bot').
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
        args: {message: userMessage},
    }])[0]
    .catch(error => {
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
            addMessageToChat('bot', response);
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
