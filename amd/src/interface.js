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

        const sendMessage = () => {
            const userInput = textarea.value.trim();

            if (!userInput) {
                return;
            }

            Log.debug('User Input:', userInput); // Log the user input

            appendMessage("user", userInput);
            textarea.value = "";

            if (userInput.toLowerCase().includes("what courses am i enrolled in")) {
                fetch(M.cfg.wwwroot + "/mod/moodlechatbot/view.php?ajax=true", {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    Log.debug('Server Response:', data); // Log the server response
                    if (data.courses && data.courses.length > 0) {
                        const messageStart = "You are currently enrolled in the following courses: ";
                        const courseNames = data.courses.map(course => course.fullname).join(", ");
                        const coursesMessage = messageStart + courseNames;
                        appendMessage("assistant", coursesMessage);
                    } else {
                        appendMessage("assistant", "You are not currently enrolled in any courses.");
                    }
                })
                .catch(error => {
                    appendMessage("assistant", "There was an error retrieving your courses.");
                    Log.error('Fetch Error:', error);
                });

                return;
            }

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
