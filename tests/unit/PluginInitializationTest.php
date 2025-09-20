<?php

namespace XnY\Tests\Unit;

use XnY\Tests\Helpers\TestCase;
use Brain\Monkey;

/**
 * Test Plugin Initialization
 * 
 * Tests the main plugin class initialization and hook registration
 */
class PluginInitializationTest extends TestCase
{
    private $plugin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->plugin = new \XnY_404_Links();
    }

    /**
     * Test plugin constructor registers all required hooks
     */
    public function testConstructorRegistersHooks()
    {
        // Verify admin hooks are registered
        $this->assertHookAdded('admin_menu');
        $this->assertHookAdded('admin_enqueue_scripts');
        $this->assertHookAdded('admin_head');
        
        // Verify AJAX hooks are registered
        $this->assertHookAdded('wp_ajax_xny_start_scan');
        $this->assertHookAdded('wp_ajax_xny_get_scan_progress');
        $this->assertHookAdded('wp_ajax_xny_stop_scan');
        $this->assertHookAdded('wp_ajax_xny_get_broken_links');
        $this->assertHookAdded('wp_ajax_xny_fix_link');
        $this->assertHookAdded('wp_ajax_xny_export_results');
        
        // Verify scheduled action hook
        $this->assertHookAdded('xny_process_scan');
    }

    /**
     * Test admin menu registration
     */
    public function testAddAdminMenu()
    {
        Monkey\Functions\expect('add_menu_page')
            ->once()
            ->with(
                Monkey\Functions\when('__')->returnArg(),
                Monkey\Functions\when('__')->returnArg(),
                'manage_options',
                'xny-404-links',
                array($this->plugin, 'render_404_page'),
                Monkey\Functions\when('plugin_dir_url')->returnArg() . 'assets/logo-x.png',
                25
            );

        $this->plugin->add_admin_menu();
    }

    /**
     * Test admin styles and scripts enqueuing
     */
    public function testEnqueueAdminStyles()
    {
        // Mock WordPress enqueue functions
        Monkey\Functions\expect('wp_enqueue_style')->times(2);
        Monkey\Functions\expect('wp_enqueue_script')->times(2);
        Monkey\Functions\expect('wp_add_inline_script')->once();
        Monkey\Functions\expect('wp_add_inline_style')->once();
        Monkey\Functions\expect('wp_localize_script')->once();

        // Test with plugin page hook
        $this->plugin->enqueue_admin_styles('toplevel_page_xny-404-links');
    }

    /**
     * Test admin styles not enqueued on other pages
     */
    public function testEnqueueAdminStylesSkippedOnOtherPages()
    {
        // Should not enqueue on other admin pages
        Monkey\Functions\expect('wp_enqueue_style')->never();
        Monkey\Functions\expect('wp_enqueue_script')->never();

        $this->plugin->enqueue_admin_styles('edit.php');
    }

    /**
     * Test admin logo CSS output
     */
    public function testAdminLogoCss()
    {
        ob_start();
        $this->plugin->admin_logo_css();
        $output = ob_get_clean();

        $this->assertStringContainsString('<style type="text/css">', $output);
        $this->assertStringContainsString('width: 20px !important', $output);
        $this->assertStringContainsString('.wp-menu-image img', $output);
    }

    /**
     * Test plugin initialization function
     */
    public function testPluginInitialization()
    {
        // Test that the initialization function creates the plugin instance
        Monkey\Actions\expectAdded('plugins_loaded');
        
        // The function should be defined
        $this->assertTrue(function_exists('xny_init_404_links'));
    }

    /**
     * Test database table creation
     */
    public function testCreateTables()
    {
        global $wpdb;
        $wpdb = $this->createMockWpdb();

        // Mock dbDelta function
        Monkey\Functions\expect('dbDelta')->times(2);

        $this->plugin->create_tables();
    }

    /**
     * Test scan depth post type mapping
     */
    public function testGetPostTypesForScan()
    {
        // Use reflection to test private method
        $reflection = new \ReflectionClass($this->plugin);
        $method = $reflection->getMethod('get_post_types_for_scan');
        $method->setAccessible(true);

        // Test depth 1 (pages only)
        $result = $method->invokeArgs($this->plugin, [1]);
        $this->assertEquals(['page'], $result);

        // Test depth 2 (pages + posts)
        $result = $method->invokeArgs($this->plugin, [2]);
        $this->assertEquals(['page', 'post'], $result);

        // Test depth 3 (all public post types)
        Monkey\Functions\when('get_post_types')
            ->with(['public' => true])
            ->justReturn(['post' => 'post', 'page' => 'page', 'custom' => 'custom']);

        $result = $method->invokeArgs($this->plugin, [3]);
        $this->assertEquals(['post', 'page', 'custom'], $result);
    }
}
