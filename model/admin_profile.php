<?php
//session pik fron login

include '../db/db.php';
 // Replace with your database connection file
 include '../menu/adminmenu.php'; 
// Assume admin is logged in and session contains admin ID
$adminId = $_SESSION['user_id'];

// Fetch admin details from the database
$query = "SELECT * FROM admins WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $adminId);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();

// Handle form submission to update admin details
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $role = $_POST['role'];

    // Handle profile picture upload
if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === 0) {
    $targetDir = "uploads/";
    // Create uploads directory if it doesn't exist
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }
    $fileName = time() . '_' . basename($_FILES['profile_picture']['name']);
    $targetFilePath = $targetDir . $fileName;

    // Validate image type
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    if (in_array($_FILES['profile_picture']['type'], $allowedTypes)) {
        // Move uploaded file
        if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $targetFilePath)) {
            $profilePicture = $targetFilePath;
        } else {
            $profilePicture = $admin['profile_picture'];
            $uploadError = "Error uploading the file.";
        }
    } else {
        $uploadError = "Invalid file type. Only JPG, PNG, and GIF are allowed.";
    }
} else {
    $profilePicture = $admin['profile_picture'];
}


    // Update admin details in the database
    $updateQuery = "UPDATE admins SET name = ?, role = ?, profile_picture = ? WHERE id = ?";
    $updateStmt = $conn->prepare($updateQuery);
    $updateStmt->bind_param("sssi", $name, $role, $profilePicture, $adminId);
    $updateStmt->execute();

    // Refresh the page with updated data
    header("Location: ../model/admin_profile.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --dark-bg:rgb(255, 255, 255);
            --dark-card: #1E1E1E;
            --neon-cyan: #00ffea;
            --neon-magenta: #ff00ff;
            --text-primary: #ffffff;
            --text-secondary: #aaaaaa;
        }

        body {
            background-color: var(--dark-bg);
            color: var(--text-primary);
            font-family: 'Inter', 'Poppins', sans-serif;
            line-height: 1.6;
        }

        .profile-wrapper {
            display: flex;
            justify-content: center;
            align-items: left;
            min-height: 100vh;
            padding: 2rem;
            margin-left:15%;
        }

        .profile-container {
            background-color: var(--dark-card);
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.7);
            padding: 2.5rem;
            width: 100%;
            max-width: 550px;
            border: 2px solid transparent;
            transition: all 0.3s ease;
        }

        .profile-container:hover {
            border-color: var(--neon-cyan);
        }

        .profile-picture-container {
            position: relative;
            width: 180px;
            height: 180px;
            margin: 0 auto 2rem;
            border-radius: 50%;
            overflow: hidden;
            border: 4px solid var(--neon-cyan);
            transition: all 0.3s ease;
        }

        .profile-picture-container:hover {
            transform: scale(1.05);
            box-shadow: 0 0 20px rgba(0, 255, 234, 0.5);
        }

        .profile-picture {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .profile-picture-container .overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(0, 0, 0, 0.7);
            overflow: hidden;
            width: 100%;
            height: 0;
            transition: 0.5s ease;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .profile-picture-container:hover .overlay {
            height: 30%;
        }

        .overlay-text {
            color: var(--text-primary);
            font-size: 0.9rem;
            text-align: center;
        }

        .form-control {
            background-color: #2a2a2a;
            border-color: #444;
            color: var(--text-primary);
            transition: all 0.3s ease;
        }

        .form-control:focus {
            background-color: #333;
            border-color: var(--neon-cyan);
            box-shadow: 0 0 10px rgba(0, 255, 234, 0.3);
            color: var(--text-primary);
        }

        .form-control[readonly] {
            background-color: #222;
            color: var(--text-secondary);
            cursor: not-allowed;
        }

        .btn-save {
            background: linear-gradient(135deg, var(--neon-cyan), var(--neon-magenta));
            color: var(--dark-bg);
            border: none;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            letter-spacing: 1px;
            text-transform: uppercase;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .btn-save:hover {
            transform: translateY(-3px);
            box-shadow: 0 7px 14px rgba(0, 0, 0, 0.4);
        }

        .btn-save::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(120deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: all 0.3s ease;
        }

        .btn-save:hover::before {
            left: 100%;
        }

        .form-label {
            color: var(--neon-cyan);
            font-weight: 500;
        }

        @media (max-width: 576px) {
            .profile-container {
                padding: 1.5rem;
            }

            .profile-picture-container {
                width: 150px;
                height: 150px;
            }
        }
        h1{
            color:black;
            margin-top:2%;
            font-weight: 600;
        }
    </style>
</head>
<body>

    <div class="profile-wrapper">
        <div class="profile-container text-center">
            <div class="profile-picture-container">
                <img id="profileImage" src="<?= htmlspecialchars($admin['profile_picture'] ?? 'https://via.placeholder.com/180') ?>" alt="Profile Picture" class="profile-picture">
                <div class="overlay">
                    <label for="uploadPicture" class="overlay-text">Change Picture</label>
                </div>
                <input type="file" id="uploadPicture" name="profile_picture" accept="image/*" form="profileForm" style="display: none;" onchange="previewImage(event)">
            </div>

            <form id="profileForm" method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="name" class="form-label">Name</label>
                    <input type="text" id="name" name="name" class="form-control" value="<?= htmlspecialchars($admin['name']) ?>">
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="text" id="email" class="form-control" value="<?= htmlspecialchars($admin['email']) ?>" readonly>
                </div>
                <div class="mb-3">
                    <label for="phone" class="form-label">Phone</label>
                    <input type="text" id="phone" class="form-control" value="<?= htmlspecialchars($admin['phone']) ?>" readonly>
                </div>
                <div class="mb-3">
                    <label for="role" class="form-label">Role</label>
                    <input type="text" id="role" name="role" class="form-control" value="<?= htmlspecialchars($admin['role']) ?>"readonly>
                </div>
                
                <button type="submit" class="btn btn-save mt-3">Save Changes</button>
            </form>
        </div>
    </div>

    <script>
        function previewImage(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('profileImage').src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        }
    </script>
</body>
</html>