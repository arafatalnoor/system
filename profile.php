<?php
session_start();

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'mydb');

// Establish database connection using mysqli
function getDBConnection() {
    $conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if (!$conn) {
        error_log("Database connection failed: " . mysqli_connect_error());
        die("Technical error occurred. Please try again later.");
    }
    mysqli_set_charset($conn, 'utf8mb4');
    return $conn;
}

// Establish database connection
$conn = getDBConnection();
// Ensure the user is logged in
if (!isset($_SESSION['matric'])) {
    header("Location: login.php");
    exit();
}

// Retrieve user details
$matric = $_SESSION['matric'];
$query = "SELECT name, picture,
          CASE WHEN email IS NULL THEN 'Not set' ELSE email END as email,
          CASE WHEN phone IS NULL THEN 'Not set' ELSE phone END as phone
          FROM users WHERE matric = ?";
$stmt = $conn->prepare($query);

if ($stmt === false) {
    die("Error preparing statement: " . $conn->error);
}

$stmt->bind_param("s", $matric);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    $name = $user['name'];
    $email = $user['email'];
    $phone = $user['phone'];
    $profileImage = !empty($user['picture']) ? $user['picture'] : 'default-profile.png';
    $_SESSION['picture'] = $profileImage;
} else {
    echo "Error: User not found.";
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $new_email = $_POST['email'];
    $new_phone = $_POST['phone'];
    
    if (!empty($new_email) && $new_email !== 'Not set' && !filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
        header("Location: profile.php");
        exit();
    }
    
    $new_email = (empty($new_email) || $new_email === 'Not set') ? NULL : $new_email;
    $new_phone = (empty($new_phone) || $new_phone === 'Not set') ? NULL : $new_phone;
    
    $update_query = "UPDATE users SET email = ?, phone = ? WHERE matric = ?";
    $update_stmt = $conn->prepare($update_query);
    
    if ($update_stmt === false) {
        header("Location: profile.php");
        exit();
    }
    
    $update_stmt->bind_param("sss", $new_email, $new_phone, $matric);
    
    if ($update_stmt->execute()) {
        $email = $new_email ?? "Not set";
        $phone = $new_phone ?? "Not set";
        
        header("Location: profile.php");
        exit();
    } else {
        header("Location: profile.php");
        exit();
}
    $update_stmt->close();
}

define('UPLOAD_DIR', 'uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024);
define('ALLOWED_TYPES', ['image/jpeg', 'image/png', 'image/gif']);

function handleFileUpload($file, $matric, $conn, $currentProfileImage) {
    try {
        if (!isset($file['error']) || is_array($file['error'])) {
            throw new RuntimeException('Invalid parameters.');
        }

        switch ($file['error']) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                throw new RuntimeException('File size exceeds limit.');
            default:
                throw new RuntimeException('Unknown error occurred.');
        }

        if ($file['size'] > MAX_FILE_SIZE) {
            throw new RuntimeException('File size exceeds limit.');
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $fileType = $finfo->file($file['tmp_name']);
        
        if (!in_array($fileType, ALLOWED_TYPES)) {
            throw new RuntimeException('Invalid file format.');
        }

        if (!file_exists(UPLOAD_DIR)) {
            if (!mkdir(UPLOAD_DIR, 0755, true)) {
                throw new RuntimeException('Failed to create upload directory.');
            }
        }

        $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $newFilename = UPLOAD_DIR . 'profile_' . $matric . '_' . uniqid() . '.' . $fileExtension;

        if (!move_uploaded_file($file['tmp_name'], $newFilename)) {
            throw new RuntimeException('Failed to move uploaded file.');
        }

        if ($currentProfileImage && $currentProfileImage !== 'default-profile.png' && file_exists($currentProfileImage)) {
            unlink($currentProfileImage);
        }

        $stmt = $conn->prepare("UPDATE users SET picture = ? WHERE matric = ?");
        $stmt->bind_param("ss", $newFilename, $matric);
        
        if (!$stmt->execute()) {
            unlink($newFilename);
            throw new RuntimeException('Failed to update database.');
        }

        $_SESSION['picture'] = $newFilename;
        return $newFilename;

    } catch (RuntimeException $e) {
        error_log("File upload error: " . $e->getMessage());
        throw $e;
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['picture'])) {
    try {
        $newProfileImage = handleFileUpload($_FILES['picture'], $matric, $conn, $profileImage);
        header("Location: profile.php");
        exit();
    } catch (RuntimeException $e) {
        echo "<script>alert('" . htmlspecialchars($e->getMessage()) . "');</script>";
    }
}

if (isset($_POST['remove_picture'])) {
    $delete_query = "UPDATE users SET picture = NULL WHERE matric = ?";
    $delete_stmt = $conn->prepare($delete_query);
    $delete_stmt->bind_param("s", $matric);
    
    if ($delete_stmt->execute()) {
        if ($profileImage !== 'default-profile.png' && file_exists($profileImage)) {
            unlink($profileImage);
        }
        
        $_SESSION['picture'] = 'default-profile.png';
        
        header("Location: profile.php");
        exit();
    }
    $delete_stmt->close();
}
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Page</title>
    <link rel="stylesheet" href="Css/profile.css">
    <style>
        .form-group {
            position: relative;
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #666;
        }

        .form-group input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }

        .form-group input::placeholder {
            color: #999;
            font-style: italic;
            opacity: 0.7;
        }

        .form-group input:focus::placeholder {
            opacity: 0.5;
        }

        .not-set {
            opacity: 0.5;
            color: #999;
            font-style: italic;
        }

        .profile-form {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .profile-form h2 {
            margin-bottom: 20px;
            color: #333;
        }

        button[type="submit"] {
            background-color: #4CAF50;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }

        button[type="submit"]:hover {
            background-color: #45a049;
        }

        #removeButton {
            background-color: #ff4444;
            margin-top: 10px;
        }

        #removeButton:hover {
            background-color: #cc0000;
        }
    </style>
</head>
<body>
    <header>
        <h1>Profile</h1>
    </header>
    <div class="dashboard-container">
        <aside class="sidebar">
            <h2>Dashboard</h2>
            <nav class="sidebar-nav">
                <ul>
                    <li><a href="dashboard.php">Home</a></li>
                    <li><a href="profile.php">Profile</a></li>
                    <li><a href="messages.php">Messages</a></li>
                    <li><a href="logout.php">Logout</a></li>
                </ul>
            </nav>
        </aside>

        <div class="main-content">
            <div class="container">
                <a href="<?php echo htmlspecialchars($profileImage); ?>" class="profile-image-link" onclick="viewImage(event, '<?php echo htmlspecialchars($profileImage); ?>')">
                    <img id="profileImage" src="<?php echo htmlspecialchars($profileImage); ?>" alt="Profile Picture" class="profile-image">
                </a>

                <div class="profile-info">
                    <h1><?php echo htmlspecialchars($name); ?></h1>
                    <p>Matric: <?php echo htmlspecialchars($matric); ?></p>
                </div>

                <div class="profile-form">
                    <h2>Update Profile Information</h2>
                    <form method="POST" action="profile.php">
                        <div class="form-group">
                            <label for="email">Email:</label>
                            <input type="email" id="email" name="email"
                                   placeholder="Not set"
                                   value="<?php echo ($email === 'Not set' ? '' : htmlspecialchars($email)); ?>">
                        </div>
                        <div class="form-group">
                            <label for="phone">Phone:</label>
                            <input type="tel" id="phone" name="phone"
                                   placeholder="Not set"
                                   minlength="10" maxlength="15" 
                                   pattern="[0-9]{10,15}"
                                   value="<?php echo ($phone === 'Not set' ? '' : htmlspecialchars($phone)); ?>">
                            <small class="error-message" id="phoneError" style="color: red; display: none;">Phone number must be between 10 and 15 digits</small>
                        </div>
                        <button type="submit" name="update_profile">Update Information</button>
                    </form>
                </div>

                <div class="profile-form">
                    <h2>Profile Picture</h2>
                    <form id="uploadForm" enctype="multipart/form-data" method="POST" action="profile.php">
                        <div class="upload-section">
                            <label for="fileUpload" class="upload-label">
                                <?php if (!empty($profileImage)): ?>
                                    <span id="label-text">Change Picture</span>
                                    <img id="preview" src="<?php echo htmlspecialchars($profileImage); ?>" alt="Uploaded Picture" style="display: none;">
                                <?php else: ?>
                                    <span id="label-text">Upload Picture</span>
                                    <img id="preview" src="" alt="" style="display: none;">
                                <?php endif; ?>
                            </label>
                            <input type="file" id="fileUpload" name="picture" accept="image/*" class="upload-input" style="display: none;">
                        </div>
                        <button type="submit" id="saveButton">Save Picture</button>
                    </form>

                    <?php if (!empty($profileImage) && $profileImage !== 'default-profile.png'): ?>
                        <form method="POST" action="profile.php">
                            <button type="submit" name="remove_picture" id="removeButton">Remove Picture</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="Css/Js/profile.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const inputs = document.querySelectorAll('.form-group input');
        const fileUpload = document.getElementById('fileUpload');
        const preview = document.getElementById('preview');
        const labelText = document.getElementById('label-text');
        const uploadForm = document.getElementById('uploadForm');
        const phoneInput = document.getElementById('phone');
        const phoneError = document.getElementById('phoneError');

        // Enhanced phone validation
        phoneInput.addEventListener('input', function(e) {
            // Remove any non-numeric characters
            let value = this.value.replace(/\D/g, '');
            
            // Limit to 15 digits
            if (value.length > 15) {
                value = value.substring(0, 15);
            }
            
            // Update the input value
            this.value = value;
            
            // Show error if length is not within bounds
            if (value.length > 0 && (value.length < 10 || value.length > 15)) {
                phoneError.style.display = 'block';
            } else {
                phoneError.style.display = 'none';
            }
        });

        // Prevent paste of invalid content
        phoneInput.addEventListener('paste', function(e) {
            e.preventDefault();
            let pastedText = (e.clipboardData || window.clipboardData).getData('text');
            pastedText = pastedText.replace(/\D/g, '');
            if (pastedText.length > 15) {
                pastedText = pastedText.substring(0, 15);
            }
            this.value = pastedText;
        });

        inputs.forEach(input => {
            input.closest('form').addEventListener('submit', function(e) {
                const inputValue = input.value.trim();
                
                // Phone specific validation
                if (input.id === 'phone' && inputValue !== '') {
                    if (inputValue.length < 10 || inputValue.length > 15) {
                        e.preventDefault();
                        phoneError.style.display = 'block';
                        return;
                    }
                }
                
                if (inputValue === '') {
                    input.value = 'Not set';
                }
            });
        });

        if (fileUpload) {
            fileUpload.addEventListener('change', function(e) {
                if (this.files && this.files[0]) {
                    const reader = new FileReader();

                    reader.onload = function(e) {
                        preview.style.display = 'block';
                        preview.src = e.target.result;
                        labelText.textContent = 'Change Picture';
                    };

                    reader.readAsDataURL(this.files[0]);
                }
            });
        }

        // Handle form submission only when save button is clicked
        if (uploadForm) {
            uploadForm.addEventListener('submit', function(e) {
                if (!fileUpload.files || !fileUpload.files[0]) {
                    e.preventDefault();
                    alert('Please select a file first');
                }
            });
        }
    });

    function viewImage(event, imagePath) {
        event.preventDefault();
        const imageWindow = window.open(imagePath, '_blank');
        if (imageWindow) {
            setTimeout(function() {
                imageWindow.close();
                window.location.href = 'profile.php';
            }, 3000);
        }
    }
    </script>
</body>
</html>
