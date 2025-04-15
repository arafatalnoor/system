// Add event listener to the message div
    document.getElementById('update-settings').addEventListener('click', function() {
        // Data to send
        var matric = '12345';  // Replace with dynamic user matric (e.g., from session)
        var title = 'Update your settings';
        var message = 'Check your settings and make sure everything is up-to-date.';

        // Create a new FormData object to send the data
        var formData = new FormData();
        formData.append('matric', matric);
        formData.append('title', title);
        formData.append('message', message);

        // Create an XMLHttpRequest to send the data via POST
        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'save_user.php', true);
        xhr.onload = function() {
            if (xhr.status === 200) {
                alert('Notification saved successfully!');
            } else {
                alert('Error: ' + xhr.status);
            }
        };
        xhr.send(formData);
    });

