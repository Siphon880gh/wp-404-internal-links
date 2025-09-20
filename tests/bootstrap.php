<?php
/**
 * PHPUnit Bootstrap File for XnY 404 Links Plugin
 * 
 * This file sets up the testing environment for the plugin.
 * It loads WordPress test suite and initializes the plugin for testing.
 */

// Define testing constants
define('XNY_PLUGIN_DIR', dirname(__DIR__));
define('XNY_PLUGIN_FILE', XNY_PLUGIN_DIR . '/plugin.php');

// Load Composer autoloader if available
if (file_exists(XNY_PLUGIN_DIR . '/vendor/autoload.php')) {
    require_once XNY_PLUGIN_DIR . '/vendor/autoload.php';
}

// Load Brain Monkey for WordPress function mocking
if (class_exists('Brain\Monkey')) {
    Brain\Monkey\setUp();
}

// WordPress test configuration
$_tests_dir = getenv('WP_TESTS_DIR');
if (!$_tests_dir) {
    $_tests_dir = '/tmp/wordpress-tests-lib';
}

// Load WordPress test functions
if (file_exists($_tests_dir . '/includes/functions.php')) {
    require_once $_tests_dir . '/includes/functions.php';
    
    /**
     * Manually load the plugin being tested.
     */
    function _manually_load_plugin() {
        require XNY_PLUGIN_FILE;
    }
    tests_add_filter('muplugins_loaded', '_manually_load_plugin');
    
    // Start up the WordPress testing environment
    require $_tests_dir . '/includes/bootstrap.php';
} else {
    // Fallback: Load plugin directly for unit tests without WordPress
    require_once XNY_PLUGIN_FILE;
}

// Load test helper classes
require_once __DIR__ . '/helpers/TestCase.php';
require_once __DIR__ . '/helpers/DatabaseTestCase.php';
require_once __DIR__ . '/helpers/AjaxTestCase.php';
require_once __DIR__ . '/helpers/MockHelper.php';

// Set up global test configuration
global $xny_test_config;
$xny_test_config = array(
    'test_db_prefix' => 'xny_test_',
    'mock_wp_functions' => true,
    'cleanup_after_tests' => true,
    'coverage_threshold' => 85
);

/**
 * Clean up function to run after all tests
 */
register_shutdown_function(function() {
    if (class_exists('Brain\Monkey')) {
        Brain\Monkey\tearDown();
    }
});
