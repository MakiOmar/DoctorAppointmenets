<?php
/**
 * Debug Log Viewer
 * 
 * Displays WordPress error logs in a readable format
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    require_once('../../../wp-load.php');
}

// Ensure we're in admin context
if (!current_user_can('manage_options')) {
    wp_die('Administrator access required');
}

echo "<h1>📋 Debug Log Viewer</h1>";
echo "<style>
    .log-entry { margin: 10px 0; padding: 10px; border-left: 4px solid #0073aa; background: #f9f9f9; font-family: monospace; }
    .log-time { color: #666; font-size: 12px; }
    .log-message { margin-top: 5px; }
    .debug-icon { color: #0073aa; }
    .error-icon { color: #dc3232; }
    .success-icon { color: #46b450; }
    .refresh-btn { margin: 20px 0; padding: 10px 20px; background: #0073aa; color: white; border: none; cursor: pointer; }
    .filter-section { margin: 20px 0; padding: 15px; background: #f1f1f1; }
    .filter-input { padding: 5px; margin: 5px; width: 300px; }
</style>";

// Get WordPress debug log file path
$log_file = WP_CONTENT_DIR . '/debug.log';

echo "<div class='filter-section'>";
echo "<h3>Filter Logs</h3>";
echo "<input type='text' id='filterInput' class='filter-input' placeholder='Filter by keyword (e.g., AI Profit Transfer)' onkeyup='filterLogs()'>";
echo "<button onclick='clearFilter()' style='padding: 5px 10px; margin: 5px;'>Clear Filter</button>";
echo "<button onclick='location.reload()' class='refresh-btn'>🔄 Refresh Logs</button>";
echo "</div>";

echo "<div id='logContainer'>";

if (file_exists($log_file)) {
    $log_content = file_get_contents($log_file);
    $log_lines = explode("\n", $log_content);
    
    // Reverse array to show newest entries first
    $log_lines = array_reverse($log_lines);
    
    $entry_count = 0;
    foreach ($log_lines as $line) {
        if (empty(trim($line))) continue;
        
        // Parse log line
        if (preg_match('/^\[(.*?)\] (.*)$/', $line, $matches)) {
            $timestamp = $matches[1];
            $message = $matches[2];
            
            // Determine icon based on message content
            $icon = '🔍';
            if (strpos($message, '❌') !== false) {
                $icon = '❌';
            } elseif (strpos($message, '✅') !== false) {
                $icon = '✅';
            } elseif (strpos($message, '🔍') !== false) {
                $icon = '🔍';
            }
            
            echo "<div class='log-entry' data-message='" . htmlspecialchars($message) . "'>";
            echo "<div class='log-time'>{$timestamp}</div>";
            echo "<div class='log-message'>{$icon} " . htmlspecialchars($message) . "</div>";
            echo "</div>";
            
            $entry_count++;
            
            // Limit to last 100 entries to avoid overwhelming the page
            if ($entry_count >= 100) break;
        }
    }
    
    if ($entry_count == 0) {
        echo "<p>No log entries found or log file is empty.</p>";
    }
} else {
    echo "<p>Debug log file not found at: {$log_file}</p>";
    echo "<p>Make sure WP_DEBUG_LOG is enabled in wp-config.php:</p>";
    echo "<code>define('WP_DEBUG_LOG', true);</code>";
}

echo "</div>";

echo "<script>
function filterLogs() {
    const filter = document.getElementById('filterInput').value.toLowerCase();
    const entries = document.querySelectorAll('.log-entry');
    
    entries.forEach(entry => {
        const message = entry.getAttribute('data-message').toLowerCase();
        if (message.includes(filter)) {
            entry.style.display = 'block';
        } else {
            entry.style.display = 'none';
        }
    });
}

function clearFilter() {
    document.getElementById('filterInput').value = '';
    const entries = document.querySelectorAll('.log-entry');
    entries.forEach(entry => {
        entry.style.display = 'block';
    });
}
</script>";

echo "<div style='margin-top: 30px; padding: 15px; background: #e7f3ff; border-left: 4px solid #0073aa;'>";
echo "<h3>📝 How to Use This Debug Viewer</h3>";
echo "<ul>";
echo "<li><strong>Filter Logs:</strong> Use the filter box to search for specific debug messages</li>";
echo "<li><strong>Refresh:</strong> Click the refresh button to get the latest log entries</li>";
echo "<li><strong>Icons:</strong> 🔍 = Info, ❌ = Error, ✅ = Success</li>";
echo "<li><strong>Test the Profit Transfer:</strong> Run the test script first, then check these logs</li>";
echo "</ul>";
echo "</div>";
?>
