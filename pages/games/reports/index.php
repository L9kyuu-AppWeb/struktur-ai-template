<?php
// Check permission
if (!hasRole(['admin', 'manager'])) {
    require_once __DIR__ . '/../../errors/403.php';
    exit;
}

// Handle export requests
if (isset($_GET['export'])) {
    $exportType = cleanInput($_GET['export']);
    $reportType = isset($_GET['type']) ? cleanInput($_GET['type']) : 'overview';
    $startDate = isset($_GET['start_date']) ? cleanInput($_GET['start_date']) : date('Y-m-01');
    $endDate = isset($_GET['end_date']) ? cleanInput($_GET['end_date']) : date('Y-m-d');

    // Include the appropriate export handler
    if ($exportType === 'excel') {
        // The variables are already defined above, just include the export file
        require_once __DIR__ . '/export/excel.php';
        exit;
    } elseif ($exportType === 'pdf') {
        // The variables are already defined above, just include the export file
        require_once __DIR__ . '/export/pdf.php';
        exit;
    }
}

$reportType = isset($_GET['report']) ? cleanInput($_GET['report']) : 'overview';

// Get date range if specified
$startDate = isset($_GET['start_date']) ? cleanInput($_GET['start_date']) : date('Y-m-01');
$endDate = isset($_GET['end_date']) ? cleanInput($_GET['end_date']) : date('Y-m-d');

switch ($reportType) {
    case 'sales':
        // Sales report - This would typically connect to a sales/orders table
        // Since we don't have a sales table in the schema, we'll create a mock report
        // based on game prices and some statistics

        // Get all games with basic stats
        $sql = "SELECT
                    g.id,
                    g.title,
                    g.price,
                    g.genre,
                    g.platform,
                    g.is_active,
                    g.created_at,
                    COUNT(*) as times_featured
                 FROM games g
                 LEFT JOIN (
                     SELECT game_id, COUNT(*) as count
                     FROM (
                         SELECT id as game_id FROM games WHERE created_at BETWEEN :start_date AND :end_date
                         UNION ALL
                         SELECT id as game_id FROM games WHERE updated_at BETWEEN :start_date AND :end_date
                     ) as activity
                     GROUP BY game_id
                 ) as featured ON g.id = featured.game_id
                 GROUP BY g.id
                 ORDER BY g.title";

        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':start_date', $startDate);
        $stmt->bindParam(':end_date', $endDate);
        $stmt->execute();
        $games = $stmt->fetchAll();

        // Calculate totals
        $totalGames = count($games);
        $activeGames = count(array_filter($games, function($game) { return $game['is_active'] == 1; }));
        $totalRevenueEstimate = array_sum(array_map(function($game) {
            return $game['is_active'] ? floatval($game['price']) : 0;
        }, $games));

        // Get top games by price
        $topGamesSql = "SELECT id, title, price, genre, platform, is_active
                       FROM games
                       ORDER BY price DESC
                       LIMIT 10";
        $topGamesStmt = $pdo->prepare($topGamesSql);
        $topGamesStmt->execute();
        $topGames = $topGamesStmt->fetchAll();

        break;

    case 'inventory':
        // Inventory report
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

        // Get overall stats
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

        // Get genre distribution
        $genreSql = "SELECT
                        genre,
                        COUNT(*) as count,
                        SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_count
                     FROM games
                     WHERE created_at BETWEEN :start_date AND :end_date
                     GROUP BY genre
                     ORDER BY count DESC";

        $genreStmt = $pdo->prepare($genreSql);
        $genreStmt->bindParam(':start_date', $startDate);
        $genreStmt->bindParam(':end_date', $endDate);
        $genreStmt->execute();
        $genreDistribution = $genreStmt->fetchAll();

        // Get platform distribution - split comma-separated values
        $platformSql = "SELECT platform, is_active FROM games WHERE created_at BETWEEN :start_date AND :end_date";

        $platformStmt = $pdo->prepare($platformSql);
        $platformStmt->bindParam(':start_date', $startDate);
        $platformStmt->bindParam(':end_date', $endDate);
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

        break;

    case 'popularity':
        // Popularity report - based on how often games appear/updated
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

        // Get platform distribution for popularity report
        $platformSql = "SELECT platform, is_active FROM games WHERE created_at BETWEEN :start_date AND :end_date";

        $platformStmt = $pdo->prepare($platformSql);
        $platformStmt->bindParam(':start_date', $startDate);
        $platformStmt->bindParam(':end_date', $endDate);
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

        break;

    default: // overview report
        // Get basic overview stats
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
        $overviewStats = $statsStmt->fetch();

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

        break;
}
?>

<div class="mb-6">
    <div class="flex items-center space-x-4 mb-4">
        <a href="index.php?page=games" class="text-gray-600 hover:text-gray-800">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Game Reports</h1>
            <p class="text-gray-500 mt-1">Generate and view reports about your game collection</p>
        </div>
    </div>
</div>

<!-- Report Type Selector -->
<div class="bg-white rounded-2xl shadow-sm p-4 mb-6">
    <div class="flex flex-wrap items-center gap-4">
        <div class="flex-1 min-w-[200px]">
            <label class="block text-sm font-medium text-gray-700 mb-1">Report Type</label>
            <select id="reportType" class="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none">
                <option value="overview" <?php echo $reportType === 'overview' ? 'selected' : ''; ?>>Overview</option>
                <option value="sales" <?php echo $reportType === 'sales' ? 'selected' : ''; ?>>Sales Analysis</option>
                <option value="inventory" <?php echo $reportType === 'inventory' ? 'selected' : ''; ?>>Inventory</option>
                <option value="popularity" <?php echo $reportType === 'popularity' ? 'selected' : ''; ?>>Popularity</option>
            </select>
        </div>
        
        <div class="flex-1 min-w-[200px]">
            <label class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
            <input type="date" id="startDate" value="<?php echo $startDate; ?>" 
                   class="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none">
        </div>
        
        <div class="flex-1 min-w-[200px]">
            <label class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
            <input type="date" id="endDate" value="<?php echo $endDate; ?>" 
                   class="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none">
        </div>
        
        <div class="self-end">
            <button id="generateReport" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-6 rounded-xl transition-colors">
                Generate Report
            </button>
        </div>
    </div>
</div>

<script>
document.getElementById('generateReport').addEventListener('click', function() {
    const reportType = document.getElementById('reportType').value;
    const startDate = document.getElementById('startDate').value;
    const endDate = document.getElementById('endDate').value;
    
    let url = `index.php?page=games&action=reports&report=${reportType}`;
    if (startDate) url += `&start_date=${startDate}`;
    if (endDate) url += `&end_date=${endDate}`;
    
    window.location.href = url;
});

// Also update when report type changes
document.getElementById('reportType').addEventListener('change', function() {
    const reportType = this.value;
    const startDate = document.getElementById('startDate').value;
    const endDate = document.getElementById('endDate').value;
    
    let url = `index.php?page=games&action=reports&report=${reportType}`;
    if (startDate) url += `&start_date=${startDate}`;
    if (endDate) url += `&end_date=${endDate}`;
    
    window.location.href = url;
});
</script>

<!-- Report Content -->
<div class="bg-white rounded-2xl shadow-sm overflow-hidden">
    <?php if ($reportType === 'overview'): ?>
        <!-- Overview Report -->
        <div class="p-6">
            <h2 class="text-xl font-bold text-gray-800 mb-6">Games Overview</h2>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl p-6 text-white">
                    <div class="text-3xl font-bold"><?php echo $overviewStats['total_games']; ?></div>
                    <div class="text-blue-100 mt-1">Total Games</div>
                </div>

                <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-2xl p-6 text-white">
                    <div class="text-3xl font-bold"><?php echo $overviewStats['active_games']; ?></div>
                    <div class="text-green-100 mt-1">Active Games</div>
                </div>

                <div class="bg-gradient-to-br from-yellow-500 to-yellow-600 rounded-2xl p-6 text-white">
                    <div class="text-3xl font-bold"><?php echo $overviewStats['inactive_games']; ?></div>
                    <div class="text-yellow-100 mt-1">Inactive Games</div>
                </div>

                <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-2xl p-6 text-white">
                    <div class="text-3xl font-bold">Rp <?php echo number_format($overviewStats['avg_price'], 2); ?></div>
                    <div class="text-purple-100 mt-1">Average Price</div>
                </div>
            </div>

            <!-- Price Range -->
            <div class="bg-gray-50 rounded-xl p-5 mb-8">
                <h3 class="font-bold text-gray-800 mb-4">Price Range</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-gray-800">Rp <?php echo number_format($overviewStats['min_price'], 2); ?></div>
                        <div class="text-sm text-gray-500">Minimum</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-gray-800">Rp <?php echo number_format($overviewStats['avg_price'], 2); ?></div>
                        <div class="text-sm text-gray-500">Average</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-gray-800">Rp <?php echo number_format($overviewStats['max_price'], 2); ?></div>
                        <div class="text-sm text-gray-500">Maximum</div>
                    </div>
                </div>
            </div>

            <!-- Charts Section -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <!-- Genre Distribution -->
                <div class="bg-gray-50 rounded-xl p-5">
                    <h3 class="font-bold text-gray-800 mb-4">Top Genres</h3>
                    <div class="space-y-3">
                        <?php foreach ($genreDistribution as $genre): ?>
                        <div>
                            <div class="flex justify-between mb-1">
                                <span class="text-sm font-medium text-gray-700"><?php echo htmlspecialchars($genre['genre']); ?></span>
                                <span class="text-sm font-medium text-gray-700"><?php echo $genre['count']; ?> games</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-blue-600 h-2 rounded-full" style="width: <?php echo ($genre['count'] / max(1, $overviewStats['total_games'])) * 100; ?>%"></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Platform Distribution -->
                <div class="bg-gray-50 rounded-xl p-5">
                    <h3 class="font-bold text-gray-800 mb-4">Platform Distribution</h3>
                    <div class="space-y-3">
                        <?php foreach ($platformDistribution as $platform): ?>
                        <div>
                            <div class="flex justify-between mb-1">
                                <span class="text-sm font-medium text-gray-700"><?php echo htmlspecialchars($platform['platform']); ?></span>
                                <span class="text-sm font-medium text-gray-700"><?php echo $platform['count']; ?> games</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-green-600 h-2 rounded-full" style="width: <?php echo ($platform['count'] / max(1, $overviewStats['total_games'])) * 100; ?>%"></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        
    <?php elseif ($reportType === 'sales'): ?>
        <!-- Sales Report -->
        <div class="p-6">
            <h2 class="text-xl font-bold text-gray-800 mb-6">Sales Analysis Report</h2>

            <!-- Sales Stats -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl p-6 text-white">
                    <div class="text-3xl font-bold"><?php echo $totalGames; ?></div>
                    <div class="text-blue-100 mt-1">Total Games</div>
                </div>

                <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-2xl p-6 text-white">
                    <div class="text-3xl font-bold"><?php echo $activeGames; ?></div>
                    <div class="text-green-100 mt-1">Active Games</div>
                </div>

                <div class="bg-gradient-to-br from-yellow-500 to-yellow-600 rounded-2xl p-6 text-white">
                    <div class="text-3xl font-bold">Rp <?php echo number_format($totalRevenueEstimate, 2); ?></div>
                    <div class="text-yellow-100 mt-1">Potential Revenue</div>
                </div>

                <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-2xl p-6 text-white">
                    <div class="text-3xl font-bold"><?php echo round($totalRevenueEstimate / max(1, $activeGames), 2); ?></div>
                    <div class="text-purple-100 mt-1">Avg Revenue per Active Game</div>
                </div>
            </div>

            <!-- Top Games by Price -->
            <div class="bg-gray-50 rounded-xl p-5 mb-8">
                <h3 class="font-bold text-gray-800 mb-4">Top 10 Highest Priced Games</h3>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">ID</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Title</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Price</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Genre</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Platform</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php foreach ($topGames as $game): ?>
                            <tr class="hover:bg-gray-100">
                                <td class="px-4 py-3 text-sm"><?php echo $game['id']; ?></td>
                                <td class="px-4 py-3 text-sm font-medium"><?php echo htmlspecialchars($game['title']); ?></td>
                                <td class="px-4 py-3 text-sm font-medium">Rp <?php echo number_format($game['price'], 2); ?></td>
                                <td class="px-4 py-3 text-sm"><?php echo htmlspecialchars($game['genre']); ?></td>
                                <td class="px-4 py-3 text-sm"><?php echo htmlspecialchars($game['platform']); ?></td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        <?php echo $game['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                        <?php echo $game['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Games List -->
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Title</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Genre</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Platform</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Price</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Status</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Created</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php foreach ($games as $game): ?>
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 font-medium text-gray-800"><?php echo htmlspecialchars($game['title']); ?></td>
                            <td class="px-6 py-4 text-sm text-gray-600"><?php echo htmlspecialchars($game['genre']); ?></td>
                            <td class="px-6 py-4 text-sm text-gray-600"><?php echo htmlspecialchars($game['platform']); ?></td>
                            <td class="px-6 py-4 text-sm font-medium">Rp <?php echo number_format($game['price'], 2); ?></td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-3 py-1 text-xs font-medium rounded-full
                                    <?php echo $game['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                    <?php echo $game['is_active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600"><?php echo date('d M Y', strtotime($game['created_at'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
    <?php elseif ($reportType === 'inventory'): ?>
        <!-- Inventory Report -->
        <div class="p-6">
            <h2 class="text-xl font-bold text-gray-800 mb-6">Inventory Report</h2>

            <!-- Inventory Stats -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-8">
                <div class="bg-blue-50 rounded-xl p-4 text-center">
                    <div class="text-2xl font-bold text-blue-700"><?php echo $stats['total_games']; ?></div>
                    <div class="text-sm text-blue-600">Total</div>
                </div>

                <div class="bg-green-50 rounded-xl p-4 text-center">
                    <div class="text-2xl font-bold text-green-700"><?php echo $stats['active_games']; ?></div>
                    <div class="text-sm text-green-600">Active</div>
                </div>

                <div class="bg-gray-50 rounded-xl p-4 text-center">
                    <div class="text-2xl font-bold text-gray-700">Rp <?php echo number_format($stats['avg_price'], 2); ?></div>
                    <div class="text-sm text-gray-600">Avg Price</div>
                </div>

                <div class="bg-yellow-50 rounded-xl p-4 text-center">
                    <div class="text-2xl font-bold text-yellow-700">Rp <?php echo number_format($stats['min_price'], 2); ?></div>
                    <div class="text-sm text-yellow-600">Min Price</div>
                </div>

                <div class="bg-purple-50 rounded-xl p-4 text-center">
                    <div class="text-2xl font-bold text-purple-700">Rp <?php echo number_format($stats['max_price'], 2); ?></div>
                    <div class="text-sm text-purple-600">Max Price</div>
                </div>
            </div>

            <!-- Charts Section -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <!-- Genre Distribution -->
                <div class="bg-gray-50 rounded-xl p-5">
                    <h3 class="font-bold text-gray-800 mb-4">Genre Distribution</h3>
                    <div class="space-y-3">
                        <?php foreach ($genreDistribution as $genre): ?>
                        <div>
                            <div class="flex justify-between mb-1">
                                <span class="text-sm font-medium text-gray-700"><?php echo htmlspecialchars($genre['genre']); ?></span>
                                <span class="text-sm font-medium text-gray-700"><?php echo $genre['count']; ?> games</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-blue-600 h-2 rounded-full" style="width: <?php echo ($genre['count'] / max(1, $stats['total_games'])) * 100; ?>%"></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Platform Distribution -->
                <div class="bg-gray-50 rounded-xl p-5">
                    <h3 class="font-bold text-gray-800 mb-4">Platform Distribution</h3>
                    <div class="space-y-3">
                        <?php foreach ($platformDistribution as $platform): ?>
                        <div>
                            <div class="flex justify-between mb-1">
                                <span class="text-sm font-medium text-gray-700"><?php echo htmlspecialchars($platform['platform']); ?></span>
                                <span class="text-sm font-medium text-gray-700"><?php echo $platform['count']; ?> games</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-green-600 h-2 rounded-full" style="width: <?php echo ($platform['count'] / max(1, $stats['total_games'])) * 100; ?>%"></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Inventory by Category -->
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Genre</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Platform</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Total</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Active</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Avg Price</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Value</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php foreach ($inventoryReport as $item): ?>
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 font-medium text-gray-800"><?php echo htmlspecialchars($item['genre']); ?></td>
                            <td class="px-6 py-4 text-sm text-gray-600"><?php echo htmlspecialchars($item['platform']); ?></td>
                            <td class="px-6 py-4 text-sm font-medium"><?php echo $item['total_count']; ?></td>
                            <td class="px-6 py-4 text-sm font-medium"><?php echo $item['active_count']; ?></td>
                            <td class="px-6 py-4 text-sm">Rp <?php echo number_format($item['avg_price'], 2); ?></td>
                            <td class="px-6 py-4 text-sm font-medium">Rp <?php echo number_format($item['avg_price'] * $item['active_count'], 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
    <?php elseif ($reportType === 'popularity'): ?>
        <!-- Popularity Report -->
        <div class="p-6">
            <h2 class="text-xl font-bold text-gray-800 mb-6">Popularity Report</h2>

            <!-- Popularity Stats -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl p-6 text-white">
                    <div class="text-3xl font-bold"><?php echo count($popularityReport); ?></div>
                    <div class="text-blue-100 mt-1">Total Games</div>
                </div>

                <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-2xl p-6 text-white">
                    <div class="text-3xl font-bold"><?php echo count(array_filter($popularityReport, function($game) { return $game['is_active'] == 1; })); ?></div>
                    <div class="text-green-100 mt-1">Active Games</div>
                </div>

                <div class="bg-gradient-to-br from-yellow-500 to-yellow-600 rounded-2xl p-6 text-white">
                    <div class="text-3xl font-bold">Rp <?php echo number_format(array_sum(array_map(function($game) {
                        return $game['is_active'] ? floatval($game['price']) : 0;
                    }, $popularityReport)), 2); ?></div>
                    <div class="text-yellow-100 mt-1">Potential Revenue</div>
                </div>

                <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-2xl p-6 text-white">
                    <div class="text-3xl font-bold">Rp <?php echo number_format(count($popularityReport) > 0 ? array_sum(array_map(function($game) {
                        return $game['is_active'] ? floatval($game['price']) : 0;
                    }, $popularityReport)) / count(array_filter($popularityReport, function($game) { return $game['is_active'] == 1; })) : 0, 2); ?></div>
                    <div class="text-purple-100 mt-1">Avg Revenue per Active Game</div>
                </div>
            </div>

            <!-- Charts Section -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <!-- Most Popular Genres -->
                <div class="bg-gray-50 rounded-xl p-5">
                    <h3 class="font-bold text-gray-800 mb-4">Most Popular Genres</h3>
                    <div class="space-y-3">
                        <?php foreach ($topGenres as $genre => $count): ?>
                        <div>
                            <div class="flex justify-between mb-1">
                                <span class="text-sm font-medium text-gray-700"><?php echo htmlspecialchars($genre); ?></span>
                                <span class="text-sm font-medium text-gray-700"><?php echo $count; ?> games</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-blue-600 h-2 rounded-full" style="width: <?php echo (count($popularityReport) > 0) ? ($count / count($popularityReport)) * 100 : 0; ?>%"></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Platform Distribution -->
                <div class="bg-gray-50 rounded-xl p-5">
                    <h3 class="font-bold text-gray-800 mb-4">Platform Distribution</h3>
                    <div class="space-y-3">
                        <?php foreach ($platformDistribution as $platform): ?>
                        <div>
                            <div class="flex justify-between mb-1">
                                <span class="text-sm font-medium text-gray-700"><?php echo htmlspecialchars($platform['platform']); ?></span>
                                <span class="text-sm font-medium text-gray-700"><?php echo $platform['count']; ?> games</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-green-600 h-2 rounded-full" style="width: <?php echo (count($popularityReport) > 0) ? ($platform['count'] / count($popularityReport)) * 100 : 0; ?>%"></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Games Popularity List -->
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Title</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Genre</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Platform</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Price</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Status</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Genre Count</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Platform Count</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php foreach ($popularityReport as $game): ?>
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 font-medium text-gray-800"><?php echo htmlspecialchars($game['title']); ?></td>
                            <td class="px-6 py-4 text-sm text-gray-600"><?php echo htmlspecialchars($game['genre']); ?></td>
                            <td class="px-6 py-4 text-sm text-gray-600"><?php echo htmlspecialchars($game['platform']); ?></td>
                            <td class="px-6 py-4 text-sm font-medium">Rp <?php echo number_format($game['price'], 2); ?></td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-3 py-1 text-xs font-medium rounded-full
                                    <?php echo $game['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                    <?php echo $game['is_active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm"><?php echo $game['genre_count']; ?> in genre</td>
                            <td class="px-6 py-4 text-sm"><?php echo $game['platform_count']; ?> on platform</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
    <?php endif; ?>
</div>

<!-- Export Buttons -->
<div class="mt-6 flex flex-wrap gap-3 justify-end">
    <button id="exportExcel" class="bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-6 rounded-xl transition-colors inline-flex items-center space-x-2">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
        </svg>
        <span>Export to Excel</span>
    </button>

    <button id="exportPdf" class="bg-red-600 hover:bg-red-700 text-white font-medium py-2 px-6 rounded-xl transition-colors inline-flex items-center space-x-2">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
        </svg>
        <span>Export to PDF</span>
    </button>
</div>

<script>
document.getElementById('exportExcel').addEventListener('click', function() {
    const reportType = '<?php echo $reportType; ?>';
    const startDate = '<?php echo $startDate; ?>';
    const endDate = '<?php echo $endDate; ?>';

    let url = `index.php?page=games&action=reports&export=excel&type=${reportType}&start_date=${startDate}&end_date=${endDate}`;

    window.open(url, '_blank');
});

document.getElementById('exportPdf').addEventListener('click', function() {
    const reportType = '<?php echo $reportType; ?>';
    const startDate = '<?php echo $startDate; ?>';
    const endDate = '<?php echo $endDate; ?>';

    let url = `index.php?page=games&action=reports&export=pdf&type=${reportType}&start_date=${startDate}&end_date=${endDate}`;

    window.open(url, '_blank');
});
</script>