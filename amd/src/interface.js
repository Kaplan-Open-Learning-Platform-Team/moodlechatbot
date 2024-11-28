define(['core/log'], function(log) {
    return {
        init: function() {
            console.log('interface.js initialized');
            // Event listener for Ctrl + Alt + Spacebar
            document.addEventListener('keydown', function(event) {
                console.log('keydown event triggered');
                // Check for Ctrl + Alt + Spacebar key combination
                if (event.ctrlKey && event.altKey && event.code === 'Space') {
                    console.log('Ctrl + Alt + Spacebar key combination detected');
                    // Prevent default spacebar behavior
                    event.preventDefault();

                    // Display hello world alert
                    alert('Hello World!');
                }
            });
        }
    };
});
