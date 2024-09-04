// mod/moodlechatbot/amd/src/interface.js

import Log from 'core/log';
import Notification from 'core/notification';

const Selectors = {
    messages: '#moodlechatbot-messages',
    sendButton: '#moodlechatbot-send',
    textarea: '#moodlechatbot-textarea'
};

/**
 * Display a message in the chat interface.
 *
 * @param {string} message - The message to display.
 * @param {string} sender - The sender of the message ('user' or 'bot').
 */
const displayMessage = (message, sender) => {
    const messagesContainer = document.querySelector(Selectors.messages);
    const messageElement = document.createElement('div');
    messageElement.classList.add('message', sender);
    messageElement.textContent = message;
    messagesContainer.appendChild(messageElement);
    messagesContainer.scrollTop = messagesContainer.scrollHeight;
};

/**
 * Send a message to the Ollama API and handle the response.
 *
 * @param {string} message - The message to send to the API.
 * @return {Promise}
 */
const sendMessageToOllama = (message) => {
    Log.debug('Sending message to Ollama: ' + message);
    const ollamaUrl = 'http://192.168.0.102:11434/api/chat'; // Replace with the correct Ollama API endpoint

    return fetch(ollamaUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            model: "PHI3.5", // Use the appropriate model name
            messages: [
                {
                    role: "user",
                    content: message
                }
            ],
            stream: false
        })
    })
    .then(response => response.json())
    .then((response) => {
        Log.debug('Received response from Ollama:', response);
        let botMessage = response.message && response.message.content
            ? response.message.content
            : "No response received from the assistant.";

        displayMessage(botMessage, 'bot');
    })
    .catch((error) => {
        Log.error('Error in Ollama API call:', error);
        displayMessage("An error occurred while processing your request.", 'bot');
        Notification.exception(error);
    });
};

/**
 * Initialize the chatbot interface.
 */
export const init = () => {
    Log.debug('Moodle chatbot interface initialized');

    document.addEventListener('DOMContentLoaded', () => {
        const sendButton = document.querySelector(Selectors.sendButton);
        const textarea = document.querySelector(Selectors.textarea);

        sendButton.addEventListener('click', () => {
            const userMessage = textarea.value.trim();
            if (userMessage !== '') {
                Log.debug('User sent message: ' + userMessage);
                displayMessage(userMessage, 'user');
                sendMessageToOllama(userMessage);
                textarea.value = '';
            }
        });

        textarea.addEventListener('keypress', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendButton.click();
            }
        });
    });
};
