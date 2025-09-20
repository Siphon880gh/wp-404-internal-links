/**
 * XnY 404 Links Plugin - Scan & Report Functionality
 * 
 * @package XnY_404_Links
 * @version 1.0.0
 */

(function($) {
    'use strict';

    let scanInterval = null;
    let currentScanId = null;

    // Initialize when DOM is ready
    $(document).ready(function() {
        initPanelAnimations();
        initTabSwitching();
        initCardInteractions();
        initLoadingAnimations();
        initScanFunctionality();
        initBrokenLinksPage();
    });

    /**
     * Initialize panel entrance animations
     */
    function initPanelAnimations() {
        // Add animation class to main content
        $('.tab-content').addClass('resource-panel');
        
        // Animate elements on page load
        setTimeout(function() {
            $('.resource-panel').addClass('animate-in');
        }, 100);

        // Add stagger animation to lists
        $('.tab-content ul').addClass('stagger-animation');
    }

    /**
     * Enhanced tab switching with smooth transitions
     */
    function initTabSwitching() {
        // Get current URL parameters
        const urlParams = new URLSearchParams(window.location.search);
        const currentTab = urlParams.get('tab') || 'wp';

        // Add click handlers to nav tabs
        $('.nav-tab').on('click', function(e) {
            e.preventDefault();
            
            const $clickedTab = $(this);
            const href = $clickedTab.attr('href');
            
            // Don't animate if already active
            if ($clickedTab.hasClass('nav-tab-active')) {
                return;
            }

            // Show loading state
            showLoadingSpinner();
            
            // Remove active state from all tabs
            $('.nav-tab').removeClass('nav-tab-active');
            
            // Add active state to clicked tab
            $clickedTab.addClass('nav-tab-active');
            
            // Animate out current content
            $('.tab-content').addClass('fade-out');
            
            // Navigate to new tab after animation
            setTimeout(function() {
                window.location.href = href;
            }, 300);
        });
    }

    /**
     * Initialize card interactions and hover effects
     */
    function initCardInteractions() {
        // Add hover effects to existing links
        $('.tab-content a').each(function() {
            const $link = $(this);
            if (!$link.hasClass('nav-tab')) {
                $link.addClass('card-link pulse-on-hover');
            }
        });

        // Add click ripple effect
        $(document).on('click', '.resource-card, .card-link', function(e) {
            const $element = $(this);
            const rect = this.getBoundingClientRect();
            const ripple = $('<span class="ripple"></span>');
            
            const size = Math.max(rect.width, rect.height);
            const x = e.clientX - rect.left - size / 2;
            const y = e.clientY - rect.top - size / 2;
            
            ripple.css({
                position: 'absolute',
                width: size + 'px',
                height: size + 'px',
                left: x + 'px',
                top: y + 'px',
                background: 'rgba(59, 130, 246, 0.3)',
                borderRadius: '50%',
                transform: 'scale(0)',
                animation: 'ripple 0.6s linear',
                pointerEvents: 'none',
                zIndex: 1
            });
            
            if ($element.css('position') === 'static') {
                $element.css('position', 'relative');
            }
            
            $element.append(ripple);
            
            setTimeout(function() {
                ripple.remove();
            }, 600);
        });

        // Add CSS for ripple animation
        if (!$('#ripple-animation').length) {
            $('head').append(`
                <style id="ripple-animation">
                @keyframes ripple {
                    to {
                        transform: scale(4);
                        opacity: 0;
                    }
                }
                .fade-out {
                    opacity: 0;
                    transform: translateY(-10px);
                    transition: all 0.3s ease;
                }
                </style>
            `);
        }
    }

    /**
     * Show loading spinner during transitions
     */
    function showLoadingSpinner() {
        const spinner = `
            <div class="loading-spinner">
                <div class="spinner"></div>
            </div>
        `;
        
        $('.tab-content').html(spinner);
    }

    /**
     * Initialize loading animations for dynamic content
     */
    function initLoadingAnimations() {
        // Animate elements that come into view
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    const $element = $(entry.target);
                    $element.addClass('animate-in');
                    observer.unobserve(entry.target);
                }
            });
        }, observerOptions);

        // Observe elements for animation
        $('.resource-card, .card-link').each(function() {
            observer.observe(this);
        });
    }

    /**
     * Smooth scroll to top when switching tabs
     */
    function smoothScrollToTop() {
        $('html, body').animate({
            scrollTop: 0
        }, 300);
    }

    /**
     * Add parallax effect to background elements
     */
    function initParallaxEffects() {
        $(window).on('scroll', function() {
            const scrolled = $(window).scrollTop();
            const parallax = $('.parallax-element');
            const speed = 0.5;
            
            parallax.each(function() {
                const $this = $(this);
                const yPos = -(scrolled * speed);
                $this.css('transform', 'translateY(' + yPos + 'px)');
            });
        });
    }

    /**
     * Initialize advanced animations
     */
    function initAdvancedAnimations() {
        // Typing animation for headings
        $('.typing-animation').each(function() {
            const $element = $(this);
            const text = $element.text();
            $element.text('');
            
            let i = 0;
            const typeWriter = setInterval(function() {
                if (i < text.length) {
                    $element.text($element.text() + text.charAt(i));
                    i++;
                } else {
                    clearInterval(typeWriter);
                }
            }, 100);
        });

        // Floating animation for icons
        $('.floating-icon').each(function() {
            const $icon = $(this);
            const delay = Math.random() * 2000;
            
            setTimeout(function() {
                $icon.css('animation', 'float 3s ease-in-out infinite');
            }, delay);
        });
    }

    // Add CSS for advanced animations
    if (!$('#advanced-animations').length) {
        $('head').append(`
            <style id="advanced-animations">
            @keyframes float {
                0%, 100% { transform: translateY(0px); }
                50% { transform: translateY(-10px); }
            }
            
            .animate-in {
                opacity: 1 !important;
                transform: translateY(0) !important;
            }
            
            .typing-animation::after {
                content: '|';
                animation: blink 1s infinite;
            }
            
            @keyframes blink {
                0%, 50% { opacity: 1; }
                51%, 100% { opacity: 0; }
            }
            </style>
        `);
    }

    // Initialize advanced features
    initParallaxEffects();
    initAdvancedAnimations();

    /**
     * Initialize scan functionality
     */
    function initScanFunctionality() {
        // Start scan button
        $('#start-scan').on('click', function() {
            const scanDepth = $('#scan-depth').val();
            const maxPages = $('#max-pages').val();
            const includeExternal = $('#include-external').is(':checked');
            
            startScan(scanDepth, maxPages, includeExternal);
        });

        // Stop scan button
        $('#stop-scan').on('click', function() {
            stopScan();
        });

        // View results button
        $('#view-results').on('click', function() {
            window.location.href = '?page=xny-404-links&tab=broken-links';
        });
    }

    /**
     * Start link scan
     */
    function startScan(scanDepth, maxPages, includeExternal) {
        $.ajax({
            url: xny_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'xny_start_scan',
                nonce: xny_ajax.nonce,
                scan_depth: scanDepth,
                max_pages: maxPages,
                include_external: includeExternal
            },
            beforeSend: function() {
                $('#start-scan').prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>Starting...');
            },
            success: function(response) {
                if (response.success) {
                    currentScanId = response.data.scan_id;
                    showScanProgress();
                    startProgressPolling();
                    
                    $('#start-scan').hide();
                    $('#stop-scan').show();
                } else {
                    alert('Failed to start scan: ' + response.data);
                    $('#start-scan').prop('disabled', false).html('<i class="fas fa-search mr-2"></i>Start Link Scan');
                }
            },
            error: function() {
                alert('Failed to start scan. Please try again.');
                $('#start-scan').prop('disabled', false).html('<i class="fas fa-search mr-2"></i>Start Link Scan');
            }
        });
    }

    /**
     * Stop scan
     */
    function stopScan() {
        $.ajax({
            url: xny_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'xny_stop_scan',
                nonce: xny_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    stopProgressPolling();
                    hideScanProgress();
                    
                    $('#start-scan').show().prop('disabled', false).html('<i class="fas fa-search mr-2"></i>Start Link Scan');
                    $('#stop-scan').hide();
                    $('#view-results').show();
                }
            }
        });
    }

    /**
     * Show scan progress section
     */
    function showScanProgress() {
        $('#scan-progress').removeClass('hidden');
        $('#scan-progress').addClass('animate-in');
    }

    /**
     * Hide scan progress section
     */
    function hideScanProgress() {
        $('#scan-progress').addClass('hidden');
    }

    /**
     * Start polling for scan progress
     */
    function startProgressPolling() {
        scanInterval = setInterval(function() {
            getScanProgress();
        }, 2000); // Poll every 2 seconds
    }

    /**
     * Stop polling for scan progress
     */
    function stopProgressPolling() {
        if (scanInterval) {
            clearInterval(scanInterval);
            scanInterval = null;
        }
    }

    /**
     * Get scan progress via AJAX
     */
    function getScanProgress() {
        $.ajax({
            url: xny_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'xny_get_scan_progress',
                nonce: xny_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    updateProgressDisplay(response.data);
                    
                    // Check if scan is complete
                    if (response.data.status === 'completed' || response.data.status === 'stopped') {
                        stopProgressPolling();
                        hideScanProgress();
                        $('#start-scan').show().prop('disabled', false).html('<i class="fas fa-search mr-2"></i>Start Link Scan');
                        $('#stop-scan').hide();
                        $('#view-results').show();
                        
                        // Update last scan info
                        updateLastScanInfo(response.data);
                    }
                }
            }
        });
    }

    /**
     * Update progress display
     */
    function updateProgressDisplay(data) {
        const percentage = data.total_pages > 0 ? Math.round((data.pages_scanned / data.total_pages) * 100) : 0;
        
        $('#overall-percentage').text(percentage + '%');
        $('#overall-progress-bar').css('width', percentage + '%');
        
        $('#pages-scanned').text(data.pages_scanned || 0);
        $('#links-found').text(data.links_found || 0);
        $('#broken-found').text(data.broken_found || 0);
        $('#current-page').text(data.current_page || 'Processing...');
    }

    /**
     * Update last scan info
     */
    function updateLastScanInfo(data) {
        const now = new Date();
        $('#last-scan-date').text('Last scan: ' + now.toLocaleString());
        $('#last-scan-stats').text(`Found ${data.links_found} links, ${data.broken_found} broken`);
    }

    /**
     * Initialize broken links page functionality
     */
    function initBrokenLinksPage() {
        // Load broken links on page load if we're on the broken links tab
        if (window.location.href.includes('tab=broken-links')) {
            loadBrokenLinks();
        }

        // Filter and search handlers
        $('#filter-status, #filter-type').on('change', function() {
            loadBrokenLinks();
        });

        $('#search-links').on('keyup', debounce(function() {
            loadBrokenLinks();
        }, 500));

        // Refresh button
        $('#refresh-scan').on('click', function() {
            loadBrokenLinks();
        });

        // Export button
        $('#export-report').on('click', function() {
            exportResults('csv');
        });

        // Select all checkbox
        $('#select-all-links').on('change', function() {
            $('.link-checkbox').prop('checked', this.checked);
        });
    }

    /**
     * Load broken links via AJAX
     */
    function loadBrokenLinks(page = 1) {
        const statusFilter = $('#filter-status').val() || 'all';
        const typeFilter = $('#filter-type').val() || 'all';
        const search = $('#search-links').val() || '';

        $.ajax({
            url: xny_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'xny_get_broken_links',
                nonce: xny_ajax.nonce,
                page: page,
                per_page: 10,
                status_filter: statusFilter,
                type_filter: typeFilter,
                search: search
            },
            beforeSend: function() {
                $('#links-loading').show();
                $('#no-broken-links').hide();
                $('#broken-links-table').hide();
            },
            success: function(response) {
                $('#links-loading').hide();
                
                if (response.success && response.data.links.length > 0) {
                    displayBrokenLinks(response.data);
                    updatePagination(response.data);
                    $('#broken-links-table').show();
                    
                    // Update summary stats
                    updateSummaryStats(response.data);
                } else {
                    $('#no-broken-links').show();
                }
            },
            error: function() {
                $('#links-loading').hide();
                alert('Failed to load broken links. Please try again.');
            }
        });
    }

    /**
     * Display broken links in table
     */
    function displayBrokenLinks(data) {
        const tbody = $('#broken-links-tbody');
        tbody.empty();

        data.links.forEach(function(link) {
            const statusBadge = getStatusBadge(link.status_code);
            const row = `
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <input type="checkbox" class="link-checkbox h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded" value="${link.id}">
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm font-medium text-gray-900">${escapeHtml(link.target_url)}</div>
                        <div class="text-sm text-gray-500">${escapeHtml(link.link_text || 'No text')}</div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm text-gray-900">${escapeHtml(link.source_url)}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        ${statusBadge}
                        ${link.error_message ? '<div class="text-xs text-gray-500 mt-1">' + escapeHtml(link.error_message) + '</div>' : ''}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <button onclick="fixLink(${link.id})" class="text-blue-600 hover:text-blue-900 mr-3">Fix</button>
                        <button onclick="ignoreLink(${link.id})" class="text-gray-600 hover:text-gray-900">Ignore</button>
                    </td>
                </tr>
            `;
            tbody.append(row);
        });
    }

    /**
     * Get status badge HTML
     */
    function getStatusBadge(statusCode) {
        if (statusCode === 404) {
            return '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">404 Error</span>';
        } else if ([301, 302, 303, 307, 308].includes(statusCode)) {
            return '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">Redirect</span>';
        } else if (!statusCode) {
            return '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">Unknown</span>';
        } else {
            return '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">' + statusCode + '</span>';
        }
    }

    /**
     * Update pagination
     */
    function updatePagination(data) {
        $('#showing-from').text(((data.page - 1) * data.per_page) + 1);
        $('#showing-to').text(Math.min(data.page * data.per_page, data.total));
        $('#total-results').text(data.total);

        // Build pagination nav
        const nav = $('#pagination-nav');
        nav.empty();

        for (let i = 1; i <= data.total_pages; i++) {
            const isActive = i === data.page;
            const button = `
                <button onclick="loadBrokenLinks(${i})" 
                        class="relative inline-flex items-center px-4 py-2 border text-sm font-medium ${isActive ? 'z-10 bg-blue-50 border-blue-500 text-blue-600' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'}">
                    ${i}
                </button>
            `;
            nav.append(button);
        }
    }

    /**
     * Update summary statistics
     */
    function updateSummaryStats(data) {
        // This would be enhanced with actual stats from the server
        $('#critical-count').text(data.total || 0);
        $('#total-links').text(data.total || 0);
        $('#last-scan-time').text('Just now');
    }

    /**
     * Export results
     */
    function exportResults(format) {
        const form = $('<form>', {
            'method': 'POST',
            'action': xny_ajax.ajax_url
        });

        form.append($('<input>', {
            'type': 'hidden',
            'name': 'action',
            'value': 'xny_export_results'
        }));

        form.append($('<input>', {
            'type': 'hidden',
            'name': 'nonce',
            'value': xny_ajax.nonce
        }));

        form.append($('<input>', {
            'type': 'hidden',
            'name': 'format',
            'value': format
        }));

        $('body').append(form);
        form.submit();
        form.remove();
    }

    /**
     * Debounce function
     */
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    /**
     * Escape HTML
     */
    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }

    // Global functions for inline event handlers
    window.fixLink = function(linkId) {
        $('#broken-link-url').val(''); // This would be populated with actual link data
        $('#fix-link-modal').removeClass('hidden');
        
        // Store link ID for later use
        $('#fix-link-modal').data('link-id', linkId);
    };

    window.ignoreLink = function(linkId) {
        if (confirm('Are you sure you want to ignore this link?')) {
            $.ajax({
                url: xny_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'xny_fix_link',
                    nonce: xny_ajax.nonce,
                    link_id: linkId,
                    fix_option: 'ignore'
                },
                success: function(response) {
                    if (response.success) {
                        loadBrokenLinks(); // Reload the list
                    } else {
                        alert('Failed to ignore link: ' + response.data);
                    }
                }
            });
        }
    };

    window.closeFixModal = function() {
        $('#fix-link-modal').addClass('hidden');
    };

    window.applyFix = function() {
        const linkId = $('#fix-link-modal').data('link-id');
        const fixOption = $('input[name="fix-option"]:checked').val();
        const replacementUrl = $('#replacement-url').val();

        $.ajax({
            url: xny_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'xny_fix_link',
                nonce: xny_ajax.nonce,
                link_id: linkId,
                fix_option: fixOption,
                replacement_url: replacementUrl
            },
            success: function(response) {
                if (response.success) {
                    closeFixModal();
                    loadBrokenLinks(); // Reload the list
                    alert('Link fixed successfully!');
                } else {
                    alert('Failed to fix link: ' + response.data);
                }
            }
        });
    };

    window.showScanHistory = function() {
        $('#scan-history-modal').removeClass('hidden');
        // Load scan history via AJAX (to be implemented)
    };

    window.closeScanHistory = function() {
        $('#scan-history-modal').addClass('hidden');
    };

    window.exportResults = function() {
        exportResults('csv');
    };

})(jQuery);
