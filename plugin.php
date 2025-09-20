<?php
/**
 * Plugin Name: 404 Broken Internal Links
 * Plugin URI: https://wengindustries.com
 * Description: Detect and manage 404 broken internal links on your WordPress site
 * Author: XnY
 * Author URI: https://wengindustries.com
 * Version: 1.0.0
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: xny-404-internal-links
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

class XnY_404_Links {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_styles'));
        add_action('admin_head', array($this, 'admin_logo_css'));
        
        // AJAX handlers
        add_action('wp_ajax_xny_start_scan', array($this, 'ajax_start_scan'));
        add_action('wp_ajax_xny_get_scan_progress', array($this, 'ajax_get_scan_progress'));
        add_action('wp_ajax_xny_stop_scan', array($this, 'ajax_stop_scan'));
        add_action('wp_ajax_xny_get_broken_links', array($this, 'ajax_get_broken_links'));
        add_action('wp_ajax_xny_fix_link', array($this, 'ajax_fix_link'));
        add_action('wp_ajax_xny_export_results', array($this, 'ajax_export_results'));
        
        // Fallback: create tables if they don't exist when admin is accessed
        add_action('admin_init', array($this, 'check_and_create_tables'));
        
        // Hook for scheduled scan processing
        add_action('xny_process_scan', array($this, 'process_scan'));
    }

    /**
     * Enqueue admin styles and scripts
     */
    public function enqueue_admin_styles($hook) {
        // Only load on our plugin's admin pages
        if (strpos($hook, 'xny-404-links') !== false || 
            $hook === 'toplevel_page_xny-404-links' || 
            (isset($_GET['page']) && $_GET['page'] === 'xny-404-links')) {
            
            // Local CSS file
            wp_enqueue_style(
                'xny-404-styles',
                plugin_dir_url(__FILE__) . 'css/plugins.css',
                array(),
                '1.0.0'
            );
    
            // Font Awesome for icons
            wp_enqueue_style(
                'xny-404-fontawesome',
                'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css',
                array(),
                '6.6.0'
            );

            // Tailwind CSS CDN with preflight disabled
            wp_enqueue_script(
                'xny-404-tailwind',
                'https://cdn.tailwindcss.com/3.4.17',
                array(),
                '3.4.17',
                false
            );

            // Tailwind configuration to disable preflight
            wp_add_inline_script(
                'xny-404-tailwind',
                'tailwind.config = {
                    corePlugins: {
                        preflight: false,
                    }
                };',
                'after'
            );

            // Plugin JS file
            wp_enqueue_script(
                'xny-404-js',
                plugin_dir_url(__FILE__) . 'js/plugins.js',
                array('jquery'),
                '1.0.0',
                true
            );

            // Localize script for AJAX
            wp_localize_script('xny-404-js', 'xny_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('xny_404_nonce'),
                'plugin_url' => plugin_dir_url(__FILE__)
            ));

            // Inline CSS for logo sizing (as backup)
            wp_add_inline_style('xny-404-styles', '
                .wp-menu-image img,
                #adminmenu .wp-menu-image img,
                #adminmenu li.toplevel_page_xny-404-links .wp-menu-image img,
                #adminmenu li.toplevel_page_xny-404-links img,
                .toplevel_page_xny-404-links .wp-menu-image img {
                    width: 20px !important;
                    height: 20px !important;
                    max-width: 20px !important;
                    max-height: 20px !important;
                    object-fit: contain !important;
                }
            ');
        }
    } // enqueue_admin_styles

    /**
     * Add logo CSS to admin head (ensures it always loads)
     */
    public function admin_logo_css() {
        ?>
        <style type="text/css">
            /* XnY Logo Sizing - Force load */
            .wp-menu-image img,
            #adminmenu .wp-menu-image img,
            #adminmenu li.toplevel_page_xny-404-links .wp-menu-image img,
            #adminmenu li.toplevel_page_xny-404-links img,
            .toplevel_page_xny-404-links .wp-menu-image img {
                width: 20px !important;
                height: 20px !important;
                max-width: 20px !important;
                max-height: 20px !important;
                object-fit: contain !important;
            }
        </style>
        <?php
    }

    /**
     * Add a top-level menu in the admin sidebar
     */
    public function add_admin_menu() {
        add_menu_page(
            __('404 Broken Internal Links', 'xny-404-internal-links'), // Page title
            __('404 by XnY', 'xny-404-internal-links'),                 // Menu title
            'manage_options',                                            // Capability
            'xny-404-links',                                            // Menu slug
            array($this, 'render_404_page'),                            // Callback
            plugin_dir_url(__FILE__) . 'assets/logo-x.png',            // Icon
            25                                                          // Position
        );
    }
    
    public function render_404_page() {
        // Default tab is "dashboard" if none selected
        $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'dashboard';
        ?>
    
        <div class="wrap">
            <h1><?php esc_html_e('404 Broken Internal Links', 'xny-404-internal-links'); ?></h1>
    
            <h2 class="nav-tab-wrapper">
                <a href="?page=xny-404-links&tab=dashboard"
                   class="nav-tab <?php echo $active_tab === 'dashboard' ? 'nav-tab-active bg-red-50' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
                <a href="?page=xny-404-links&tab=scan"
                   class="nav-tab <?php echo $active_tab === 'scan' ? 'nav-tab-active bg-red-50' : ''; ?>">
                    <i class="fas fa-search"></i> Scan Links
                </a>
                <a href="?page=xny-404-links&tab=broken-links"
                   class="nav-tab <?php echo $active_tab === 'broken-links' ? 'nav-tab-active bg-red-50' : ''; ?>">
                    <i class="fas fa-exclamation-triangle"></i> Broken Links
                </a>
                <a href="?page=xny-404-links&tab=settings"
                   class="nav-tab <?php echo $active_tab === 'settings' ? 'nav-tab-active bg-red-50' : ''; ?>">
                    <i class="fas fa-cog"></i> Settings
                </a>
            </h2>
    
            <div class="tab-content">
                <?php
                if ($active_tab === 'dashboard') {
                    include plugin_dir_path(__FILE__) . 'views/dashboard-page.php';
                }
                else if ($active_tab === 'scan') {
                    include plugin_dir_path(__FILE__) . 'views/scan-links-page.php';
                }
                else if ($active_tab === 'broken-links') {
                    include plugin_dir_path(__FILE__) . 'views/broken-links-page.php';
                }
                else if ($active_tab === 'settings') {
                    include plugin_dir_path(__FILE__) . 'views/settings-page.php';
                }
                else {
                    echo '
                        <div class="resource-panel">
                            <h2>Welcome to 404 Broken Internal Links!</h2>
                            <p>Select a tab above to get started with managing your site\'s internal links.</p>
                        </div>
                        ';
                }
                ?>
            </div>
            
            <!-- XnY Footer -->
            <div class="xny-footer" style="margin-top: 2rem; padding: 1rem; text-align: center; border-top: 1px solid #e2e8f0; color: #64748b; font-size: 0.9rem;">
                Powered by <strong>XnY</strong>
            </div>
        </div>
        <?php
    }

    /**
     * Check if tables exist and create them if they don't (fallback method)
     */
    public function check_and_create_tables() {
        global $wpdb;
        
        $scans_table = $wpdb->prefix . 'xny_404_scans';
        $links_table = $wpdb->prefix . 'xny_404_links';
        
        // Check if tables exist
        $scans_exists = $wpdb->get_var("SHOW TABLES LIKE '$scans_table'") === $scans_table;
        $links_exists = $wpdb->get_var("SHOW TABLES LIKE '$links_table'") === $links_table;
        
        // Create tables if they don't exist
        if (!$scans_exists || !$links_exists) {
            $this->create_tables();
        }
    }

    /**
     * Create database tables for storing scan results
     */
    public function create_tables() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'xny_404_scans';
        $links_table = $wpdb->prefix . 'xny_404_links';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Scans table
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            scan_date datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            status varchar(20) DEFAULT 'running' NOT NULL,
            total_pages int(11) DEFAULT 0 NOT NULL,
            total_links int(11) DEFAULT 0 NOT NULL,
            broken_links int(11) DEFAULT 0 NOT NULL,
            scan_config text,
            completed_at datetime,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        // Links table
        $sql2 = "CREATE TABLE $links_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            scan_id mediumint(9) NOT NULL,
            source_url varchar(500) NOT NULL,
            target_url varchar(500) NOT NULL,
            link_text varchar(255),
            status_code int(3),
            error_message text,
            is_broken tinyint(1) DEFAULT 0,
            is_fixed tinyint(1) DEFAULT 0,
            found_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY (id),
            KEY scan_id (scan_id),
            KEY is_broken (is_broken)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        dbDelta($sql2);
    }

    /**
     * AJAX handler to start link scan
     */
    public function ajax_start_scan() {
        check_ajax_referer('xny_404_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $scan_depth = intval($_POST['scan_depth'] ?? 2);
        $max_pages = intval($_POST['max_pages'] ?? 100);
        $include_external = isset($_POST['include_external']) && $_POST['include_external'] === 'true';
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'xny_404_scans';
        
        // Ensure tables exist before trying to insert
        $this->check_and_create_tables();
        
        // Create new scan record
        $scan_config = json_encode(array(
            'scan_depth' => $scan_depth,
            'max_pages' => $max_pages,
            'include_external' => $include_external
        ));
        
        $result = $wpdb->insert(
            $table_name,
            array(
                'scan_date' => current_time('mysql'),
                'status' => 'running',
                'scan_config' => $scan_config
            )
        );
        
        if ($result === false) {
            $error_message = 'Failed to create scan record';
            if ($wpdb->last_error) {
                $error_message .= ': ' . $wpdb->last_error;
            }
            wp_send_json_error($error_message);
        }
        
        $scan_id = $wpdb->insert_id;
        
        // Store scan ID in option for progress tracking
        update_option('xny_current_scan_id', $scan_id);
        update_option('xny_scan_progress', json_encode(array(
            'scan_id' => $scan_id,
            'status' => 'running',
            'pages_scanned' => 0,
            'links_found' => 0,
            'broken_found' => 0,
            'current_page' => 'Initializing...'
        )));
        
        // Start the actual scanning process
        wp_schedule_single_event(time(), 'xny_process_scan', array($scan_id));
        
        // Also start immediate processing for better user experience
        $this->process_scan($scan_id);
        
        wp_send_json_success(array(
            'scan_id' => $scan_id,
            'message' => 'Scan started successfully'
        ));
    }

    /**
     * AJAX handler to get scan progress
     */
    public function ajax_get_scan_progress() {
        check_ajax_referer('xny_404_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $progress = get_option('xny_scan_progress', '{}');
        $progress_data = json_decode($progress, true);
        
        if (empty($progress_data)) {
            wp_send_json_error('No scan in progress');
        }
        
        wp_send_json_success($progress_data);
    }

    /**
     * AJAX handler to stop scan
     */
    public function ajax_stop_scan() {
        check_ajax_referer('xny_404_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $scan_id = get_option('xny_current_scan_id');
        
        if ($scan_id) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'xny_404_scans';
            
            $wpdb->update(
                $table_name,
                array('status' => 'stopped', 'completed_at' => current_time('mysql')),
                array('id' => $scan_id)
            );
            
            delete_option('xny_current_scan_id');
            delete_option('xny_scan_progress');
        }
        
        wp_send_json_success('Scan stopped');
    }

    /**
     * AJAX handler to get broken links
     */
    public function ajax_get_broken_links() {
        check_ajax_referer('xny_404_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        global $wpdb;
        $links_table = $wpdb->prefix . 'xny_404_links';
        
        $page = intval($_POST['page'] ?? 1);
        $per_page = intval($_POST['per_page'] ?? 10);
        $status_filter = sanitize_text_field($_POST['status_filter'] ?? 'all');
        $type_filter = sanitize_text_field($_POST['type_filter'] ?? 'all');
        $search = sanitize_text_field($_POST['search'] ?? '');
        
        $offset = ($page - 1) * $per_page;
        
        // Build query
        $where_conditions = array('is_broken = 1');
        
        if ($status_filter !== 'all') {
            if ($status_filter === '404') {
                $where_conditions[] = 'status_code = 404';
            } elseif ($status_filter === 'redirect') {
                $where_conditions[] = 'status_code IN (301, 302, 303, 307, 308)';
            } elseif ($status_filter === 'timeout') {
                $where_conditions[] = 'error_message LIKE "%timeout%"';
            }
        }
        
        if (!empty($search)) {
            $search = $wpdb->esc_like($search);
            $where_conditions[] = $wpdb->prepare(
                '(target_url LIKE %s OR source_url LIKE %s OR link_text LIKE %s OR error_message LIKE %s)',
                "%$search%", "%$search%", "%$search%", "%$search%"
            );
        }
        
        $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
        
        // Get total count
        $total_query = "SELECT COUNT(*) FROM $links_table $where_clause";
        $total = $wpdb->get_var($total_query);
        
        // Get links
        $links_query = "SELECT * FROM $links_table $where_clause ORDER BY found_at DESC LIMIT $per_page OFFSET $offset";
        $links = $wpdb->get_results($links_query);
        
        wp_send_json_success(array(
            'links' => $links,
            'total' => $total,
            'page' => $page,
            'per_page' => $per_page,
            'total_pages' => ceil($total / $per_page)
        ));
    }

    /**
     * AJAX handler to fix a broken link
     */
    public function ajax_fix_link() {
        check_ajax_referer('xny_404_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $link_id = intval($_POST['link_id']);
        $fix_option = sanitize_text_field($_POST['fix_option']);
        $replacement_url = esc_url_raw($_POST['replacement_url'] ?? '');
        
        global $wpdb;
        $links_table = $wpdb->prefix . 'xny_404_links';
        
        // Get the link details
        $link = $wpdb->get_row($wpdb->prepare("SELECT * FROM $links_table WHERE id = %d", $link_id));
        
        if (!$link) {
            wp_send_json_error('Link not found');
        }
        
        $result = false;
        $message = '';
        
        switch ($fix_option) {
            case 'replace':
                if (empty($replacement_url)) {
                    wp_send_json_error('Replacement URL is required');
                }
                // This would require more complex logic to actually replace links in content
                $result = $wpdb->update(
                    $links_table,
                    array('is_fixed' => 1),
                    array('id' => $link_id)
                );
                $message = 'Link marked as fixed (replacement functionality coming soon)';
                break;
                
            case 'remove':
                // Mark as fixed (removal functionality would be implemented here)
                $result = $wpdb->update(
                    $links_table,
                    array('is_fixed' => 1),
                    array('id' => $link_id)
                );
                $message = 'Link marked as fixed (removal functionality coming soon)';
                break;
                
            case 'ignore':
                $result = $wpdb->update(
                    $links_table,
                    array('is_broken' => 0),
                    array('id' => $link_id)
                );
                $message = 'Link ignored';
                break;
        }
        
        if ($result !== false) {
            wp_send_json_success($message);
        } else {
            wp_send_json_error('Failed to update link');
        }
    }

    /**
     * AJAX handler to export results
     */
    public function ajax_export_results() {
        check_ajax_referer('xny_404_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        global $wpdb;
        $links_table = $wpdb->prefix . 'xny_404_links';
        
        $format = sanitize_text_field($_POST['format'] ?? 'csv');
        
        // Get all broken links
        $links = $wpdb->get_results("SELECT * FROM $links_table WHERE is_broken = 1 ORDER BY found_at DESC");
        
        if ($format === 'csv') {
            $filename = 'broken-links-' . date('Y-m-d-H-i-s') . '.csv';
            
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            
            $output = fopen('php://output', 'w');
            
            // CSV headers
            fputcsv($output, array('Source URL', 'Target URL', 'Link Text', 'Status Code', 'Error Message', 'Found At'));
            
            // CSV data
            foreach ($links as $link) {
                fputcsv($output, array(
                    $link->source_url,
                    $link->target_url,
                    $link->link_text,
                    $link->status_code,
                    $link->error_message,
                    $link->found_at
                ));
            }
            
            fclose($output);
            exit;
        }
        
        wp_send_json_error('Unsupported export format');
    }

    /**
     * Process the actual link scanning
     */
    public function process_scan($scan_id) {
        global $wpdb;
        $links_table = $wpdb->prefix . 'xny_404_links';
        $scans_table = $wpdb->prefix . 'xny_404_scans';
        
        // Get scan configuration
        $scan = $wpdb->get_row($wpdb->prepare("SELECT * FROM $scans_table WHERE id = %d", $scan_id));
        if (!$scan) {
            return;
        }
        
        $config = json_decode($scan->scan_config, true);
        $scan_depth = $config['scan_depth'] ?? 2;
        $max_pages = $config['max_pages'] ?? 100;
        $include_external = $config['include_external'] ?? false;
        
        // Get posts to scan based on depth
        $post_types = $this->get_post_types_for_scan($scan_depth);
        $posts = get_posts(array(
            'post_type' => $post_types,
            'post_status' => 'publish',
            'numberposts' => $max_pages,
            'orderby' => 'date',
            'order' => 'DESC'
        ));
        
        $total_posts = count($posts);
        $pages_scanned = 0;
        $total_links = 0;
        $broken_links = 0;
        
        foreach ($posts as $post) {
            // Update progress
            $pages_scanned++;
            $this->update_scan_progress($scan_id, array(
                'pages_scanned' => $pages_scanned,
                'current_page' => $post->post_title,
                'total_pages' => $total_posts
            ));
            
            // Extract links from content
            $links = $this->extract_links_from_content($post->post_content, $post->ID);
            
            foreach ($links as $link) {
                $total_links++;
                
                // Test the link
                $link_status = $this->test_link($link['url'], $include_external);
                
                if ($link_status['is_broken']) {
                    $broken_links++;
                    
                    // Store broken link
                    $wpdb->insert(
                        $links_table,
                        array(
                            'scan_id' => $scan_id,
                            'source_url' => get_permalink($post->ID),
                            'target_url' => $link['url'],
                            'link_text' => $link['text'],
                            'status_code' => $link_status['status_code'],
                            'error_message' => $link_status['error_message'],
                            'is_broken' => 1,
                            'found_at' => current_time('mysql')
                        )
                    );
                }
            }
            
            // Update progress with current stats
            $this->update_scan_progress($scan_id, array(
                'pages_scanned' => $pages_scanned,
                'links_found' => $total_links,
                'broken_found' => $broken_links,
                'current_page' => $post->post_title,
                'total_pages' => $total_posts
            ));
            
            // Small delay to prevent server overload
            usleep(100000); // 0.1 second
        }
        
        // Mark scan as completed
        $wpdb->update(
            $scans_table,
            array(
                'status' => 'completed',
                'total_pages' => $total_posts,
                'total_links' => $total_links,
                'broken_links' => $broken_links,
                'completed_at' => current_time('mysql')
            ),
            array('id' => $scan_id)
        );
        
        // Final progress update
        $this->update_scan_progress($scan_id, array(
            'status' => 'completed',
            'pages_scanned' => $pages_scanned,
            'links_found' => $total_links,
            'broken_found' => $broken_links,
            'current_page' => 'Scan completed',
            'total_pages' => $total_posts
        ));
        
        // Clean up
        delete_option('xny_current_scan_id');
    }
    
    /**
     * Get post types based on scan depth
     */
    private function get_post_types_for_scan($depth) {
        switch ($depth) {
            case 1:
                return array('page');
            case 2:
                return array('page', 'post');
            case 3:
                $post_types = get_post_types(array('public' => true));
                return array_keys($post_types);
            case 4:
                $post_types = get_post_types(array('public' => true));
                return array_keys($post_types);
            default:
                return array('page', 'post');
        }
    }
    
    /**
     * Extract links from post content
     */
    private function extract_links_from_content($content, $post_id) {
        $links = array();
        $site_url = get_site_url();
        
        // Use DOMDocument to parse HTML and extract links
        $dom = new DOMDocument();
        @$dom->loadHTML('<?xml encoding="utf-8" ?>' . $content);
        $xpath = new DOMXPath($dom);
        $link_nodes = $xpath->query('//a[@href]');
        
        foreach ($link_nodes as $node) {
            $href = $node->getAttribute('href');
            $text = trim($node->textContent);
            
            // Skip empty links, anchors, and mailto links
            if (empty($href) || $href[0] === '#' || strpos($href, 'mailto:') === 0) {
                continue;
            }
            
            // Convert relative URLs to absolute
            if (strpos($href, 'http') !== 0) {
                if ($href[0] === '/') {
                    $href = $site_url . $href;
                } else {
                    $href = get_permalink($post_id) . $href;
                }
            }
            
            $links[] = array(
                'url' => $href,
                'text' => $text
            );
        }
        
        return $links;
    }
    
    /**
     * Test if a link is broken
     */
    private function test_link($url, $include_external = false) {
        $site_url = get_site_url();
        $is_internal = strpos($url, $site_url) === 0;
        
        // Skip external links if not included
        if (!$is_internal && !$include_external) {
            return array('is_broken' => false);
        }
        
        // For internal links, check if the post/page exists
        if ($is_internal) {
            $path = str_replace($site_url, '', $url);
            $path = trim($path, '/');
            
            // Try to get the post ID from the URL
            $post_id = url_to_postid($url);
            
            if ($post_id > 0) {
                $post = get_post($post_id);
                if ($post && $post->post_status === 'publish') {
                    return array('is_broken' => false);
                }
            }
            
            // If no post found, it's likely broken
            return array(
                'is_broken' => true,
                'status_code' => 404,
                'error_message' => 'Page not found'
            );
        }
        
        // For external links, make HTTP request
        $response = wp_remote_head($url, array(
            'timeout' => 10,
            'redirection' => 5,
            'user-agent' => 'WordPress Link Checker'
        ));
        
        if (is_wp_error($response)) {
            return array(
                'is_broken' => true,
                'status_code' => 0,
                'error_message' => $response->get_error_message()
            );
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        
        // Consider 4xx and 5xx as broken
        if ($status_code >= 400) {
            return array(
                'is_broken' => true,
                'status_code' => $status_code,
                'error_message' => wp_remote_retrieve_response_message($response)
            );
        }
        
        return array('is_broken' => false, 'status_code' => $status_code);
    }
    
    /**
     * Update scan progress
     */
    private function update_scan_progress($scan_id, $data) {
        $progress = get_option('xny_scan_progress', '{}');
        $progress_data = json_decode($progress, true);
        
        $progress_data = array_merge($progress_data, $data);
        $progress_data['scan_id'] = $scan_id;
        
        update_option('xny_scan_progress', json_encode($progress_data));
    }
}

/**
 * Plugin activation hook - create tables
 */
function xny_404_links_activate() {
    $plugin = new XnY_404_Links();
    $plugin->create_tables();
}
register_activation_hook(__FILE__, 'xny_404_links_activate');

/**
 * Initialize the plugin
 */
function xny_init_404_links() {
    new XnY_404_Links();
}
add_action('plugins_loaded', 'xny_init_404_links');

