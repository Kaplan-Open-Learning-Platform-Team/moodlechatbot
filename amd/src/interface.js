
//interface.js
// Define the module using Moodle's AMD module structure
define(['core/ajax', 'core/str', 'core/log'], function(Ajax, Str, Log) {

    // Initialize function to bind events and set up the chatbot
    const init = () => {
        const sendButton = document.getElementById("moodlechatbot-send");
        const textarea = document.getElementById("moodlechatbot-textarea");
        const messagesContainer = document.getElementById("moodlechatbot-messages");

        // Function to append messages to the chat
        const appendMessage = (role, content) => {
            const messageElement = document.createElement("div");
            messageElement.classList.add('message', role); // 'message' class with 'user' or 'assistant' roles
            messageElement.textContent = content;
            messagesContainer.appendChild(messageElement);
            messagesContainer.scrollTop = messagesContainer.scrollHeight; // Auto-scroll to the bottom
        };

        // Function to send the user input to the API using fetch
        const sendMessage = () => {
            const userInput = textarea.value.trim();

            if (!userInput) {
                return;
            }

            appendMessage("user", userInput);
            textarea.value = "";

            if (userInput.toLowerCase() === "what courses am i enrolled in?") {
                // Call the web service
                Ajax.call([{
                    methodname: 'mod_moodlechatbot_get_enrolled_courses',
                    args: {},
                }])[0].then(function(response) {
                    let message = "You are enrolled in the following courses:\n";
                    response.forEach(course => {
                        message += `- ${course.fullname} (${course.shortname})\n`;
                    });
                    appendMessage("assistant", message);
                }).catch(function(error) {
                    Log.error('Error fetching enrolled courses:', error);
                    appendMessage("assistant", "Sorry, I couldn't retrieve your enrolled courses at the moment.");
                });
            } else {
                // Existing code for other queries...
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

                fetch("http://192.168.130.1:11434/api/chat", {
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

        // Add event listener for the send button
        sendButton.addEventListener("click", sendMessage);

        // Add event listener to trigger the send action when "Enter" is pressed in the textarea
        textarea.addEventListener("keypress", (event) => {
            if (event.key === "Enter" && !event.shiftKey) {
                event.preventDefault(); // Prevent creating a new line
                sendMessage();
            }
        });
    };

    // Return the public API
    return {
        init: init
    };
});


