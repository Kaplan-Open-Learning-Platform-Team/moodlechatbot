import Ajax from 'core/ajax';
import Notification from 'core/notification';
import * as Templates from 'core/templates';
import { init as initChatInput } from './chat_input';
import Log from 'core/log';

const Selectors = {
    CHAT_CONTAINER: '.mod_moodlechatbot_chat',
    MESSAGES_CONTAINER: '[data-region="messages"]',
};

/**
 * Initialize the chat functionality.
 *
 * @param {string} uniqueId The unique identifier for this chat instance.
 */
export const init = (uniqueId) => {
    const chatContainerSelector = `#${uniqueId}`;
    const chatContainer = document.querySelector(chatContainerSelector);

    if (!chatContainer) {
        Log.debug(`Chat container not found with selector: ${chatContainerSelector}`);
        return;
    }

    const messagesContainer = chatContainer.querySelector(Selectors.MESSAGES_CONTAINER);

    if (!messagesContainer) {
        Log.debug('Messages container not found within chat container');
        return;
    }

    /**
     * Display a new message in the chat.
     *
     * @param {Object} messageData The message data to display.
     */
    const displayMessage = async (messageData) => {
        try {
            const messageHtml = await Templates.render('mod_moodlechatbot/chat_message', messageData);
            messagesContainer.insertAdjacentHTML('beforeend', messageHtml);
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        } catch (error) {
            Notification.exception(error);
        }
    };

    /**
     * Fetch and display chat history.
     */
    const loadChatHistory = () => {
        const chatbotId = chatContainer.dataset.chatbotid;
        
        if (!chatbotId) {
            Log.debug('Chatbot ID not found in chat container dataset');
            return;
        }

        Ajax.call([{
            methodname: 'mod_moodlechatbot_get_chat_history',
            args: { chatbotid: chatbotId },
            done: (messages) => {
                messages.forEach(displayMessage);
            },
            fail: Notification.exception
        }]);
    };

    // Initialize chat input
    initChatInput(uniqueId);

    // Load chat history
    loadChatHistory();

    // Listen for new messages
    chatContainer.addEventListener('mod_moodlechatbot:messagesent', (event) => {
        displayMessage(event.detail);
    });

    Log.debug('Chat initialized successfully');
};
