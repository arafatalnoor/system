// Toggles the visibility of the reason input field
function toggleReasonInput(button) {
    const reasonContainer = button.nextElementSibling;
    if (reasonContainer.style.display === "none") {
        reasonContainer.style.display = "block";
    } else {
        reasonContainer.style.display = "none";
    }
}

// Sends the refusal reason
function sendReason(reasonId) {
    const reasonTextarea = document.getElementById(reasonId);
    const reason = reasonTextarea.value.trim(); // Get the input value
    const finalReason = reason || "No reason provided."; // Default to "No reason provided" if empty
    
    // Simulate sending the reason (e.g., via an API call)
    console.log(`Reason for refusal: ${finalReason}`);
    
    // Provide feedback and hide the input
    alert("Response sent: " + finalReason);
    reasonTextarea.closest(".refuse-reason").style.display = "none";
}

function saveAvailability() {
    // Get all checked checkboxes
    const checkboxes = document.querySelectorAll("#availability-form input[type='checkbox']:checked");
    const availability = {};

    checkboxes.forEach((checkbox) => {
        const [day, time] = checkbox.name.split("-");
        if (!availability[day]) {
            availability[day] = [];
        }
        availability[day].push(time);
    });

    // Simulate saving the availability (e.g., send it to a server)
    console.log("Availability saved:", availability);
    alert("Your availability has been saved successfully!");

    // Optionally, clear the form after saving
    // document.getElementById('availability-form').reset();
}
