import Ajax from 'core/ajax';
import Notification from 'core/notification';
import Templates from 'core/templates';
import Log from 'core/log';

export const init = (chatContainerSelector) => {
    const chatContainer = document.querySelector(chatContainerSelector);
    if (!chatContainer) {
        Log.debug(`Chat container not found with selector: ${chatContainerSelector}`);
        return;
    }

    const messagesContainer = chatContainer.querySelector('[data-region="messages"]');
    const inputArea = chatContainer.querySelector('[data-region="input"]');
    const sendButton = chatContainer.querySelector('[data-action="send"]');
    const chatbotId = chatContainer.dataset.chatbotid;

    const scrollToBottom = () => {
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    };

    const appendMessage = async (messageData) => {
        try {
            const html = await Templates.renderForPromise('mod_moodlechatbot/message', messageData);
            messagesContainer.insertAdjacentHTML('beforeend', html);
            scrollToBottom();
        } catch (error) {
            Log.error('Error rendering message template:', error);
            Notification.exception(error);
        }
    };

    const sendMessage = async () => {
        const message = inputArea.value.trim();
        if (!message) {
            return;
        }

        inputArea.value = '';
        await appendMessage({
            content: message,
            sender: 'You',
            timestamp: new Date().toLocaleTimeString(),
            isbot: false
        });

        try {
            const response = await Ajax.call([{
                methodname: 'mod_moodlechatbot_send_message',
                args: { chatbotid: chatbotId, message: message }
            }])[0];

            if (response.status === 'success') {
                await appendMessage({
                    content: response.message,
                    sender: 'Bot',
                    timestamp: new Date().toLocaleTimeString(),
                    isbot: true
                });
            } else {
                throw new Error(response.message);
            }
        } catch (error) {
            Log.error('Error sending message:', error);
            Notification.exception(error);
        }
    };

    sendButton.addEventListener('click', sendMessage);

    inputArea.addEventListener('keypress', (event) => {
        if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();
            sendMessage();
        }
    });

    Log.debug('Chat initialized successfully');
};
