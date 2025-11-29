<?php
require_once __DIR__ . '/../../config/db_connect.php';
require_once __DIR__ . '/../../lib/auth.php';

require_login();

// Access Control: Admin and Faculty Only
if ($_SESSION['user_type'] == 'student') {
    header("Location: /research_management/public/dashboard_student.php");
    exit();
}

$error = '';
$success = '';

// Fetch Researchers for Dropdown
$researchers_query = "SELECT researcher_id, f_name, l_name FROM researcher";
$researchers_result = $conn->query($researchers_query);
$researchers = [];
while ($row = $researchers_result->fetch_assoc()) {
    $researchers[] = $row;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $project_title = $_POST['project_title'];
    $project_lead = $_POST['project_lead'];
    $status = $_POST['status'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];

    // Validate dates
    if ($start_date > $end_date) {
        $error = "Start date cannot be after end date.";
    } else {
        $conn->begin_transaction();
        try {
            // Generate Custom ID
            $id_query = "SELECT project_id FROM project 
                         ORDER BY CAST(SUBSTRING(project_id, 2) AS UNSIGNED) DESC LIMIT 1";
            $id_result = $conn->query($id_query);

            if ($id_result->num_rows > 0) {
                $row = $id_result->fetch_assoc();
                $last_id = $row['project_id'];
                $number = intval(substr($last_id, 1));
                $project_id = "p" . ($number + 1);
            } else {
                $project_id = "p1";
            }

            $stmt = $conn->prepare("INSERT INTO project (project_id, project_title, project_lead, status, start_date, end_date) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssss", $project_id, $project_title, $project_lead, $status, $start_date, $end_date);

            if (!$stmt->execute()) {
                throw new Exception("Error adding project: " . $stmt->error);
            }

            // Handle Team Members
            if (isset($_POST['team_members']) && isset($_POST['roles'])) {
                $team_members = $_POST['team_members'];
                $roles = $_POST['roles'];

                $stmt_team = $conn->prepare("INSERT INTO researcher_project (researcher_id, project_id, role) VALUES (?, ?, ?)");

                for ($i = 0; $i < count($team_members); $i++) {
                    $r_id = $team_members[$i];
                    $role = $roles[$i];

                    if (!empty($r_id) && !empty($role)) {
                        $stmt_team->bind_param("sss", $r_id, $project_id, $role);
                        if (!$stmt_team->execute()) {
                            throw new Exception("Error adding team member: " . $stmt_team->error);
                        }
                    }
                }
            }

            $conn->commit();
            $success = "Project added successfully!";

        } catch (Exception $e) {
            $conn->rollback();
            $error = $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Project</title>
    <script>
        function addTeamMember() {
            const container = document.getElementById('team-members-container');
            const index = container.children.length;

            const div = document.createElement('div');
            div.style.marginBottom = '10px';

            let html = '<select name="team_members[]" required>';
            html += '<option value="">Select Researcher</option>';
            <?php foreach ($researchers as $r): ?>
                html += '<option value="<?php echo htmlspecialchars($r['researcher_id']); ?>">';
                html += '<?php echo htmlspecialchars($r['f_name'] . ' ' . $r['l_name']); ?> (<?php echo $r['researcher_id']; ?>)';
                html += '</option>';
            <?php endforeach; ?>
            html += '</select> ';

            html += '<input type="text" name="roles[]" placeholder="Role (e.g. Co-PI)" required> ';
            html += '<button type="button" onclick="this.parentNode.remove()">Remove</button>';

            div.innerHTML = html;
            container.appendChild(div);
        }
    </script>
</head>

<body>
    <h1>Add New Project</h1>
    <?php if ($error)
        echo "<p style='color:red'>$error</p>"; ?>
    <?php if ($success)
        echo "<p style='color:green'>$success</p>"; ?>

    <form action="add.php" method="POST">
        <label>Project Title:</label> <input type="text" name="project_title" required><br><br>

        <label>Project Lead:</label>
        <select name="project_lead" required>
            <option value="">Select Researcher</option>
            <?php foreach ($researchers as $r): ?>
                <option value="<?php echo htmlspecialchars($r['researcher_id']); ?>">
                    <?php echo htmlspecialchars($r['f_name'] . ' ' . $r['l_name']); ?> (<?php echo $r['researcher_id']; ?>)
                </option>
            <?php endforeach; ?>
        </select><br><br>

        <label>Status:</label>
        <select name="status" required>
            <option value="ongoing">Ongoing</option>
            <option value="completed">Completed</option>
            <option value="published">Published</option>
        </select><br><br>

        <label>Start Date:</label> <input type="date" name="start_date" required><br><br>
        <label>End Date:</label> <input type="date" name="end_date" required><br><br>

        <h3>Team Members</h3>
        <div id="team-members-container">
            <!-- Dynamic rows will be added here -->
        </div>
        <button type="button" onclick="addTeamMember()">Add Team Member</button>
        <br><br>

        <button type="submit">Add Project</button>
    </form>
    <br>
    <a href="list.php">Back to List</a>
</body>

</html>