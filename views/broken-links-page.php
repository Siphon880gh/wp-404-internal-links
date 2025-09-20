<?php
/**
 * Broken Links Page - Report and management interface
 * 
 * @package XnY_404_Links
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="resource-panel">
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-800 mb-3 typing-animation">Broken Links Report</h2>
        <p class="text-gray-600 text-lg">Detailed analysis of all broken internal links found on your WordPress site with actionable fix suggestions.</p>
    </div>

    <!-- Summary Statistics -->
    <div class="resource-grid mb-6">
        <div class="resource-card">
            <div class="card-icon broken floating-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h3 class="card-title">Critical Issues</h3>
            <div class="text-3xl font-bold text-red-600 mb-2" id="critical-count">0</div>
            <p class="card-description text-sm">404 errors that need immediate attention</p>
        </div>

        <div class="resource-card">
            <div class="card-icon scan floating-icon">
                <i class="fas fa-exclamation-circle"></i>
            </div>
            <h3 class="card-title">Warning Issues</h3>
            <div class="text-3xl font-bold text-yellow-600 mb-2" id="warning-count">0</div>
            <p class="card-description text-sm">Redirects and potential issues</p>
        </div>

        <div class="resource-card">
            <div class="card-icon dashboard floating-icon">
                <i class="fas fa-chart-pie"></i>
            </div>
            <h3 class="card-title">Total Links Checked</h3>
            <div class="text-3xl font-bold text-blue-600 mb-2" id="total-links">0</div>
            <p class="card-description text-sm">Links scanned in last analysis</p>
        </div>

        <div class="resource-card">
            <div class="card-icon link-check floating-icon">
                <i class="fas fa-clock"></i>
            </div>
            <h3 class="card-title">Last Scan</h3>
            <div class="text-lg font-bold text-gray-600 mb-2" id="last-scan-time">Never</div>
            <p class="card-description text-sm">Most recent scan completion</p>
        </div>
    </div>

    <!-- Filter and Search -->
    <div class="resource-card mb-6">
        <div class="flex flex-wrap gap-4 items-center justify-between">
            <div class="flex flex-wrap gap-4 items-center">
                <div>
                    <label for="filter-status" class="block text-sm font-medium text-gray-700 mb-1">Filter by Status</label>
                    <select id="filter-status" class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="all">All Issues</option>
                        <option value="404">404 Errors</option>
                        <option value="redirect">Redirects</option>
                        <option value="timeout">Timeouts</option>
                        <option value="other">Other Issues</option>
                    </select>
                </div>
                <div>
                    <label for="filter-type" class="block text-sm font-medium text-gray-700 mb-1">Content Type</label>
                    <select id="filter-type" class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="all">All Types</option>
                        <option value="page">Pages</option>
                        <option value="post">Posts</option>
                        <option value="custom">Custom Post Types</option>
                    </select>
                </div>
            </div>
            <div class="flex gap-2">
                <button id="refresh-scan" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg transition-all duration-200">
                    <i class="fas fa-sync-alt mr-2"></i>
                    Refresh
                </button>
                <button id="export-report" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-lg transition-all duration-200">
                    <i class="fas fa-download mr-2"></i>
                    Export
                </button>
            </div>
        </div>
        <div class="mt-4">
            <input type="text" id="search-links" placeholder="Search links, pages, or error messages..." 
                   class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
    </div>

    <!-- Broken Links Table -->
    <div class="resource-card">
        <div class="flex justify-between items-center mb-4">
            <h3 class="card-title">Broken Links Details</h3>
            <div class="flex gap-2">
                <button id="fix-all-btn" class="bg-orange-600 hover:bg-orange-700 text-white font-bold py-2 px-4 rounded-lg transition-all duration-200">
                    <i class="fas fa-tools mr-2"></i>
                    Bulk Fix
                </button>
                <button id="ignore-all-btn" class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded-lg transition-all duration-200">
                    <i class="fas fa-eye-slash mr-2"></i>
                    Ignore Selected
                </button>
            </div>
        </div>

        <!-- Loading State -->
        <div id="links-loading" class="text-center py-8">
            <div class="spinner inline-block"></div>
            <p class="mt-4 text-gray-600">Loading broken links...</p>
        </div>

        <!-- Empty State -->
        <div id="no-broken-links" class="text-center py-12 hidden">
            <div class="text-6xl text-green-500 mb-4">
                <i class="fas fa-check-circle"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-800 mb-2">No Broken Links Found!</h3>
            <p class="text-gray-600 mb-4">Your website's internal links are all working perfectly.</p>
            <button onclick="window.location.href='?page=xny-404-links&tab=scan'" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg">
                Run New Scan
            </button>
        </div>

        <!-- Links Table -->
        <div id="broken-links-table" class="hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <input type="checkbox" id="select-all-links" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Broken Link
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Found On
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Status
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody id="broken-links-tbody" class="bg-white divide-y divide-gray-200">
                        <!-- Dynamic content will be inserted here -->
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <div class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
                <div class="flex-1 flex justify-between sm:hidden">
                    <button id="prev-page-mobile" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                        Previous
                    </button>
                    <button id="next-page-mobile" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                        Next
                    </button>
                </div>
                <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm text-gray-700">
                            Showing <span id="showing-from">1</span> to <span id="showing-to">10</span> of <span id="total-results">0</span> results
                        </p>
                    </div>
                    <div>
                        <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" id="pagination-nav">
                            <!-- Pagination buttons will be inserted here -->
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Fix Link Modal -->
    <div id="fix-link-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-bold text-gray-900">Fix Broken Link</h3>
                    <button onclick="closeFixModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div id="fix-link-content">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Broken Link:</label>
                        <input type="text" id="broken-link-url" readonly class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50">
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Replacement URL:</label>
                        <input type="text" id="replacement-url" placeholder="Enter the correct URL..." class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Fix Options:</label>
                        <div class="space-y-2">
                            <label class="flex items-center">
                                <input type="radio" name="fix-option" value="replace" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300" checked>
                                <span class="ml-2 text-sm text-gray-700">Replace this link everywhere</span>
                            </label>
                            <label class="flex items-center">
                                <input type="radio" name="fix-option" value="remove" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300">
                                <span class="ml-2 text-sm text-gray-700">Remove this link</span>
                            </label>
                            <label class="flex items-center">
                                <input type="radio" name="fix-option" value="ignore" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300">
                                <span class="ml-2 text-sm text-gray-700">Ignore this link</span>
                            </label>
                        </div>
                    </div>
                    <div class="flex justify-end gap-3">
                        <button onclick="closeFixModal()" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                            Cancel
                        </button>
                        <button onclick="applyFix()" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                            Apply Fix
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
