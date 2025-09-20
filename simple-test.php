<?php
echo "XnY 404 Links Plugin - Test Validation\n";
echo "=====================================\n\n";

// Check if files exist
$files_to_check = [
    'plugin.php' => 'Main plugin file',
    'composer.json' => 'Composer configuration', 
    'phpunit.xml' => 'PHPUnit configuration',
    'tests/bootstrap.php' => 'Test bootstrap',
    'vendor/bin/phpunit' => 'PHPUnit executable'
];

echo "File Check:\n";
foreach ($files_to_check as $file => $description) {
    if (file_exists($file)) {
        echo "✅ $description: $file\n";
    } else {
        echo "❌ Missing $description: $file\n";
    }
}

// Check PHP version
echo "\nPHP Version Check:\n";
echo "✅ PHP Version: " . PHP_VERSION . "\n";

// Check if composer dependencies are installed
echo "\nDependency Check:\n";
if (file_exists('vendor/autoload.php')) {
    echo "✅ Composer dependencies installed\n";
} else {
    echo "❌ Run 'composer install' first\n";
}

// Check test files
echo "\nTest Files Check:\n";
$test_files = glob('tests/unit/*.php');
echo "✅ Found " . count($test_files) . " unit test files\n";

$integration_files = glob('tests/integration/*.php');
echo "✅ Found " . count($integration_files) . " integration test files\n";

echo "\nReady to run tests!\n";
echo "Run: composer test\n";
