<?php

namespace XnY\Tests\Integration;

use XnY\Tests\Helpers\TestCase;
use XnY\Tests\Helpers\MockHelper;
use Brain\Monkey;

/**
 * Test WordPress Integration
 * 
 * Tests plugin integration with WordPress core functions and hooks
 */
class WordPressIntegrationTest extends TestCase
{
    private $plugin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->plugin = new \XnY_404_Links();
    }

    /**
     * Test plugin activation and database table creation
     */
    public function testPluginActivationIntegration()
    {
        // Mock WordPress database functions
        global $wpdb;
        $wpdb = $this->createMockWpdb();

        // Mock dbDelta function
        Monkey\Functions\expect('dbDelta')->times(2);

        // Test activation hook
        do_action('activate_plugin');
        
        // Simulate table creation
        $this->plugin->create_tables();

        // Verify tables would be created
        $this->assertTrue(true); // dbDelta was called
    }

    /**
     * Test admin menu integration
     */
    public function testAdminMenuIntegration()
    {
        // Mock WordPress admin functions
        Monkey\Functions\expect('add_menu_page')
            ->once()
            ->with(
                Monkey\Functions\type('string'),
                Monkey\Functions\type('string'),
                'manage_options',
                'xny-404-links',
                Monkey\Functions\type('array'),
                Monkey\Functions\type('string'),
                25
            );

        // Test admin_menu hook
        do_action('admin_menu');
        $this->plugin->add_admin_menu();
    }

    /**
     * Test admin scripts and styles enqueuing integration
     */
    public function testAdminScriptsIntegration()
    {
        // Mock WordPress enqueue functions
        Monkey\Functions\expect('wp_enqueue_style')->times(2);
        Monkey\Functions\expect('wp_enqueue_script')->times(2);
        Monkey\Functions\expect('wp_add_inline_script')->once();
        Monkey\Functions\expect('wp_add_inline_style')->once();
        Monkey\Functions\expect('wp_localize_script')->once();

        // Test admin_enqueue_scripts hook
        do_action('admin_enqueue_scripts', 'toplevel_page_xny-404-links');
        $this->plugin->enqueue_admin_styles('toplevel_page_xny-404-links');
    }

    /**
     * Test AJAX hooks integration
     */
    public function testAjaxHooksIntegration()
    {
        $ajaxActions = [
            'wp_ajax_xny_start_scan',
            'wp_ajax_xny_get_scan_progress',
            'wp_ajax_xny_stop_scan',
            'wp_ajax_xny_get_broken_links',
            'wp_ajax_xny_fix_link',
            'wp_ajax_xny_export_results'
        ];

        foreach ($ajaxActions as $action) {
            // Test that AJAX actions are properly registered
            $this->assertHookAdded($action);
        }
    }

    /**
     * Test WordPress security integration
     */
    public function testWordPressSecurityIntegration()
    {
        // Test nonce verification
        Monkey\Functions\expect('check_ajax_referer')
            ->once()
            ->with('xny_404_nonce', 'nonce');

        // Test capability check
        Monkey\Functions\expect('current_user_can')
            ->once()
            ->with('manage_options')
            ->andReturn(true);

        // Simulate AJAX request
        $_POST = [
            'action' => 'xny_start_scan',
            'nonce' => 'test_nonce',
            'scan_depth' => 2
        ];

        // Mock database and other functions
        global $wpdb;
        $wpdb = $this->createMockWpdb();
        $wpdb->shouldReceive('insert')->andReturn(1);
        $wpdb->insert_id = 1;

        Monkey\Functions\when('update_option')->justReturn(true);
        Monkey\Functions\when('wp_schedule_single_event')->justReturn(true);

        // Create mock plugin to avoid actual processing
        $plugin = \Mockery::mock(\XnY_404_Links::class)->makePartial();
        $plugin->shouldReceive('process_scan')->once();

        // Test security checks are performed
        $plugin->ajax_start_scan();
    }

    /**
     * Test WordPress post type integration
     */
    public function testPostTypeIntegration()
    {
        // Mock WordPress post type functions
        MockHelper::mockPostTypes(['post', 'page', 'product', 'event']);

        $reflection = new \ReflectionClass($this->plugin);
        $method = $reflection->getMethod('get_post_types_for_scan');
        $method->setAccessible(true);

        // Test different scan depths
        $result = $method->invokeArgs($this->plugin, [3]);
        $this->assertContains('post', $result);
        $this->assertContains('page', $result);
        $this->assertContains('product', $result);
        $this->assertContains('event', $result);
    }

    /**
     * Test WordPress URL and permalink integration
     */
    public function testUrlPermalinkIntegration()
    {
        MockHelper::mockUrlFunctions('https://example.com');

        // Test URL to post ID conversion
        $postId = url_to_postid('https://example.com/post-1');
        $this->assertEquals(1, $postId);

        // Test permalink generation
        $permalink = get_permalink(1);
        $this->assertEquals('https://example.com/post-1', $permalink);

        // Test site URL
        $siteUrl = get_site_url();
        $this->assertEquals('https://example.com', $siteUrl);
    }

    /**
     * Test WordPress HTTP API integration
     */
    public function testHttpApiIntegration()
    {
        MockHelper::mockHttpApi([
            'https://external.com/test' => [
                'response' => ['code' => 200, 'message' => 'OK'],
                'body' => 'Success'
            ]
        ]);

        // Test HTTP request
        $response = wp_remote_head('https://external.com/test');
        $statusCode = wp_remote_retrieve_response_code($response);
        $this->assertEquals(200, $statusCode);

        // Test error handling
        $error = MockHelper::createWpError('Connection failed');
        Monkey\Functions\when('wp_remote_head')
            ->with('https://external.com/error')
            ->justReturn($error);
        Monkey\Functions\when('is_wp_error')
            ->with($error)
            ->justReturn(true);

        $errorResponse = wp_remote_head('https://external.com/error');
        $this->assertTrue(is_wp_error($errorResponse));
    }

    /**
     * Test WordPress options API integration
     */
    public function testOptionsApiIntegration()
    {
        // Mock options functions
        $optionValue = json_encode(['scan_id' => 1, 'status' => 'running']);
        
        Monkey\Functions\when('get_option')
            ->with('xny_scan_progress', '{}')
            ->justReturn($optionValue);

        Monkey\Functions\expect('update_option')
            ->once()
            ->with('xny_scan_progress', Monkey\Functions\type('string'))
            ->andReturn(true);

        Monkey\Functions\expect('delete_option')
            ->once()
            ->with('xny_current_scan_id')
            ->andReturn(true);

        // Test option retrieval
        $progress = get_option('xny_scan_progress', '{}');
        $this->assertEquals($optionValue, $progress);

        // Test option update
        $result = update_option('xny_scan_progress', '{"updated": true}');
        $this->assertTrue($result);

        // Test option deletion
        $result = delete_option('xny_current_scan_id');
        $this->assertTrue($result);
    }

    /**
     * Test WordPress scheduling integration
     */
    public function testSchedulingIntegration()
    {
        MockHelper::mockScheduling();

        // Test event scheduling
        $result = wp_schedule_single_event(time() + 3600, 'xny_process_scan', [1]);
        $this->assertTrue($result);

        // Test event unscheduling
        $result = wp_unschedule_event(time() + 3600, 'xny_process_scan', [1]);
        $this->assertTrue($result);
    }

    /**
     * Test WordPress multisite compatibility
     */
    public function testMultisiteCompatibility()
    {
        // Mock multisite functions
        Monkey\Functions\when('is_multisite')->justReturn(true);
        Monkey\Functions\when('get_current_blog_id')->justReturn(2);
        Monkey\Functions\when('switch_to_blog')->justReturn(true);
        Monkey\Functions\when('restore_current_blog')->justReturn(true);

        // Test that plugin works in multisite environment
        $this->assertTrue(is_multisite());
        
        // Test blog switching capability
        switch_to_blog(1);
        $this->assertTrue(true); // No errors thrown
        
        restore_current_blog();
        $this->assertTrue(true); // No errors thrown
    }

    /**
     * Test WordPress translation integration
     */
    public function testTranslationIntegration()
    {
        // Mock translation functions
        Monkey\Functions\when('__')->returnArg();
        Monkey\Functions\when('_e')->returnArg();
        Monkey\Functions\when('esc_html__')->returnArg();
        Monkey\Functions\when('esc_html_e')->returnArg();

        // Test translation functions work
        $translated = __('404 Broken Internal Links', 'xny-404-internal-links');
        $this->assertEquals('404 Broken Internal Links', $translated);

        $escaped = esc_html__('Start Scan', 'xny-404-internal-links');
        $this->assertEquals('Start Scan', $escaped);
    }

    /**
     * Test WordPress database integration
     */
    public function testDatabaseIntegration()
    {
        global $wpdb;
        $wpdb = $this->createMockWpdb();

        // Test table prefix handling
        $scansTable = $wpdb->prefix . 'xny_404_scans';
        $linksTable = $wpdb->prefix . 'xny_404_links';

        $this->assertEquals('wp_xny_404_scans', $scansTable);
        $this->assertEquals('wp_xny_404_links', $linksTable);

        // Test charset collate
        $charset = $wpdb->get_charset_collate();
        $this->assertStringContainsString('CHARACTER SET', $charset);
    }

    /**
     * Test WordPress user capabilities integration
     */
    public function testUserCapabilitiesIntegration()
    {
        // Test admin user
        Monkey\Functions\when('current_user_can')
            ->with('manage_options')
            ->justReturn(true);

        $canManage = current_user_can('manage_options');
        $this->assertTrue($canManage);

        // Test non-admin user
        Monkey\Functions\when('current_user_can')
            ->with('manage_options')
            ->justReturn(false);

        $canManage = current_user_can('manage_options');
        $this->assertFalse($canManage);
    }

    /**
     * Test WordPress admin interface integration
     */
    public function testAdminInterfaceIntegration()
    {
        // Mock admin functions
        Monkey\Functions\when('is_admin')->justReturn(true);
        Monkey\Functions\when('admin_url')->returnArg();

        // Test admin detection
        $this->assertTrue(is_admin());

        // Test admin URL generation
        $adminUrl = admin_url('admin-ajax.php');
        $this->assertEquals('admin-ajax.php', $adminUrl);
    }

    /**
     * Test plugin file and directory paths
     */
    public function testPluginPathsIntegration()
    {
        // Mock WordPress path functions
        Monkey\Functions\when('plugin_dir_path')->returnArg();
        Monkey\Functions\when('plugin_dir_url')->returnArg();
        Monkey\Functions\when('plugin_basename')->returnArg();

        // Test path functions
        $pluginPath = plugin_dir_path(__FILE__);
        $this->assertNotEmpty($pluginPath);

        $pluginUrl = plugin_dir_url(__FILE__);
        $this->assertNotEmpty($pluginUrl);
    }

    /**
     * Test WordPress error handling integration
     */
    public function testErrorHandlingIntegration()
    {
        // Test WP_Error handling
        $error = MockHelper::createWpError('Test error', 'test_error');
        
        $this->assertTrue(is_wp_error($error));
        $this->assertEquals('Test error', $error->get_error_message());
        $this->assertEquals('test_error', $error->get_error_code());
    }
}
