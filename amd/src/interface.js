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

        const logDebug = (debugInfo) => {
            console.log("Chatbot Debug Information:");
            debugInfo.forEach(item => console.log(item));
        };

        const handleResponse = (response) => {
            try {
                const parsedResponse = JSON.parse(response);
                if (parsedResponse.success) {
                    appendMessage("assistant", parsedResponse.message);
                } else {
                    appendMessage("error", "Error: " + parsedResponse.message);
                }
                if (parsedResponse.debug && parsedResponse.debug.length > 0) {
                    logDebug(parsedResponse.debug);
                }
            } catch (error) {
                console.error("Failed to parse response:", error);
                appendMessage("error", "An error occurred while processing the response.");
            }
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
                done: handleResponse,
                fail: function(error) {
                    console.error("AJAX call failed:", error);
                    appendMessage("error", "Failed to send message. Please try again.");
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
