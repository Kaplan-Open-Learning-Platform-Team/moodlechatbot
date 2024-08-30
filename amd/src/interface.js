// Define the module using AMD (Asynchronous Module Definition)
define(['jquery'], function($) {
    console.log('Moodle chatbot module loaded');
    return {
        // Initialize the chatbot functionality
        init: function() {
            console.log('Initializing chatbot');
            const messageContainer = $('#moodlechatbot-messages');
            const textarea = $('#moodlechatbot-textarea');
            const sendButton = $('#moodlechatbot-send');

            if (!messageContainer.length || !textarea.length || !sendButton.length) {
                console.error('One or more required elements not found');
                return;
            }

            // Event listener for the send button click event
            sendButton.on('click', function() {
                console.log('Send button clicked');
                const userMessage = textarea.val().trim();
                if (userMessage === '') {
                    console.log('Empty message, not sending');
                    return; // Prevent sending empty messages
                }
                console.log('User message:', userMessage);
                // Display the user's message in the chat
                displayMessage(userMessage, 'user');
                textarea.val(''); // Clear the textarea after sending
                // Send the user's message to the Ollama API
                sendMessageToOllama(userMessage)
                    .then(response => {
                        console.log('Received response from Ollama:', response);
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
                console.log(`Displaying ${sender} message:`, message);
                const messageElement = $('<div>').addClass('message ' + sender).text(message);
                messageContainer.append(messageElement);
                // Automatically scroll to the bottom of the chat to show the latest message
                messageContainer.scrollTop(messageContainer.prop('scrollHeight'));
            }

            // Function to send a message to the Ollama API and return the response
            async function sendMessageToOllama(message) {
                console.log('Sending message to Ollama:', message);
                try {
                    const apiUrl = M.cfg.wwwroot + '/local/myplugin/ollama/api/generate';
                    console.log('API URL:', apiUrl);
                    const response = await fetch(apiUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({
                            model: 'llama2', // Replace with the desired model
                            prompt: message
                        })
                    });
                    console.log('Ollama API response status:', response.status);
                    if (!response.ok) {
                        throw new Error(`Ollama API request failed with status ${response.status}`);
                    }
                    const data = await response.json();
                    console.log('Ollama API response data:', data);
                    return data.choices[0].text.trim(); 
                } catch (error) {
                    console.error('Failed to fetch from Ollama API:', error);
                    throw error;
                }
            }

            console.log('Chatbot initialization complete');
        }
    };
});
