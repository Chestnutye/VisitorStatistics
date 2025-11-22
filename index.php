<?php
// index.php - Dashboard

require_once 'auth.php';
check_login();

$pv = 0;
$uv = 0;
$avgDuration = 0;
$topPages = [];
$topReferrers = [];
$chartLabels = [];
$chartData = [];
$deviceStats = [];
$osStats = [];

try {
    require_once 'config.php';

    // 1. Ensure table exists with FULL schema
    $query = "CREATE TABLE IF NOT EXISTS visits (
        id INT AUTO_INCREMENT PRIMARY KEY,
        visitor_id VARCHAR(50),
        page_view_id VARCHAR(50),
        ip_address VARCHAR(45),
        page_url TEXT,
        referrer TEXT,
        user_agent TEXT,
        screen_res VARCHAR(20),
        viewport VARCHAR(20),
        language VARCHAR(10),
        platform VARCHAR(50),
        timezone VARCHAR(50),
        country VARCHAR(50),
        city VARCHAR(50),
        region VARCHAR(50),
        isp VARCHAR(100),
        os VARCHAR(50),
        browser VARCHAR(50),
        device_model VARCHAR(50),
        device_memory VARCHAR(10),
        cpu_cores VARCHAR(10),
        connection_type VARCHAR(20),
        duration INT DEFAULT 0,
        visit_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($query);

    // 2. Auto-Migration
    try {
        $pdo->query("SELECT device_model, os, duration FROM visits LIMIT 1");
    } catch (PDOException $e) {
        $columns = [
            'visitor_id' => 'VARCHAR(50)',
            'page_view_id' => 'VARCHAR(50)',
            'viewport' => 'VARCHAR(20)',
            'duration' => 'INT DEFAULT 0',
            'device_model' => 'VARCHAR(50)',
            'os' => 'VARCHAR(50)',
            'browser' => 'VARCHAR(50)',
            'country' => 'VARCHAR(50)',
            'city' => 'VARCHAR(50)',
            'isp' => 'VARCHAR(100)'
        ];
        foreach ($columns as $col => $type) {
            try {
                $pdo->exec("ALTER TABLE visits ADD COLUMN $col $type");
            } catch (Exception $ex) {
            }
        }
    }

    // Total PV
    $stmt = $pdo->query("SELECT COUNT(*) FROM visits");
    $pv = $stmt->fetchColumn();

    // Total UV
    $stmt = $pdo->query("SELECT COUNT(DISTINCT ip_address) FROM visits");
    $uv = $stmt->fetchColumn();

    // Avg Duration
    $stmt = $pdo->query("SELECT AVG(duration) FROM visits WHERE duration > 0");
    $avgDuration = round($stmt->fetchColumn() ?: 0);

    // Top Pages
    $stmt = $pdo->query("SELECT page_url, COUNT(*) as count FROM visits GROUP BY page_url ORDER BY count DESC LIMIT 10");
    $topPages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Top Referrers
    $stmt = $pdo->query("SELECT referrer, COUNT(*) as count FROM visits WHERE referrer != '' GROUP BY referrer ORDER BY count DESC LIMIT 10");
    $topReferrers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Device Stats
    $stmt = $pdo->query("SELECT COALESCE(device_model, 'Unknown') as model, COUNT(*) as count FROM visits GROUP BY model ORDER BY count DESC");
    $deviceStats = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    // OS Stats
    $stmt = $pdo->query("SELECT COALESCE(os, 'Unknown') as os_name, COUNT(*) as count FROM visits GROUP BY os_name ORDER BY count DESC");
    $osStats = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    // Traffic
    $stmt = $pdo->query("
        SELECT DATE_FORMAT(visit_time, '%H:00') as hour, COUNT(*) as count 
        FROM visits 
        WHERE visit_time >= DATE_SUB(NOW(), INTERVAL 24 HOUR) 
        GROUP BY hour 
        ORDER BY MIN(visit_time) ASC
    ");
    $traffic = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    // Fill chart data
    for ($i = 23; $i >= 0; $i--) {
        $h = date('H:00', strtotime("-$i hours"));
        $chartLabels[] = $h;
        $chartData[] = isset($traffic[$h]) ? $traffic[$h] : 0;
    }

    // Recent Visits (No Pagination, just last 50)
    $stmt = $pdo->query("SELECT * FROM visits ORDER BY visit_time DESC LIMIT 50");
    $recentVisits = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visitor Statistics</title>
    <!-- Use cdnjs for global reliability -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
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

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .card {
            background: var(--card-bg);
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            color: var(--accent);
        }

        .stat-label {
            color: var(--text-secondary);
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .chart-container {
            position: relative;
            height: 300px;
            margin-bottom: 30px;
        }

        .pie-chart-container {
            position: relative;
            height: 250px;
        }

        .tables-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .charts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
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

        .table-wrapper {
            overflow-x: auto;
        }

        footer {
            text-align: center;
            margin-top: 50px;
            color: var(--text-secondary);
            font-size: 0.8rem;
        }
    </style>
</head>

<body>
    <div class="container">
        <header>
            <h1>Visitor Statistics</h1>
            <div style="display: flex; align-items: center; gap: 15px;">
                <div style="font-size: 0.9rem; color: var(--text-secondary);">
                    <?php echo date('Y-m-d H:i'); ?>
                </div>
                <a href="auth.php?logout=1"
                    style="color: var(--accent); text-decoration: none; font-size: 0.9rem;">Logout</a>
            </div>
        </header>

        <?php if (isset($error)): ?>
            <div class="card" style="text-align: center; padding: 40px;">
                <p><?php echo $error; ?></p>
            </div>
        <?php else: ?>
            <div class="stats-grid">
                <div class="card">
                    <div class="stat-label">Total Page Views</div>
                    <div class="stat-value"><?php echo number_format($pv); ?></div>
                </div>
                <div class="card">
                    <div class="stat-label">Unique Visitors</div>
                    <div class="stat-value"><?php echo number_format($uv); ?></div>
                </div>
                <div class="card">
                    <div class="stat-label">Avg. Time on Page</div>
                    <div class="stat-value">
                        <?php
                        $m = floor($avgDuration / 60);
                        $s = $avgDuration % 60;
                        echo sprintf("%02d:%02d", $m, $s);
                        ?>
                    </div>
                </div>
            </div>

            <div class="card">
                <h3>Traffic (Last 24 Hours)</h3>
                <div class="chart-container">
                    <canvas id="trafficChart"></canvas>
                </div>
            </div>

            <div class="charts-grid">
                <div class="card">
                    <h3>Device Breakdown</h3>
                    <div class="pie-chart-container">
                        <canvas id="deviceChart"></canvas>
                    </div>
                </div>
                <div class="card">
                    <h3>OS Breakdown</h3>
                    <div class="pie-chart-container">
                        <canvas id="osChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="tables-grid">
                <div class="card">
                    <h3>Top Pages</h3>
                    <div class="table-wrapper">
                        <table>
                            <thead>
                                <tr>
                                    <th>Page</th>
                                    <th width="60">Views</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($topPages as $page): ?>
                                    <tr>
                                        <td class="url-cell" title="<?php echo htmlspecialchars($page['page_url']); ?>">
                                            <?php echo htmlspecialchars($page['page_url']); ?>
                                        </td>
                                        <td><?php echo $page['count']; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($topPages))
                                    echo "<tr><td colspan='2'>No data</td></tr>"; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card">
                    <h3>Top Referrers</h3>
                    <div class="table-wrapper">
                        <table>
                            <thead>
                                <tr>
                                    <th>Referrer</th>
                                    <th width="60">Views</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($topReferrers as $ref): ?>
                                    <tr>
                                        <td class="url-cell" title="<?php echo htmlspecialchars($ref['referrer']); ?>">
                                            <?php echo htmlspecialchars($ref['referrer']); ?>
                                        </td>
                                        <td><?php echo $ref['count']; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($topReferrers))
                                    echo "<tr><td colspan='2'>No data</td></tr>"; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        <?php endif; ?>

        <?php if (isset($recentVisits)): ?>
            <div class="card" style="margin-top: 30px; overflow-x: auto;">
                <h3>Visitor Log (Last 50)</h3>
                <table style="min-width: 1000px;">
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
                        <?php foreach ($recentVisits as $v): ?>
                            <tr>
                                <td><?php echo date('m-d H:i', strtotime($v['visit_time'])); ?></td>
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
                                        echo "CPU: {$v['cpu_cores']} Cores<br>"; ?>
                                    <?php if ($v['device_memory'])
                                        echo "RAM: ~{$v['device_memory']} GB<br>"; ?>
                                    <?php if ($v['connection_type'])
                                        echo "Net: {$v['connection_type']}"; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($recentVisits))
                            echo "<tr><td colspan='6'>No data</td></tr>"; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <footer>
            Page generated in <?php echo round((microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"]), 4); ?>s
        </footer>
    </div>

    <script>
        <?php if (!isset($error)): ?>
            try {
                console.log("Initializing Charts...");

                if (typeof Chart === 'undefined') {
                    throw new Error("Chart.js library not loaded. Check your internet connection or CDN.");
                }

                // Data Debugging
                var chartLabels = <?php echo json_encode($chartLabels, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
                var chartData = <?php echo json_encode($chartData, JSON_NUMERIC_CHECK); ?>;
                var deviceLabels = <?php echo json_encode(array_keys($deviceStats), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
                var deviceData = <?php echo json_encode(array_values($deviceStats), JSON_NUMERIC_CHECK); ?>;
                var osLabels = <?php echo json_encode(array_keys($osStats), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
                var osData = <?php echo json_encode(array_values($osStats), JSON_NUMERIC_CHECK); ?>;

                console.log("Traffic Data:", chartData);
                console.log("Device Data:", deviceData);
                console.log("OS Data:", osData);

                // Traffic Chart
                var ctx = document.getElementById('trafficChart').getContext('2d');
                var gradient = ctx.createLinearGradient(0, 0, 0, 300);
                gradient.addColorStop(0, 'rgba(49, 130, 206, 0.5)');
                gradient.addColorStop(1, 'rgba(49, 130, 206, 0.0)');

                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: chartLabels,
                        datasets: [{
                            label: 'Page Views',
                            data: chartData,
                            borderColor: '#3182ce',
                            backgroundColor: gradient,
                            borderWidth: 2,
                            fill: true,
                            tension: 0.4,
                            pointRadius: 3,
                            pointHoverRadius: 5
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: {
                            y: { beginAtZero: true, grid: { borderDash: [2, 4] } },
                            x: { grid: { display: false } }
                        },
                        interaction: {
                            intersect: false,
                            mode: 'index',
                        },
                    }
                });

                // Device Chart
                var deviceCtx = document.getElementById('deviceChart').getContext('2d');
                new Chart(deviceCtx, {
                    type: 'doughnut',
                    data: {
                        labels: deviceLabels,
                        datasets: [{
                            data: deviceData,
                            backgroundColor: [
                                '#3182ce', '#63b3ed', '#4299e1', '#90cdf4', '#bee3f8'
                            ],
                            borderWidth: 0
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { position: 'bottom' }
                        },
                        cutout: '60%'
                    }
                });

                // OS Chart
                var osCtx = document.getElementById('osChart').getContext('2d');
                new Chart(osCtx, {
                    type: 'pie',
                    data: {
                        labels: osLabels,
                        datasets: [{
                            data: osData,
                            backgroundColor: [
                                '#38a169', '#68d391', '#48bb78', '#9ae6b4', '#c6f6d5'
                            ],
                            borderWidth: 0
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { position: 'bottom' }
                        }
                    }
                });

                console.log("Charts Initialized Successfully");
            } catch (err) {
                console.error("Chart Error:", err);
                document.querySelectorAll('.chart-container, .pie-chart-container').forEach(el => {
                    el.innerHTML = '<div style="display:flex;align-items:center;justify-content:center;height:100%;color:red;">Chart Error: ' + err.message + '</div>';
                });
            }
        <?php endif; ?>
    </script>
</body>

</html>