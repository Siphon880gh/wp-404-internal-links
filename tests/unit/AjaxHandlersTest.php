<?php

namespace XnY\Tests\Unit;

use XnY\Tests\Helpers\AjaxTestCase;
use XnY\Tests\Helpers\MockHelper;
use Brain\Monkey;

/**
 * Test AJAX Handlers
 * 
 * Tests all AJAX endpoints for security, functionality, and error handling
 */
class AjaxHandlersTest extends AjaxTestCase
{
    /**
     * Test start scan AJAX handler - successful request
     */
    public function testAjaxStartScanSuccess()
    {
        $this->createScanStartRequest(2, 100, false);
        
        // Mock database operations
        $this->mockDatabaseInsert($this->wpdb->prefix . 'xny_404_scans', [
            'scan_date' => '2023-01-01 12:00:00',
            'status' => 'running',
            'scan_config' => Monkey\Functions\type('string')
        ], 1);

        // Mock options
        Monkey\Functions\expect('update_option')->times(2);
        Monkey\Functions\expect('wp_schedule_single_event')->once();

        // Mock the process_scan method to prevent actual processing
        $plugin = \Mockery::mock(\XnY_404_Links::class)->makePartial();
        $plugin->shouldReceive('process_scan')->once();

        $this->expectAjaxSuccess([
            'scan_id' => 1,
            'message' => 'Scan started successfully'
        ]);

        $plugin->ajax_start_scan();
    }

    /**
     * Test start scan AJAX handler - database error
     */
    public function testAjaxStartScanDatabaseError()
    {
        $this->createScanStartRequest();
        
        // Mock database failure
        $this->wpdb->shouldReceive('insert')->andReturn(false);

        $this->expectAjaxError('Failed to create scan record');

        $this->plugin->ajax_start_scan();
    }

    /**
     * Test start scan AJAX handler - unauthorized access
     */
    public function testAjaxStartScanUnauthorized()
    {
        $this->createScanStartRequest();
        $this->assertUnauthorizedAccess('ajax_start_scan');
    }

    /**
     * Test start scan AJAX handler - invalid nonce
     */
    public function testAjaxStartScanInvalidNonce()
    {
        $this->createScanStartRequest();
        $this->assertInvalidNonce('ajax_start_scan');
    }

    /**
     * Test get scan progress AJAX handler - success
     */
    public function testAjaxGetScanProgressSuccess()
    {
        $this->simulateAjaxRequest('xny_get_scan_progress');
        
        $progressData = MockHelper::createTestProgress();
        $this->mockProgressOptions(1, $progressData);

        $this->expectAjaxSuccess($progressData);

        $this->plugin->ajax_get_scan_progress();
    }

    /**
     * Test get scan progress AJAX handler - no scan in progress
     */
    public function testAjaxGetScanProgressNoScan()
    {
        $this->simulateAjaxRequest('xny_get_scan_progress');
        
        Monkey\Functions\when('get_option')
            ->with('xny_scan_progress', '{}')
            ->justReturn('{}');

        $this->expectAjaxError('No scan in progress');

        $this->plugin->ajax_get_scan_progress();
    }

    /**
     * Test stop scan AJAX handler - success
     */
    public function testAjaxStopScanSuccess()
    {
        $this->simulateAjaxRequest('xny_stop_scan');
        
        Monkey\Functions\when('get_option')
            ->with('xny_current_scan_id')
            ->justReturn(1);

        $this->mockDatabaseUpdate(
            $this->wpdb->prefix . 'xny_404_scans',
            ['status' => 'stopped', 'completed_at' => '2023-01-01 12:00:00'],
            ['id' => 1]
        );

        Monkey\Functions\expect('delete_option')->times(2);

        $this->expectAjaxSuccess('Scan stopped');

        $this->plugin->ajax_stop_scan();
    }

    /**
     * Test get broken links AJAX handler - success with results
     */
    public function testAjaxGetBrokenLinksSuccess()
    {
        $this->createBrokenLinksRequest(1, 10, 'all', '');
        
        $brokenLinks = $this->createTestBrokenLinks(3);
        
        // Mock count query
        $this->mockDatabaseSelect(
            "SELECT COUNT(*) FROM {$this->wpdb->prefix}xny_404_links WHERE is_broken = 1",
            3
        );
        
        // Mock links query
        $this->mockDatabaseSelect(
            "SELECT * FROM {$this->wpdb->prefix}xny_404_links WHERE is_broken = 1 ORDER BY found_at DESC LIMIT 10 OFFSET 0",
            $brokenLinks
        );

        $expectedData = [
            'links' => array_map(function($link) {
                return (object) $link;
            }, $brokenLinks),
            'total' => 3,
            'page' => 1,
            'per_page' => 10,
            'total_pages' => 1
        ];

        $this->expectAjaxSuccess($expectedData);

        $this->plugin->ajax_get_broken_links();
    }

    /**
     * Test get broken links AJAX handler - with search filter
     */
    public function testAjaxGetBrokenLinksWithSearch()
    {
        $this->createBrokenLinksRequest(1, 10, 'all', 'test');
        
        // Mock search query with LIKE conditions
        $this->wpdb->shouldReceive('esc_like')
            ->with('test')
            ->andReturn('test');
            
        $this->wpdb->shouldReceive('prepare')
            ->andReturn("(target_url LIKE '%test%' OR source_url LIKE '%test%' OR link_text LIKE '%test%' OR error_message LIKE '%test%')");

        $this->mockDatabaseSelect('SELECT COUNT(*) FROM', 1);
        $this->mockDatabaseSelect('SELECT * FROM', [$this->createTestLink()]);

        $this->plugin->ajax_get_broken_links();
    }

    /**
     * Test get broken links AJAX handler - with status filter
     */
    public function testAjaxGetBrokenLinksWithStatusFilter()
    {
        $this->createBrokenLinksRequest(1, 10, '404', '');
        
        $this->mockDatabaseSelect('SELECT COUNT(*) FROM', 1);
        $this->mockDatabaseSelect('SELECT * FROM', [$this->createTestLink()]);

        $this->plugin->ajax_get_broken_links();
    }

    /**
     * Test fix link AJAX handler - ignore option
     */
    public function testAjaxFixLinkIgnore()
    {
        $this->createFixLinkRequest(1, 'ignore');
        
        // Mock link retrieval
        $this->mockDatabaseSelect(
            "SELECT * FROM {$this->wpdb->prefix}xny_404_links WHERE id = 1",
            [$this->createTestLink()]
        );
        
        // Mock update operation
        $this->mockDatabaseUpdate(
            $this->wpdb->prefix . 'xny_404_links',
            ['is_broken' => 0],
            ['id' => 1]
        );

        $this->expectAjaxSuccess('Link ignored');

        $this->plugin->ajax_fix_link();
    }

    /**
     * Test fix link AJAX handler - replace option
     */
    public function testAjaxFixLinkReplace()
    {
        $this->createFixLinkRequest(1, 'replace', 'https://example.com/replacement');
        
        $this->mockDatabaseSelect(
            "SELECT * FROM {$this->wpdb->prefix}xny_404_links WHERE id = 1",
            [$this->createTestLink()]
        );
        
        $this->mockDatabaseUpdate(
            $this->wpdb->prefix . 'xny_404_links',
            ['is_fixed' => 1],
            ['id' => 1]
        );

        $this->expectAjaxSuccess('Link marked as fixed (replacement functionality coming soon)');

        $this->plugin->ajax_fix_link();
    }

    /**
     * Test fix link AJAX handler - missing replacement URL
     */
    public function testAjaxFixLinkMissingReplacementUrl()
    {
        $this->createFixLinkRequest(1, 'replace', '');
        
        $this->mockDatabaseSelect(
            "SELECT * FROM {$this->wpdb->prefix}xny_404_links WHERE id = 1",
            [$this->createTestLink()]
        );

        $this->expectAjaxError('Replacement URL is required');

        $this->plugin->ajax_fix_link();
    }

    /**
     * Test fix link AJAX handler - link not found
     */
    public function testAjaxFixLinkNotFound()
    {
        $this->createFixLinkRequest(999, 'ignore');
        
        $this->mockDatabaseSelect(
            "SELECT * FROM {$this->wpdb->prefix}xny_404_links WHERE id = 999",
            []
        );

        $this->expectAjaxError('Link not found');

        $this->plugin->ajax_fix_link();
    }

    /**
     * Test export results AJAX handler - CSV format
     */
    public function testAjaxExportResultsCSV()
    {
        $this->simulateAjaxRequest('xny_export_results', ['format' => 'csv']);
        
        $brokenLinks = $this->createTestBrokenLinks(2);
        $this->mockDatabaseSelect(
            "SELECT * FROM {$this->wpdb->prefix}xny_404_links WHERE is_broken = 1 ORDER BY found_at DESC",
            $brokenLinks
        );

        // Mock file output functions
        Monkey\Functions\when('fopen')->justReturn(true);
        Monkey\Functions\when('fputcsv')->justReturn(true);
        Monkey\Functions\when('fclose')->justReturn(true);

        // This should trigger headers and exit, so we expect no return
        $this->expectOutputString('');

        try {
            $this->plugin->ajax_export_results();
        } catch (\Exception $e) {
            // Expected due to exit() call in export
            $this->assertStringContainsString('exit', strtolower($e->getMessage()));
        }
    }

    /**
     * Test export results AJAX handler - unsupported format
     */
    public function testAjaxExportResultsUnsupportedFormat()
    {
        $this->simulateAjaxRequest('xny_export_results', ['format' => 'xml']);
        
        $this->expectAjaxError('Unsupported export format');

        $this->plugin->ajax_export_results();
    }

    /**
     * Test AJAX security for all handlers
     */
    public function testAjaxSecurityForAllHandlers()
    {
        $handlers = [
            'ajax_start_scan',
            'ajax_get_scan_progress', 
            'ajax_stop_scan',
            'ajax_get_broken_links',
            'ajax_fix_link',
            'ajax_export_results'
        ];

        foreach ($handlers as $handler) {
            $this->assertAjaxSecurity($handler, true);
        }
    }

    /**
     * Test input sanitization
     */
    public function testInputSanitization()
    {
        // Test with malicious input
        $_POST = [
            'action' => 'xny_start_scan',
            'nonce' => 'test_nonce',
            'scan_depth' => '<script>alert("xss")</script>',
            'max_pages' => 'not_a_number',
            'include_external' => 'malicious_value'
        ];

        Monkey\Functions\when('sanitize_text_field')->returnArg();
        Monkey\Functions\when('intval')->alias(function($value) {
            return is_numeric($value) ? (int) $value : 0;
        });

        // Mock database to prevent actual insertion
        $this->mockDatabaseInsert($this->wpdb->prefix . 'xny_404_scans', [], 1);
        Monkey\Functions\when('update_option')->justReturn(true);
        Monkey\Functions\when('wp_schedule_single_event')->justReturn(true);

        // Create mock plugin to avoid actual processing
        $plugin = \Mockery::mock(\XnY_404_Links::class)->makePartial();
        $plugin->shouldReceive('process_scan')->once();

        // Should not throw errors due to input sanitization
        $plugin->ajax_start_scan();
    }
}
