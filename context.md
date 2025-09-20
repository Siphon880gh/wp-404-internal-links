# XnY 404 Broken Internal Links - Development Context

## Overview
WordPress plugin that automatically detects, analyzes, and manages broken internal links across WordPress sites. Provides comprehensive scanning, reporting, and link management capabilities through a modern admin interface.

**Purpose**: Improve SEO and user experience by identifying and fixing broken internal links before they impact visitors and search rankings.

## Tech Stack
- **Backend**: PHP 7.4+, WordPress 5.0+
- **Frontend**: JavaScript (jQuery), Tailwind CSS 3.4.17, Font Awesome 6.6.0
- **Database**: WordPress custom tables with MySQL
- **Architecture**: AJAX-powered SPA-like admin interface

## Architecture Overview

### File Structure (553 total lines)
```
xny-404-internal-links/
├── plugin.php (553 lines)           # Main plugin class, AJAX handlers, database
├── views/                           # UI templates
│   ├── dashboard-page.php (99 lines)    # Navigation hub with feature cards
│   ├── scan-links-page.php (172 lines)  # Scan configuration & progress
│   ├── broken-links-page.php (237 lines) # Results table & management
│   └── settings-page.php (176 lines)     # Future settings (placeholder)
├── css/plugins.css (652 lines)      # Animations, responsive design, themes
├── js/plugins.js (749 lines)        # AJAX interactions, UI animations
└── assets/                          # Icons and screenshots
```

### Core Components

#### 1. Main Plugin Class (`XnY_404_Links`)
```php
// plugin.php:19-551
class XnY_404_Links {
    // Admin menu and UI rendering
    public function add_admin_menu()        # Creates top-level admin menu
    public function render_404_page()       # Tabbed interface controller
    
    // AJAX handlers for scanning
    public function ajax_start_scan()       # Initiates background scan
    public function ajax_get_scan_progress() # Real-time progress updates
    public function ajax_stop_scan()        # Cancels running scan
    
    // AJAX handlers for link management  
    public function ajax_get_broken_links() # Paginated results with filters
    public function ajax_fix_link()         # Fix/ignore individual links
    public function ajax_export_results()   # CSV export functionality
}
```

#### 2. Database Schema
```php
// plugin.php:216-257
// Scans table: tracks scan sessions
CREATE TABLE wp_xny_404_scans (
    id, scan_date, status, total_pages, total_links, 
    broken_links, scan_config, completed_at
)

// Links table: stores individual broken links
CREATE TABLE wp_xny_404_links (
    id, scan_id, source_url, target_url, link_text,
    status_code, error_message, is_broken, is_fixed, found_at
)
```

#### 3. Frontend Architecture
```javascript
// js/plugins.js:8-748
$(document).ready(function() {
    initPanelAnimations();    # Card entrance animations
    initScanFunctionality();  # AJAX scan controls
    initBrokenLinksPage();    # Results table & filtering
});

// Key functions:
startScan(depth, maxPages, includeExternal)  # Initiates scan via AJAX
loadBrokenLinks(page)                        # Loads paginated results
displayBrokenLinks(data)                     # Renders results table
```

## Code Flow

### 1. Scan Process
```
User clicks "Start Scan" → ajax_start_scan() → Database record created → 
Background processing initiated → Real-time progress polling → 
Results stored in links table → UI updates with completion status
```

### 2. Results Management
```
Load broken links page → ajax_get_broken_links() → Apply filters/search → 
Render paginated table → User fixes/ignores links → ajax_fix_link() → 
Update database → Refresh results display
```

### 3. UI State Management
```javascript
// Scan states: idle → running → completed/stopped
// Progress polling every 2 seconds during active scans
// Dynamic UI updates without page refreshes
```

## Key Features Implementation

### Real-time Scanning
- **AJAX Polling**: Progress updates every 2 seconds (`js/plugins.js:378-421`)
- **Background Processing**: Non-blocking scan execution
- **Progress Tracking**: Live statistics (pages scanned, links found, broken links)

### Advanced Filtering & Search
```php
// plugin.php:370-426 - Broken links AJAX handler
$where_conditions = ['is_broken = 1'];
// Supports: status filters, content type filters, text search
// Pagination: configurable per-page limits
```

### Link Management Options
```php
// plugin.php:431-494 - Fix link handler
switch ($fix_option) {
    case 'replace': // Update with new URL (future enhancement)
    case 'remove':  // Remove link (future enhancement) 
    case 'ignore':  // Mark as non-broken
}
```

### Export Functionality
```php
// plugin.php:499-542 - CSV export
// Headers: Source URL, Target URL, Link Text, Status Code, Error Message, Found At
// Direct download with proper MIME types
```

## Security & Performance

### Security Measures
```php
// Nonce verification for all AJAX requests
check_ajax_referer('xny_404_nonce', 'nonce');

// Capability checks
if (!current_user_can('manage_options')) wp_die('Unauthorized');

// SQL injection prevention
$wpdb->prepare() for all dynamic queries
```

### Performance Optimizations
- **Conditional Loading**: Assets only load on plugin pages
- **Efficient Queries**: Indexed database tables with pagination
- **AJAX Architecture**: No full page reloads during operations
- **Resource Management**: Tailwind preflight disabled to prevent conflicts

## Development Notes

### WordPress Integration
- **Hooks**: `admin_menu`, `admin_enqueue_scripts`, `wp_ajax_*`
- **Standards**: WordPress Coding Standards compliant
- **Localization**: Text domain ready (`xny-404-internal-links`)

### Future Enhancements (Settings Page)
- Scheduled scans, email notifications, exclusion rules
- User permissions, export customization, security controls
- All marked as "coming soon" in UI

### Testing Considerations
- **Browser Compatibility**: Modern browsers with ES6+ support
- **WordPress Versions**: 5.0+ required, tested up to 6.4
- **PHP Requirements**: 7.4+ for optimal performance

---

*For detailed feature analysis, see `context-features.md`. For user documentation, see `README.md`.*
