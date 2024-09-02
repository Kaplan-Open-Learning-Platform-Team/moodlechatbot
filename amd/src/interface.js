define(['jquery', 'core/ajax', 'core/notification'], function($, Ajax, Notification) {
    return {
        init: function() {
            var messagesContainer = $('#moodlechatbot-messages');
            var textArea = $('#moodlechatbot-textarea');
            var sendButton = $('#moodlechatbot-send');

            function addMessage(message, isUser) {
                var messageElement = $('<div>').addClass('message');
                if (isUser) {
                    messageElement.addClass('user-message');
                } else {
                    messageElement.addClass('bot-message');
                }
                messageElement.text(message);
                messagesContainer.append(messageElement);
                messagesContainer.scrollTop(messagesContainer[0].scrollHeight);
            }

            function sendMessage() {
                var message = textArea.val().trim();
                if (message) {
                    addMessage(message, true);
                    textArea.val('');

                    Ajax.call([{
                        methodname: 'local_moodlechatbot_send_message',
                        args: { message: message },
                        done: function(response) {
                            if (response && response.response) {
                                addMessage(response.response);
                            } else {
                                addMessage('Sorry, I couldn\'t process that request.');
                            }
                        },
                        fail: Notification.exception
                    }]);
                }
            }

            sendButton.on('click', sendMessage);

            textArea.on('keypress', function(e) {
                if (e.which === 13 && !e.shiftKey) {
                    e.preventDefault();
                    sendMessage();
                }
            });
        }
    };
});
