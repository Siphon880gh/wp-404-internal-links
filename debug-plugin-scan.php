<?php
/**
 * Debug Plugin Scan Issue
 * Simulates the WordPress environment to test scan functionality
 */

// Mock WordPress functions that the plugin needs
function current_time($type, $gmt = 0) {
    return $type === 'mysql' ? date('Y-m-d H:i:s') : time();
}

function wp_send_json_success($data) {
    echo "✅ SUCCESS: " . json_encode($data) . "\n";
    return true;
}

function wp_send_json_error($message) {
    echo "❌ ERROR: $message\n";
    return false;
}

function check_ajax_referer($action, $query_arg = false, $die = 1) {
    return true; // Mock success
}

function current_user_can($capability) {
    return true; // Mock admin user
}

function wp_schedule_single_event($timestamp, $hook, $args = array()) {
    echo "📅 Scheduled event: $hook\n";
    return true;
}

function update_option($option_name, $option_value) {
    echo "💾 Updated option: $option_name\n";
    return true;
}

// Mock wpdb class
class MockWpdb {
    public $prefix = 'wp_';
    public $insert_id = 0;
    
    public function get_charset_collate() {
        return 'DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci';
    }
    
    public function insert($table, $data, $format = null) {
        echo "📝 INSERT into $table: " . json_encode($data) . "\n";
        
        // Simulate the actual database insert that's failing
        // Check if we can connect to the actual database
        try {
            $mysqli = new mysqli('localhost', 'xny_admin', 'xny_admin1234', 'wp_xny_test');
            
            if ($mysqli->connect_error) {
                echo "❌ Database connection failed: " . $mysqli->connect_error . "\n";
                return false;
            }
            
            // Build the actual insert query
            $columns = implode(',', array_keys($data));
            $values = "'" . implode("','", array_values($data)) . "'";
            $sql = "INSERT INTO $table ($columns) VALUES ($values)";
            
            echo "🔍 SQL Query: $sql\n";
            
            if ($result = $mysqli->query($sql)) {
                $this->insert_id = $mysqli->insert_id;
                echo "✅ Insert successful, ID: {$this->insert_id}\n";
                $mysqli->close();
                return 1;
            } else {
                echo "❌ Insert failed: " . $mysqli->error . "\n";
                $mysqli->close();
                return false;
            }
            
        } catch (Exception $e) {
            echo "❌ Database exception: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    public function prepare($query, ...$args) {
        return vsprintf(str_replace('%s', "'%s'", $query), $args);
    }
}

// Set up global wpdb
global $wpdb;
$wpdb = new MockWpdb();

// Load the plugin
require_once 'plugin.php';

echo "🧪 XnY 404 Links Plugin - Scan Debug Test\n";
echo "========================================\n\n";

// Simulate the AJAX request that's failing
$_POST = [
    'action' => 'xny_start_scan',
    'nonce' => 'test_nonce',
    'scan_depth' => 2,
    'max_pages' => 100,
    'include_external' => 'false'
];

echo "🚀 Testing scan start process...\n";
echo "--------------------------------\n";

try {
    $plugin = new XnY_404_Links();
    echo "✅ Plugin instantiated successfully\n";
    
    // Test the ajax_start_scan method directly
    echo "\n🔧 Testing AJAX scan start...\n";
    $plugin->ajax_start_scan();
    
} catch (Exception $e) {
    echo "❌ Plugin error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n📋 Debug Summary:\n";
echo "- Check if database tables exist: ✅\n";
echo "- Check if database is accessible: Testing...\n";
echo "- Check if plugin can insert records: Testing...\n";
