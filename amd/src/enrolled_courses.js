// File: amd/src/enrolled_courses.js
define(['core/ajax'], function(ajax) {
    return {
        getCourses: function() {
            return ajax.call([{
                methodname: 'moodlechatbot_get_enrolled_courses',
                args: {}
            }])[0].then(response => {
                return response.courses || [];
            }).catch(error => {
                console.error('Error fetching enrolled courses:', error);
                throw error;
            });
        }
    };
});

