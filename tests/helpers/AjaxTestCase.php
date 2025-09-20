<?php

namespace XnY\Tests\Helpers;

use Brain\Monkey;

/**
 * AJAX Test Case Class
 * 
 * Provides AJAX-specific testing functionality
 */
abstract class AjaxTestCase extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock AJAX-specific WordPress functions
        $this->setUpAjaxMocks();
    }

    /**
     * Set up AJAX-specific mocks
     */
    protected function setUpAjaxMocks()
    {
        // Mock WordPress AJAX functions
        Monkey\Functions\when('check_ajax_referer')->justReturn(true);
        Monkey\Functions\when('wp_send_json_success')->alias(function($data) {
            return array('success' => true, 'data' => $data);
        });
        Monkey\Functions\when('wp_send_json_error')->alias(function($data) {
            return array('success' => false, 'data' => $data);
        });
        Monkey\Functions\when('wp_die')->alias(function($message) {
            throw new \Exception($message);
        });
        
        // Mock $_POST superglobal
        $_POST = array();
        $_GET = array();
    }

    /**
     * Simulate AJAX request
     */
    protected function simulateAjaxRequest($action, $data = array(), $nonce = 'test_nonce')
    {
        $_POST = array_merge(array(
            'action' => $action,
            'nonce' => $nonce
        ), $data);
        
        // Mock WordPress AJAX environment
        Monkey\Functions\when('wp_doing_ajax')->justReturn(true);
        Monkey\Functions\when('is_admin')->justReturn(true);
        
        return $_POST;
    }

    /**
     * Test AJAX security
     */
    protected function assertAjaxSecurity($method, $expectSecurityCheck = true)
    {
        if ($expectSecurityCheck) {
            // Expect nonce verification
            Monkey\Functions\expect('check_ajax_referer')
                ->once()
                ->with('xny_404_nonce', 'nonce');
                
            // Expect capability check
            Monkey\Functions\expect('current_user_can')
                ->once()
                ->with('manage_options')
                ->andReturn(true);
        }
        
        // Call the method
        $result = call_user_func(array($this->plugin, $method));
        
        return $result;
    }

    /**
     * Test unauthorized AJAX access
     */
    protected function assertUnauthorizedAccess($method)
    {
        // Mock unauthorized user
        Monkey\Functions\when('current_user_can')->justReturn(false);
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Unauthorized');
        
        call_user_func(array($this->plugin, $method));
    }

    /**
     * Test invalid nonce
     */
    protected function assertInvalidNonce($method)
    {
        // Mock invalid nonce
        Monkey\Functions\when('check_ajax_referer')->justReturn(false);
        
        $this->expectException(\Exception::class);
        
        call_user_func(array($this->plugin, $method));
    }

    /**
     * Create AJAX response expectation
     */
    protected function expectAjaxSuccess($data = null)
    {
        if ($data !== null) {
            Monkey\Functions\expect('wp_send_json_success')
                ->once()
                ->with($data);
        } else {
            Monkey\Functions\expect('wp_send_json_success')->once();
        }
    }

    /**
     * Create AJAX error expectation
     */
    protected function expectAjaxError($message = null)
    {
        if ($message !== null) {
            Monkey\Functions\expect('wp_send_json_error')
                ->once()
                ->with($message);
        } else {
            Monkey\Functions\expect('wp_send_json_error')->once();
        }
    }

    /**
     * Mock WordPress options for progress tracking
     */
    protected function mockProgressOptions($scanId = 1, $progressData = array())
    {
        $defaultProgress = array(
            'scan_id' => $scanId,
            'status' => 'running',
            'pages_scanned' => 0,
            'links_found' => 0,
            'broken_found' => 0,
            'current_page' => 'Initializing...'
        );
        
        $progress = array_merge($defaultProgress, $progressData);
        
        Monkey\Functions\when('get_option')
            ->with('xny_current_scan_id')
            ->justReturn($scanId);
            
        Monkey\Functions\when('get_option')
            ->with('xny_scan_progress', '{}')
            ->justReturn(json_encode($progress));
            
        Monkey\Functions\when('update_option')->justReturn(true);
        Monkey\Functions\when('delete_option')->justReturn(true);
    }

    /**
     * Test scan start AJAX request
     */
    protected function createScanStartRequest($depth = 2, $maxPages = 100, $includeExternal = false)
    {
        return $this->simulateAjaxRequest('xny_start_scan', array(
            'scan_depth' => $depth,
            'max_pages' => $maxPages,
            'include_external' => $includeExternal ? 'true' : 'false'
        ));
    }

    /**
     * Test broken links request
     */
    protected function createBrokenLinksRequest($page = 1, $perPage = 10, $statusFilter = 'all', $search = '')
    {
        return $this->simulateAjaxRequest('xny_get_broken_links', array(
            'page' => $page,
            'per_page' => $perPage,
            'status_filter' => $statusFilter,
            'type_filter' => 'all',
            'search' => $search
        ));
    }

    /**
     * Test fix link request
     */
    protected function createFixLinkRequest($linkId, $fixOption = 'ignore', $replacementUrl = '')
    {
        return $this->simulateAjaxRequest('xny_fix_link', array(
            'link_id' => $linkId,
            'fix_option' => $fixOption,
            'replacement_url' => $replacementUrl
        ));
    }
}
