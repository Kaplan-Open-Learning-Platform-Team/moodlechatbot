define(['core/ajax', 'core/str', 'core/log'], function(Ajax, Str, Log) {

    const init = (userId) => {
        Log.debug('Current User ID:', userId);

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

        // Function to send the user input to the API
        const sendMessage = () => {
            const userInput = textarea.value.trim();
            // Ensure the user input is not empty
            if (!userInput) {
                return;
            }
            // Append the user's message to the chat
            appendMessage("user", userInput);
            // Clear the textarea after sending
            textarea.value = "";
            // Check if the query is about enrolled courses
            if (userInput.toLowerCase().includes("what courses am i currently enrolled in")) {
                // Make an AJAX request to get the courses
                fetch(`${M.cfg.wwwroot}/mod/moodlechatbot/view.php?action=get_courses`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.courses && data.courses.length > 0) {
                            appendMessage("assistant", "You are enrolled in the following courses:");
                            data.courses.forEach(course => {
                                appendMessage("assistant", course);
                            });
                        } else {
                            appendMessage("assistant", "You are not enrolled in any courses.");
                        }
                    })
                    .catch(error => {
                        appendMessage("assistant", "Error retrieving courses.");
                        Log.error('Fetch Error:', error);
                    });
            } else {
                // Handle other messages with the external API
                const payload = {
                    model: "gemma:2b",
                    messages: [
                        {
                            role: "user",
                            content: userInput
                        }
                    ],
                    stream: false
                };
                fetch("http://192.168.0.102:11434/api/chat", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(payload)
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data && data.message && data.message.content) {
                        appendMessage("assistant", data.message.content);
                    } else {
                        appendMessage("assistant", "Sorry, I couldn't process the response.");
                    }
                })
                .catch(error => {
                    appendMessage("assistant", "There was an error connecting to the server.");
                    Log.error('Fetch Error:', error);
                });
            }
        };

        sendButton.addEventListener("click", sendMessage);
        textarea.addEventListener("keypress", (event) => {
            if (event.key === "Enter" && !event.shiftKey) {
                event.preventDefault();
                sendMessage();
            }
        });
    };

    return { init: init };
});
