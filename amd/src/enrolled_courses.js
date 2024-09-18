define(['core/ajax'], function(Ajax) {
    return {
        getCourses: function() {
            var request = Ajax.call([{
                methodname: 'yourpluginname_get_enrolled_courses',  // Call the web service.
                args: {}
            }]);

            request[0].done(function(response) {
                var courses = response;
                var message = 'You are enrolled in the following courses:\n';
                courses.forEach(function(course) {
                    message += '- ' + course.fullname + '\n';
                });
                // console.log(message);
                // The chatbot can then display this message in the interface.
            }).fail(function(error) {
                // console.error("Error fetching enrolled courses: ", error);
            });
        }
    };
});
