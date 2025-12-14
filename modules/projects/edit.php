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

// Fetch Publication Type if exists (for pre-fill)
$current_pub_type = '';
if ($project['status'] == 'published') {
    $stmt_pt = $conn->prepare("SELECT type FROM publication WHERE project_id = ?");
    $stmt_pt->bind_param("s", $id);
    $stmt_pt->execute();
    $res_pt = $stmt_pt->get_result();
    if ($row_pt = $res_pt->fetch_assoc()) {
        $current_pub_type = $row_pt['type'];
    }
}

// Fetch Existing Collaboration
$collab_query = "
    SELECT c.* 
    FROM collaboration c
    JOIN project_collaboration pc ON c.collaboration_id = pc.collaboration_id
    WHERE pc.project_id = ?
";
$stmt_col = $conn->prepare($collab_query);
$stmt_col->bind_param("s", $id);
$stmt_col->execute();
$res_col = $stmt_col->get_result();
$existing_collab = $res_col->fetch_assoc();
$has_collab = $existing_collab ? true : false;
$collab_id = $existing_collab['collaboration_id'] ?? null;

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
            // 1. Update Project (without publication_type)
            $stmt = $conn->prepare("UPDATE project SET project_title=?, project_lead=?, status=?, start_date=?, end_date=? WHERE project_id=?");
            $stmt->bind_param("ssssss", $project_title, $project_lead, $status, $start_date, $end_date, $id);

            if (!$stmt->execute()) {
                throw new Exception("Error updating project: " . $stmt->error);
            }

            // 2. Handle Publication if Published
            if ($status == 'published') {
                $pub_type = $_POST['publication_type'] ?? 'paper';
                $pub_id = 'pub_' . $id;

                // Handle File Upload
                if (isset($_FILES['publication_file']) && $_FILES['publication_file']['error'] == 0) {
                    $file = $_FILES['publication_file'];
                    
                    // Validate PDF type
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mime = finfo_file($finfo, $file['tmp_name']);
                    finfo_close($finfo);
                    
                    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

                    if ($mime !== 'application/pdf' || $ext !== 'pdf') {
                         throw new Exception("Only PDF files are allowed.");
                    }

                    // Directory: Root/uploads/publications
                    $upload_dir = __DIR__ . '/../../uploads/publications/';
                    
                    if (!is_dir($upload_dir)) {
                        if (!mkdir($upload_dir, 0777, true)) {
                             throw new Exception("Failed to create upload directory.");
                        }
                    }
                    
                    $filename = $pub_id . ".pdf";
                    $destination = $upload_dir . $filename;

                    if (!move_uploaded_file($file['tmp_name'], $destination)) {
                         throw new Exception("Failed to upload file. Check permissions.");
                    }
                }

                // Check if publication exists 
                $check_pub = $conn->prepare("SELECT publication_id FROM publication WHERE project_id = ?");
                $check_pub->bind_param("s", $id);
                $check_pub->execute();
                $res_pub = $check_pub->get_result();

                if ($res_pub->num_rows > 0) {
                    // Update
                    $stmt_upd_pub = $conn->prepare("UPDATE publication SET title=?, publication_date=?, type=? WHERE project_id=?");
                    // Using end_date as publication_date for consistency matching insert logic
                    $stmt_upd_pub->bind_param("ssss", $project_title, $end_date, $pub_type, $id);
                    $stmt_upd_pub->execute();
                } else {
                    // Insert (Fetch Department First)
                    $dept_query = "SELECT department FROM researcher WHERE researcher_id = ?";
                    $stmt_dept = $conn->prepare($dept_query);
                    $stmt_dept->bind_param("s", $project_lead);
                    $stmt_dept->execute();
                    $res_dept = $stmt_dept->get_result();
                    $dept_row = $res_dept->fetch_assoc();
                    $department = $dept_row['department'] ?? 'Unassigned';

                    $citations = rand(1, 50);
                    // Insert without file_path column
                    $stmt_ins_pub = $conn->prepare("INSERT INTO publication (publication_id, project_id, title, publication_date, department, citation_count, type) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt_ins_pub->bind_param("sssssis", $pub_id, $id, $project_title, $end_date, $department, $citations, $pub_type);
                    $stmt_ins_pub->execute();
                }
            } else {
                // If status is NOT published (e.g. reverted to ongoing), remove from publication table
                // Optional: Delete file? Keeping it safe for now.
                $stmt_del_pub = $conn->prepare("DELETE FROM publication WHERE project_id = ?");
                $stmt_del_pub->bind_param("s", $id);
                $stmt_del_pub->execute();
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

            // Handle Collaboration Update
            $is_collab_checked = isset($_POST['has_collaboration']) && $_POST['has_collaboration'] == '1';

            if ($is_collab_checked) {
                // Prepare values
                $col_type = $_POST['collaboration_type'];
                $country = $_POST['country'];
                $mou_date = !empty($_POST['mou_agreement_date']) ? $_POST['mou_agreement_date'] : null;
                $mou_details = !empty($_POST['mou_agreement_details']) ? $_POST['mou_agreement_details'] : null;

                if ($collab_id) {
                    // Update Existing
                    $stmt_upd_col = $conn->prepare("UPDATE collaboration SET collaboration_type=?, country=?, mou_agreement_date=?, mou_agreement_details=? WHERE collaboration_id=?");
                    $stmt_upd_col->bind_param("sssss", $col_type, $country, $mou_date, $mou_details, $collab_id);
                    if (!$stmt_upd_col->execute()) {
                         throw new Exception("Error updating collaboration: " . $stmt_upd_col->error);
                    }
                } else {
                    // Create New
                    $new_col_id = 'col_' . uniqid();
                    $stmt_new_col = $conn->prepare("INSERT INTO collaboration (collaboration_id, collaboration_type, country, mou_agreement_date, mou_agreement_details) VALUES (?, ?, ?, ?, ?)");
                    $stmt_new_col->bind_param("sssss", $new_col_id, $col_type, $country, $mou_date, $mou_details);
                    if (!$stmt_new_col->execute()) {
                        throw new Exception("Error adding collaboration: " . $stmt_new_col->error);
                    }

                    // Link to Project
                    $stmt_link = $conn->prepare("INSERT INTO project_collaboration (project_id, collaboration_id) VALUES (?, ?)");
                    $stmt_link->bind_param("ss", $id, $new_col_id);
                    if (!$stmt_link->execute()) {
                        throw new Exception("Error linking collaboration: " . $stmt_link->error);
                    }
                }
            } else {
                // If unchecked, check if we need to remove existing
                if ($collab_id) {
                    // Delete Link
                    $stmt_del_link = $conn->prepare("DELETE FROM project_collaboration WHERE project_id = ?");
                    $stmt_del_link->bind_param("s", $id);
                    $stmt_del_link->execute();

                    // Optional: Delete the collaboration record if it's orphan?
                    // Assuming 1:1 for simplicity based on uniqid generation in add/edit
                    $stmt_del_col = $conn->prepare("DELETE FROM collaboration WHERE collaboration_id = ?");
                    $stmt_del_col->bind_param("s", $collab_id);
                    $stmt_del_col->execute();
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
    <title>Edit Project - RMS</title>
    <link rel="stylesheet" href="/research_management/public/css/style.css?v=<?php echo time(); ?>">
    <script>
        function addTeamMember(rId = '', role = '') {
            const container = document.getElementById('team-members-container');
            
            const div = document.createElement('div');
            div.className = 'form-group';
            div.style.display = 'flex';
            div.style.gap = '10px';
            div.style.marginBottom = '10px';
            div.style.alignItems = 'end';
            
            let html = '<div style="flex: 2;">';
            html += '<label style="font-size: 0.85rem; color: #6b7280; margin-bottom: 4px;">Researcher</label>';
            html += '<select name="team_members[]" required style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; background: #f9fafb;">';
            html += '<option value="">Select Researcher</option>';
            <?php foreach ($researchers as $r): ?>
                var selected = (rId == '<?php echo $r['researcher_id']; ?>') ? 'selected' : '';
                html += '<option value="<?php echo htmlspecialchars($r['researcher_id'], ENT_QUOTES); ?>" ' + selected + '>';
                html += '<?php echo htmlspecialchars($r['f_name'] . ' ' . $r['l_name'], ENT_QUOTES); ?> (<?php echo $r['researcher_id']; ?>)';
                html += '</option>';
            <?php endforeach; ?>
            html += '</select></div>';
            
            html += '<div style="flex: 2;">';
            html += '<label style="font-size: 0.85rem; color: #6b7280; margin-bottom: 4px;">Role</label>';
            html += '<input type="text" name="roles[]" value="' + role + '" placeholder="e.g. Co-PI" required style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; background: #f9fafb;"></div>';
            
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
                <h1>Edit Project</h1>
                <p>Update project details, status, and team members.</p>
            </div>

            <?php if ($error)
                echo "<div class='error-message'>$error</div>"; ?>
            <?php if ($success)
                echo "<div class='success-message' style='color: #065f46; background: #d1fae5; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #a7f3d0;'>$success</div>"; ?>

            <div class="form-container">
                <form action="edit.php?id=<?php echo $id; ?>" method="POST" class="modern-form" enctype="multipart/form-data">
                    <div class="form-group">
                        <label>Project Title</label>
                        <input type="text" name="project_title" value="<?php echo htmlspecialchars($project['project_title']); ?>" required>
                    </div>

                    <div style="display: flex; gap: 20px;">
                        <div class="form-group" style="flex: 1;">
                            <label>Project Lead</label>
                            <select name="project_lead" required>
                                <option value="">Select Researcher</option>
                                <?php foreach ($researchers as $r): ?>
                                    <option value="<?php echo htmlspecialchars($r['researcher_id']); ?>"
                                        <?php if ($r['researcher_id'] == $project['project_lead']) echo 'selected'; ?>>
                                        <?php echo htmlspecialchars($r['f_name'] . ' ' . $r['l_name']); ?> (<?php echo $r['researcher_id']; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group" style="flex: 1;">
                            <label>Status</label>
                            <select name="status" id="project_status" onchange="togglePublicationType()" required>
                                <option value="ongoing" <?php if ($project['status'] == 'ongoing') echo 'selected'; ?>>Ongoing</option>
                                <option value="completed" <?php if ($project['status'] == 'completed') echo 'selected'; ?>>Completed</option>
                                <option value="published" <?php if ($project['status'] == 'published') echo 'selected'; ?>>Published</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group" id="publication_type_container" style="display: <?php echo ($project['status'] == 'published') ? 'block' : 'none'; ?>;">
                        <label>Publication Type</label>
                        <select name="publication_type" id="publication_type" <?php echo ($project['status'] == 'published') ? 'required' : ''; ?>>
                            <option value="">Select Type</option>
                            <option value="paper" <?php if ($current_pub_type == 'paper') echo 'selected'; ?>>Paper</option>
                            <option value="journal" <?php if ($current_pub_type == 'journal') echo 'selected'; ?>>Journal</option>
                            <option value="conference" <?php if ($current_pub_type == 'conference') echo 'selected'; ?>>Conference</option>
                        </select>
                        
                        <div style="margin-top: 15px;">
                            <label>Upload Publication PDF</label>
                            <input type="file" name="publication_file" id="publication_file" accept=".pdf" style="padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; width: 100%;">
                            <small style="color: #6b7280; display: block; margin-top: 4px;">Only PDF files allowed. Will be saved as <?php echo 'pub_' . $id; ?>.pdf</small>
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
                                if (!typeSelect.value) typeSelect.value = ''; // Don't clear if user is just switching back and forth, but require for published
                            }
                        }
                    </script>

                    <div style="display: flex; gap: 20px;">
                        <div class="form-group" style="flex: 1;">
                            <label>Start Date</label>
                            <input type="date" name="start_date" value="<?php echo htmlspecialchars($project['start_date']); ?>" required>
                        </div>
                        <div class="form-group" style="flex: 1;">
                            <label>End Date</label>
                            <input type="date" name="end_date" value="<?php echo htmlspecialchars($project['end_date']); ?>" required>
                        </div>
                    </div>

                    <div style="margin: 30px 0; padding-top: 20px; border-top: 1px solid #e5e7eb;">
                        <div class="form-group" style="margin-bottom: 20px;">
                            <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; font-size: 1rem; color: #374151;">
                                <input type="checkbox" name="has_collaboration" id="has_collaboration" value="1" onchange="toggleCollaboration()" style="width: 18px; height: 18px;" <?php echo $has_collab ? 'checked' : ''; ?>>
                                Is there a collaboration?
                            </label>
                        </div>

                        <div id="collaboration_details" style="display: <?php echo $has_collab ? 'block' : 'none'; ?>; background: #f9fafb; padding: 20px; border-radius: 8px; border: 1px solid #e5e7eb;">
                            <h4 style="margin-top: 0; margin-bottom: 15px; color: #4b5563;">Collaboration Details</h4>
                            
                            <div class="form-group">
                                <label>Collaboration Type</label>
                                <select name="collaboration_type" id="collaboration_type" <?php echo $has_collab ? 'required' : ''; ?>>
                                    <option value="">Select Type</option>
                                    <option value="interdepartmental" <?php if (($existing_collab['collaboration_type'] ?? '') == 'interdepartmental') echo 'selected'; ?>>Interdepartmental</option>
                                    <option value="inter-university" <?php if (($existing_collab['collaboration_type'] ?? '') == 'inter-university') echo 'selected'; ?>>Inter-university</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Country</label>
                                <input type="text" name="country" id="country" placeholder="e.g. USA, UK, India" value="<?php echo htmlspecialchars($existing_collab['country'] ?? ''); ?>" <?php echo $has_collab ? 'required' : ''; ?>>
                            </div>

                            <div class="form-group">
                                <label>MoU Agreement Date (If any)</label>
                                <input type="date" name="mou_agreement_date" value="<?php echo htmlspecialchars($existing_collab['mou_agreement_date'] ?? ''); ?>">
                            </div>

                            <div class="form-group">
                                <label>MoU Agreement Details (If any)</label>
                                <textarea name="mou_agreement_details" rows="3" placeholder="Enter details..."><?php echo htmlspecialchars($existing_collab['mou_agreement_details'] ?? ''); ?></textarea>
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

                    
                    <script>
                        // Pre-fill existing team members
                        <?php foreach ($existing_team_members as $tm): ?>
                            addTeamMember('<?php echo $tm['researcher_id']; ?>', '<?php echo htmlspecialchars($tm['role'], ENT_QUOTES); ?>');
                        <?php endforeach; ?>
                    </script>

                    <div style="margin-top: 30px;">
                        <button type="submit" class="btn-submit">Update Project</button>
                        <a href="list.php" style="margin-left: 20px; color: #6b7280; text-decoration: none; font-weight: 500;">Cancel</a>
                    </div>
                </form>
            </div>
        </main>
    </div>
</body>

</html>