<?php
/**
 * Test Database Connection for XnY 404 Links Plugin
 */

// WordPress database configuration
define('DB_NAME', 'wp_xny_test');
define('DB_USER', 'xny_admin');
define('DB_PASSWORD', 'xny_admin1234');
define('DB_HOST', 'localhost');
define('DB_CHARSET', 'utf8mb4');
define('DB_COLLATE', 'utf8mb4_unicode_ci');

// Create a simple wpdb-like class for testing
class TestWpdb {
    private $connection;
    public $prefix = 'wp_';
    public $insert_id = 0;
    
    public function __construct() {
        $this->connection = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
            DB_USER,
            DB_PASSWORD,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
    }
    
    public function insert($table, $data) {
        try {
            $columns = implode(',', array_keys($data));
            $placeholders = ':' . implode(', :', array_keys($data));
            
            $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
            $stmt = $this->connection->prepare($sql);
            
            foreach ($data as $key => $value) {
                $stmt->bindValue(":$key", $value);
            }
            
            $result = $stmt->execute();
            $this->insert_id = $this->connection->lastInsertId();
            
            return $result ? 1 : false;
        } catch (Exception $e) {
            echo "Database Error: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    public function get_charset_collate() {
        return 'DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci';
    }
}

echo "🔧 XnY 404 Links Plugin - Database Connection Test\n";
echo "================================================\n\n";

// Test database connection
try {
    $wpdb = new TestWpdb();
    echo "✅ Database connection successful\n";
    
    // Test table existence
    $tables_query = $wpdb->connection->query("SHOW TABLES LIKE '%xny%'");
    $table_count = $tables_query->rowCount();
    echo "✅ Found {$table_count} XnY plugin tables\n";
    
    // Test scan record insertion
    $scan_data = [
        'scan_date' => date('Y-m-d H:i:s'),
        'status' => 'running',
        'scan_config' => json_encode([
            'scan_depth' => 2,
            'max_pages' => 100,
            'include_external' => false
        ])
    ];
    
    $result = $wpdb->insert('wp_xny_404_scans', $scan_data);
    
    if ($result) {
        echo "✅ Test scan record created successfully (ID: {$wpdb->insert_id})\n";
        
        // Test broken link insertion
        $link_data = [
            'scan_id' => $wpdb->insert_id,
            'source_url' => 'https://example.com/test-page',
            'target_url' => 'https://example.com/broken-link',
            'link_text' => 'Test Broken Link',
            'status_code' => 404,
            'error_message' => 'Page not found',
            'is_broken' => 1,
            'found_at' => date('Y-m-d H:i:s')
        ];
        
        $link_result = $wpdb->insert('wp_xny_404_links', $link_data);
        
        if ($link_result) {
            echo "✅ Test broken link record created successfully\n";
        } else {
            echo "❌ Failed to create broken link record\n";
        }
        
    } else {
        echo "❌ Failed to create scan record\n";
    }
    
    // Show current records
    echo "\n📊 Current Database State:\n";
    echo "------------------------\n";
    
    $scans = $wpdb->connection->query("SELECT COUNT(*) as count FROM wp_xny_404_scans")->fetch();
    echo "Scan records: {$scans['count']}\n";
    
    $links = $wpdb->connection->query("SELECT COUNT(*) as count FROM wp_xny_404_links")->fetch();
    echo "Link records: {$links['count']}\n";
    
    echo "\n🎉 Database test completed successfully!\n";
    echo "The plugin should now be able to create scan records.\n";
    
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
    echo "Please check your database credentials and server.\n";
}
