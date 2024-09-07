define(['core/ajax', 'core/str', 'core/log'], function(Ajax, Str, Log) {

    const init = (userId) => {
        Log.debug('Current User ID:', userId);
        Log.debug('Chatbot module initialized');

        const sendButton = document.getElementById("moodlechatbot-send");
        const textarea = document.getElementById("moodlechatbot-textarea");
        const messagesContainer = document.getElementById("moodlechatbot-messages");

        const appendMessage = (role, content) => {
            const messageElement = document.createElement("div");
            messageElement.classList.add('message', role);
            messageElement.textContent = content;
            messagesContainer.appendChild(messageElement);
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        };

        const getEnrolledCourses = () => {
            return new Promise((resolve, reject) => {
                Ajax.call([{
                    methodname: 'mod_moodlechatbot_get_enrolled_courses',
                    args: {}
                }])[0].done(function(courses) {
                    if (courses.length > 0) {
                        let courseList = 'You are currently enrolled in the following courses:\n';
                        courses.forEach(course => {
                            courseList += `- ${course.fullname}\n`;
                        });
                        resolve(courseList);
                    } else {
                        resolve('You are not currently enrolled in any courses.');
                    }
                }).fail(function(error) {
                    Log.error('AJAX call failed:', error);
                    reject('Sorry, I encountered an error while fetching your enrolled courses.');
                });
            });
        };

        const isEnrollmentQuery = (input) => {
            const enrollmentKeywords = [
                'what courses am i enrolled in',
                'which courses am i taking',
                'my courses',
                'show me my courses',
                'list my courses',
                'what classes am i in',
                'what are my current courses'
            ];
            return enrollmentKeywords.some(keyword => input.toLowerCase().includes(keyword));
        };

        const sendMessage = () => {
            const userInput = textarea.value.trim();

            if (!userInput) {
                return;
            }

            appendMessage("user", userInput);
            textarea.value = "";

            if (isEnrollmentQuery(userInput)) {
                appendMessage("assistant", "Let me fetch your enrolled courses for you...");
                getEnrolledCourses()
                    .then(response => appendMessage("assistant", response))
                    .catch(error => appendMessage("assistant", error));
            } else {
                // Existing code for other queries
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

                fetch("http://192.168.0.102:11434/api/chat", {
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
                    Log.error('Fetch Error:', error);
                });
            }
        };

        sendButton.addEventListener("click", sendMessage);

        textarea.addEventListener("keypress", (event) => {
            if (event.key === "Enter" && !event.shiftKey) {
                event.preventDefault();
                sendMessage();
            }
        });
    };

    return {
        init: init
    };
});
