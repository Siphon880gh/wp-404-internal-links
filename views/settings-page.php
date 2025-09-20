<?php
/**
 * Settings Page - Plugin configuration and preferences
 * 
 * @package XnY_404_Links
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="resource-panel">
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-800 mb-3 typing-animation">Plugin Settings</h2>
        <p class="text-gray-600 text-lg">Configure your 404 Broken Internal Links plugin settings and preferences.</p>
    </div>

    <!-- Coming Soon Section -->
    <div class="resource-card text-center py-12">
        <div class="mb-6">
            <div class="text-8xl text-blue-500 mb-4">
                <i class="fas fa-cog"></i>
            </div>
            <h3 class="text-3xl font-bold text-gray-800 mb-4">Settings Coming Soon</h3>
            <p class="text-lg text-gray-600 mb-6 max-w-2xl mx-auto">
                We're working hard to bring you comprehensive settings to customize your link scanning experience. 
                Advanced configuration options will be available in the next update.
            </p>
        </div>

        <!-- Preview of upcoming features -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
            <div class="bg-gray-50 rounded-lg p-4 border-2 border-dashed border-gray-300">
                <div class="text-2xl text-gray-400 mb-2">
                    <i class="fas fa-clock"></i>
                </div>
                <h4 class="font-semibold text-gray-600 mb-2">Scan Scheduling</h4>
                <p class="text-sm text-gray-500">Automatic periodic scans</p>
            </div>

            <div class="bg-gray-50 rounded-lg p-4 border-2 border-dashed border-gray-300">
                <div class="text-2xl text-gray-400 mb-2">
                    <i class="fas fa-bell"></i>
                </div>
                <h4 class="font-semibold text-gray-600 mb-2">Notifications</h4>
                <p class="text-sm text-gray-500">Email alerts for broken links</p>
            </div>

            <div class="bg-gray-50 rounded-lg p-4 border-2 border-dashed border-gray-300">
                <div class="text-2xl text-gray-400 mb-2">
                    <i class="fas fa-filter"></i>
                </div>
                <h4 class="font-semibold text-gray-600 mb-2">Exclusion Rules</h4>
                <p class="text-sm text-gray-500">Skip certain URLs or patterns</p>
            </div>

            <div class="bg-gray-50 rounded-lg p-4 border-2 border-dashed border-gray-300">
                <div class="text-2xl text-gray-400 mb-2">
                    <i class="fas fa-users"></i>
                </div>
                <h4 class="font-semibold text-gray-600 mb-2">User Permissions</h4>
                <p class="text-sm text-gray-500">Control who can scan and fix links</p>
            </div>

            <div class="bg-gray-50 rounded-lg p-4 border-2 border-dashed border-gray-300">
                <div class="text-2xl text-gray-400 mb-2">
                    <i class="fas fa-download"></i>
                </div>
                <h4 class="font-semibold text-gray-600 mb-2">Export Options</h4>
                <p class="text-sm text-gray-500">Customize report formats</p>
            </div>

            <div class="bg-gray-50 rounded-lg p-4 border-2 border-dashed border-gray-300">
                <div class="text-2xl text-gray-400 mb-2">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <h4 class="font-semibold text-gray-600 mb-2">Security Settings</h4>
                <p class="text-sm text-gray-500">Rate limiting and safety controls</p>
            </div>
        </div>

        <!-- Current basic info -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-6">
            <h4 class="text-lg font-semibold text-blue-800 mb-3">Current Plugin Information</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-left">
                <div>
                    <strong class="text-blue-700">Plugin Version:</strong>
                    <span class="text-blue-600 ml-2">1.0.0</span>
                </div>
                <div>
                    <strong class="text-blue-700">WordPress Version:</strong>
                    <span class="text-blue-600 ml-2"><?php echo get_bloginfo('version'); ?></span>
                </div>
                <div>
                    <strong class="text-blue-700">Database Version:</strong>
                    <span class="text-blue-600 ml-2"><?php echo get_option('db_version'); ?></span>
                </div>
                <div>
                    <strong class="text-blue-700">PHP Version:</strong>
                    <span class="text-blue-600 ml-2"><?php echo PHP_VERSION; ?></span>
                </div>
            </div>
        </div>

        <!-- Newsletter signup -->
        <div class="bg-gradient-to-r from-blue-500 to-purple-600 rounded-lg p-6 text-white">
            <h4 class="text-xl font-bold mb-3">Stay Updated</h4>
            <p class="mb-4">Be the first to know when new features and settings become available!</p>
            <div class="flex flex-col sm:flex-row gap-3 justify-center items-center max-w-md mx-auto">
                <input type="email" placeholder="Enter your email..." 
                       class="flex-1 px-4 py-2 rounded-md text-gray-800 focus:outline-none focus:ring-2 focus:ring-white">
                <button class="bg-white text-blue-600 font-bold py-2 px-6 rounded-md hover:bg-gray-100 transition-colors duration-200">
                    Notify Me
                </button>
            </div>
        </div>

        <!-- Action buttons -->
        <div class="flex flex-wrap gap-4 justify-center mt-8">
            <a href="?page=xny-404-links&tab=scan" 
               class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-lg transition-all duration-200 transform hover:scale-105">
                <i class="fas fa-search mr-2"></i>
                Start Scanning Links
            </a>
            <a href="?page=xny-404-links&tab=broken-links" 
               class="bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-6 rounded-lg transition-all duration-200 transform hover:scale-105">
                <i class="fas fa-eye mr-2"></i>
                View Reports
            </a>
        </div>
    </div>

    <!-- Support section -->
    <div class="resource-grid mt-8">
        <div class="resource-card">
            <div class="card-icon dashboard floating-icon">
                <i class="fas fa-life-ring"></i>
            </div>
            <h3 class="card-title">Need Help?</h3>
            <p class="card-description">
                Contact our support team for assistance with the plugin or feature requests.
            </p>
            <a href="mailto:support@wengindustries.com" class="card-link">
                Contact Support
            </a>
        </div>

        <div class="resource-card">
            <div class="card-icon scan floating-icon">
                <i class="fas fa-star"></i>
            </div>
            <h3 class="card-title">Rate This Plugin</h3>
            <p class="card-description">
                Help others discover this plugin by leaving a review on WordPress.org.
            </p>
            <a href="#" class="card-link">
                Leave Review
            </a>
        </div>

        <div class="resource-card">
            <div class="card-icon link-check floating-icon">
                <i class="fas fa-code"></i>
            </div>
            <h3 class="card-title">Developer Resources</h3>
            <p class="card-description">
                Access documentation and resources for extending this plugin's functionality.
            </p>
            <a href="https://wengindustries.com/docs" target="_blank" class="card-link">
                View Documentation
            </a>
        </div>
    </div>
</div>
