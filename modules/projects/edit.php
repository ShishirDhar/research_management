<?php
require_once __DIR__ . '/../../config/db_connect.php';
require_once __DIR__ . '/../../lib/auth.php';

require_login();

// Access Control: Admin and Faculty Only
if ($_SESSION['user_type'] == 'student') {
    header("Location: /research_management/public/dashboard_student.php");
    exit();
}

$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: list.php");
    exit();
}

$error = '';
$success = '';
if (isset($_GET['msg']) && $_GET['msg'] == 'updated') {
    $success = "Project updated successfully!";
}

// Fetch existing project
$stmt = $conn->prepare("SELECT * FROM project WHERE project_id = ?");
$stmt->bind_param("s", $id);
$stmt->execute();
$project = $stmt->get_result()->fetch_assoc();

if (!$project) {
    die("Project not found.");
}

// Fetch Researchers for Dropdown
$researchers_query = "SELECT researcher_id, f_name, l_name FROM researcher";
$researchers_result = $conn->query($researchers_query);
$researchers = [];
while ($row = $researchers_result->fetch_assoc()) {
    $researchers[] = $row;
}

// Fetch Existing Team Members
$team_query = "SELECT researcher_id, role FROM researcher_project WHERE project_id = ?";
$stmt_team = $conn->prepare($team_query);
$stmt_team->bind_param("s", $id);
$stmt_team->execute();
$team_result = $stmt_team->get_result();
$existing_team_members = [];
while ($row = $team_result->fetch_assoc()) {
    $existing_team_members[] = $row;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $project_title = $_POST['project_title'];
    $project_lead = $_POST['project_lead'];
    $status = $_POST['status'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];

    if ($start_date > $end_date) {
        $error = "Start date cannot be after end date.";
    } else {
        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare("UPDATE project SET project_title=?, project_lead=?, status=?, start_date=?, end_date=? WHERE project_id=?");
            $stmt->bind_param("ssssss", $project_title, $project_lead, $status, $start_date, $end_date, $id);

            if (!$stmt->execute()) {
                throw new Exception("Error updating project: " . $stmt->error);
            }

            // Sync Team Members: Delete all existing and re-insert
            $stmt_del = $conn->prepare("DELETE FROM researcher_project WHERE project_id = ?");
            $stmt_del->bind_param("s", $id);
            if (!$stmt_del->execute()) {
                throw new Exception("Error clearing team members: " . $stmt_del->error);
            }

            if (isset($_POST['team_members']) && isset($_POST['roles'])) {
                $team_members = $_POST['team_members'];
                $roles = $_POST['roles'];
                
                $stmt_ins = $conn->prepare("INSERT INTO researcher_project (researcher_id, project_id, role) VALUES (?, ?, ?)");
                
                for ($i = 0; $i < count($team_members); $i++) {
                    $r_id = $team_members[$i];
                    $role = $roles[$i];
                    
                    if (!empty($r_id) && !empty($role)) {
                        $stmt_ins->bind_param("sss", $r_id, $id, $role);
                        if (!$stmt_ins->execute()) {
                            throw new Exception("Error adding team member: " . $stmt_ins->error);
                        }
                    }
                }
            }

            $conn->commit();
            header("Location: edit.php?id=$id&msg=updated");
            exit();
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
    <title>Edit Project</title>
    <script>
        function addTeamMember(rId = '', role = '') {
            const container = document.getElementById('team-members-container');
            
            const div = document.createElement('div');
            div.style.marginBottom = '10px';
            
            let html = '<select name="team_members[]" required>';
            html += '<option value="">Select Researcher</option>';
            <?php foreach ($researchers as $r): ?>
                var selected = (rId == '<?php echo $r['researcher_id']; ?>') ? 'selected' : '';
                html += '<option value="<?php echo htmlspecialchars($r['researcher_id'], ENT_QUOTES); ?>" ' + selected + '>';
                html += '<?php echo htmlspecialchars($r['f_name'] . ' ' . $r['l_name'], ENT_QUOTES); ?> (<?php echo $r['researcher_id']; ?>)';
                html += '</option>';
            <?php endforeach; ?>
            html += '</select> ';
            
            html += '<input type="text" name="roles[]" value="' + role + '" placeholder="Role (e.g. Co-PI)" required> ';
            html += '<button type="button" onclick="this.parentNode.remove()">Remove</button>';
            
            div.innerHTML = html;
            container.appendChild(div);
        }
    </script>
</head>

<body>
    <h1>Edit Project</h1>
    <?php if ($error)
        echo "<p style='color:red'>$error</p>"; ?>
    <?php if ($success)
        echo "<p style='color:green'>$success</p>"; ?>

    <form action="edit.php?id=<?php echo $id; ?>" method="POST">
        <label>Project Title:</label> <input type="text" name="project_title"
            value="<?php echo htmlspecialchars($project['project_title']); ?>" required><br><br>

        <label>Project Lead:</label>
        <select name="project_lead" required>
            <option value="">Select Researcher</option>
            <?php foreach ($researchers as $r): ?>
                <option value="<?php echo htmlspecialchars($r['researcher_id']); ?>"
                    <?php if ($r['researcher_id'] == $project['project_lead']) echo 'selected'; ?>>
                    <?php echo htmlspecialchars($r['f_name'] . ' ' . $r['l_name']); ?> (<?php echo $r['researcher_id']; ?>)
                </option>
            <?php endforeach; ?>
        </select><br><br>

        <label>Status:</label>
        <select name="status" required>
            <option value="ongoing" <?php if ($project['status'] == 'ongoing')
                echo 'selected'; ?>>Ongoing</option>
            <option value="completed" <?php if ($project['status'] == 'completed')
                echo 'selected'; ?>>Completed</option>
            <option value="published" <?php if ($project['status'] == 'published')
                echo 'selected'; ?>>Published</option>
        </select><br><br>

        <label>Start Date:</label> <input type="date" name="start_date"
            value="<?php echo htmlspecialchars($project['start_date']); ?>" required><br><br>
        <label>End Date:</label> <input type="date" name="end_date"
            value="<?php echo htmlspecialchars($project['end_date']); ?>" required><br><br>

        <h3>Team Members</h3>
        <div id="team-members-container">
            <!-- Dynamic rows will be added here -->
        </div>
        <button type="button" onclick="addTeamMember()">Add Team Member</button>
        <br><br>
        
        <script>
            // Pre-fill existing team members
            <?php foreach ($existing_team_members as $tm): ?>
                addTeamMember('<?php echo $tm['researcher_id']; ?>', '<?php echo htmlspecialchars($tm['role'], ENT_QUOTES); ?>');
            <?php endforeach; ?>
        </script>

        <button type="submit">Update Project</button>
    </form>
    <br>
    <a href="list.php">Back to List</a>
</body>

</html>