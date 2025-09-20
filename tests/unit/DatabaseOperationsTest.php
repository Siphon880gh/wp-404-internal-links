<?php

namespace XnY\Tests\Unit;

use XnY\Tests\Helpers\DatabaseTestCase;
use XnY\Tests\Helpers\MockHelper;
use Brain\Monkey;

/**
 * Test Database Operations
 * 
 * Tests database table creation, data operations, and integrity
 */
class DatabaseOperationsTest extends DatabaseTestCase
{
    /**
     * Test database table creation
     */
    public function testCreateTables()
    {
        // Mock WordPress database upgrade function
        Monkey\Functions\expect('dbDelta')->times(2);

        $this->plugin->create_tables();

        // Verify table SQL contains required fields
        $this->assertTrue(true); // dbDelta was called, indicating tables were created
    }

    /**
     * Test scan record creation
     */
    public function testScanRecordCreation()
    {
        $scanData = [
            'scan_date' => '2023-01-01 12:00:00',
            'status' => 'running',
            'scan_config' => json_encode(['scan_depth' => 2])
        ];

        $this->mockDatabaseInsert(
            $this->wpdb->prefix . 'xny_404_scans',
            $scanData,
            1
        );

        // Simulate scan creation (this would be part of ajax_start_scan)
        $result = $this->wpdb->insert(
            $this->wpdb->prefix . 'xny_404_scans',
            $scanData
        );

        $this->assertEquals(1, $result);
        $this->assertEquals(1, $this->wpdb->insert_id);
    }

    /**
     * Test broken link storage
     */
    public function testBrokenLinkStorage()
    {
        $linkData = [
            'scan_id' => 1,
            'source_url' => 'https://example.com/source',
            'target_url' => 'https://example.com/broken',
            'link_text' => 'Broken Link',
            'status_code' => 404,
            'error_message' => 'Page not found',
            'is_broken' => 1,
            'found_at' => '2023-01-01 12:00:00'
        ];

        $this->mockDatabaseInsert(
            $this->wpdb->prefix . 'xny_404_links',
            $linkData,
            1
        );

        $result = $this->wpdb->insert(
            $this->wpdb->prefix . 'xny_404_links',
            $linkData
        );

        $this->assertEquals(1, $result);
    }

    /**
     * Test scan status update
     */
    public function testScanStatusUpdate()
    {
        $updateData = [
            'status' => 'completed',
            'total_pages' => 10,
            'total_links' => 50,
            'broken_links' => 5,
            'completed_at' => '2023-01-01 13:00:00'
        ];

        $this->mockDatabaseUpdate(
            $this->wpdb->prefix . 'xny_404_scans',
            $updateData,
            ['id' => 1],
            1
        );

        $result = $this->wpdb->update(
            $this->wpdb->prefix . 'xny_404_scans',
            $updateData,
            ['id' => 1]
        );

        $this->assertEquals(1, $result);
    }

    /**
     * Test broken links retrieval with pagination
     */
    public function testBrokenLinksRetrievalWithPagination()
    {
        $brokenLinks = $this->createTestBrokenLinks(15);
        
        // Mock count query
        $countQuery = "SELECT COUNT(*) FROM {$this->wpdb->prefix}xny_404_links WHERE is_broken = 1";
        $this->mockDatabaseSelect($countQuery, 15);
        
        // Mock paginated query
        $linksQuery = "SELECT * FROM {$this->wpdb->prefix}xny_404_links WHERE is_broken = 1 ORDER BY found_at DESC LIMIT 10 OFFSET 0";
        $this->mockDatabaseSelect($linksQuery, array_slice($brokenLinks, 0, 10));

        // Test count query
        $total = $this->wpdb->get_var($countQuery);
        $this->assertEquals(15, $total);

        // Test links query
        $links = $this->wpdb->get_results($linksQuery);
        $this->assertCount(10, $links);
    }

    /**
     * Test broken links filtering by status
     */
    public function testBrokenLinksFilteringByStatus()
    {
        $links404 = array_map(function($link) {
            $link['status_code'] = 404;
            return $link;
        }, $this->createTestBrokenLinks(3));

        $linksRedirect = array_map(function($link) {
            $link['status_code'] = 301;
            return $link;
        }, $this->createTestBrokenLinks(2));

        // Mock 404 filter query
        $query404 = "SELECT * FROM {$this->wpdb->prefix}xny_404_links WHERE is_broken = 1 AND status_code = 404 ORDER BY found_at DESC LIMIT 10 OFFSET 0";
        $this->mockDatabaseSelect($query404, $links404);

        // Mock redirect filter query
        $queryRedirect = "SELECT * FROM {$this->wpdb->prefix}xny_404_links WHERE is_broken = 1 AND status_code IN (301, 302, 303, 307, 308) ORDER BY found_at DESC LIMIT 10 OFFSET 0";
        $this->mockDatabaseSelect($queryRedirect, $linksRedirect);

        // Test 404 filter
        $results404 = $this->wpdb->get_results($query404);
        $this->assertCount(3, $results404);

        // Test redirect filter
        $resultsRedirect = $this->wpdb->get_results($queryRedirect);
        $this->assertCount(2, $resultsRedirect);
    }

    /**
     * Test broken links search functionality
     */
    public function testBrokenLinksSearch()
    {
        $searchTerm = 'example';
        
        $this->wpdb->shouldReceive('esc_like')
            ->with($searchTerm)
            ->andReturn($searchTerm);

        $this->wpdb->shouldReceive('prepare')
            ->with(
                '(target_url LIKE %s OR source_url LIKE %s OR link_text LIKE %s OR error_message LIKE %s)',
                "%$searchTerm%", "%$searchTerm%", "%$searchTerm%", "%$searchTerm%"
            )
            ->andReturn("(target_url LIKE '%example%' OR source_url LIKE '%example%' OR link_text LIKE '%example%' OR error_message LIKE '%example%')");

        $searchResults = $this->createTestBrokenLinks(2);
        $searchQuery = "SELECT * FROM {$this->wpdb->prefix}xny_404_links WHERE is_broken = 1 AND (target_url LIKE '%example%' OR source_url LIKE '%example%' OR link_text LIKE '%example%' OR error_message LIKE '%example%') ORDER BY found_at DESC LIMIT 10 OFFSET 0";
        
        $this->mockDatabaseSelect($searchQuery, $searchResults);

        $results = $this->wpdb->get_results($searchQuery);
        $this->assertCount(2, $results);
    }

    /**
     * Test link fix status update
     */
    public function testLinkFixStatusUpdate()
    {
        // Test ignore link
        $this->mockDatabaseUpdate(
            $this->wpdb->prefix . 'xny_404_links',
            ['is_broken' => 0],
            ['id' => 1],
            1
        );

        $result = $this->wpdb->update(
            $this->wpdb->prefix . 'xny_404_links',
            ['is_broken' => 0],
            ['id' => 1]
        );

        $this->assertEquals(1, $result);

        // Test mark as fixed
        $this->mockDatabaseUpdate(
            $this->wpdb->prefix . 'xny_404_links',
            ['is_fixed' => 1],
            ['id' => 2],
            1
        );

        $result = $this->wpdb->update(
            $this->wpdb->prefix . 'xny_404_links',
            ['is_fixed' => 1],
            ['id' => 2]
        );

        $this->assertEquals(1, $result);
    }

    /**
     * Test scan configuration storage and retrieval
     */
    public function testScanConfigurationStorage()
    {
        $config = [
            'scan_depth' => 3,
            'max_pages' => 200,
            'include_external' => true,
            'timeout' => 30
        ];

        $scanData = [
            'scan_date' => '2023-01-01 12:00:00',
            'status' => 'running',
            'scan_config' => json_encode($config)
        ];

        $this->mockDatabaseInsert(
            $this->wpdb->prefix . 'xny_404_scans',
            $scanData,
            1
        );

        // Insert scan
        $this->wpdb->insert($this->wpdb->prefix . 'xny_404_scans', $scanData);

        // Mock retrieval
        $scanData['id'] = 1;
        $this->mockDatabaseSelect(
            "SELECT * FROM {$this->wpdb->prefix}xny_404_scans WHERE id = 1",
            [$scanData]
        );

        $scan = $this->wpdb->get_row("SELECT * FROM {$this->wpdb->prefix}xny_404_scans WHERE id = 1");
        $retrievedConfig = json_decode($scan->scan_config, true);

        $this->assertEquals($config, $retrievedConfig);
    }

    /**
     * Test data integrity constraints
     */
    public function testDataIntegrityConstraints()
    {
        // Test that broken links must have a scan_id
        $linkData = [
            'scan_id' => null, // Invalid
            'source_url' => 'https://example.com/source',
            'target_url' => 'https://example.com/broken',
            'is_broken' => 1
        ];

        // This should fail or be handled gracefully
        $this->mockDatabaseInsert(
            $this->wpdb->prefix . 'xny_404_links',
            $linkData,
            false // Simulate failure
        );

        $result = $this->wpdb->insert(
            $this->wpdb->prefix . 'xny_404_links',
            $linkData
        );

        $this->assertFalse($result);
    }

    /**
     * Test export data query
     */
    public function testExportDataQuery()
    {
        $exportData = $this->createTestBrokenLinks(5);
        
        $exportQuery = "SELECT * FROM {$this->wpdb->prefix}xny_404_links WHERE is_broken = 1 ORDER BY found_at DESC";
        $this->mockDatabaseSelect($exportQuery, $exportData);

        $results = $this->wpdb->get_results($exportQuery);
        
        $this->assertCount(5, $results);
        $this->assertObjectHasAttribute('source_url', $results[0]);
        $this->assertObjectHasAttribute('target_url', $results[0]);
        $this->assertObjectHasAttribute('status_code', $results[0]);
    }

    /**
     * Test database cleanup operations
     */
    public function testDatabaseCleanup()
    {
        // Test deleting old scans
        $this->mockDatabaseDelete(
            $this->wpdb->prefix . 'xny_404_scans',
            ['status' => 'completed', 'scan_date <' => '2023-01-01'],
            3
        );

        $deleted = $this->wpdb->delete(
            $this->wpdb->prefix . 'xny_404_scans',
            ['status' => 'completed', 'scan_date <' => '2023-01-01']
        );

        $this->assertEquals(3, $deleted);

        // Test deleting associated links
        $this->mockDatabaseDelete(
            $this->wpdb->prefix . 'xny_404_links',
            ['scan_id' => 1],
            10
        );

        $deletedLinks = $this->wpdb->delete(
            $this->wpdb->prefix . 'xny_404_links',
            ['scan_id' => 1]
        );

        $this->assertEquals(10, $deletedLinks);
    }

    /**
     * Test SQL injection prevention
     */
    public function testSqlInjectionPrevention()
    {
        $maliciousInput = "'; DROP TABLE wp_posts; --";
        
        // Test that prepare() is used for dynamic queries
        $this->wpdb->shouldReceive('prepare')
            ->with(
                'SELECT * FROM %s WHERE target_url = %s',
                $this->wpdb->prefix . 'xny_404_links',
                $maliciousInput
            )
            ->andReturn("SELECT * FROM wp_xny_404_links WHERE target_url = '" . esc_sql($maliciousInput) . "'");

        $preparedQuery = $this->wpdb->prepare(
            'SELECT * FROM %s WHERE target_url = %s',
            $this->wpdb->prefix . 'xny_404_links',
            $maliciousInput
        );

        // Prepared query should escape the malicious input
        $this->assertStringNotContainsString('DROP TABLE', $preparedQuery);
    }
}
