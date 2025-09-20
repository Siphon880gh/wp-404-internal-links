<?php

namespace XnY\Tests\Helpers;

use Brain\Monkey;
use Mockery;

/**
 * Mock Helper Class
 * 
 * Provides utility methods for creating mocks and stubs
 */
class MockHelper
{
    /**
     * Create mock WordPress posts
     */
    public static function createMockPosts($count = 3, $postType = 'post')
    {
        $posts = array();
        
        for ($i = 1; $i <= $count; $i++) {
            $post = new \stdClass();
            $post->ID = $i;
            $post->post_title = "Test {$postType} {$i}";
            $post->post_content = self::createHtmlWithLinks($i);
            $post->post_status = 'publish';
            $post->post_type = $postType;
            $post->post_date = '2023-01-0' . $i . ' 12:00:00';
            
            $posts[] = $post;
        }
        
        return $posts;
    }

    /**
     * Create HTML content with various types of links
     */
    public static function createHtmlWithLinks($postId = 1)
    {
        return sprintf('
            <p>This is test content for post %d.</p>
            <p>Here are some links:</p>
            <ul>
                <li><a href="https://example.com/page-%d">Internal Link %d</a></li>
                <li><a href="https://external.com/page-%d">External Link %d</a></li>
                <li><a href="/relative-link-%d">Relative Link %d</a></li>
                <li><a href="#anchor-%d">Anchor Link %d</a></li>
                <li><a href="mailto:test%d@example.com">Email Link %d</a></li>
                <li><a href="">Empty Link</a></li>
            </ul>
            <p>More content here with <a href="https://example.com/broken-%d">broken link %d</a>.</p>
        ', $postId, $postId, $postId, $postId, $postId, $postId, $postId, $postId, $postId, $postId, $postId, $postId, $postId);
    }

    /**
     * Mock WordPress get_posts function
     */
    public static function mockGetPosts($posts = null, $args = array())
    {
        if ($posts === null) {
            $posts = self::createMockPosts();
        }
        
        Monkey\Functions\when('get_posts')
            ->justReturn($posts);
            
        return $posts;
    }

    /**
     * Mock WordPress post type functions
     */
    public static function mockPostTypes($publicTypes = array('post', 'page'))
    {
        $postTypes = array();
        foreach ($publicTypes as $type) {
            $postTypes[$type] = (object) array(
                'name' => $type,
                'public' => true
            );
        }
        
        Monkey\Functions\when('get_post_types')
            ->with(array('public' => true))
            ->justReturn($postTypes);
    }

    /**
     * Mock WordPress URL functions
     */
    public static function mockUrlFunctions($siteUrl = 'https://example.com')
    {
        Monkey\Functions\when('get_site_url')->justReturn($siteUrl);
        Monkey\Functions\when('get_permalink')->alias(function($postId) use ($siteUrl) {
            return $siteUrl . '/post-' . $postId;
        });
        Monkey\Functions\when('url_to_postid')->alias(function($url) use ($siteUrl) {
            if (strpos($url, $siteUrl . '/post-') === 0) {
                return (int) str_replace($siteUrl . '/post-', '', $url);
            }
            return 0;
        });
        Monkey\Functions\when('get_post')->alias(function($postId) {
            if ($postId > 0 && $postId <= 3) {
                $post = new \stdClass();
                $post->ID = $postId;
                $post->post_status = 'publish';
                return $post;
            }
            return null;
        });
    }

    /**
     * Mock WordPress HTTP API
     */
    public static function mockHttpApi($responses = array())
    {
        $defaultResponse = array(
            'response' => array(
                'code' => 200,
                'message' => 'OK'
            ),
            'body' => '',
            'headers' => array()
        );

        Monkey\Functions\when('wp_remote_head')->alias(function($url) use ($responses, $defaultResponse) {
            return $responses[$url] ?? $defaultResponse;
        });

        Monkey\Functions\when('wp_remote_get')->alias(function($url) use ($responses, $defaultResponse) {
            return $responses[$url] ?? $defaultResponse;
        });

        Monkey\Functions\when('wp_remote_retrieve_response_code')->alias(function($response) {
            return $response['response']['code'] ?? 200;
        });

        Monkey\Functions\when('wp_remote_retrieve_response_message')->alias(function($response) {
            return $response['response']['message'] ?? 'OK';
        });

        Monkey\Functions\when('is_wp_error')->alias(function($response) {
            return $response instanceof \WP_Error;
        });
    }

    /**
     * Create mock WP_Error
     */
    public static function createWpError($message = 'Test error', $code = 'test_error')
    {
        $error = Mockery::mock('WP_Error');
        $error->shouldReceive('get_error_message')->andReturn($message);
        $error->shouldReceive('get_error_code')->andReturn($code);
        
        return $error;
    }

    /**
     * Mock WordPress scheduling functions
     */
    public static function mockScheduling()
    {
        Monkey\Functions\when('wp_schedule_single_event')->justReturn(true);
        Monkey\Functions\when('wp_unschedule_event')->justReturn(true);
        Monkey\Functions\when('wp_next_scheduled')->justReturn(false);
    }

    /**
     * Mock WordPress file functions
     */
    public static function mockFileFunctions()
    {
        Monkey\Functions\when('wp_upload_dir')->justReturn(array(
            'path' => '/tmp/uploads',
            'url' => 'https://example.com/uploads',
            'subdir' => '',
            'basedir' => '/tmp/uploads',
            'baseurl' => 'https://example.com/uploads',
            'error' => false
        ));
        
        Monkey\Functions\when('wp_mkdir_p')->justReturn(true);
        Monkey\Functions\when('wp_is_writable')->justReturn(true);
    }

    /**
     * Mock WordPress time functions
     */
    public static function mockTimeFunctions($currentTime = '2023-01-01 12:00:00')
    {
        Monkey\Functions\when('current_time')
            ->with('mysql')
            ->justReturn($currentTime);
            
        Monkey\Functions\when('current_time')
            ->with('timestamp')
            ->justReturn(strtotime($currentTime));
    }

    /**
     * Create test configuration array
     */
    public static function createTestConfig($overrides = array())
    {
        $defaults = array(
            'scan_depth' => 2,
            'max_pages' => 100,
            'include_external' => false,
            'timeout' => 30,
            'user_agent' => 'WordPress Link Checker Test'
        );
        
        return array_merge($defaults, $overrides);
    }

    /**
     * Create test scan progress data
     */
    public static function createTestProgress($overrides = array())
    {
        $defaults = array(
            'scan_id' => 1,
            'status' => 'running',
            'pages_scanned' => 5,
            'links_found' => 25,
            'broken_found' => 3,
            'current_page' => 'Test Page 5',
            'total_pages' => 10
        );
        
        return array_merge($defaults, $overrides);
    }

    /**
     * Reset all mocks
     */
    public static function resetMocks()
    {
        Mockery::close();
        
        if (class_exists('Brain\Monkey')) {
            Monkey\tearDown();
            Monkey\setUp();
        }
    }
}
