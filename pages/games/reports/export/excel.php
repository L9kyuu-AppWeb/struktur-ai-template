<?php
// Include necessary files for database connection and functions
// Using __DIR__ to build the correct path from this file's location
$basePath = dirname(dirname(dirname(dirname(__DIR__)))); // Go up 4 levels from reports/export/ to root
require_once $basePath . '/config.php';
require_once $basePath . '/includes/db_connect.php';
require_once $basePath . '/includes/functions.php';

// Check permission
if (!hasRole(['admin', 'manager'])) {
    http_response_code(403);
    die('Access denied');
}

// Set headers for CSV download (using CSV since we're not using a library)
header('Content-Type: text/csv');
header('Content-Disposition: attachment;filename="games_report_' . date('Y-m-d_H-i-s') . '.csv"');
header('Cache-Control: max-age=0');

// Open output stream
$output = fopen('php://output', 'w');

// Determine report type to export
// These values are passed from the parent script
global $reportType, $startDate, $endDate;

// If these variables are not set, fallback to GET parameters
$reportType = isset($reportType) ? $reportType : (isset($_GET['type']) ? cleanInput($_GET['type']) : 'overview');
$startDate = isset($startDate) ? $startDate : (isset($_GET['start_date']) ? cleanInput($_GET['start_date']) : date('Y-m-01'));
$endDate = isset($endDate) ? $endDate : (isset($_GET['end_date']) ? cleanInput($_GET['end_date']) : date('Y-m-d'));

// Prepare data based on report type
switch ($reportType) {
    case 'sales':
        // Sales report data
        $sql = "SELECT 
                    g.id,
                    g.title,
                    g.price,
                    g.genre,
                    g.platform,
                    g.is_active,
                    g.created_at,
                    g.updated_at
                 FROM games g
                 WHERE g.created_at BETWEEN :start_date AND :end_date
                 ORDER BY g.title";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':start_date', $startDate);
        $stmt->bindParam(':end_date', $endDate);
        $stmt->execute();
        $games = $stmt->fetchAll();
        
        // Write headers
        fputcsv($output, ['ID', 'Title', 'Price', 'Genre', 'Platform', 'Status', 'Created At', 'Updated At']);
        
        // Write data rows
        foreach ($games as $game) {
            fputcsv($output, [
                $game['id'],
                $game['title'],
                $game['price'],
                $game['genre'],
                $game['platform'],
                $game['is_active'] ? 'Active' : 'Inactive',
                $game['created_at'],
                $game['updated_at']
            ]);
        }
        break;
        
    case 'inventory':
        // Inventory report data
        $sql = "SELECT 
                    genre,
                    platform,
                    COUNT(*) as total_count,
                    SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_count,
                    AVG(price) as avg_price,
                    MIN(price) as min_price,
                    MAX(price) as max_price
                 FROM games 
                 WHERE created_at BETWEEN :start_date AND :end_date
                 GROUP BY genre, platform
                 ORDER BY genre, platform";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':start_date', $startDate);
        $stmt->bindParam(':end_date', $endDate);
        $stmt->execute();
        $inventoryReport = $stmt->fetchAll();
        
        // Write headers
        fputcsv($output, ['Genre', 'Platform', 'Total Count', 'Active Count', 'Average Price', 'Min Price', 'Max Price']);
        
        // Write data rows
        foreach ($inventoryReport as $item) {
            fputcsv($output, [
                $item['genre'],
                $item['platform'],
                $item['total_count'],
                $item['active_count'],
                $item['avg_price'],
                $item['min_price'],
                $item['max_price']
            ]);
        }
        break;
        
    case 'popularity':
        // Popularity report data
        $sql = "SELECT 
                    g.id,
                    g.title,
                    g.genre,
                    g.platform,
                    g.price,
                    g.is_active,
                    g.created_at,
                    g.updated_at,
                    (SELECT COUNT(*) FROM games WHERE genre = g.genre) as genre_count,
                    (SELECT COUNT(*) FROM games WHERE platform = g.platform) as platform_count
                 FROM games g
                 WHERE created_at BETWEEN :start_date AND :end_date
                 ORDER BY g.title";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':start_date', $startDate);
        $stmt->bindParam(':end_date', $endDate);
        $stmt->execute();
        $popularityReport = $stmt->fetchAll();
        
        // Write headers
        fputcsv($output, ['ID', 'Title', 'Genre', 'Platform', 'Price', 'Status', 'Created', 'Updated', 'Genre Count', 'Platform Count']);
        
        // Write data rows
        foreach ($popularityReport as $game) {
            fputcsv($output, [
                $game['id'],
                $game['title'],
                $game['genre'],
                $game['platform'],
                $game['price'],
                $game['is_active'] ? 'Active' : 'Inactive',
                $game['created_at'],
                $game['updated_at'],
                $game['genre_count'],
                $game['platform_count']
            ]);
        }
        break;
        
    default: // overview report
        // Overview report data
        $sql = "SELECT
                    id,
                    title,
                    price,
                    genre,
                    platform,
                    is_active,
                    created_at,
                    updated_at
                 FROM games
                 WHERE created_at BETWEEN :start_date AND :end_date
                 ORDER BY title";

        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':start_date', $startDate);
        $stmt->bindParam(':end_date', $endDate);
        $stmt->execute();
        $games = $stmt->fetchAll();

        // Write headers
        fputcsv($output, ['ID', 'Title', 'Price', 'Genre', 'Platform', 'Status', 'Created At', 'Updated At']);

        // Write data rows
        foreach ($games as $game) {
            fputcsv($output, [
                $game['id'],
                $game['title'],
                $game['price'],
                $game['genre'],
                $game['platform'],
                $game['is_active'] ? 'Active' : 'Inactive',
                $game['created_at'],
                $game['updated_at']
            ]);
        }
        break;
}

// Close output stream
fclose($output);
exit;