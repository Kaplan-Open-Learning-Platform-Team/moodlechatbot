define(['jquery', 'core/ajax'], function($, Ajax) {
    return {
        init: function() {
            var messageContainer = $('#moodlechatbot-messages');
            var inputTextarea = $('#moodlechatbot-textarea');
            var sendButton = $('#moodlechatbot-send');

            function addMessageToChat(sender, message) {
                var messageElement = $('<div>').addClass('message ' + sender + '-message').text(message);
                messageContainer.append(messageElement);
                messageContainer.scrollTop(messageContainer[0].scrollHeight);
            }

            function getBotResponse(userMessage) {
                return Ajax.call([{
                    methodname: 'mod_moodlechatbot_get_bot_response',
                    args: { message: userMessage },
                }])[0];
            }

            function sendMessage() {
                var userMessage = inputTextarea.val().trim();
                if (userMessage) {
                    addMessageToChat('user', userMessage);
                    inputTextarea.val('');
                    inputTextarea.css('height', 'auto');

                    getBotResponse(userMessage).then(function(response) {
                        addMessageToChat('bot', response);
                    }).catch(function(error) {
                        console.error('Error getting bot response:', error);
                        addMessageToChat('bot', 'Sorry, I encountered an error. Please try again later.');
                    });
                }
            }

            sendButton.on('click', sendMessage);
            inputTextarea.on('keypress', function(e) {
                if (e.which === 13 && !e.shiftKey) {
                    e.preventDefault();
                    sendMessage();
                }
            });
        }
    };
});
