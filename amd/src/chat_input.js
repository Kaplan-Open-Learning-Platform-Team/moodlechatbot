import Ajax from 'core/ajax';
import Notification from 'core/notification';

const Selectors = {
    FORM: '#mod_moodlechatbot_input_form_',
    INPUT: '[data-region="input"]',
    SEND_BUTTON: '[data-action="send"]',
    CHATBOT_ID: 'input[name="chatbotid"]',
    SESSKEY: 'input[name="sesskey"]'
};

/**
 * Initialize the chat input functionality.
 *
 * @param {string} uniqueId The unique identifier for this chat instance.
 */
export const init = (uniqueId) => {
    const form = document.querySelector(Selectors.FORM + uniqueId);
    const input = form.querySelector(Selectors.INPUT);
    const submitButton = form.querySelector(Selectors.SEND_BUTTON);

    const sendMessage = () => {
        const message = input.value.trim();
        if (!message) {
            return;
        }

        const chatbotId = form.querySelector(Selectors.CHATBOT_ID).value;
        const sesskey = form.querySelector(Selectors.SESSKEY).value;

        Ajax.call([{
            methodname: 'mod_moodlechatbot_send_message',
            args: {
                chatbotid: chatbotId,
                message: message,
                sesskey: sesskey
            },
            done: (response) => {
                if (response.status === 'success') {
                    // Clear the input
                    input.value = '';
                    // Trigger a custom event to update the chat display
                    const event = new CustomEvent('mod_moodlechatbot:messagesent', {
                        detail: response.message,
                        bubbles: true
                    });
                    form.dispatchEvent(event);
                } else {
                    Notification.alert('Error', response.message);
                }
            },
            fail: Notification.exception
        }]);
    };

    form.addEventListener('submit', (e) => {
        e.preventDefault();
        sendMessage();
    });

    submitButton.addEventListener('click', (e) => {
        e.preventDefault();
        sendMessage();
    });
};
