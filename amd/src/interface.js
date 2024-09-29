// interface.js
define(['core/ajax', 'core/notification'], function(Ajax, Notification) {
    const init = () => {
        const sendButton = document.getElementById("moodlechatbot-send");
        const textarea = document.getElementById("moodlechatbot-textarea");
        const messagesContainer = document.getElementById("moodlechatbot-messages");

        const appendMessage = (role, content) => {
            const messageElement = document.createElement("div");
            messageElement.classList.add('message', role);
            messageElement.textContent = content;
            messagesContainer.appendChild(messageElement);
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        };

        const sendMessage = () => {
            const userInput = textarea.value.trim();
            if (!userInput) {
                return;
            }
            appendMessage("user", userInput);
            textarea.value = "";

            Ajax.call([{
                methodname: 'mod_moodlechatbot_send_message',
                args: { message: userInput },
                done: function(response) {
                    if (response.error) {
                        console.error('Error:', response.error);
                        appendMessage("assistant", "An error occurred: " + response.error);
                    } else {
                        appendMessage("assistant", response.response);
                    }
                },
                fail: function(reason) {
                    console.error('AJAX error:', reason);
                    appendMessage("assistant", "An error occurred while processing your request.");
                    Notification.exception(reason);
                }
            }]);
        };

        sendButton.addEventListener("click", sendMessage);
        textarea.addEventListener("keypress", (event) => {
            if (event.key === "Enter" && !event.shiftKey) {
                event.preventDefault();
                sendMessage();
            }
        });
    };

    return {
        init: init
    };
});
