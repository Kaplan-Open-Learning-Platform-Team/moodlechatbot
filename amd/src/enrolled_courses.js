// File: amd/src/enrolled_courses.js
define(['core/ajax'], (ajax) => ({ // ES6 arrow function and object literal return

    getCourses: () => 
        ajax.call([{
            methodname: 'moodlechatbot_get_enrolled_courses',
            args: {}
        }])[0].then(response => response.courses || []) 
        .catch(error => {
            console.error('Error fetching enrolled courses:', error); // Consider using a Moodle logger here
            throw error;
        })

}));
