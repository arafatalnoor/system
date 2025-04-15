// external.js

window.onload = function() {
    // Use the variables passed from PHP
    if (phpName && phpMatric) {
        console.log("User Name: " + phpName);
        console.log("Matric Number: " + phpMatric);

        // You can also manipulate the DOM dynamically with these values
        document.querySelector('h1').innerText = 'Welcome, ' + phpName + '!';
        document.querySelector('p').innerText = 'Your Matric Number: ' + phpMatric;
    }
};


// Simulate an API call to retrieve user data
function fetchUserData() {
    // Simulated user data (normally this would come from a backend/database)
    const userData = {
        userName: "JohnDoe",  // This would be retrieved from the database
        messagesCount: 5,     // This too
        friendRequestsCount: 3
    };

    // Now, update the HTML with the user data
    document.getElementById('user-name').innerText = userData.userName;
    document.getElementById('user-name-card').innerText = userData.userName;
    document.getElementById('messages-count').innerText = userData.messagesCount;
    document.getElementById('friend-requests-count').innerText = userData.friendRequestsCount;
}

// Call the function when the page is loaded
window.onload = fetchUserData;
