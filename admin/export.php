<?php
// export.php - Export Visitor Data to CSV
require_once '../includes/auth.php';
check_login();

require_once '../includes/config.php';

// Set headers to force download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=visitor_history_' . date('Y-m-d_H-i') . '.csv');

// Create a file pointer connected to the output stream
$output = fopen('php://output', 'w');

// Add BOM for Excel UTF-8 compatibility
fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

// Output the column headings
fputcsv($output, array(
    'ID',
    'Time',
    'IP Address',
    'Country',
    'City',
    'Region',
    'ISP',
    'Page URL',
    'Referrer',
    'Duration (s)',
    'OS',
    'Browser',
    'Device Model',
    'Screen Res',
    'User Agent'
));

try {
    // Fetch all records
    // Use unbuffered query for large datasets to save memory
    $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
    $stmt = $pdo->query("SELECT * FROM visits ORDER BY visit_time DESC");

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, array(
            $row['id'],
            $row['visit_time'],
            $row['ip_address'],
            $row['country'],
            $row['city'],
            $row['region'],
            $row['isp'],
            $row['page_url'],
            $row['referrer'],
            $row['duration'],
            $row['os'],
            $row['browser'],
            $row['device_model'],
            $row['screen_res'],
            $row['user_agent']
        ));
    }
} catch (PDOException $e) {
    // In a CSV download, we can't easily show an HTML error. 
    // We might write the error to the CSV or just stop.
    // For now, let's just write the error to the file.
    fputcsv($output, array('Error exporting data: ' . $e->getMessage()));
}

fclose($output);
exit;
