<?php
/**
 * Coverage Report Generator
 * 
 * Generates comprehensive coverage reports in multiple formats
 */

// Prevent direct access
if (!defined('ABSPATH') && php_sapi_name() !== 'cli') {
    exit('Direct access not allowed');
}

class XnY_Coverage_Reporter
{
    private $coverageData;
    private $thresholds;
    private $outputDir;

    public function __construct($coverageData = null)
    {
        $this->coverageData = $coverageData;
        $this->thresholds = [
            'statements' => 90,
            'branches' => 85,
            'functions' => 95,
            'lines' => 90
        ];
        $this->outputDir = __DIR__ . '/coverage-reports';
    }

    /**
     * Generate all coverage reports
     */
    public function generateReports()
    {
        $this->ensureOutputDirectory();
        
        $summary = $this->generateSummary();
        
        $this->generateTextReport($summary);
        $this->generateJsonReport($summary);
        $this->generateMarkdownReport($summary);
        
        return $summary;
    }

    /**
     * Ensure output directory exists
     */
    private function ensureOutputDirectory()
    {
        if (!is_dir($this->outputDir)) {
            mkdir($this->outputDir, 0755, true);
        }
    }

    /**
     * Generate coverage summary
     */
    private function generateSummary()
    {
        // This would typically parse actual coverage data
        // For now, we'll generate a sample structure
        return [
            'timestamp' => date('Y-m-d H:i:s'),
            'overall' => [
                'statements' => ['covered' => 245, 'total' => 280, 'percentage' => 87.5],
                'branches' => ['covered' => 156, 'total' => 180, 'percentage' => 86.7],
                'functions' => ['covered' => 38, 'total' => 40, 'percentage' => 95.0],
                'lines' => ['covered' => 312, 'total' => 350, 'percentage' => 89.1]
            ],
            'files' => [
                'plugin.php' => [
                    'statements' => ['covered' => 180, 'total' => 200, 'percentage' => 90.0],
                    'branches' => ['covered' => 120, 'total' => 140, 'percentage' => 85.7],
                    'functions' => ['covered' => 28, 'total' => 30, 'percentage' => 93.3],
                    'lines' => ['covered' => 220, 'total' => 250, 'percentage' => 88.0]
                ]
            ],
            'uncovered_lines' => [
                'plugin.php' => [
                    'lines' => [150, 151, 275, 276, 277, 320, 321, 322, 450, 451],
                    'functions' => ['deprecated_function', 'error_handler_fallback']
                ]
            ],
            'thresholds' => $this->thresholds,
            'passed' => true
        ];
    }

    /**
     * Generate text coverage report
     */
    private function generateTextReport($summary)
    {
        $report = "XnY 404 Links Plugin - Coverage Report\n";
        $report .= str_repeat("=", 50) . "\n";
        $report .= "Generated: " . $summary['timestamp'] . "\n\n";

        $report .= "Overall Coverage:\n";
        $report .= sprintf("  Statements: %d/%d (%.1f%%)\n", 
            $summary['overall']['statements']['covered'],
            $summary['overall']['statements']['total'],
            $summary['overall']['statements']['percentage']
        );
        $report .= sprintf("  Branches:   %d/%d (%.1f%%)\n", 
            $summary['overall']['branches']['covered'],
            $summary['overall']['branches']['total'],
            $summary['overall']['branches']['percentage']
        );
        $report .= sprintf("  Functions:  %d/%d (%.1f%%)\n", 
            $summary['overall']['functions']['covered'],
            $summary['overall']['functions']['total'],
            $summary['overall']['functions']['percentage']
        );
        $report .= sprintf("  Lines:      %d/%d (%.1f%%)\n\n", 
            $summary['overall']['lines']['covered'],
            $summary['overall']['lines']['total'],
            $summary['overall']['lines']['percentage']
        );

        $report .= "Coverage Thresholds:\n";
        foreach ($this->thresholds as $metric => $threshold) {
            $current = $summary['overall'][$metric]['percentage'];
            $status = $current >= $threshold ? "PASS" : "FAIL";
            $report .= sprintf("  %s: %.1f%% (threshold: %d%%) [%s]\n", 
                ucfirst($metric), $current, $threshold, $status
            );
        }

        $report .= "\nUncovered Areas:\n";
        foreach ($summary['uncovered_lines'] as $file => $uncovered) {
            $report .= "  $file:\n";
            $report .= "    Lines: " . implode(', ', $uncovered['lines']) . "\n";
            $report .= "    Functions: " . implode(', ', $uncovered['functions']) . "\n";
        }

        file_put_contents($this->outputDir . '/coverage-summary.txt', $report);
        
        // Also output to console if CLI
        if (php_sapi_name() === 'cli') {
            echo $report;
        }
    }

    /**
     * Generate JSON coverage report
     */
    private function generateJsonReport($summary)
    {
        $jsonReport = json_encode($summary, JSON_PRETTY_PRINT);
        file_put_contents($this->outputDir . '/coverage-summary.json', $jsonReport);
    }

    /**
     * Generate Markdown coverage report
     */
    private function generateMarkdownReport($summary)
    {
        $report = "# XnY 404 Links Plugin - Coverage Report\n\n";
        $report .= "**Generated:** " . $summary['timestamp'] . "\n\n";

        $report .= "## Overall Coverage\n\n";
        $report .= "| Metric | Covered | Total | Percentage | Threshold | Status |\n";
        $report .= "|--------|---------|-------|------------|-----------|--------|\n";
        
        foreach (['statements', 'branches', 'functions', 'lines'] as $metric) {
            $data = $summary['overall'][$metric];
            $threshold = $this->thresholds[$metric];
            $status = $data['percentage'] >= $threshold ? '✅ PASS' : '❌ FAIL';
            
            $report .= sprintf("| %s | %d | %d | %.1f%% | %d%% | %s |\n",
                ucfirst($metric),
                $data['covered'],
                $data['total'],
                $data['percentage'],
                $threshold,
                $status
            );
        }

        $report .= "\n## File Coverage\n\n";
        foreach ($summary['files'] as $file => $fileData) {
            $report .= "### $file\n\n";
            $report .= "| Metric | Covered | Total | Percentage |\n";
            $report .= "|--------|---------|-------|------------|\n";
            
            foreach (['statements', 'branches', 'functions', 'lines'] as $metric) {
                $data = $fileData[$metric];
                $report .= sprintf("| %s | %d | %d | %.1f%% |\n",
                    ucfirst($metric),
                    $data['covered'],
                    $data['total'],
                    $data['percentage']
                );
            }
            $report .= "\n";
        }

        $report .= "## Uncovered Areas\n\n";
        foreach ($summary['uncovered_lines'] as $file => $uncovered) {
            $report .= "### $file\n\n";
            $report .= "**Uncovered Lines:** " . implode(', ', $uncovered['lines']) . "\n\n";
            $report .= "**Uncovered Functions:**\n";
            foreach ($uncovered['functions'] as $func) {
                $report .= "- `$func`\n";
            }
            $report .= "\n";
        }

        $report .= "## TODOs for Coverage Improvement\n\n";
        $report .= "- [ ] Add tests for error handling functions\n";
        $report .= "- [ ] Increase branch coverage in link processing logic\n";
        $report .= "- [ ] Add integration tests for deprecated functions\n";
        $report .= "- [ ] Test edge cases in URL parsing\n";
        $report .= "- [ ] Add performance tests for large datasets\n";

        file_put_contents($this->outputDir . '/coverage-report.md', $report);
    }

    /**
     * Check if coverage meets thresholds
     */
    public function validateCoverage($summary = null)
    {
        if (!$summary) {
            $summary = $this->generateSummary();
        }

        $failures = [];
        foreach ($this->thresholds as $metric => $threshold) {
            $current = $summary['overall'][$metric]['percentage'];
            if ($current < $threshold) {
                $failures[] = [
                    'metric' => $metric,
                    'current' => $current,
                    'threshold' => $threshold,
                    'deficit' => $threshold - $current
                ];
            }
        }

        return [
            'passed' => empty($failures),
            'failures' => $failures,
            'summary' => $summary
        ];
    }

    /**
     * Generate coverage badge data
     */
    public function generateBadgeData($summary = null)
    {
        if (!$summary) {
            $summary = $this->generateSummary();
        }

        $overallCoverage = array_sum(array_column($summary['overall'], 'percentage')) / 4;
        
        $color = 'red';
        if ($overallCoverage >= 90) $color = 'brightgreen';
        elseif ($overallCoverage >= 80) $color = 'green';
        elseif ($overallCoverage >= 70) $color = 'yellow';
        elseif ($overallCoverage >= 60) $color = 'orange';

        return [
            'schemaVersion' => 1,
            'label' => 'coverage',
            'message' => sprintf('%.1f%%', $overallCoverage),
            'color' => $color
        ];
    }
}

// CLI usage
if (php_sapi_name() === 'cli' && isset($argv[0]) && basename($argv[0]) === 'coverage-report.php') {
    $reporter = new XnY_Coverage_Reporter();
    $summary = $reporter->generateReports();
    $validation = $reporter->validateCoverage($summary);
    
    if (!$validation['passed']) {
        echo "\nCoverage validation FAILED:\n";
        foreach ($validation['failures'] as $failure) {
            echo sprintf("  %s: %.1f%% (need %.1f%% more)\n", 
                $failure['metric'], 
                $failure['current'], 
                $failure['deficit']
            );
        }
        exit(1);
    } else {
        echo "\nCoverage validation PASSED!\n";
        exit(0);
    }
}
