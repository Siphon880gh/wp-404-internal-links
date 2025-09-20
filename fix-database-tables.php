<?php
/**
 * XnY 404 Links - Database Table Fix Script
 * Run this file directly in your WordPress root directory
 */

// Try to load WordPress
if (file_exists('./wp-config.php')) {
    require_once('./wp-config.php');
    require_once('./wp-includes/wp-db.php');
    
    echo "🔧 XnY 404 Links - Database Fix Script\n";
    echo "====================================\n\n";
    
    // Create wpdb instance
    $wpdb = new wpdb(DB_USER, DB_PASSWORD, DB_NAME, DB_HOST);
    
    if ($wpdb->last_error) {
        echo "❌ Database connection failed: " . $wpdb->last_error . "\n";
        exit(1);
    }
    
    echo "✅ Connected to WordPress database: " . DB_NAME . "\n";
    echo "✅ Table prefix: " . $wpdb->prefix . "\n\n";
    
    // Create tables
    $scans_table = $wpdb->prefix . 'xny_404_scans';
    $links_table = $wpdb->prefix . 'xny_404_links';
    
    $charset_collate = $wpdb->get_charset_collate();
    
    // Scans table SQL
    $sql1 = "CREATE TABLE IF NOT EXISTS $scans_table (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        scan_date datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        status varchar(20) DEFAULT 'running' NOT NULL,
        total_pages int(11) DEFAULT 0 NOT NULL,
        total_links int(11) DEFAULT 0 NOT NULL,
        broken_links int(11) DEFAULT 0 NOT NULL,
        scan_config text,
        completed_at datetime,
        PRIMARY KEY (id)
    ) $charset_collate;";
    
    // Links table SQL
    $sql2 = "CREATE TABLE IF NOT EXISTS $links_table (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        scan_id mediumint(9) NOT NULL,
        source_url varchar(500) NOT NULL,
        target_url varchar(500) NOT NULL,
        link_text varchar(255),
        status_code int(3),
        error_message text,
        is_broken tinyint(1) DEFAULT 0,
        is_fixed tinyint(1) DEFAULT 0,
        found_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY (id),
        KEY scan_id (scan_id),
        KEY is_broken (is_broken)
    ) $charset_collate;";
    
    // Execute table creation
    echo "🔨 Creating tables...\n";
    
    $result1 = $wpdb->query($sql1);
    if ($result1 === false) {
        echo "❌ Failed to create scans table: " . $wpdb->last_error . "\n";
    } else {
        echo "✅ Scans table created: $scans_table\n";
    }
    
    $result2 = $wpdb->query($sql2);
    if ($result2 === false) {
        echo "❌ Failed to create links table: " . $wpdb->last_error . "\n";
    } else {
        echo "✅ Links table created: $links_table\n";
    }
    
    // Verify tables exist
    echo "\n🔍 Verifying tables...\n";
    $tables = $wpdb->get_results("SHOW TABLES LIKE '{$wpdb->prefix}xny_%'");
    
    foreach ($tables as $table) {
        $table_name = array_values((array)$table)[0];
        echo "✅ Found table: $table_name\n";
    }
    
    // Test insert
    echo "\n🧪 Testing scan record creation...\n";
    $test_data = array(
        'scan_date' => current_time('mysql'),
        'status' => 'running',
        'scan_config' => json_encode(array(
            'scan_depth' => 2,
            'max_pages' => 100,
            'include_external' => false
        ))
    );
    
    $insert_result = $wpdb->insert($scans_table, $test_data);
    
    if ($insert_result) {
        echo "✅ Test scan record created successfully (ID: {$wpdb->insert_id})\n";
        
        // Clean up test record
        $wpdb->delete($scans_table, array('id' => $wpdb->insert_id));
        echo "✅ Test record cleaned up\n";
    } else {
        echo "❌ Failed to create test record: " . $wpdb->last_error . "\n";
    }
    
    echo "\n🎉 Database fix completed!\n";
    echo "You can now try running a scan in the WordPress admin.\n";
    
} else {
    echo "❌ WordPress not found. Please run this script from your WordPress root directory.\n";
    echo "Or copy the SQL commands and run them manually in your database.\n";
}
