<?php
/**
 * Dashboard Page - Main overview and navigation
 * 
 * @package XnY_404_Links
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="resource-panel">
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-800 mb-3 typing-animation">404 Broken Internal Links Dashboard</h2>
        <p class="text-gray-600 text-lg">Monitor and fix broken internal links on your WordPress site to improve SEO and user experience.</p>
    </div>

    <div class="resource-grid">
        <div class="resource-card">
            <div class="card-icon dashboard floating-icon">
                <i class="fas fa-chart-line"></i>
            </div>
            <h3 class="card-title">Link Status Overview</h3>
            <p class="card-description">
                Get a comprehensive overview of all internal links on your site and their current status.
            </p>
            <a href="?page=xny-404-links&tab=dashboard" class="card-link">
                View Dashboard
            </a>
        </div>

        <div class="resource-card">
            <div class="card-icon scan floating-icon">
                <i class="fas fa-search"></i>
            </div>
            <h3 class="card-title">Scan Internal Links</h3>
            <p class="card-description">
                Automatically scan your website to discover all internal links and identify broken ones.
            </p>
            <a href="?page=xny-404-links&tab=scan" class="card-link">
                Start Scan
            </a>
        </div>

        <div class="resource-card">
            <div class="card-icon broken floating-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h3 class="card-title">Broken Links Report</h3>
            <p class="card-description">
                View detailed reports of all broken internal links found on your site with fix suggestions.
            </p>
            <a href="?page=xny-404-links&tab=broken-links" class="card-link">
                View Report
            </a>
        </div>

        <div class="resource-card">
            <div class="card-icon link-check floating-icon">
                <i class="fas fa-link"></i>
            </div>
            <h3 class="card-title">Link Validation</h3>
            <p class="card-description">
                Validate and test individual links to ensure they're working correctly and redirect properly.
            </p>
            <a href="?page=xny-404-links&tab=scan" class="card-link">
                Validate Links
            </a>
        </div>

        <div class="resource-card">
            <div class="card-icon settings floating-icon">
                <i class="fas fa-cog"></i>
            </div>
            <h3 class="card-title">Plugin Settings</h3>
            <p class="card-description">
                Configure scan frequency, notification settings, and customize how broken links are handled.
            </p>
            <a href="?page=xny-404-links&tab=settings" class="card-link">
                Configure Settings
            </a>
        </div>

        <div class="resource-card">
            <div class="card-icon dashboard floating-icon">
                <i class="fas fa-history"></i>
            </div>
            <h3 class="card-title">Scan History</h3>
            <p class="card-description">
                Review previous scans and track improvements in your site's link health over time.
            </p>
            <a href="?page=xny-404-links&tab=dashboard" class="card-link">
                View History
            </a>
        </div>
    </div>
</div>
