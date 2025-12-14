<?php
require_once __DIR__ . '/../../config/db_connect.php';
require_once __DIR__ . '/../../lib/auth.php';

require_login();

if (!isset($_GET['project_id'])) {
    die("No project selected.");
}

$id = $_GET['project_id'];
$uid = $_SESSION['uid'];

// --- 1. Access Control ---
// Verify user can view this project
if ($_SESSION['user_type'] != 'admin') {
    $check = $conn->prepare("
        SELECT 1 FROM project p
        LEFT JOIN researcher_project rp ON p.project_id = rp.project_id
        WHERE p.project_id = ? AND (p.project_lead = ? OR rp.researcher_id = ?)
    ");
    $check->bind_param("sss", $id, $uid, $uid);
    $check->execute();
    if ($check->get_result()->num_rows == 0) {
        die("Access denied.");
    }
}

// --- 2. Fetch Project Details ---
$stmt = $conn->prepare("
    SELECT p.*, r.f_name as lead_fname, r.l_name as lead_lname, r.department as lead_dept 
    FROM project p
    LEFT JOIN researcher r ON p.project_lead = r.researcher_id
    WHERE p.project_id = ?
");
$stmt->bind_param("s", $id);
$stmt->execute();
$project = $stmt->get_result()->fetch_assoc();

if (!$project)
    die("Project not found.");

// --- 3. Fetch Team Members ---
$team_query = "
    SELECT r.f_name, r.l_name, r.department, rp.role 
    FROM researcher_project rp
    JOIN researcher r ON rp.researcher_id = r.researcher_id
    WHERE rp.project_id = ?
";
$stmt = $conn->prepare($team_query);
$stmt->bind_param("s", $id);
$stmt->execute();
$team_members = $stmt->get_result();

// --- 4. Fetch Collaboration ---
$collab_query = "
    SELECT c.* 
    FROM project_collaboration pc
    JOIN collaboration c ON pc.collaboration_id = c.collaboration_id
    WHERE pc.project_id = ?
";
$stmt = $conn->prepare($collab_query);
$stmt->bind_param("s", $id);
$stmt->execute();
$collaboration = $stmt->get_result()->fetch_assoc();

// --- 5. Fetch Funding & Expenditures ---
$funding_query = "
    SELECT f.* 
    FROM project_funding pf
    JOIN funding f ON pf.funding_id = f.funding_id
    WHERE pf.project_id = ?
";
$stmt = $conn->prepare($funding_query);
$stmt->bind_param("s", $id);
$stmt->execute();
$funding_res = $stmt->get_result();

$funding_data = [];
$total_grant = 0;
$total_spent = 0;

while ($fund = $funding_res->fetch_assoc()) {
    $fid = $fund['funding_id'];
    $total_grant += $fund['total_grant'];

    // Fetch Expenditures for this funding source
    // NOTE: Expenditures are linked to funding, not strictly project+funding. 
    // Assuming 1 funding source covers 1 project typically, or expenditures are just listed under the source.
    $exp_query = "SELECT * FROM expenditures WHERE funding_id = ? ORDER BY expenditure_date DESC";
    $stmt_exp = $conn->prepare($exp_query);
    $stmt_exp->bind_param("s", $fid);
    $stmt_exp->execute();
    $exp_res = $stmt_exp->get_result();

    $expenditures = [];
    $fund_spent = 0;
    while ($exp = $exp_res->fetch_assoc()) {
        $fund_spent += $exp['amount'];
        $expenditures[] = $exp;
    }

    $fund['expenditures'] = $expenditures;
    $fund['spent'] = $fund_spent;
    $fund['balance'] = $fund['total_grant'] - $fund_spent;
    $total_spent += $fund_spent;

    $funding_data[] = $fund;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Report - <?php echo htmlspecialchars($project['project_title']); ?></title>
    <style>
        body {
            font-family: 'Times New Roman', serif;
            line-height: 1.6;
            color: #000;
            max-width: 800px;
            margin: 0 auto;
            padding: 40px;
        }

        h1,
        h2,
        h3 {
            margin-bottom: 10px;
            border-bottom: 2px solid #000;
            padding-bottom: 5px;
        }

        h1 {
            text-align: center;
            border-bottom: none;
            margin-bottom: 40px;
        }

        .header-info {
            text-align: center;
            margin-bottom: 40px;
            font-style: italic;
        }

        .section {
            margin-bottom: 30px;
        }

        .meta-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .label {
            font-weight: bold;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #f0f0f0;
        }

        .amount {
            text-align: right;
            font-family: monospace;
        }

        .no-print {
            margin-bottom: 20px;
            text-align: right;
        }

        .btn-print {
            background: #000;
            color: #fff;
            padding: 10px 20px;
            text-decoration: none;
            cursor: pointer;
            border: none;
            font-size: 14px;
        }

        @media print {
            .no-print {
                display: none;
            }

            body {
                padding: 0;
            }
        }
    </style>
</head>

<body>
    <div class="no-print">
        <button onclick="window.print()" class="btn-print">Save as PDF / Print</button>
    </div>

    <h1>Project Report</h1>

    <div class="header-info">
        Generated on: <?php echo date("F j, Y"); ?><br>
        Research Management System
    </div>

    <div class="section">
        <h2>Basic Information</h2>
        <div class="meta-grid">
            <div><span class="label">Project Title:</span> <?php echo htmlspecialchars($project['project_title']); ?>
            </div>
            <div><span class="label">Project ID:</span> <?php echo $project['project_id']; ?></div>
            <div><span class="label">Status:</span> <?php echo ucfirst($project['status']); ?></div>
            <div><span class="label">Duration:</span> <?php echo $project['start_date']; ?> to
                <?php echo $project['end_date']; ?></div>
            <div><span class="label">Project Lead:</span>
                <?php echo htmlspecialchars($project['lead_fname'] . ' ' . $project['lead_lname']); ?>
                (<?php echo $project['lead_dept']; ?>)</div>
        </div>
    </div>

    <div class="section">
        <h2>Team Members</h2>
        <?php if ($team_members->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Department</th>
                        <th>Role</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($tm = $team_members->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($tm['f_name'] . ' ' . $tm['l_name']); ?></td>
                            <td><?php echo htmlspecialchars($tm['department']); ?></td>
                            <td><?php echo htmlspecialchars($tm['role']); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No additional team members.</p>
        <?php endif; ?>
    </div>

    <?php if ($collaboration): ?>
        <div class="section">
            <h2>Collaboration Details</h2>
            <div class="meta-grid">
                <div><span class="label">Type:</span> <?php echo ucfirst($collaboration['collaboration_type']); ?></div>
                <div><span class="label">Country:</span> <?php echo htmlspecialchars($collaboration['country']); ?></div>
                <?php if ($collaboration['mou_agreement_date']): ?>
                    <div><span class="label">MoU Date:</span> <?php echo $collaboration['mou_agreement_date']; ?></div>
                <?php endif; ?>
            </div>
            <?php if ($collaboration['mou_agreement_details']): ?>
                <p><span class="label">Agreement Details:</span><br>
                    <?php echo nl2br(htmlspecialchars($collaboration['mou_agreement_details'])); ?></p>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="section">
        <h2>Financial Overview</h2>
        <div class="meta-grid">
            <div><span class="label">Total Grants:</span> ৳<?php echo number_format($total_grant, 2); ?></div>
            <div><span class="label">Total Spent:</span> ৳<?php echo number_format($total_spent, 2); ?></div>
            <div><span class="label">Remaining:</span> ৳<?php echo number_format($total_grant - $total_spent, 2); ?>
            </div>
        </div>

        <?php if (count($funding_data) > 0): ?>
            <h3>Funding Sources Breakdown</h3>
            <?php foreach ($funding_data as $fund): ?>
                <div style="margin-top: 20px; border: 1px solid #ccc; padding: 10px;">
                    <div
                        style="background: #f9f9f9; padding: 5px; font-weight: bold; display: flex; justify-content: space-between;">
                        <span><?php echo htmlspecialchars($fund['agency_name']); ?></span>
                        <span>Grant: ৳<?php echo number_format($fund['total_grant'], 2); ?></span>
                    </div>

                    <?php if (count($fund['expenditures']) > 0): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Details</th>
                                    <th class="amount">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($fund['expenditures'] as $exp): ?>
                                    <tr>
                                        <td><?php echo $exp['expenditure_date']; ?></td>
                                        <td><?php echo htmlspecialchars($exp['details'] ?? '-'); ?></td>
                                        <td class="amount">৳<?php echo number_format($exp['amount'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr style="font-weight: bold; background: #eee;">
                                    <td colspan="2" style="text-align: right;">Spent</td>
                                    <td class="amount">৳<?php echo number_format($fund['spent'], 2); ?></td>
                                </tr>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p style="font-style: italic; padding: 10px;">No expenditures recorded.</p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No funding records found.</p>
        <?php endif; ?>
    </div>

</body>

</html>