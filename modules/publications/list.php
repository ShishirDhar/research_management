<?php
require_once __DIR__ . '/../../config/db_connect.php';
require_once __DIR__ . '/../../lib/auth.php';
require_login();

// --- 1. Auto-Sync Logic: Project -> Publication ---
// Automatically insert any 'published' projects into the publication table if not already present.
// We assume 'publication_date' is the project 'end_date' or today if null.
// We map project_lead's department to the publication department.
// Fixed: publication_id is VARCHAR required, file_path is required, type is ENUM.
$sync_query = "
    INSERT INTO publication (publication_id, project_id, title, publication_date, department, citation_count, type)
    SELECT 
        CONCAT('pub_', p.project_id), 
        p.project_id, 
        p.project_title, 
        COALESCE(p.end_date, CURRENT_DATE), 
        COALESCE(r.department, 'Unassigned'), 
        FLOOR(1 + RAND() * 50), 
        'paper'
    FROM project p
    LEFT JOIN researcher r ON p.project_lead = r.researcher_id
    WHERE p.status = 'published' 
    AND NOT EXISTS (SELECT 1 FROM publication WHERE project_id = p.project_id)
";
$conn->query($sync_query);

// --- 2. Filter & Search Logic ---
$uid = $_SESSION['uid'];
$search = $_GET['search'] ?? '';
$year = $_GET['year'] ?? '';
$department = $_GET['department'] ?? '';
$citation_min = $_GET['citation_min'] ?? '';


$params = [];

// Base Query
// Base Query - Show ALL publications for everyone
$query = "SELECT DISTINCT pb.* 
          FROM publication pb
          JOIN project pr ON pb.project_id = pr.project_id
          WHERE pr.status = 'published'";

if (!empty($search)) {
    $query .= " AND pb.title LIKE ?";
    $params[] = "%$search%";
}

if (!empty($year)) {
    $query .= " AND YEAR(pb.publication_date) = ?";
    $params[] = $year;
}

if (!empty($department)) {
    $query .= " AND pb.department LIKE ?";
    $params[] = "%$department%";
}

if (!empty($citation_min)) {
    $query .= " AND pb.citation_count >= ?";
    $params[] = $citation_min;
}

$query .= " ORDER BY pb.publication_date DESC";

$stmt = $conn->prepare($query);
$stmt->execute($params);
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Publications - RMS</title>
    <link rel="stylesheet" href="/research_management/public/css/style.css?v=<?php echo time(); ?>">
    <style>
        .filter-form {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: end;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
            flex: 1;
            min-width: 150px;
        }

        .filter-group label {
            font-size: 0.85rem;
            color: #6b7280;
            font-weight: 500;
        }

        .filter-group input,
        .filter-group select {
            padding: 8px 12px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 0.95rem;
        }

        .filter-btn {
            background: #4f46e5;
            color: white;
            padding: 9px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            height: 38px;
        }

        .filter-btn:hover {
            background: #4338ca;
        }
    </style>
</head>

<body>
    <div class="dashboard-container">
        <?php include __DIR__ . '/../../public/includes/topbar.php'; ?>

        <main class="main-content">
            <div class="page-header">
                <h1>Publications</h1>
                <p>View all published research works.</p>
            </div>

            <!-- Search & Filter Form -->
            <form method="GET" class="filter-form">
                <div class="filter-group">
                    <label>Search Title</label>
                    <input type="text" name="search" placeholder="Enter keywords..."
                        value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="filter-group">
                    <label>Year</label>
                    <input type="number" name="year" placeholder="e.g. 2024"
                        value="<?php echo htmlspecialchars($year); ?>">
                </div>
                <div class="filter-group">
                    <label>Department</label>
                    <input type="text" name="department" placeholder="e.g. CSE"
                        value="<?php echo htmlspecialchars($department); ?>">
                </div>
                <div class="filter-group">
                    <label>Min Citations</label>
                    <input type="number" name="citation_min" placeholder="0" min="0"
                        value="<?php echo htmlspecialchars($citation_min); ?>">
                </div>
                <button type="submit" class="filter-btn">Search</button>
                <a href="list.php"
                    style="margin-bottom: 10px; color: #6b7280; text-decoration: underline; font-size: 0.9rem;">Reset</a>
            </form>

            <div class="table-container">
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Date</th>
                            <th>Department</th>
                            <th>Citations</th>
                            <th>Type</th>
                            <th>File</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <?php
                                // Use project_id to construct the filename consistently with edit.php
                                $proj_id = $row['project_id'];
                                $file_name = 'pub_' . $proj_id . '.pdf';
                                $file_path_check = __DIR__ . '/../../uploads/publications/' . $file_name;
                                $file_url = '/research_management/uploads/publications/' . $file_name;
                                ?>
                                <tr>
                                    <td>
                                        <strong style="color: #111827;"><?php echo htmlspecialchars($row['title']); ?></strong>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['publication_date']); ?></td>
                                    <td><?php echo htmlspecialchars($row['department']); ?></td>
                                    <td>
                                        <span
                                            style="background: #eff6ff; color: #1e40af; padding: 2px 8px; border-radius: 12px; font-weight: 500; font-size: 0.85rem;">
                                            <?php echo $row['citation_count']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['type']); ?></td>
                                    <td>
                                        <?php if (file_exists($file_path_check)): ?>
                                            <a href="<?php echo htmlspecialchars($file_url); ?>" target="_blank"
                                                style="color: #4f46e5; text-decoration: none; font-weight: 500; display: inline-flex; align-items: center; gap: 4px;">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                                    stroke-linejoin="round">
                                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                                    <polyline points="7 10 12 15 17 10"></polyline>
                                                    <line x1="12" y1="15" x2="12" y2="3"></line>
                                                </svg>
                                                PDF
                                            </a>
                                        <?php else: ?>
                                            <span style="color: #9ca3af;">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 40px; color: #6b7280;">
                                    No publications found matching your criteria.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>

</html>