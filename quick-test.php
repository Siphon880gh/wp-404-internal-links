<?php
/**
 * Quick Development Test
 */
require_once 'plugin.php';

echo "🧪 Quick Plugin Test\n";
echo "==================\n\n";

// Test plugin class
$plugin = new XnY_404_Links();
echo "✅ Plugin instantiated\n";

// Test method existence
$methods = ['ajax_start_scan', 'create_tables', 'process_scan'];
foreach ($methods as $method) {
    if (method_exists($plugin, $method)) {
        echo "✅ Method $method exists\n";
    } else {
        echo "❌ Method $method missing\n";
    }
}

echo "\n🎉 Quick test completed!\n";
echo "For full tests: Set up WordPress environment first\n";
