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
    if (userInput.toLowerCase().includes('what courses am i enrolled in')) {
        // Use require to load the module that fetches enrolled courses
        require(['yourpluginname/enrolled_courses'], function(enrolledCourses) {
            enrolledCourses.getCourses()
                .then(courses => {
                    // Append the list of courses as the assistant's response
                    const courseList = courses.join(", ");
                    appendMessage("assistant", `You are enrolled in the following courses: ${courseList}`);
                })
                .catch(error => {
                    appendMessage("assistant", "Sorry, I couldn't fetch your enrolled courses.");
                    // console.error('Error fetching courses:', error);
                });
        });
        return; // Do not send the message to the API if it's about courses
    }

    // If the query is not about courses, send it to the chatbot API
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

    // Make the AJAX request using fetch
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
        // console.error('Fetch Error:', error);
    });
};
