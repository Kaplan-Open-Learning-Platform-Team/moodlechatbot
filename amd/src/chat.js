import Ajax from 'core/ajax';
import Notification from 'core/notification';
import Templates from 'core/templates';
import Log from 'core/log';

const Selectors = {
    CHAT_CONTAINER: '.mod_moodlechatbot_chat',
    MESSAGES_CONTAINER: '[data-region="messages"]',
    INPUT: '[data-region="input"]',
    SEND_BUTTON: '[data-action="send"]'
};

export const init = (chatId) => {
    const chatContainer = document.getElementById(chatId);
    if (!chatContainer) {
        Log.debug(`Chat container not found with id: ${chatId}`);
        return;
    }

    const messagesContainer = chatContainer.querySelector(Selectors.MESSAGES_CONTAINER);
    const input = chatContainer.querySelector(Selectors.INPUT);
    const sendButton = chatContainer.querySelector(Selectors.SEND_BUTTON);

    const displayMessage = async (messageData) => {
        try {
            const messageHtml = await Templates.renderForPromise('mod_moodlechatbot/chat_message', messageData);
            messagesContainer.insertAdjacentHTML('beforeend', messageHtml.html);
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        } catch (error) {
            Notification.exception(error);
        }
    };

    const sendMessage = () => {
        const message = input.value.trim();
        if (!message) return;

        const chatbotId = chatContainer.dataset.chatbotid;

        Ajax.call([{
            methodname: 'mod_moodlechatbot_send_message',
            args: { chatbotid: chatbotId, message: message },
            done: async (response) => {
                await displayMessage({ sender: 'You', content: message, isbot: false });
                input.value = '';
                if (response.status === 'success') {
                    await displayMessage({ sender: 'Bot', content: response.message, isbot: true });
                } else {
                    Notification.alert('Error', response.message);
                }
            },
            fail: Notification.exception
        }]);
    };

    sendButton.addEventListener('click', sendMessage);
    input.addEventListener('keypress', (e) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });

    Log.debug('Chat initialized successfully');
};
