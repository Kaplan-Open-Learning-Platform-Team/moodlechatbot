// Define the module using Moodle's AMD module structure
define(['core/ajax', 'core/str', 'core/log'], function(Ajax, Str, Log) {

    // Initialize function to bind events and set up the chatbot
    const init = (userId) => {
        // Log the userId to ensure it's being passed correctly
        Log.debug('Current User ID:', userId);

        const sendButton = document.getElementById("moodlechatbot-send");
        const textarea = document.getElementById("moodlechatbot-textarea");
        const messagesContainer = document.getElementById("moodlechatbot-messages");

        // Function to append messages to the chat
        const appendMessage = (role, content) => {
            const messageElement = document.createElement("div");
            messageElement.classList.add('message', role);
            messageElement.textContent = content;
            messagesContainer.appendChild(messageElement);
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        };

        // Function to send the user input to the API using fetch
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

            // Detect if the user is asking about their enrolled courses
            if (userInput.toLowerCase().includes("what courses am i enrolled in")) {
                // Make an AJAX call to the server to get the user's courses
                fetch(M.cfg.wwwroot + "/mod/moodlechatbot/view.php?ajax=true", {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.courses && data.courses.length > 0) {
                        const coursesMessage = "You are currently enrolled in the following courses: " + data.courses.join(", ");
                        appendMessage("assistant", coursesMessage);
                    } else {
                        appendMessage("assistant", "You are not currently enrolled in any courses.");
                    }
                })
                .catch(error => {
                    appendMessage("assistant", "There was an error retrieving your courses.");
                    Log.error('Fetch Error:', error);
                });

                return;  // Exit early since we're handling this specific case.
            }

            // Otherwise, send the user's input to the external chatbot API
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
        };

        // Add event listener for the send button
        sendButton.addEventListener("click", sendMessage);

        // Add event listener to trigger the send action when "Enter" is pressed in the textarea
        textarea.addEventListener("keypress", (event) => {
            if (event.key === "Enter" && !event.shiftKey) {
                event.preventDefault();
                sendMessage();
            }
        });
    };

    // Return the public API
    return {
        init: init
    };
});
