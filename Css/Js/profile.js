document.getElementById("fileUpload").addEventListener("change", function (event) {
    const file = event.target.files[0];
    const preview = document.getElementById("preview");
    const labelText = document.getElementById("label-text");
    const uploadLabel = document.querySelector('.upload-label'); // Get the label element

    if (file) {
        const reader = new FileReader();
        reader.onload = function (e) {
            preview.src = e.target.result;
            preview.style.display = "block";
            
            // Hide the default label text and update the label text
            if (labelText) labelText.style.display = "none";
            uploadLabel.textContent = "Change Uploaded";  // Change the label text
        };
        reader.readAsDataURL(file);
    } else {
        // If no file selected, revert the label text
        if (labelText) labelText.style.display = "block";
        uploadLabel.textContent = "Change Picture";  // Reset the label text if no image
    }
});

document.addEventListener("DOMContentLoaded", function () {
    const labelText = document.getElementById('label-text');
    const previewImage = document.getElementById('preview');
    const removeButton = document.getElementById('removeButton');
    
    // If there's already an image preview, show the correct label
    if (previewImage.src && previewImage.src !== "") {
        labelText.textContent = 'Upload Picture';
    }

    // Handle file input change
    fileInput.addEventListener('change', function (event) {
        const file = event.target.files[0];
        
        // If a file is selected, update the label and preview image
        if (file) {
            const reader = new FileReader();
            
            reader.onload = function (e) {
                previewImage.src = e.target.result;
                previewImage.style.display = 'block';
                labelText.textContent = 'Change Picture'; // Change to "Change Picture" after selecting an image
            };
            
            reader.readAsDataURL(file);
        }
    });

    // Handle remove button click
    if (removeButton) {
        removeButton.addEventListener('click', function (event) {
            event.preventDefault();

            // Make an AJAX request to remove the picture (or submit the form)
            fetch('profile.php', {
                method: 'POST',
                body: new URLSearchParams({
                    'remove_picture': true
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Reset the label and hide the preview image
                    labelText.textContent = 'Upload Picture';  // Change the label to "Upload Picture"
                    previewImage.style.display = 'none'; // Hide the image preview
                    fileInput.value = ''; // Clear the file input field
                    
                    // Ensure the upload label is reset properly
                    if (labelText) labelText.style.display = "block";
                    uploadLabel.textContent = "Upload Picture";  // Ensure label text is "Upload Picture"
                }
            });
        });
    }
});

document.addEventListener('DOMContentLoaded', function() {
    // File upload preview
    const fileUpload = document.getElementById('fileUpload');
    const preview = document.getElementById('preview');
    const labelText = document.getElementById('label-text');
    const saveButton = document.getElementById('saveButton');

    fileUpload.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.style.display = 'block';
                labelText.textContent = 'Change Picture';
            }
            reader.readAsDataURL(file);
        }
    });

    // Add loading state to buttons
    document.querySelectorAll('button').forEach(button => {
        button.addEventListener('click', function() {
            if (!this.classList.contains('loading')) {
                this.classList.add('loading');
                const originalText = this.textContent;
                this.textContent = 'Processing...';
                
                // Simulate processing (remove in production)
                setTimeout(() => {
                    this.classList.remove('loading');
                    this.classList.add('success');
                    this.textContent = originalText;
                    
                    setTimeout(() => {
                        this.classList.remove('success');
                    }, 2000);
                }, 1000);
            }
        });
    });

    // Smooth scroll for navigation
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            document.querySelector(this.getAttribute('href')).scrollIntoView({
                behavior: 'smooth'
            });
        });
    });
});
