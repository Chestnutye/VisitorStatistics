<?php
// history.php - Full Visitor History
require_once '../includes/auth.php';
check_login();

require_once '../includes/config.php';

$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$perPage = 50;
$offset = ($page - 1) * $perPage;

try {
    // Total count for pagination
    $stmt = $pdo->query("SELECT COUNT(*) FROM visits");
    $totalRecords = $stmt->fetchColumn();
    $totalPages = ceil($totalRecords / $perPage);

    // Fetch records
    $stmt = $pdo->prepare("SELECT * FROM visits ORDER BY visit_time DESC LIMIT :limit OFFSET :offset");
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $visits = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visitor History</title>
    <style>
        :root {
            --bg-color: #f8f9fa;
            --card-bg: #ffffff;
            --text-primary: #2d3748;
            --text-secondary: #718096;
            --accent: #3182ce;
            --border: #e2e8f0;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background-color: var(--bg-color);
            color: var(--text-primary);
            margin: 0;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        header {
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        h1 {
            margin: 0;
            font-size: 1.5rem;
        }

        .card {
            background: var(--card-bg);
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1000px;
        }

        th,
        td {
            text-align: left;
            padding: 10px;
            border-bottom: 1px solid var(--border);
            font-size: 0.9rem;
        }

        th {
            color: var(--text-secondary);
            font-weight: 600;
        }

        .url-cell {
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .btn {
            display: inline-block;
            padding: 8px 16px;
            background-color: var(--accent);
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-size: 0.9rem;
        }

        .btn:hover {
            opacity: 0.9;
        }

        .pagination {
            margin-top: 20px;
            display: flex;
            justify-content: center;
            gap: 10px;
        }

        .page-link {
            padding: 8px 12px;
            background: var(--card-bg);
            border: 1px solid var(--border);
            text-decoration: none;
            color: var(--text-primary);
            border-radius: 4px;
        }

        .page-link.active {
            background: var(--accent);
            color: white;
            border-color: var(--accent);
        }

        .nav-link {
            color: var(--accent);
            text-decoration: none;
            margin-right: 15px;
        }
    </style>
</head>

<body>
    <div class="container">
        <header>
            <div style="display: flex; align-items: center; gap: 20px;">
                <h1>Visitor History</h1>
                <a href="index.php" class="nav-link">← Back to Dashboard</a>
            </div>
            <div>
                <a href="export.php" class="btn">Export to Excel</a>
            </div>
        </header>

        <?php if (isset($error)): ?>
            <div class="card" style="text-align: center; padding: 40px;">
                <p><?php echo $error; ?></p>
            </div>
        <?php else: ?>
            <div class="card">
                <table>
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>IP / Location</th>
                            <th>Device / OS</th>
                            <th>Browser</th>
                            <th>Page</th>
                            <th>Hardware</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($visits as $v): ?>
                            <tr>
                                <td><?php echo date('Y-m-d H:i:s', strtotime($v['visit_time'])); ?></td>
                                <td>
                                    <div><?php echo htmlspecialchars($v['ip_address']); ?></div>
                                    <div style="font-size: 0.8rem; color: var(--text-secondary);">
                                        <?php echo htmlspecialchars($v['city'] . ', ' . $v['country']); ?>
                                    </div>
                                    <div style="font-size: 0.75rem; color: var(--text-secondary);">
                                        <?php echo htmlspecialchars($v['isp']); ?>
                                    </div>
                                </td>
                                <td>
                                    <div><?php echo htmlspecialchars($v['os']); ?></div>
                                    <div style="font-size: 0.8rem; color: var(--text-secondary);">
                                        <?php echo htmlspecialchars($v['device_model']); ?>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($v['browser']); ?></td>
                                <td class="url-cell" title="<?php echo htmlspecialchars($v['page_url']); ?>">
                                    <?php echo htmlspecialchars($v['page_url']); ?>
                                    <div style="font-size: 0.8rem; color: var(--text-secondary);">
                                        Ref:
                                        <?php echo htmlspecialchars(parse_url($v['referrer'], PHP_URL_HOST) ?: 'Direct'); ?>
                                    </div>
                                    <?php if ($v['duration'] > 0): ?>
                                        <div style="font-size: 0.8rem; color: green;">
                                            Time: <?php echo $v['duration']; ?>s
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td style="font-size: 0.85rem;">
                                    <?php if ($v['cpu_cores'])
                                        echo "CPU: " . htmlspecialchars($v['cpu_cores']) . " Cores<br>"; ?>
                                    <?php if ($v['device_memory'])
                                        echo "RAM: ~" . htmlspecialchars($v['device_memory']) . " GB<br>"; ?>
                                    <?php if ($v['connection_type'])
                                        echo "Net: " . htmlspecialchars($v['connection_type']); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($visits))
                            echo "<tr><td colspan='6'>No data</td></tr>"; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>" class="page-link">Previous</a>
                    <?php endif; ?>

                    <?php
                    $start = max(1, $page - 2);
                    $end = min($totalPages, $page + 2);
                    for ($i = $start; $i <= $end; $i++):
                        ?>
                        <a href="?page=<?php echo $i; ?>" class="page-link <?php echo $i === $page ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?php echo $page + 1; ?>" class="page-link">Next</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>

</html>