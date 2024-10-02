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
            console.group("Chatbot Debug Information");
            debugInfo.forEach(item => {
                console.log(item);
            });
            console.groupEnd();
        };

        const handleResponse = (response) => {
            console.log("Raw response:", response);  // Log the raw response for debugging

            let parsedResponse;
            
            try {
                // Handle if response is already an object
                if (typeof response === 'object') {
                    parsedResponse = response;
                } else {
                    parsedResponse = JSON.parse(response);
                }

                // Log the parsed response for debugging
                console.log("Parsed response:", parsedResponse);

                // Check if the response has a 'data' property
                if (parsedResponse.data) {
                    parsedResponse = parsedResponse.data;
                }

                // Handle the regular response
                if (parsedResponse.courses) {
                    // Handle courses if present
                    console.log("Courses found:", parsedResponse.courses);
                    appendMessage("assistant", "Found " + parsedResponse.courses.length + " courses");
                }

                if (parsedResponse.message) {
                    appendMessage("assistant", parsedResponse.message);
                }

                // Handle debug information
                if (parsedResponse.debug && Array.isArray(parsedResponse.debug)) {
                    logDebug(parsedResponse.debug);
                }

            } catch (error) {
                console.error("Failed to process response:", error);
                console.error("Problematic response:", response);
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
                done: function(response) {
                    try {
                        handleResponse(response);
                    } catch (error) {
                        console.error("Error in response handler:", error);
                        appendMessage("error", "An error occurred while processing the response.");
                    }
                },
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