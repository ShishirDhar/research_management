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

            // 1. Insert Project (without publication_type column)
            $stmt = $conn->prepare("INSERT INTO project (project_id, project_title, project_lead, status, start_date, end_date) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssss", $project_id, $project_title, $project_lead, $status, $start_date, $end_date);

            if (!$stmt->execute()) {
                throw new Exception("Error adding project: " . $stmt->error);
            }

            // 2. Handle Publication if Published
            if ($status == 'published') {
                $pub_type = $_POST['publication_type'] ?? 'paper';
                $pub_id = 'pub_' . $project_id;

                // Fetch department from lead researcher
                $dept_query = "SELECT department FROM researcher WHERE researcher_id = ?";
                $stmt_dept = $conn->prepare($dept_query);
                $stmt_dept->bind_param("s", $project_lead);
                $stmt_dept->execute();
                $res_dept = $stmt_dept->get_result();
                $dept_row = $res_dept->fetch_assoc();
                $department = $dept_row['department'] ?? 'Unassigned';

                // Insert into Publication
                // Use project_id as publication_id or derived one. The schema requires publication_id.
                // Assuming end_date as publication date
                $citations = rand(1, 50);
                $stmt_pub = $conn->prepare("INSERT INTO publication (publication_id, project_id, title, publication_date, department, citation_count, type) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt_pub->bind_param("sssssis", $pub_id, $project_id, $project_title, $end_date, $department, $citations, $pub_type);

                if (!$stmt_pub->execute()) {
                    throw new Exception("Error creating publication record: " . $stmt_pub->error);
                }
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

            // Handle Collaboration
            if (isset($_POST['has_collaboration']) && $_POST['has_collaboration'] == '1') {
                $col_type = $_POST['collaboration_type'];
                $country = $_POST['country'];
                $mou_date = !empty($_POST['mou_agreement_date']) ? $_POST['mou_agreement_date'] : null;
                $mou_details = !empty($_POST['mou_agreement_details']) ? $_POST['mou_agreement_details'] : null;

                // Generate Collaboration ID
                $col_id = 'col_' . uniqid();

                // Insert into Collaboration
                $stmt_col = $conn->prepare("INSERT INTO collaboration (collaboration_id, collaboration_type, country, mou_agreement_date, mou_agreement_details) VALUES (?, ?, ?, ?, ?)");
                $stmt_col->bind_param("sssss", $col_id, $col_type, $country, $mou_date, $mou_details);

                if (!$stmt_col->execute()) {
                    throw new Exception("Error adding collaboration: " . $stmt_col->error);
                }

                // Insert into Project_Collaboration
                $stmt_pc = $conn->prepare("INSERT INTO project_collaboration (project_id, collaboration_id) VALUES (?, ?)");
                $stmt_pc->bind_param("ss", $project_id, $col_id);

                if (!$stmt_pc->execute()) {
                    throw new Exception("Error linking collaboration: " . $stmt_pc->error);
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
    <title>Add New Project - RMS</title>
    <link rel="stylesheet" href="/research_management/public/css/style.css?v=<?php echo time(); ?>">
    <script>
        function addTeamMember() {
            const container = document.getElementById('team-members-container');
            const index = container.children.length;

            const div = document.createElement('div');
            div.className = 'form-group';
            div.style.display = 'flex';
            div.style.gap = '10px';
            div.style.alignItems = 'end';

            let html = '<div style="flex: 2;">';
            html += '<label style="font-size: 0.85rem; color: #6b7280; margin-bottom: 4px;">Researcher</label>';
            html += '<select name="team_members[]" required style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; background: #f9fafb;">';
            html += '<option value="">Select Researcher</option>';
            <?php foreach ($researchers as $r): ?>
                html += '<option value="<?php echo htmlspecialchars($r['researcher_id']); ?>">';
                html += '<?php echo htmlspecialchars($r['f_name'] . ' ' . $r['l_name']); ?> (<?php echo $r['researcher_id']; ?>)';
                html += '</option>';
            <?php endforeach; ?>
            html += '</select></div>';

            html += '<div style="flex: 2;">';
            html += '<label style="font-size: 0.85rem; color: #6b7280; margin-bottom: 4px;">Role</label>';
            html += '<input type="text" name="roles[]" placeholder="e.g. Co-PI" required style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; background: #f9fafb;"></div>';

            html += '<button type="button" onclick="this.parentNode.remove()" style="padding: 10px; background: #fee2e2; color: #ef4444; border: 1px solid #fecaca; border-radius: 6px; cursor: pointer;">Remove</button>';

            div.innerHTML = html;
            container.appendChild(div);
        }
    </script>
</head>

<body>
    <div class="dashboard-container">
        <?php include __DIR__ . '/../../public/includes/topbar.php'; ?>

        <main class="main-content">
            <div class="page-header">
                <h1>Add New Project</h1>
                <p>Initialize a new research project.</p>
            </div>

            <?php if ($error)
                echo "<div class='error-message'>$error</div>"; ?>
            <?php if ($success)
                echo "<div class='success-message' style='color: green; padding: 10px; background: #ecfdf5; border-radius: 6px; margin-bottom: 20px;'>$success</div>"; ?>

            <div class="form-container">
                <form action="add.php" method="POST" class="modern-form">
                    <div class="form-group">
                        <label>Project Title</label>
                        <input type="text" name="project_title" required>
                    </div>

                    <div class="form-group">
                        <label>Project Lead</label>
                        <select name="project_lead" required>
                            <option value="">Select Researcher</option>
                            <?php foreach ($researchers as $r): ?>
                                <option value="<?php echo htmlspecialchars($r['researcher_id']); ?>">
                                    <?php echo htmlspecialchars($r['f_name'] . ' ' . $r['l_name']); ?>
                                    (<?php echo $r['researcher_id']; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" id="project_status" onchange="togglePublicationType()" required>
                            <option value="ongoing">Ongoing</option>
                            <option value="completed">Completed</option>
                            <option value="published">Published</option>
                        </select>
                    </div>

                    <div class="form-group" id="publication_type_container" style="display: none;">
                        <label>Publication Type</label>
                        <select name="publication_type" id="publication_type">
                            <option value="">Select Type</option>
                            <option value="paper">Paper</option>
                            <option value="journal">Journal</option>
                            <option value="conference">Conference</option>
                        </select>
                    </div>

                    <div style="display: flex; gap: 20px;">
                        <div class="form-group" style="flex: 1;">
                            <label>Start Date</label>
                            <input type="date" name="start_date" required>
                        </div>
                        <div class="form-group" style="flex: 1;">
                            <label>End Date</label>
                            <input type="date" name="end_date" required>
                        </div>
                    </div>

                    <script>
                        function togglePublicationType() {
                            const status = document.getElementById('project_status').value;
                            const typeContainer = document.getElementById('publication_type_container');
                            const typeSelect = document.getElementById('publication_type');

                            if (status === 'published') {
                                typeContainer.style.display = 'block';
                                typeSelect.required = true;
                            } else {
                                typeContainer.style.display = 'none';
                                typeSelect.required = false;
                                typeSelect.value = '';
                            }
                        }
                    </script>

                    <div style="margin: 30px 0; padding-top: 20px; border-top: 1px solid #e5e7eb;">
                        <div class="form-group" style="margin-bottom: 20px;">
                            <label
                                style="display: flex; align-items: center; gap: 10px; cursor: pointer; font-size: 1rem; color: #374151;">
                                <input type="checkbox" name="has_collaboration" id="has_collaboration" value="1"
                                    onchange="toggleCollaboration()" style="width: 18px; height: 18px;">
                                Is there a collaboration?
                            </label>
                        </div>

                        <div id="collaboration_details"
                            style="display: none; background: #f9fafb; padding: 20px; border-radius: 8px; border: 1px solid #e5e7eb;">
                            <h4 style="margin-top: 0; margin-bottom: 15px; color: #4b5563;">Collaboration Details</h4>

                            <div class="form-group">
                                <label>Collaboration Type</label>
                                <select name="collaboration_type" id="collaboration_type">
                                    <option value="">Select Type</option>
                                    <option value="interdepartmental">Interdepartmental</option>
                                    <option value="inter-university">Inter-university</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Country</label>
                                <input type="text" name="country" id="country" placeholder="e.g. USA, UK, India">
                            </div>

                            <div class="form-group">
                                <label>MoU Agreement Date (If any)</label>
                                <input type="date" name="mou_agreement_date">
                            </div>

                            <div class="form-group">
                                <label>MoU Agreement Details (If any)</label>
                                <textarea name="mou_agreement_details" rows="3"
                                    placeholder="Enter details..."></textarea>
                            </div>
                        </div>
                    </div>

                    <script>
                        function toggleCollaboration() {
                            const checkbox = document.getElementById('has_collaboration');
                            const details = document.getElementById('collaboration_details');
                            const type = document.getElementById('collaboration_type');
                            const country = document.getElementById('country');

                            if (checkbox.checked) {
                                details.style.display = 'block';
                                type.required = true;
                                country.required = true;
                            } else {
                                details.style.display = 'none';
                                type.required = false;
                                country.required = false;
                            }
                        }
                    </script>

                    <div style="margin: 30px 0; padding-top: 20px; border-top: 1px solid #e5e7eb;">
                        <h3 style="font-size: 1.1rem; color: #374151; margin-bottom: 15px;">Team Members</h3>
                        <div id="team-members-container">
                            <!-- Dynamic rows will be added here -->
                        </div>
                        <button type="button" onclick="addTeamMember()"
                            style="background: white; border: 1px dashed #d1d5db; color: #4f46e5; padding: 10px; width: 100%; border-radius: 6px; cursor: pointer; font-weight: 500;">+
                            Add Team Member</button>
                    </div>

                    <button type="submit" class="btn-submit">Add Project</button>
                    <a href="list.php" style="margin-left: 15px; color: #6b7280; text-decoration: none;">Cancel</a>
                </form>
            </div>
        </main>
    </div>
</body>

</html>