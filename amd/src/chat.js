import Ajax from 'core/ajax';
import Notification from 'core/notification';

/**
 * Initialize the chat functionality.
 */
export const init = () => {
    const messages = document.getElementById('moodlechatbot-messages');
    const input = document.getElementById('moodlechatbot-input');
    const sendButton = document.getElementById('moodlechatbot-send');

    /**
     * Add a message to the chat display.
     * @param {string} sender The sender of the message.
     * @param {string} message The message content.
     */
    const addMessage = (sender, message) => {
        const messageElement = document.createElement('p');
        messageElement.innerHTML = `<strong>${sender}:</strong> ${message}`;
        messages.appendChild(messageElement);
        messages.scrollTop = messages.scrollHeight;
    };

    /**
     * Send a message to the server and handle the response.
     */
    const sendMessage = () => {
        const message = input.value.trim();
        if (message) {
            addMessage('You', message);
            input.value = '';

            Ajax.call([{
                methodname: 'mod_moodlechatbot_send_message',
                args: { message: message },
                done: (response) => {
                    if (response.status === 'success') {
                        addMessage('Bot', response.message);
                    } else {
                        Notification.alert('Error', response.message);
                    }
                },
                fail: Notification.exception
            }]);
        }
    };

    // Event listeners
    sendButton.addEventListener('click', sendMessage);
    input.addEventListener('keypress', (e) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });
};
