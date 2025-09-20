<?php

namespace XnY\Tests\Helpers;

use Mockery;

/**
 * Database Test Case Class
 * 
 * Provides database-specific testing functionality
 */
abstract class DatabaseTestCase extends TestCase
{
    protected $wpdb;
    protected $plugin;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create mock database
        $this->wpdb = $this->createMockWpdb();
        
        // Set global wpdb
        global $wpdb;
        $wpdb = $this->wpdb;
        
        // Initialize plugin
        $this->plugin = new \XnY_404_Links();
    }

    /**
     * Test database table creation
     */
    protected function assertTablesCreated()
    {
        $scans_table = $this->wpdb->prefix . 'xny_404_scans';
        $links_table = $this->wpdb->prefix . 'xny_404_links';
        
        // Mock dbDelta function
        \Brain\Monkey\Functions\when('dbDelta')->justReturn(true);
        
        // Test table creation
        $this->plugin->create_tables();
        
        // Verify dbDelta was called
        \Brain\Monkey\Functions\expect('dbDelta')->once();
    }

    /**
     * Mock database insert operation
     */
    protected function mockDatabaseInsert($table, $data, $returnId = 1)
    {
        $this->wpdb->shouldReceive('insert')
            ->with($table, $data)
            ->andReturn(1);
            
        $this->wpdb->insert_id = $returnId;
    }

    /**
     * Mock database select operation
     */
    protected function mockDatabaseSelect($query, $results = array())
    {
        if (is_array($results) && count($results) === 1) {
            $this->wpdb->shouldReceive('get_row')
                ->with($query)
                ->andReturn((object) $results[0]);
        } elseif (is_array($results)) {
            $this->wpdb->shouldReceive('get_results')
                ->with($query)
                ->andReturn(array_map(function($item) {
                    return (object) $item;
                }, $results));
        } else {
            $this->wpdb->shouldReceive('get_var')
                ->with($query)
                ->andReturn($results);
        }
    }

    /**
     * Mock database update operation
     */
    protected function mockDatabaseUpdate($table, $data, $where, $result = 1)
    {
        $this->wpdb->shouldReceive('update')
            ->with($table, $data, $where)
            ->andReturn($result);
    }

    /**
     * Mock database delete operation
     */
    protected function mockDatabaseDelete($table, $where, $result = 1)
    {
        $this->wpdb->shouldReceive('delete')
            ->with($table, $where)
            ->andReturn($result);
    }

    /**
     * Create test scan data
     */
    protected function createTestScan($id = 1, $status = 'running')
    {
        return array(
            'id' => $id,
            'scan_date' => '2023-01-01 12:00:00',
            'status' => $status,
            'total_pages' => 10,
            'total_links' => 50,
            'broken_links' => 5,
            'scan_config' => json_encode(array(
                'scan_depth' => 2,
                'max_pages' => 100,
                'include_external' => false
            )),
            'completed_at' => null
        );
    }

    /**
     * Create test broken links data
     */
    protected function createTestBrokenLinks($count = 3)
    {
        $links = array();
        
        for ($i = 1; $i <= $count; $i++) {
            $links[] = array(
                'id' => $i,
                'scan_id' => 1,
                'source_url' => "https://example.com/page-{$i}",
                'target_url' => "https://example.com/broken-{$i}",
                'link_text' => "Broken Link {$i}",
                'status_code' => 404,
                'error_message' => 'Page not found',
                'is_broken' => 1,
                'is_fixed' => 0,
                'found_at' => '2023-01-01 12:00:00'
            );
        }
        
        return $links;
    }

    /**
     * Assert scan record was created
     */
    protected function assertScanCreated($scanData)
    {
        $this->wpdb->shouldHaveReceived('insert')
            ->once()
            ->with(
                $this->wpdb->prefix . 'xny_404_scans',
                Mockery::subset($scanData)
            );
    }

    /**
     * Assert broken link was stored
     */
    protected function assertBrokenLinkStored($linkData)
    {
        $this->wpdb->shouldHaveReceived('insert')
            ->once()
            ->with(
                $this->wpdb->prefix . 'xny_404_links',
                Mockery::subset($linkData)
            );
    }

    /**
     * Assert scan was updated
     */
    protected function assertScanUpdated($scanId, $updateData)
    {
        $this->wpdb->shouldHaveReceived('update')
            ->once()
            ->with(
                $this->wpdb->prefix . 'xny_404_scans',
                Mockery::subset($updateData),
                array('id' => $scanId)
            );
    }
}
