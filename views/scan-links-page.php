<?php
/**
 * Scan Links Page - Link scanning configuration and progress
 * 
 * @package XnY_404_Links
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="resource-panel">
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-800 mb-3 typing-animation">Scan Internal Links</h2>
        <p class="text-gray-600 text-lg">Discover and analyze all internal links on your WordPress site to identify potential 404 errors.</p>
    </div>

    <!-- Scan Configuration Section -->
    <div class="resource-card mb-6">
        <div class="card-icon scan floating-icon">
            <i class="fas fa-cog"></i>
        </div>
        <h3 class="card-title">Scan Configuration</h3>
        <div class="scan-config-form">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label for="scan-depth" class="block text-sm font-medium text-gray-700 mb-2">Scan Depth</label>
                    <select id="scan-depth" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="1">Pages only</option>
                        <option value="2" selected>Pages + Posts</option>
                        <option value="3">All content types</option>
                        <option value="4">Deep scan (including comments)</option>
                    </select>
                </div>
                <div>
                    <label for="max-pages" class="block text-sm font-medium text-gray-700 mb-2">Max Pages to Scan</label>
                    <input type="number" id="max-pages" value="100" min="10" max="1000" step="10" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>
            <div class="flex items-center mb-4">
                <input type="checkbox" id="include-external" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                <label for="include-external" class="ml-2 block text-sm text-gray-700">
                    Also check external links for broken references
                </label>
            </div>
        </div>
    </div>

    <!-- Scan Controls -->
    <div class="flex flex-wrap gap-4 mb-6">
        <button id="start-scan" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-lg transition-all duration-200 transform hover:scale-105 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
            <i class="fas fa-search mr-2"></i>
            Start Link Scan
        </button>
        <button id="stop-scan" class="bg-red-600 hover:bg-red-700 text-white font-bold py-3 px-6 rounded-lg transition-all duration-200 transform hover:scale-105 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 hidden">
            <i class="fas fa-stop mr-2"></i>
            Stop Scan
        </button>
        <button id="view-results" class="bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-6 rounded-lg transition-all duration-200 transform hover:scale-105 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 hidden">
            <i class="fas fa-eye mr-2"></i>
            View Results
        </button>
    </div>

    <!-- Progress Section -->
    <div id="scan-progress" class="resource-card mb-6 hidden">
        <h3 class="card-title mb-4">
            <i class="fas fa-spinner fa-spin mr-2"></i>
            Scanning in Progress...
        </h3>
        
        <!-- Overall Progress -->
        <div class="mb-4">
            <div class="flex justify-between text-sm text-gray-600 mb-1">
                <span>Overall Progress</span>
                <span id="overall-percentage">0%</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2.5">
                <div id="overall-progress-bar" class="bg-blue-600 h-2.5 rounded-full transition-all duration-300" style="width: 0%"></div>
            </div>
        </div>

        <!-- Current Status -->
        <div class="bg-gray-50 rounded-lg p-4 mb-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-center">
                <div>
                    <div class="text-2xl font-bold text-blue-600" id="pages-scanned">0</div>
                    <div class="text-sm text-gray-600">Pages Scanned</div>
                </div>
                <div>
                    <div class="text-2xl font-bold text-green-600" id="links-found">0</div>
                    <div class="text-sm text-gray-600">Links Found</div>
                </div>
                <div>
                    <div class="text-2xl font-bold text-red-600" id="broken-found">0</div>
                    <div class="text-sm text-gray-600">Broken Links</div>
                </div>
            </div>
        </div>

        <!-- Current Activity -->
        <div class="bg-blue-50 border-l-4 border-blue-400 p-4">
            <div class="flex">
                <div class="ml-3">
                    <p class="text-sm text-blue-700">
                        <strong>Currently scanning:</strong> <span id="current-page">Initializing...</span>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="resource-grid">
        <div class="resource-card">
            <div class="card-icon dashboard floating-icon">
                <i class="fas fa-chart-bar"></i>
            </div>
            <h3 class="card-title">Last Scan Results</h3>
            <p class="card-description">
                <span id="last-scan-date">No previous scans</span><br>
                <span id="last-scan-stats" class="text-sm text-gray-500">Run your first scan to see results</span>
            </p>
        </div>

        <div class="resource-card">
            <div class="card-icon scan floating-icon">
                <i class="fas fa-clock"></i>
            </div>
            <h3 class="card-title">Scan History</h3>
            <p class="card-description">
                View detailed history of all previous link scans and track improvements over time.
            </p>
            <a href="#" class="card-link" onclick="showScanHistory()">
                View History
            </a>
        </div>

        <div class="resource-card">
            <div class="card-icon link-check floating-icon">
                <i class="fas fa-download"></i>
            </div>
            <h3 class="card-title">Export Results</h3>
            <p class="card-description">
                Export scan results to CSV or PDF for external analysis and reporting.
            </p>
            <a href="#" class="card-link" onclick="exportResults()">
                Export Data
            </a>
        </div>
    </div>

    <!-- Scan History Modal -->
    <div id="scan-history-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-bold text-gray-900">Scan History</h3>
                    <button onclick="closeScanHistory()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div id="scan-history-content">
                    <p class="text-gray-600">Loading scan history...</p>
                </div>
            </div>
        </div>
    </div>
</div>
