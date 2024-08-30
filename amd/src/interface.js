
// Define the module using AMD (Asynchronous Module Definition)
define(['jquery'], function($) {
    return {
        // Initialize the chatbot functionality
        init: function() {
            const messageContainer = $('#moodlechatbot-messages');
            const textarea = $('#moodlechatbot-textarea');
            const sendButton = $('#moodlechatbot-send');
            // Event listener for the send button click event
            sendButton.on('click', function() {
                const userMessage = textarea.val().trim();
                if (userMessage === '') {
                    return; // Prevent sending empty messages
                }
                // Display the user's message in the chat
                displayMessage(userMessage, 'user');
                textarea.val(''); // Clear the textarea after sending
                // Send the user's message to the Ollama API
                sendMessageToOllama(userMessage)
                    .then(response => {
                        // Display the response from Ollama in the chat
                        displayMessage(response, 'bot');
                    })
                    .catch(error => {
                        console.error('Error communicating with Ollama:', error);
                        displayMessage('Sorry, there was an error processing your request.', 'bot');
                    });
            });
            // Function to display a message in the chat interface
            function displayMessage(message, sender) {
                const messageElement = $('<div>').addClass('message ' + sender).text(message);
                messageContainer.append(messageElement);
                // Automatically scroll to the bottom of the chat to show the latest message
                messageContainer.scrollTop(messageContainer.prop('scrollHeight'));
            }
            // Function to send a message to the Ollama API and return the response
            async function sendMessageToOllama(message) {
                try {
                    const response = await fetch(M.cfg.wwwroot + '/local/myplugin/ollama/api/generate', { // Ensure the URL is relative to Moodle
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest' // Ensure this is set for AJAX requests in Moodle
                        },
                        body: JSON.stringify({
                            model: 'llama2', // Replace with the desired model
                            prompt: message
                        })
                    });
                    if (!response.ok) {
                        throw new Error(`Ollama API request failed with status ${response.status}`);
                    }
                    const data = await response.json();
                    return data.choices[0].text.trim(); 
                } catch (error) {
                    console.error('Failed to fetch from Ollama API:', error);
                    throw error;
                }
            }
        }
    };
});
