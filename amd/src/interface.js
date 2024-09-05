
define(['core/ajax', 'core/log'], function(ajax, log) {
    return {
        init: function() {
            const messagesContainer = document.getElementById('moodlechatbot-messages');
            const textarea = document.getElementById('moodlechatbot-textarea');
            const sendButton = document.getElementById('moodlechatbot-send');

            // Debug: Check if DOM elements are found
            if (messagesContainer && textarea && sendButton) {
                log.debug('MoodleChatbot: DOM elements found successfully.'); 
            } else {
                log.debug('MoodleChatbot: Error finding DOM elements.'); 
            }

            function addMessage(content, isUser = false) {
                const messageElement = document.createElement('div');
                messageElement.classList.add('message');
                messageElement.classList.add(isUser ? 'user-message' : 'bot-message');
                messageElement.textContent = content;
                messagesContainer.appendChild(messageElement);
                messagesContainer.scrollTop = messagesContainer.scrollHeight;

                // Debug: Log added message
                log.debug('MoodleChatbot: Added message:', content, '(isUser:', isUser, ')'); 
            }

            function sendMessage() {
                const message = textarea.value.trim();
                if (message) {
                    addMessage(message, true);
                    textarea.value = '';

                    // Debug: Log message being sent to API
                    log.debug('MoodleChatbot: Sending message to API:', message); 

                    // Send request to Ollama API
                    fetch('http://192.168.0.102:11434/api/chat', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            model: 'llama2', // Adjust this to the model you're using
                            messages: [{ role: 'user', content: message }],
                        }),
                    })
                    .then(response => {
                        // Debug: Log API response status
                        log.debug('MoodleChatbot: API response status:', response.status); 
                        return response.json();
                    })
                    .then(data => {
                        const botResponse = data.message.content;
                        addMessage(botResponse);

                        // Debug: Log API response data
                        log.debug('MoodleChatbot: API response data:', data); 
                    })
                    .catch(error => {
                        log.error('MoodleChatbot: Error:', error); 
                        addMessage('Sorry, there was an error processing your request.');
                    });
                }
            }

            sendButton.addEventListener('click', sendMessage);
            textarea.addEventListener('keypress', function(e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    sendMessage();
                }
            });
        }
    };
});
