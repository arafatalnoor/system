document.getElementById("nameForm").addEventListener("submit", function(event) {
    event.preventDefault(); // Prevent the form from submitting
    const nameInput = document.getElementById("name");
    if (nameInput.value.trim() !== "") {
        document.getElementById("selectContainer").style.display = "block";
    } else {
        alert("Please enter your name.");
    }
});

function navigateToSheet() {
    // Get the selected value from the dropdown
    const selectedValue = document.getElementById('options').value;

    // Check if a valid option is selected
    if (selectedValue) {
      // Navigate to the selected sheet
      window.location.href = selectedValue;
    } else {
      alert('Please select an option before proceeding.');
    }
  }

  const express = require('express');
const app = express();
const PORT = 3000;

app.use(express.json());

// Mock notifications database
let notifications = [
  { id: 1, userId: 101, message: 'Your appointment is confirmed.', isRead: false },
  { id: 2, userId: 102, message: 'Your appointment has been rescheduled.', isRead: false },
];

// Endpoint for user to fetch notifications
app.get('/api/notifications', (req, res) => {
  const userId = parseInt(req.query.userId, 10);
  const userNotifications = notifications.filter(n => n.userId === userId);
  res.json(userNotifications);
});

// Endpoint for admin to send a notification
app.post('/api/notifications', (req, res) => {
  const { userId, message } = req.body;
  const newNotification = {
    id: notifications.length + 1,
    userId,
    message,
    isRead: false,
  };
  notifications.push(newNotification);
  res.status(201).json({ message: 'Notification sent!', notification: newNotification });
});

// Mark notification as read
app.post('/api/notifications/read', (req, res) => {
  const { id } = req.body;
  const notification = notifications.find(n => n.id === id);
  if (notification) {
    notification.isRead = true;
    res.json({ message: 'Notification marked as read.' });
  } else {
    res.status(404).json({ message: 'Notification not found.' });
  }
});

app.listen(PORT, () => {
  console.log(`Server running on http://localhost:${PORT}`);
});


