<?php

namespace XnY\Tests\Helpers;

use PHPUnit\Framework\TestCase as PHPUnitTestCase;
use Brain\Monkey;
use Mockery;

/**
 * Base Test Case Class
 * 
 * Provides common functionality for all test cases
 */
abstract class TestCase extends PHPUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Initialize Brain Monkey for WordPress function mocking
        if (class_exists('Brain\Monkey')) {
            Monkey\setUp();
        }
        
        // Set up common WordPress function mocks
        $this->setUpWordPressMocks();
    }

    protected function tearDown(): void
    {
        // Clean up Mockery
        Mockery::close();
        
        // Clean up Brain Monkey
        if (class_exists('Brain\Monkey')) {
            Monkey\tearDown();
        }
        
        parent::tearDown();
    }

    /**
     * Set up common WordPress function mocks
     */
    protected function setUpWordPressMocks()
    {
        // Mock common WordPress functions
        Monkey\Functions\when('wp_create_nonce')->justReturn('test_nonce');
        Monkey\Functions\when('wp_verify_nonce')->justReturn(true);
        Monkey\Functions\when('current_user_can')->justReturn(true);
        Monkey\Functions\when('is_admin')->justReturn(true);
        Monkey\Functions\when('admin_url')->returnArg();
        Monkey\Functions\when('plugin_dir_url')->returnArg();
        Monkey\Functions\when('plugin_dir_path')->returnArg();
        Monkey\Functions\when('get_site_url')->justReturn('https://example.com');
        Monkey\Functions\when('current_time')->justReturn('2023-01-01 12:00:00');
        Monkey\Functions\when('esc_html_e')->returnArg();
        Monkey\Functions\when('esc_html__')->returnArg();
        Monkey\Functions\when('sanitize_text_field')->returnArg();
        Monkey\Functions\when('esc_url_raw')->returnArg();
        
        // Mock WordPress actions and filters
        Monkey\Actions\expectAdded('admin_menu');
        Monkey\Actions\expectAdded('admin_enqueue_scripts');
        Monkey\Actions\expectAdded('wp_ajax_xny_start_scan');
        Monkey\Actions\expectAdded('wp_ajax_xny_get_scan_progress');
        Monkey\Actions\expectAdded('wp_ajax_xny_stop_scan');
        Monkey\Actions\expectAdded('wp_ajax_xny_get_broken_links');
        Monkey\Actions\expectAdded('wp_ajax_xny_fix_link');
        Monkey\Actions\expectAdded('wp_ajax_xny_export_results');
    }

    /**
     * Create a mock WordPress database object
     */
    protected function createMockWpdb()
    {
        $wpdb = Mockery::mock('wpdb');
        $wpdb->prefix = 'wp_';
        $wpdb->shouldReceive('get_charset_collate')->andReturn('DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $wpdb->shouldReceive('prepare')->andReturnUsing(function($query, ...$args) {
            return vsprintf(str_replace('%s', "'%s'", str_replace('%d', '%d', $query)), $args);
        });
        
        return $wpdb;
    }

    /**
     * Create test post data
     */
    protected function createTestPost($id = 1, $title = 'Test Post', $content = '<p>Test content</p>')
    {
        $post = new \stdClass();
        $post->ID = $id;
        $post->post_title = $title;
        $post->post_content = $content;
        $post->post_status = 'publish';
        $post->post_type = 'post';
        
        return $post;
    }

    /**
     * Create test link data
     */
    protected function createTestLink($id = 1, $url = 'https://example.com/test', $text = 'Test Link')
    {
        return array(
            'id' => $id,
            'scan_id' => 1,
            'source_url' => 'https://example.com/source',
            'target_url' => $url,
            'link_text' => $text,
            'status_code' => 404,
            'error_message' => 'Page not found',
            'is_broken' => 1,
            'is_fixed' => 0,
            'found_at' => '2023-01-01 12:00:00'
        );
    }

    /**
     * Assert that a WordPress hook was added
     */
    protected function assertHookAdded($hook, $function = null, $priority = 10)
    {
        if ($function) {
            Monkey\Actions\expectAdded($hook)->with($function, $priority);
        } else {
            Monkey\Actions\expectAdded($hook);
        }
    }

    /**
     * Assert that an AJAX response is successful
     */
    protected function assertAjaxSuccess($response, $expectedData = null)
    {
        $this->assertTrue($response['success']);
        
        if ($expectedData !== null) {
            $this->assertEquals($expectedData, $response['data']);
        }
    }

    /**
     * Assert that an AJAX response is an error
     */
    protected function assertAjaxError($response, $expectedMessage = null)
    {
        $this->assertFalse($response['success']);
        
        if ($expectedMessage !== null) {
            $this->assertEquals($expectedMessage, $response['data']);
        }
    }

    /**
     * Mock WordPress HTTP API responses
     */
    protected function mockHttpResponse($url, $statusCode = 200, $body = '', $headers = array())
    {
        $response = array(
            'response' => array(
                'code' => $statusCode,
                'message' => 'OK'
            ),
            'body' => $body,
            'headers' => $headers
        );

        Monkey\Functions\when('wp_remote_head')->justReturn($response);
        Monkey\Functions\when('wp_remote_get')->justReturn($response);
        Monkey\Functions\when('wp_remote_retrieve_response_code')->justReturn($statusCode);
        Monkey\Functions\when('wp_remote_retrieve_response_message')->justReturn('OK');
        Monkey\Functions\when('is_wp_error')->justReturn(false);
    }

    /**
     * Mock WordPress HTTP API error
     */
    protected function mockHttpError($errorMessage = 'Connection failed')
    {
        $error = Mockery::mock('WP_Error');
        $error->shouldReceive('get_error_message')->andReturn($errorMessage);

        Monkey\Functions\when('wp_remote_head')->justReturn($error);
        Monkey\Functions\when('wp_remote_get')->justReturn($error);
        Monkey\Functions\when('is_wp_error')->justReturn(true);
    }
}
