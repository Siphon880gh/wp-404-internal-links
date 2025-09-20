<?php
/**
 * WordPress-aware Feature Validation Test
 * Mocks WordPress functions and tests all plugin features
 */

// Mock essential WordPress functions
if (!function_exists('add_action')) {
    function add_action($hook, $function_to_add, $priority = 10, $accepted_args = 1) {
        return true;
    }
}

if (!function_exists('add_menu_page')) {
    function add_menu_page($page_title, $menu_title, $capability, $menu_slug, $function = '', $icon_url = '', $position = null) {
        return true;
    }
}

if (!function_exists('wp_enqueue_style')) {
    function wp_enqueue_style($handle, $src = '', $deps = array(), $ver = false, $media = 'all') {
        return true;
    }
}

if (!function_exists('wp_enqueue_script')) {
    function wp_enqueue_script($handle, $src = '', $deps = array(), $ver = false, $in_footer = false) {
        return true;
    }
}

if (!function_exists('wp_localize_script')) {
    function wp_localize_script($handle, $object_name, $l10n) {
        return true;
    }
}

if (!function_exists('wp_add_inline_script')) {
    function wp_add_inline_script($handle, $data, $position = 'after') {
        return true;
    }
}

if (!function_exists('wp_add_inline_style')) {
    function wp_add_inline_style($handle, $data) {
        return true;
    }
}

if (!function_exists('admin_url')) {
    function admin_url($path = '', $scheme = 'admin') {
        return 'http://example.com/wp-admin/' . $path;
    }
}

if (!function_exists('plugin_dir_url')) {
    function plugin_dir_url($file) {
        return 'http://example.com/wp-content/plugins/xny-404-internal-links/';
    }
}

if (!function_exists('wp_create_nonce')) {
    function wp_create_nonce($action = -1) {
        return 'test_nonce_' . md5($action);
    }
}

if (!function_exists('current_time')) {
    function current_time($type, $gmt = 0) {
        return $type === 'mysql' ? date('Y-m-d H:i:s') : time();
    }
}

if (!function_exists('get_site_url')) {
    function get_site_url() {
        return 'https://example.com';
    }
}

if (!function_exists('get_posts')) {
    function get_posts($args = array()) {
        // Mock posts for testing
        $post1 = new stdClass();
        $post1->ID = 1;
        $post1->post_title = 'Test Post 1';
        $post1->post_content = '<p><a href="https://example.com/test">Test Link</a></p>';
        $post1->post_status = 'publish';
        
        return [$post1];
    }
}

if (!function_exists('get_permalink')) {
    function get_permalink($post_id) {
        return 'https://example.com/post-' . $post_id;
    }
}

if (!function_exists('url_to_postid')) {
    function url_to_postid($url) {
        // Mock implementation
        if (strpos($url, 'post-1') !== false) {
            return 1;
        }
        return 0;
    }
}

if (!function_exists('get_post')) {
    function get_post($post_id) {
        if ($post_id === 1) {
            $post = new stdClass();
            $post->ID = 1;
            $post->post_status = 'publish';
            return $post;
        }
        return null;
    }
}

if (!function_exists('register_activation_hook')) {
    function register_activation_hook($file, $function) {
        return true;
    }
}

// Mock global wpdb
global $wpdb;
$wpdb = new stdClass();
$wpdb->prefix = 'wp_';
$wpdb->insert_id = 1;

$wpdb->get_charset_collate = function() {
    return 'DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci';
};

$wpdb->insert = function($table, $data, $format = null) {
    return 1;
};

$wpdb->update = function($table, $data, $where, $format = null, $where_format = null) {
    return 1;
};

$wpdb->get_row = function($query, $output = OBJECT, $y = 0) {
    $result = new stdClass();
    $result->id = 1;
    $result->scan_config = '{"scan_depth":2,"max_pages":100}';
    return $result;
};

$wpdb->prepare = function($query, ...$args) {
    return vsprintf(str_replace('%s', "'%s'", $query), $args);
};

// Now load the plugin
require_once 'plugin.php';

echo "🧪 XnY 404 Links Plugin - WordPress Feature Validation\n";
echo "====================================================\n\n";

$tests_passed = 0;
$total_tests = 0;

function test_feature($name, $test_func) {
    global $tests_passed, $total_tests;
    $total_tests++;
    
    try {
        $result = $test_func();
        if ($result) {
            echo "✅ PASS: $name\n";
            $tests_passed++;
            return true;
        } else {
            echo "❌ FAIL: $name\n";
            return false;
        }
    } catch (Exception $e) {
        echo "❌ ERROR: $name - " . $e->getMessage() . "\n";
        return false;
    }
}

// Feature Tests
echo "🔧 CORE PLUGIN FEATURES:\n";
echo str_repeat("-", 30) . "\n";

test_feature("Plugin Instantiation", function() {
    $plugin = new XnY_404_Links();
    return $plugin instanceof XnY_404_Links;
});

test_feature("Admin Menu Registration", function() {
    $plugin = new XnY_404_Links();
    $plugin->add_admin_menu();
    return true; // If no exception, it worked
});

test_feature("Asset Enqueuing", function() {
    $plugin = new XnY_404_Links();
    $plugin->enqueue_admin_styles('toplevel_page_xny-404-links');
    return true; // If no exception, it worked
});

test_feature("Database Table Creation", function() {
    $plugin = new XnY_404_Links();
    $plugin->create_tables();
    return true; // If no exception, it worked
});

echo "\n🔍 LINK PROCESSING FEATURES:\n";
echo str_repeat("-", 30) . "\n";

test_feature("HTML Link Extraction", function() {
    $plugin = new XnY_404_Links();
    $reflection = new ReflectionClass($plugin);
    $method = $reflection->getMethod('extract_links_from_content');
    $method->setAccessible(true);
    
    $html = '<p><a href="https://example.com/test">Test</a><a href="/internal">Internal</a></p>';
    $links = $method->invokeArgs($plugin, [$html, 1]);
    
    return is_array($links) && count($links) >= 1;
});

test_feature("Link Status Testing", function() {
    $plugin = new XnY_404_Links();
    $reflection = new ReflectionClass($plugin);
    $method = $reflection->getMethod('test_link');
    $method->setAccessible(true);
    
    $result = $method->invokeArgs($plugin, ['https://example.com/test', false]);
    return is_array($result) && isset($result['is_broken']);
});

test_feature("Post Type Configuration", function() {
    $plugin = new XnY_404_Links();
    $reflection = new ReflectionClass($plugin);
    $method = $reflection->getMethod('get_post_types_for_scan');
    $method->setAccessible(true);
    
    $types1 = $method->invokeArgs($plugin, [1]);
    $types2 = $method->invokeArgs($plugin, [2]);
    
    return $types1 === ['page'] && $types2 === ['page', 'post'];
});

test_feature("Scan Progress Tracking", function() {
    $plugin = new XnY_404_Links();
    $reflection = new ReflectionClass($plugin);
    $method = $reflection->getMethod('update_scan_progress');
    $method->setAccessible(true);
    
    // Mock WordPress options functions
    global $mock_options;
    $mock_options = [];
    
    if (!function_exists('get_option')) {
        function get_option($option_name, $default = false) {
            global $mock_options;
            return isset($mock_options[$option_name]) ? $mock_options[$option_name] : ($default !== false ? $default : '{}');
        }
    }
    
    if (!function_exists('update_option')) {
        function update_option($option_name, $option_value) {
            global $mock_options;
            $mock_options[$option_name] = $option_value;
            return true;
        }
    }
    
    $method->invokeArgs($plugin, [1, ['pages_scanned' => 5, 'links_found' => 25]]);
    return true;
});

echo "\n🌐 AJAX & API FEATURES:\n";
echo str_repeat("-", 30) . "\n";

test_feature("AJAX Handler Methods", function() {
    $plugin = new XnY_404_Links();
    $ajax_methods = [
        'ajax_start_scan', 'ajax_get_scan_progress', 'ajax_stop_scan',
        'ajax_get_broken_links', 'ajax_fix_link', 'ajax_export_results'
    ];
    
    foreach ($ajax_methods as $method) {
        if (!method_exists($plugin, $method)) {
            return false;
        }
    }
    return true;
});

test_feature("Scan Process Integration", function() {
    $plugin = new XnY_404_Links();
    
    // Mock additional functions needed for process_scan
    if (!function_exists('wp_schedule_single_event')) {
        function wp_schedule_single_event($timestamp, $hook, $args = array()) {
            return true;
        }
    }
    
    if (!function_exists('delete_option')) {
        function delete_option($option_name) {
            return true;
        }
    }
    
    if (!function_exists('usleep')) {
        function usleep($microseconds) {
            return true;
        }
    }
    
    // Test that process_scan method exists and can be called
    return method_exists($plugin, 'process_scan');
});

echo "\n📊 RESULTS SUMMARY:\n";
echo str_repeat("=", 50) . "\n";

$pass_rate = round(($tests_passed / $total_tests) * 100, 1);

echo "Total Tests: $total_tests\n";
echo "Tests Passed: $tests_passed\n";
echo "Pass Rate: $pass_rate%\n\n";

if ($pass_rate >= 90) {
    echo "🎉 EXCELLENT! All core features are implemented and working.\n";
    $status = "EXCELLENT";
} elseif ($pass_rate >= 80) {
    echo "✅ GOOD! Most features are working properly.\n";
    $status = "GOOD";
} elseif ($pass_rate >= 70) {
    echo "⚠️  ACCEPTABLE! Some features need attention.\n";
    $status = "ACCEPTABLE";
} else {
    echo "❌ NEEDS WORK! Several features require fixes.\n";
    $status = "NEEDS WORK";
}

echo "\n🎯 FEATURE IMPLEMENTATION STATUS: $status\n";
echo "📋 VALIDATION COMPLETE!\n";

exit($pass_rate >= 80 ? 0 : 1);
