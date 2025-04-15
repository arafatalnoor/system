const express = require('express');
const app = express();
const PORT = 3000;

// Middleware to parse JSON
app.use(express.json());

// Mock database
let users = [
  { id: 1, name: 'Alice', email: 'alice@example.com' },
  { id: 2, name: 'Bob', email: 'bob@example.com' },
];

// Routes

// Get all users
app.get('/api/users', (req, res) => {
  res.json(users);
});

// Get a single user by ID
app.get('/api/users/:id', (req, res) => {
  const userId = parseInt(req.params.id, 10);
  const user = users.find(u => u.id === userId);
  if (user) {
    res.json(user);
  } else {
    res.status(404).json({ message: 'User not found' });
  }
});

// Create a new user
app.post('/api/users', (req, res) => {
  const newUser = {
    id: users.length + 1,
    name: req.body.name,
    email: req.body.email,
  };
  users.push(newUser);
  res.status(201).json(newUser);
});

// Update a user
app.put('/api/users/:id', (req, res) => {
  const userId = parseInt(req.params.id, 10);
  const user = users.find(u => u.id === userId);
  if (user) {
    user.name = req.body.name || user.name;
    user.email = req.body.email || user.email;
    res.json(user);
  } else {
    res.status(404).json({ message: 'User not found' });
  }
});

// Delete a user
app.delete('/api/users/:id', (req, res) => {
  const userId = parseInt(req.params.id, 10);
  users = users.filter(u => u.id !== userId);
  res.json({ message: 'User deleted' });
});

// Start the server
app.listen(PORT, () => {
  console.log(`Server running on http://localhost:${PORT}`);
});
