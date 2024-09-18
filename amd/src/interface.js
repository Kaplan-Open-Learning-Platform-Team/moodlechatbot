// Define the module using Moodle's AMD module structure
define(['core/ajax', 'core/log', 'moodlechatbot/enrolled_courses'], (Ajax, Log, enrolledCourses) => { 

    // Initialize function to bind events and set up the chatbot
    const init = () => {
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

        const sendMessage = () => {
            const userInput = textarea.value.trim();

            if (!userInput) {
                return;
            }

            appendMessage("user", userInput);
            textarea.value = "";

            if (userInput.toLowerCase().includes('what courses am i enrolled in')) {
                enrolledCourses.getCourses()
                    .then(courses => {
                        const courseList = courses.join(", ");
                        appendMessage("assistant", `You are enrolled in the following courses: ${courseList}`);
                    })
                    .catch(error => {
                        appendMessage("assistant", "Sorry, I couldn't fetch your enrolled courses.");
                        // Log the error for debugging (optional)
                        Log.error('Error fetching courses:', error); 
                    });
                return; 
            }

            const payload = {
                model: "gemma:2b",
                messages: [{ role: "user", content: userInput }],
                stream: false
            };

            fetch("http://192.168.130.1:11434/api/chat", {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
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
                // Log the error for debugging (optional)
                Log.error('Fetch Error:', error); 
            });
        };

        // ... (Rest of your initialization logic, e.g., event listeners) ...

    }; // End of init()

    return { init }; 
});
