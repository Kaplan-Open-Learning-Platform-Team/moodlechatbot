import Ajax from 'core/ajax';
import { get_string as getString } from 'core/str';
import * as Repository from './repository';

const Selectors = {
    actions: {
        send: '[data-action="send"]'
    },
    regions: {
        messages: '[data-region="messages"]',
        input: '[data-region="input"]'
    }
};

/**
 * Initialize the chat module.
 *
 * @param {string} containerId The id of the chat container.
 */
export const init = (containerId) => {
    const container = document.getElementById(containerId);
    const messagesRegion = container.querySelector(Selectors.regions.messages);
    const inputRegion = container.querySelector(Selectors.regions.input);
    const sendButton = container.querySelector(Selectors.actions.send);

    /**
     * Append a message to the chat.
     *
     * @param {string} sender The sender of the message.
     * @param {string} message The message content.
     */
    const appendMessage = (sender, message) => {
        const messageElement = document.createElement('p');
        messageElement.innerHTML = `<strong>${sender}:</strong> ${message}`;
        messagesRegion.appendChild(messageElement);
        messagesRegion.scrollTop = messagesRegion.scrollHeight;
    };

    /**
     * Send a message to the server.
     *
     * @param {string} message The message to send.
     * @return {Promise}
     */
    const sendMessage = async (message) => {
        try {
            const response = await Repository.sendChatMessage(message);
            if (response.status === 'success') {
                appendMessage('Bot', response.message);
            } else {
                const errorString = await getString('error', 'moodle');
                appendMessage('Bot', errorString);
            }
        } catch (error) {
            const errorString = await getString('error', 'moodle');
            appendMessage('Bot', errorString);
        }
    };

    /**
     * Register event listeners.
     */
    const registerEventListeners = () => {
        sendButton.addEventListener('click', () => {
            const message = inputRegion.value.trim();
            if (message) {
                appendMessage('You', message);
                inputRegion.value = '';
                sendMessage(message);
            }
        });

        inputRegion.addEventListener('keypress', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendButton.click();
            }
        });
    };

    registerEventListeners();
};
