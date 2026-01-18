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

// Set headers for PDF-like HTML download
header('Content-Type: text/html');
header('Content-Disposition: attachment;filename="games_summary_report_' . date('Y-m-d_H-i-s') . '.html"');
header('Cache-Control: max-age=0');

// These values are passed from the parent script
global $reportType, $startDate, $endDate;

// If these variables are not set, fallback to GET parameters
$reportType = isset($reportType) ? $reportType : (isset($_GET['type']) ? cleanInput($_GET['type']) : 'overview');
$startDate = isset($startDate) ? $startDate : (isset($_GET['start_date']) ? cleanInput($_GET['start_date']) : date('Y-m-01'));
$endDate = isset($endDate) ? $endDate : (isset($_GET['end_date']) ? cleanInput($_GET['end_date']) : date('Y-m-d'));

// Generate HTML content for the PDF
$html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Games Summary Report</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header { text-align: center; margin-bottom: 20px; }
        .title { font-size: 24px; font-weight: bold; color: #333; }
        .subtitle { font-size: 16px; color: #666; margin-bottom: 30px; }
        .section { margin-bottom: 20px; }
        .section-title { font-size: 18px; font-weight: bold; color: #333; margin-bottom: 10px; }
        .stats-container { display: flex; flex-wrap: wrap; gap: 15px; margin-bottom: 20px; }
        .stat-card { flex: 1; min-width: 200px; padding: 15px; background-color: #f5f5f5; border-radius: 8px; }
        .stat-number { font-size: 24px; font-weight: bold; color: #007bff; }
        .stat-label { font-size: 14px; color: #666; }
        .chart-placeholder { width: 100%; height: 200px; background-color: #f9f9f9; border: 1px solid #ddd; border-radius: 8px; margin: 10px 0; display: flex; align-items: center; justify-content: center; }
        .footer { margin-top: 30px; text-align: center; font-size: 12px; color: #666; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">Games Summary Report</div>
        <div class="subtitle">Period: ' . $startDate . ' to ' . $endDate . '</div>
        <div class="subtitle">Report Type: ' . ucfirst($reportType) . '</div>
    </div>';

// Add content based on report type
switch ($reportType) {
    case 'sales':
        // Get sales data
        $statsSql = "SELECT 
                        COUNT(*) as total_games,
                        SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_games,
                        SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) as inactive_games,
                        AVG(price) as avg_price,
                        MIN(price) as min_price,
                        MAX(price) as max_price
                     FROM games";
        
        $statsStmt = $pdo->prepare($statsSql);
        $statsStmt->execute();
        $stats = $statsStmt->fetch();
        
        $html .= '
        <div class="section">
            <div class="section-title">Sales Summary</div>
            <div class="stats-container">
                <div class="stat-card">
                    <div class="stat-number">' . $stats['total_games'] . '</div>
                    <div class="stat-label">Total Games</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">' . $stats['active_games'] . '</div>
                    <div class="stat-label">Active Games</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">Rp ' . number_format($stats['avg_price'], 2) . '</div>
                    <div class="stat-label">Average Price</div>
                </div>
            </div>
        </div>
        
        <div class="section">
            <div class="section-title">Price Range</div>
            <div class="stats-container">
                <div class="stat-card">
                    <div class="stat-number">Rp ' . number_format($stats['min_price'], 2) . '</div>
                    <div class="stat-label">Minimum Price</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">Rp ' . number_format($stats['avg_price'], 2) . '</div>
                    <div class="stat-label">Average Price</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">Rp ' . number_format($stats['max_price'], 2) . '</div>
                    <div class="stat-label">Maximum Price</div>
                </div>
            </div>
        </div>';
        
        // Add top games by price
        $topGamesSql = "SELECT id, title, price, genre, platform, is_active 
                       FROM games 
                       ORDER BY price DESC 
                       LIMIT 10";
        $topGamesStmt = $pdo->prepare($topGamesSql);
        $topGamesStmt->execute();
        $topGames = $topGamesStmt->fetchAll();
        
        $html .= '
        <div class="section">
            <div class="section-title">Top 10 Highest Priced Games</div>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Price</th>
                        <th>Genre</th>
                        <th>Platform</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>';
        foreach ($topGames as $game) {
            $html .= '
                <tr>
                    <td>' . $game['id'] . '</td>
                    <td>' . htmlspecialchars($game['title']) . '</td>
                    <td>Rp ' . number_format($game['price'], 2) . '</td>
                    <td>' . htmlspecialchars($game['genre']) . '</td>
                    <td>' . htmlspecialchars($game['platform']) . '</td>
                    <td>' . ($game['is_active'] ? 'Active' : 'Inactive') . '</td>
                </tr>';
        }
        $html .= '
                </tbody>
            </table>
        </div>';
        break;
        
    case 'inventory':
        // Get inventory data
        $statsSql = "SELECT 
                        COUNT(*) as total_games,
                        SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_games,
                        AVG(price) as avg_price,
                        MIN(price) as min_price,
                        MAX(price) as max_price
                     FROM games 
                     WHERE created_at BETWEEN :start_date AND :end_date";
        
        $statsStmt = $pdo->prepare($statsSql);
        $statsStmt->bindParam(':start_date', $startDate);
        $statsStmt->bindParam(':end_date', $endDate);
        $statsStmt->execute();
        $stats = $statsStmt->fetch();
        
        $html .= '
        <div class="section">
            <div class="section-title">Inventory Summary</div>
            <div class="stats-container">
                <div class="stat-card">
                    <div class="stat-number">' . $stats['total_games'] . '</div>
                    <div class="stat-label">Total Items</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">' . $stats['active_games'] . '</div>
                    <div class="stat-label">Active Items</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">Rp ' . number_format($stats['avg_price'], 2) . '</div>
                    <div class="stat-label">Average Value</div>
                </div>
            </div>
        </div>';
        
        // Get genre distribution
        $genreSql = "SELECT 
                        genre,
                        COUNT(*) as count,
                        SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_count
                     FROM games
                     GROUP BY genre
                     ORDER BY count DESC";
        
        $genreStmt = $pdo->prepare($genreSql);
        $genreStmt->execute();
        $genreDistribution = $genreStmt->fetchAll();
        
        $html .= '
        <div class="section">
            <div class="section-title">Genre Distribution</div>
            <table>
                <thead>
                    <tr>
                        <th>Genre</th>
                        <th>Total</th>
                        <th>Active</th>
                        <th>Percentage</th>
                    </tr>
                </thead>
                <tbody>';
        $totalGames = $stats['total_games'];
        foreach ($genreDistribution as $genre) {
            $percentage = $totalGames > 0 ? round(($genre['count'] / $totalGames) * 100, 2) : 0;
            $html .= '
                <tr>
                    <td>' . htmlspecialchars($genre['genre']) . '</td>
                    <td>' . $genre['count'] . '</td>
                    <td>' . $genre['active_count'] . '</td>
                    <td>' . $percentage . '%</td>
                </tr>';
        }
        $html .= '
                </tbody>
            </table>
        </div>';
        break;
        
    case 'popularity':
        // Get popularity data
        $statsSql = "SELECT 
                        COUNT(*) as total_games,
                        SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_games,
                        AVG(price) as avg_price
                     FROM games 
                     WHERE created_at BETWEEN :start_date AND :end_date";
        
        $statsStmt = $pdo->prepare($statsSql);
        $statsStmt->bindParam(':start_date', $startDate);
        $statsStmt->bindParam(':end_date', $endDate);
        $statsStmt->execute();
        $stats = $statsStmt->fetch();
        
        $html .= '
        <div class="section">
            <div class="section-title">Popularity Summary</div>
            <div class="stats-container">
                <div class="stat-card">
                    <div class="stat-number">' . $stats['total_games'] . '</div>
                    <div class="stat-label">Total Games</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">' . $stats['active_games'] . '</div>
                    <div class="stat-label">Active Games</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">Rp ' . number_format($stats['avg_price'], 2) . '</div>
                    <div class="stat-label">Average Price</div>
                </div>
            </div>
        </div>';
        
        // Get top genres
        $genreCounts = [];
        $allGamesSql = "SELECT genre FROM games WHERE created_at BETWEEN :start_date AND :end_date";
        $allGamesStmt = $pdo->prepare($allGamesSql);
        $allGamesStmt->bindParam(':start_date', $startDate);
        $allGamesStmt->bindParam(':end_date', $endDate);
        $allGamesStmt->execute();
        $allGames = $allGamesStmt->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($allGames as $genre) {
            if (!isset($genreCounts[$genre])) {
                $genreCounts[$genre] = 0;
            }
            $genreCounts[$genre]++;
        }
        arsort($genreCounts);
        $topGenres = array_slice($genreCounts, 0, 5, true);
        
        $html .= '
        <div class="section">
            <div class="section-title">Most Popular Genres</div>
            <table>
                <thead>
                    <tr>
                        <th>Genre</th>
                        <th>Count</th>
                        <th>Percentage</th>
                    </tr>
                </thead>
                <tbody>';
        $totalCount = array_sum($topGenres);
        foreach ($topGenres as $genre => $count) {
            $percentage = $totalCount > 0 ? round(($count / $totalCount) * 100, 2) : 0;
            $html .= '
                <tr>
                    <td>' . htmlspecialchars($genre) . '</td>
                    <td>' . $count . '</td>
                    <td>' . $percentage . '%</td>
                </tr>';
        }
        $html .= '
                </tbody>
            </table>
        </div>';
        break;
        
    default: // overview report
        // Get overview data
        $statsSql = "SELECT 
                        COUNT(*) as total_games,
                        SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_games,
                        SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) as inactive_games,
                        AVG(price) as avg_price,
                        MIN(price) as min_price,
                        MAX(price) as max_price
                     FROM games";
        
        $statsStmt = $pdo->prepare($statsSql);
        $statsStmt->execute();
        $stats = $statsStmt->fetch();
        
        $html .= '
        <div class="section">
            <div class="section-title">Games Overview</div>
            <div class="stats-container">
                <div class="stat-card">
                    <div class="stat-number">' . $stats['total_games'] . '</div>
                    <div class="stat-label">Total Games</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">' . $stats['active_games'] . '</div>
                    <div class="stat-label">Active Games</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">' . $stats['inactive_games'] . '</div>
                    <div class="stat-label">Inactive Games</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">Rp ' . number_format($stats['avg_price'], 2) . '</div>
                    <div class="stat-label">Average Price</div>
                </div>
            </div>
        </div>
        
        <div class="section">
            <div class="section-title">Price Range</div>
            <div class="stats-container">
                <div class="stat-card">
                    <div class="stat-number">Rp ' . number_format($stats['min_price'], 2) . '</div>
                    <div class="stat-label">Minimum</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">Rp ' . number_format($stats['avg_price'], 2) . '</div>
                    <div class="stat-label">Average</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">Rp ' . number_format($stats['max_price'], 2) . '</div>
                    <div class="stat-label">Maximum</div>
                </div>
            </div>
        </div>';
        
        // Get genre distribution
        $genreSql = "SELECT 
                        genre,
                        COUNT(*) as count,
                        SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_count
                     FROM games
                     GROUP BY genre
                     ORDER BY count DESC
                     LIMIT 5";
        
        $genreStmt = $pdo->prepare($genreSql);
        $genreStmt->execute();
        $genreDistribution = $genreStmt->fetchAll();
        
        // Get platform distribution - split comma-separated values
        $platformSql = "SELECT platform, is_active FROM games";

        $platformStmt = $pdo->prepare($platformSql);
        $platformStmt->execute();
        $allPlatformRows = $platformStmt->fetchAll();

        // Process platforms to split comma-separated values
        $platformCounts = [];
        $activePlatformCounts = [];

        foreach ($allPlatformRows as $row) {
            $platforms = explode(',', $row['platform']);
            foreach ($platforms as $platform) {
                $platform = trim($platform);
                if (!empty($platform)) {
                    if (!isset($platformCounts[$platform])) {
                        $platformCounts[$platform] = 0;
                        $activePlatformCounts[$platform] = 0;
                    }
                    $platformCounts[$platform]++;
                    if ($row['is_active'] == 1) {
                        $activePlatformCounts[$platform]++;
                    }
                }
            }
        }

        // Sort by count descending
        arsort($platformCounts);

        // Format for display
        $platformDistribution = [];
        foreach ($platformCounts as $platform => $count) {
            $platformDistribution[] = [
                'platform' => $platform,
                'count' => $count,
                'active_count' => $activePlatformCounts[$platform]
            ];
        }

        $html .= '
        <div class="section">
            <div class="section-title">Top Genres</div>
            <table>
                <thead>
                    <tr>
                        <th>Genre</th>
                        <th>Total</th>
                        <th>Active</th>
                        <th>Percentage</th>
                    </tr>
                </thead>
                <tbody>';
        $totalGames = $stats['total_games'];
        foreach ($genreDistribution as $genre) {
            $percentage = $totalGames > 0 ? round(($genre['count'] / $totalGames) * 100, 2) : 0;
            $html .= '
                <tr>
                    <td>' . htmlspecialchars($genre['genre']) . '</td>
                    <td>' . $genre['count'] . '</td>
                    <td>' . $genre['active_count'] . '</td>
                    <td>' . $percentage . '%</td>
                </tr>';
        }
        $html .= '
                </tbody>
            </table>
        </div>';

        $html .= '
        <div class="section">
            <div class="section-title">Platform Distribution</div>
            <table>
                <thead>
                    <tr>
                        <th>Platform</th>
                        <th>Total</th>
                        <th>Active</th>
                        <th>Percentage</th>
                    </tr>
                </thead>
                <tbody>';
        foreach ($platformDistribution as $platform) {
            $percentage = $totalGames > 0 ? round(($platform['count'] / $totalGames) * 100, 2) : 0;
            $html .= '
                <tr>
                    <td>' . htmlspecialchars($platform['platform']) . '</td>
                    <td>' . $platform['count'] . '</td>
                    <td>' . $platform['active_count'] . '</td>
                    <td>' . $percentage . '%</td>
                </tr>';
        }
        $html .= '
                </tbody>
            </table>
        </div>';
        break;
}

$html .= '
    <div class="footer">
        Generated on ' . date('Y-m-d H:i:s') . ' | L9kyuuPanel Game Reports
    </div>
</body>
</html>';

echo $html;

exit;