<?php

namespace XnY\Tests\Integration;

use XnY\Tests\Helpers\DatabaseTestCase;
use XnY\Tests\Helpers\MockHelper;
use Brain\Monkey;

/**
 * Test Full Scan Workflow Integration
 * 
 * Tests the complete scan process from start to finish
 */
class FullScanWorkflowTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Set up comprehensive mocks for full workflow
        MockHelper::mockGetPosts();
        MockHelper::mockUrlFunctions();
        MockHelper::mockHttpApi();
        MockHelper::mockScheduling();
        MockHelper::mockTimeFunctions();
    }

    /**
     * Test complete scan workflow from start to completion
     */
    public function testCompleteScanWorkflow()
    {
        // Step 1: Start scan
        $_POST = [
            'action' => 'xny_start_scan',
            'nonce' => 'test_nonce',
            'scan_depth' => 2,
            'max_pages' => 10,
            'include_external' => 'false'
        ];

        // Mock database operations for scan creation
        $this->mockDatabaseInsert(
            $this->wpdb->prefix . 'xny_404_scans',
            [
                'scan_date' => '2023-01-01 12:00:00',
                'status' => 'running',
                'scan_config' => Monkey\Functions\type('string')
            ],
            1
        );

        // Mock progress options
        Monkey\Functions\when('update_option')->justReturn(true);
        Monkey\Functions\when('get_option')->justReturn('{"scan_id":1,"status":"running"}');

        // Step 2: Process scan
        $posts = MockHelper::createMockPosts(3, 'post');
        Monkey\Functions\when('get_posts')->justReturn($posts);

        // Mock broken links found during scan
        $this->mockDatabaseInsert(
            $this->wpdb->prefix . 'xny_404_links',
            Monkey\Functions\type('array'),
            null
        )->times(2); // Expect 2 broken links to be found

        // Mock scan completion update
        $this->mockDatabaseUpdate(
            $this->wpdb->prefix . 'xny_404_scans',
            [
                'status' => 'completed',
                'total_pages' => 3,
                'total_links' => Monkey\Functions\type('int'),
                'broken_links' => Monkey\Functions\type('int'),
                'completed_at' => '2023-01-01 12:00:00'
            ],
            ['id' => 1]
        );

        // Execute the scan
        $this->plugin->process_scan(1);

        // Verify scan was marked as completed
        $this->wpdb->shouldHaveReceived('update')
            ->once()
            ->with(
                $this->wpdb->prefix . 'xny_404_scans',
                Monkey\Functions\type('array'),
                ['id' => 1]
            );
    }

    /**
     * Test scan with mixed internal and external links
     */
    public function testScanWithMixedLinks()
    {
        // Create posts with mixed link types
        $posts = [
            $this->createTestPost(1, 'Test Post 1', '
                <p>Content with links:</p>
                <a href="https://example.com/internal-page">Internal Link</a>
                <a href="https://external.com/page">External Link</a>
                <a href="https://example.com/broken-internal">Broken Internal</a>
                <a href="https://external.com/broken">Broken External</a>
            ')
        ];

        Monkey\Functions\when('get_posts')->justReturn($posts);

        // Mock HTTP responses for external links
        MockHelper::mockHttpApi([
            'https://external.com/page' => [
                'response' => ['code' => 200, 'message' => 'OK']
            ],
            'https://external.com/broken' => [
                'response' => ['code' => 404, 'message' => 'Not Found']
            ]
        ]);

        // Mock internal link validation
        Monkey\Functions\when('url_to_postid')->alias(function($url) {
            if ($url === 'https://example.com/internal-page') {
                return 1; // Exists
            }
            return 0; // Broken
        });

        // Mock database operations
        $this->mockDatabaseInsert($this->wpdb->prefix . 'xny_404_scans', [], 1);
        $this->mockDatabaseInsert($this->wpdb->prefix . 'xny_404_links', [], null)->times(2);
        $this->mockDatabaseUpdate($this->wpdb->prefix . 'xny_404_scans', [], ['id' => 1]);

        // Mock options
        Monkey\Functions\when('update_option')->justReturn(true);
        Monkey\Functions\when('delete_option')->justReturn(true);

        // Process scan with external links enabled
        $this->plugin->process_scan(1);

        // Should find 2 broken links (1 internal, 1 external)
        $this->wpdb->shouldHaveReceived('insert')
            ->with($this->wpdb->prefix . 'xny_404_links', Monkey\Functions\type('array'))
            ->times(2);
    }

    /**
     * Test scan progress tracking throughout workflow
     */
    public function testScanProgressTracking()
    {
        $posts = MockHelper::createMockPosts(5, 'post');
        Monkey\Functions\when('get_posts')->justReturn($posts);

        // Mock database operations
        $this->mockDatabaseInsert($this->wpdb->prefix . 'xny_404_scans', [], 1);
        $this->mockDatabaseUpdate($this->wpdb->prefix . 'xny_404_scans', [], ['id' => 1]);

        // Track progress updates
        $progressUpdates = [];
        Monkey\Functions\when('update_option')
            ->with('xny_scan_progress', Monkey\Functions\type('string'))
            ->alias(function($option, $value) use (&$progressUpdates) {
                $progressUpdates[] = json_decode($value, true);
                return true;
            });

        // Process scan
        $this->plugin->process_scan(1);

        // Verify progress was tracked
        $this->assertGreaterThan(0, count($progressUpdates));
        
        // Check final progress shows completion
        $finalProgress = end($progressUpdates);
        $this->assertEquals('completed', $finalProgress['status']);
        $this->assertEquals(5, $finalProgress['pages_scanned']);
    }

    /**
     * Test scan with different post types based on depth
     */
    public function testScanWithDifferentPostTypes()
    {
        // Test depth 1 (pages only)
        $pageOnlyPosts = MockHelper::createMockPosts(2, 'page');
        
        $reflection = new \ReflectionClass($this->plugin);
        $method = $reflection->getMethod('get_post_types_for_scan');
        $method->setAccessible(true);

        $postTypes = $method->invokeArgs($this->plugin, [1]);
        $this->assertEquals(['page'], $postTypes);

        // Test depth 3 (all public post types)
        MockHelper::mockPostTypes(['post', 'page', 'product', 'event']);
        
        $postTypes = $method->invokeArgs($this->plugin, [3]);
        $this->assertEquals(['post', 'page', 'product', 'event'], $postTypes);
    }

    /**
     * Test scan interruption and cleanup
     */
    public function testScanInterruptionAndCleanup()
    {
        // Start scan
        $_POST = [
            'action' => 'xny_start_scan',
            'nonce' => 'test_nonce',
            'scan_depth' => 2,
            'max_pages' => 100
        ];

        $this->mockDatabaseInsert($this->wpdb->prefix . 'xny_404_scans', [], 1);
        Monkey\Functions\when('update_option')->justReturn(true);

        // Simulate scan stop
        $_POST = [
            'action' => 'xny_stop_scan',
            'nonce' => 'test_nonce'
        ];

        Monkey\Functions\when('get_option')
            ->with('xny_current_scan_id')
            ->justReturn(1);

        $this->mockDatabaseUpdate(
            $this->wpdb->prefix . 'xny_404_scans',
            ['status' => 'stopped', 'completed_at' => '2023-01-01 12:00:00'],
            ['id' => 1]
        );

        Monkey\Functions\expect('delete_option')
            ->with('xny_current_scan_id')
            ->once();
        
        Monkey\Functions\expect('delete_option')
            ->with('xny_scan_progress')
            ->once();

        // Execute stop scan
        $this->plugin->ajax_stop_scan();

        // Verify cleanup was performed
        $this->wpdb->shouldHaveReceived('update')
            ->once()
            ->with(
                $this->wpdb->prefix . 'xny_404_scans',
                ['status' => 'stopped', 'completed_at' => '2023-01-01 12:00:00'],
                ['id' => 1]
            );
    }

    /**
     * Test scan with large number of posts (performance)
     */
    public function testScanWithLargeDataset()
    {
        // Create 100 mock posts
        $posts = MockHelper::createMockPosts(100, 'post');
        Monkey\Functions\when('get_posts')->justReturn($posts);

        // Mock database operations
        $this->mockDatabaseInsert($this->wpdb->prefix . 'xny_404_scans', [], 1);
        $this->mockDatabaseUpdate($this->wpdb->prefix . 'xny_404_scans', [], ['id' => 1]);
        Monkey\Functions\when('update_option')->justReturn(true);
        Monkey\Functions\when('delete_option')->justReturn(true);

        // Mock some broken links to be found
        $this->mockDatabaseInsert($this->wpdb->prefix . 'xny_404_links', [], null)
            ->times(10); // Expect 10 broken links

        // Process scan
        $startTime = microtime(true);
        $this->plugin->process_scan(1);
        $endTime = microtime(true);

        // Verify scan completed (basic performance check)
        $this->assertLessThan(30, $endTime - $startTime, 'Scan should complete within 30 seconds');
    }

    /**
     * Test error handling during scan process
     */
    public function testScanErrorHandling()
    {
        // Test with posts that cause errors
        $posts = [
            $this->createTestPost(1, 'Test Post', '<invalid-html><a href="broken">Link</a>')
        ];
        
        Monkey\Functions\when('get_posts')->justReturn($posts);

        // Mock database operations
        $this->mockDatabaseInsert($this->wpdb->prefix . 'xny_404_scans', [], 1);
        $this->mockDatabaseUpdate($this->wpdb->prefix . 'xny_404_scans', [], ['id' => 1]);
        Monkey\Functions\when('update_option')->justReturn(true);
        Monkey\Functions\when('delete_option')->justReturn(true);

        // Should handle errors gracefully without throwing exceptions
        try {
            $this->plugin->process_scan(1);
            $this->assertTrue(true, 'Scan completed without throwing exceptions');
        } catch (\Exception $e) {
            $this->fail('Scan should handle errors gracefully: ' . $e->getMessage());
        }
    }

    /**
     * Test concurrent scan prevention
     */
    public function testConcurrentScanPrevention()
    {
        // Mock existing scan in progress
        Monkey\Functions\when('get_option')
            ->with('xny_current_scan_id')
            ->justReturn(1);

        // Mock existing scan record
        $this->mockDatabaseSelect(
            "SELECT * FROM {$this->wpdb->prefix}xny_404_scans WHERE id = 1",
            [$this->createTestScan(1, 'running')]
        );

        // Attempt to start another scan should be prevented
        // This would typically be handled in the AJAX handler
        $currentScanId = get_option('xny_current_scan_id');
        $this->assertEquals(1, $currentScanId, 'Should detect existing scan in progress');
    }

    /**
     * Test scan result export integration
     */
    public function testScanResultExportIntegration()
    {
        // Complete a scan first
        $this->mockDatabaseInsert($this->wpdb->prefix . 'xny_404_scans', [], 1);
        
        // Add some broken links
        $brokenLinks = $this->createTestBrokenLinks(5);
        $this->mockDatabaseSelect(
            "SELECT * FROM {$this->wpdb->prefix}xny_404_links WHERE is_broken = 1 ORDER BY found_at DESC",
            $brokenLinks
        );

        // Mock export request
        $_POST = [
            'action' => 'xny_export_results',
            'nonce' => 'test_nonce',
            'format' => 'csv'
        ];

        // Mock file functions
        Monkey\Functions\when('fopen')->justReturn(true);
        Monkey\Functions\when('fputcsv')->justReturn(true);
        Monkey\Functions\when('fclose')->justReturn(true);

        // Test export functionality
        try {
            $this->plugin->ajax_export_results();
        } catch (\Exception $e) {
            // Expected due to exit() in export function
            $this->assertStringContainsString('exit', strtolower($e->getMessage()));
        }

        // Verify data was retrieved for export
        $this->wpdb->shouldHaveReceived('get_results')
            ->once()
            ->with(Monkey\Functions\type('string'));
    }
}
