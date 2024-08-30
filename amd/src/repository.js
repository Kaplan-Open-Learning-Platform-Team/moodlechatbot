import Ajax from 'core/ajax';

/**
 * Send a chat message to the server.
 *
 * @param {string} message The message to send.
 * @return {Promise}
 */
export const sendChatMessage = (message) => {
    return Ajax.call([{
        methodname: 'mod_moodlechatbot_send_message',
        args: { message: message }
    }])[0];
};
