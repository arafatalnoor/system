function toggleReasonInput(button) {
    const reasonDiv = button.nextElementSibling;
    reasonDiv.style.display = reasonDiv.style.display === 'none' ? 'block' : 'none';
}

document.getElementById('availability-form').addEventListener('submit', function(e) {
    const checkboxes = this.querySelectorAll('input[type="checkbox"]:checked');
    if (checkboxes.length === 0) {
        e.preventDefault();
        alert('Please select at least one time slot.');
        return;
    }
    
    // Show saving feedback
    const button = this.querySelector('button[type="submit"]');
    const originalText = button.textContent;
    button.textContent = 'Saving...';
    button.disabled = true;
    
    // Re-enable button after submission
    setTimeout(() => {
        button.textContent = originalText;
        button.disabled = false;
    }, 1000);
});

// Add animation for save status message
if (document.querySelector('#timetable p')) {
    const message = document.querySelector('#timetable p');
    message.style.animation = 'fadeOut 3s forwards';
    setTimeout(() => {
        message.remove();
    }, 3000);
}

// Add this CSS for the animation
const style = document.createElement('style');
style.textContent = `
    @keyframes fadeOut {
        0% { opacity: 1; }
        70% { opacity: 1; }
        100% { opacity: 0; }
    }
`;
document.head.appendChild(style);