<?php

namespace XnY\Tests\Unit;

use XnY\Tests\Helpers\TestCase;
use XnY\Tests\Helpers\MockHelper;
use Brain\Monkey;

/**
 * Test Link Processing Functionality
 * 
 * Tests link extraction, validation, and processing logic
 */
class LinkProcessingTest extends TestCase
{
    private $plugin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->plugin = new \XnY_404_Links();
        MockHelper::mockUrlFunctions();
    }

    /**
     * Test link extraction from HTML content
     */
    public function testExtractLinksFromContent()
    {
        $reflection = new \ReflectionClass($this->plugin);
        $method = $reflection->getMethod('extract_links_from_content');
        $method->setAccessible(true);

        $htmlContent = '
            <p>Test content with links:</p>
            <a href="https://example.com/page1">External Link</a>
            <a href="/internal-page">Internal Relative Link</a>
            <a href="#anchor">Anchor Link</a>
            <a href="mailto:test@example.com">Email Link</a>
            <a href="">Empty Link</a>
            <a href="https://example.com/page2">Another External Link</a>
        ';

        $links = $method->invokeArgs($this->plugin, [$htmlContent, 1]);

        // Should extract only valid links (excluding anchors, mailto, empty)
        $this->assertCount(3, $links);
        
        // Check extracted links
        $this->assertEquals('https://example.com/page1', $links[0]['url']);
        $this->assertEquals('External Link', $links[0]['text']);
        
        $this->assertEquals('https://example.com/internal-page', $links[1]['url']);
        $this->assertEquals('Internal Relative Link', $links[1]['text']);
        
        $this->assertEquals('https://example.com/page2', $links[2]['url']);
        $this->assertEquals('Another External Link', $links[2]['text']);
    }

    /**
     * Test relative URL conversion
     */
    public function testRelativeUrlConversion()
    {
        $reflection = new \ReflectionClass($this->plugin);
        $method = $reflection->getMethod('extract_links_from_content');
        $method->setAccessible(true);

        $htmlContent = '
            <a href="/absolute-path">Absolute Path</a>
            <a href="relative-path">Relative Path</a>
        ';

        Monkey\Functions\when('get_permalink')
            ->with(1)
            ->justReturn('https://example.com/post-1/');

        $links = $method->invokeArgs($this->plugin, [$htmlContent, 1]);

        $this->assertEquals('https://example.com/absolute-path', $links[0]['url']);
        $this->assertEquals('https://example.com/post-1/relative-path', $links[1]['url']);
    }

    /**
     * Test internal link validation (existing post)
     */
    public function testTestLinkInternal()
    {
        $reflection = new \ReflectionClass($this->plugin);
        $method = $reflection->getMethod('test_link');
        $method->setAccessible(true);

        // Mock existing post
        Monkey\Functions\when('url_to_postid')
            ->with('https://example.com/existing-post')
            ->justReturn(1);

        Monkey\Functions\when('get_post')
            ->with(1)
            ->justReturn((object) ['post_status' => 'publish']);

        $result = $method->invokeArgs($this->plugin, ['https://example.com/existing-post']);

        $this->assertFalse($result['is_broken']);
    }

    /**
     * Test internal link validation (non-existing post)
     */
    public function testTestLinkInternalBroken()
    {
        $reflection = new \ReflectionClass($this->plugin);
        $method = $reflection->getMethod('test_link');
        $method->setAccessible(true);

        // Mock non-existing post
        Monkey\Functions\when('url_to_postid')
            ->with('https://example.com/non-existing-post')
            ->justReturn(0);

        $result = $method->invokeArgs($this->plugin, ['https://example.com/non-existing-post']);

        $this->assertTrue($result['is_broken']);
        $this->assertEquals(404, $result['status_code']);
        $this->assertEquals('Page not found', $result['error_message']);
    }

    /**
     * Test external link validation (successful)
     */
    public function testTestLinkExternalSuccess()
    {
        $reflection = new \ReflectionClass($this->plugin);
        $method = $reflection->getMethod('test_link');
        $method->setAccessible(true);

        // Mock successful HTTP response
        MockHelper::mockHttpApi([
            'https://external.com/page' => [
                'response' => ['code' => 200, 'message' => 'OK'],
                'body' => 'Success'
            ]
        ]);

        $result = $method->invokeArgs($this->plugin, ['https://external.com/page', true]);

        $this->assertFalse($result['is_broken']);
        $this->assertEquals(200, $result['status_code']);
    }

    /**
     * Test external link validation (404 error)
     */
    public function testTestLinkExternal404()
    {
        $reflection = new \ReflectionClass($this->plugin);
        $method = $reflection->getMethod('test_link');
        $method->setAccessible(true);

        // Mock 404 HTTP response
        MockHelper::mockHttpApi([
            'https://external.com/broken' => [
                'response' => ['code' => 404, 'message' => 'Not Found'],
                'body' => 'Not Found'
            ]
        ]);

        $result = $method->invokeArgs($this->plugin, ['https://external.com/broken', true]);

        $this->assertTrue($result['is_broken']);
        $this->assertEquals(404, $result['status_code']);
        $this->assertEquals('Not Found', $result['error_message']);
    }

    /**
     * Test external link validation (network error)
     */
    public function testTestLinkExternalNetworkError()
    {
        $reflection = new \ReflectionClass($this->plugin);
        $method = $reflection->getMethod('test_link');
        $method->setAccessible(true);

        // Mock network error
        $error = MockHelper::createWpError('Connection timeout', 'http_request_failed');
        
        Monkey\Functions\when('wp_remote_head')
            ->with('https://external.com/timeout')
            ->justReturn($error);
            
        Monkey\Functions\when('is_wp_error')
            ->with($error)
            ->justReturn(true);

        $result = $method->invokeArgs($this->plugin, ['https://external.com/timeout', true]);

        $this->assertTrue($result['is_broken']);
        $this->assertEquals(0, $result['status_code']);
        $this->assertEquals('Connection timeout', $result['error_message']);
    }

    /**
     * Test external link skipping when not included
     */
    public function testTestLinkExternalSkipped()
    {
        $reflection = new \ReflectionClass($this->plugin);
        $method = $reflection->getMethod('test_link');
        $method->setAccessible(true);

        $result = $method->invokeArgs($this->plugin, ['https://external.com/page', false]);

        $this->assertFalse($result['is_broken']);
        $this->assertArrayNotHasKey('status_code', $result);
    }

    /**
     * Test scan progress update
     */
    public function testUpdateScanProgress()
    {
        $reflection = new \ReflectionClass($this->plugin);
        $method = $reflection->getMethod('update_scan_progress');
        $method->setAccessible(true);

        // Mock existing progress
        Monkey\Functions\when('get_option')
            ->with('xny_scan_progress', '{}')
            ->justReturn('{"scan_id":1,"status":"running"}');

        Monkey\Functions\expect('update_option')
            ->once()
            ->with('xny_scan_progress', Monkey\Functions\type('string'));

        $updateData = [
            'pages_scanned' => 5,
            'links_found' => 25,
            'current_page' => 'Test Page'
        ];

        $method->invokeArgs($this->plugin, [1, $updateData]);
    }

    /**
     * Test malformed HTML handling
     */
    public function testExtractLinksFromMalformedHtml()
    {
        $reflection = new \ReflectionClass($this->plugin);
        $method = $reflection->getMethod('extract_links_from_content');
        $method->setAccessible(true);

        $malformedHtml = '
            <p>Malformed HTML with unclosed tags
            <a href="https://example.com/link1">Link 1
            <div><a href="https://example.com/link2">Link 2</a>
            <p><a href="https://example.com/link3">Link 3</a></p>
        ';

        $links = $method->invokeArgs($this->plugin, [$malformedHtml, 1]);

        // Should still extract links despite malformed HTML
        $this->assertGreaterThan(0, count($links));
        $this->assertEquals('https://example.com/link1', $links[0]['url']);
    }

    /**
     * Test empty content handling
     */
    public function testExtractLinksFromEmptyContent()
    {
        $reflection = new \ReflectionClass($this->plugin);
        $method = $reflection->getMethod('extract_links_from_content');
        $method->setAccessible(true);

        $links = $method->invokeArgs($this->plugin, ['', 1]);

        $this->assertEmpty($links);
    }

    /**
     * Test content with no links
     */
    public function testExtractLinksFromContentWithoutLinks()
    {
        $reflection = new \ReflectionClass($this->plugin);
        $method = $reflection->getMethod('extract_links_from_content');
        $method->setAccessible(true);

        $content = '<p>This is just plain text content without any links.</p>';

        $links = $method->invokeArgs($this->plugin, [$content, 1]);

        $this->assertEmpty($links);
    }
}
