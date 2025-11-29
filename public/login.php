<?php
//session_start();
require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../lib/auth.php';



$error = '';
if (is_logged_in()) {
    header('Location: /research_management/public/dashboard.php');
    exit();
}

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $user_type = $_POST['user_type'];

    if ($user_type == 'admin') {

        $stmt = $conn->prepare('SELECT id,password FROM admin_user WHERE username = ?');
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();


        if ($user && $password === $user['password']) {
            $_SESSION['uid'] = $user['id'];
            $_SESSION['user_type'] = 'admin';
            header('Location: /research_management/public/dashboard.php');
            exit();
        } else {
            $error = "Invalid username or password.";
        }
    } else if ($user_type == "student" || $user_type == "faculty") {

        // First authenticate against the common researcher table
        $stmt = $conn->prepare('SELECT researcher_id, password FROM researcher WHERE email = ?');
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user && $password === $user['password']) {
            $researcher_id = $user['researcher_id'];

            // Now check if this researcher exists in the specific type table
            $type_table = ($user_type == 'student') ? 'student' : 'faculty';

            // Assuming the linking column is researcher_id in both tables
            $stmt_type = $conn->prepare("SELECT * FROM $type_table WHERE researcher_id = ?");
            $stmt_type->bind_param("s", $researcher_id);
            $stmt_type->execute();
            $result_type = $stmt_type->get_result();

            if ($result_type->num_rows > 0) {
                $_SESSION['uid'] = $researcher_id;
                $_SESSION['user_type'] = $user_type;
                if ($user_type == 'student') {
                    header('Location: /research_management/public/dashboard_student.php');
                } else {
                    header('Location: /research_management/public/dashboard_faculty.php');
                }
                exit();
            } else {
                $error = "User not found in $user_type records.";
            }
        } else {
            $error = "Invalid username or password.";
        }

    }

}
?>


<!-- HTML Form for login -->
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Research Management</title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body>
    <div class="login-container">
        <h2>Welcome Back</h2>
        <p>Please login to your account</p>

        <?php if (isset($error)): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <div class="form-group">
                <label for="username">Email / Username</label>
                <input type="text" id="username" name="username" placeholder="Enter your email or username" required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Enter your password" required>
            </div>

            <div class="form-group">
                <label>Select Role</label>
                <div class="role-selection">
                    <div class="role-option">
                        <input type="radio" id="admin" name="user_type" value="admin" checked>
                        <label for="admin">Admin</label>
                    </div>
                    <div class="role-option">
                        <input type="radio" id="student" name="user_type" value="student">
                        <label for="student">Student</label>
                    </div>
                    <div class="role-option">
                        <input type="radio" id="faculty" name="user_type" value="faculty">
                        <label for="faculty">Faculty</label>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn-login">Login</button>
        </form>
    </div>
</body>

</html>