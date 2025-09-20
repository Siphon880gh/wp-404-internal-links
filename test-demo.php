<?php
/**
 * Simple Test Demonstration
 */

require_once 'plugin.php';

echo "=== XnY 404 Links Plugin Test Demo ===\n\n";

// Test 1: Plugin Class Instantiation
echo "Test 1: Plugin Class Instantiation\n";
try {
    $plugin = new XnY_404_Links();
    echo "✅ PASS: Plugin class created successfully\n";
} catch (Exception $e) {
    echo "❌ FAIL: " . $e->getMessage() . "\n";
}

// Test 2: Check Required Methods Exist
echo "\nTest 2: Required Methods Check\n";
$required_methods = [
    'ajax_start_scan',
    'ajax_get_broken_links', 
    'ajax_fix_link',
    'create_tables',
    'process_scan'
];

$reflection = new ReflectionClass('XnY_404_Links');
foreach ($required_methods as $method) {
    if ($reflection->hasMethod($method)) {
        echo "✅ PASS: Method $method exists\n";
    } else {
        echo "❌ FAIL: Method $method missing\n";
    }
}

// Test 3: Link Processing Test
echo "\nTest 3: Link Processing Logic\n";
$plugin = new XnY_404_Links();
$reflection = new ReflectionClass($plugin);
$method = $reflection->getMethod('extract_links_from_content');
$method->setAccessible(true);

$test_html = '<p><a href="https://example.com/test">Test Link</a></p>';
try {
    $links = $method->invokeArgs($plugin, [$test_html, 1]);
    if (is_array($links) && count($links) > 0) {
        echo "✅ PASS: Link extraction works (found " . count($links) . " links)\n";
    } else {
        echo "❌ FAIL: Link extraction returned empty result\n";
    }
} catch (Exception $e) {
    echo "❌ FAIL: Link extraction error: " . $e->getMessage() . "\n";
}

echo "\n=== Test Summary ===\n";
echo "Basic functionality tests completed!\n";
echo "For full test suite, run: composer run test:coverage\n";
