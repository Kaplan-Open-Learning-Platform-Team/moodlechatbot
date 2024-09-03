

/**
 * Moodle chatbot interface module.
 *
 * @module mod_moodlechatbot/interface
 */
define('mod_moodlechatbot/interface', ['jquery', 'core/ajax', 'core/log'], function($, ajax, log) {
    'use strict';

    console.log('Moodle chatbot interface module loaded');

    /**
     * Display a message in the chat interface.
     *
     * @param {string} message - The message to display.
     * @param {string} sender - The sender of the message ('user' or 'bot').
     */
    function displayMessage(message, sender) {
        const messageElement = $('<div>').addClass('message').addClass(sender);
        messageElement.text(message);
        $('#moodlechatbot-messages').append(messageElement);
        $('#moodlechatbot-messages').scrollTop($('#moodlechatbot-messages')[0].scrollHeight);
    }

    /**
     * Send a message to the Ollama API and handle the response.
     *
     * @param {string} message - The message to send to the API.
     */
    function sendMessageToOllama(message) {
        log.debug('Sending message to Ollama: ' + message);
        const ollamaUrl = 'http://192.168.0.102:11434/api/chat'; // Replace with the correct Ollama API endpoint

        ajax.call({
            url: ollamaUrl,
            type: 'POST',
            data: JSON.stringify({
                "model": "PHI3.5", // Use the appropriate model name
                "messages": [
                    {
                        "role": "user",
                        "content": message // The user's input message
                    }
                ],
                "stream": false // Assuming you don't want to use streaming
            }),
            success: function(response) {
                log.debug('Received response from Ollama:', response);
                // Access the content of the assistant's message
                let botMessage = response.message && response.message.content
                    ? response.message.content
                    : "No response received from the assistant.";

                displayMessage(botMessage, 'bot');
            },
            error: function(xhr, status, error) {
                log.error('Error in Ollama API call:', error);
                displayMessage("An error occurred: " + error, 'bot');
            }
        });
    }

    return {
        /**
         * Initialize the chatbot interface.
         */
        init: function() {
            log.debug('Moodle chatbot interface initialized');
            $(document).ready(function() {
                $('#moodlechatbot-send').click(function() {
                    const userMessage = $('#moodlechatbot-textarea').val();
                    if (userMessage.trim() !== '') {
                        log.debug('User sent message: ' + userMessage);
                        displayMessage(userMessage, 'user');
                        sendMessageToOllama(userMessage);
                        $('#moodlechatbot-textarea').val('');
                    }
                });

                $('#moodlechatbot-textarea').keypress(function(e) {
                    if (e.which === 13 && !e.shiftKey) {
                        $('#moodlechatbot-send').click();
                        return false;
                    }
                });
            });
        }
    };
});
