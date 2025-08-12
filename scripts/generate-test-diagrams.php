<?php
/**
 * Generate Mermaid sequence diagrams from Codeception test cases
 * This script analyzes test files and creates visual documentation
 */

class TestDiagramGenerator
{
    private string $testDir;
    private string $outputDir;
    private array $diagrams = [];

    public function __construct(string $testDir = 'tests/Acceptance', string $outputDir = 'docs/diagrams')
    {
        $this->testDir = $testDir;
        $this->outputDir = $outputDir;
        
        // Ensure output directory exists
        if (!is_dir($this->outputDir)) {
            mkdir($this->outputDir, 0755, true);
        }
    }

    public function generateAll(): void
    {
        echo "🔍 Analyzing test files in {$this->testDir}/\n";
        
        $testFiles = glob($this->testDir . '/*Cest.php');
        
        foreach ($testFiles as $testFile) {
            $this->analyzeTestFile($testFile);
        }
        
        $this->generateOverviewDiagram();
        $this->generateReadme();
        
        echo "✅ Generated " . count($this->diagrams) . " diagrams in {$this->outputDir}/\n";
    }

    private function analyzeTestFile(string $filePath): void
    {
        $content = file_get_contents($filePath);
        $className = basename($filePath, '.php');
        
        echo "📝 Processing {$className}...\n";
        
        // Extract test methods
        preg_match_all('/public function (\w+)\(.*?\): void\s*\{(.*?)\}/s', $content, $matches);
        
        $testMethods = [];
        for ($i = 0; $i < count($matches[1]); $i++) {
            $methodName = $matches[1][$i];
            $methodBody = $matches[2][$i];
            
            if (strpos($methodName, 'test') === 0 || !in_array($methodName, ['_before', '_after'])) {
                $testMethods[$methodName] = $this->analyzeTestMethod($methodBody);
            }
        }
        
        if (!empty($testMethods)) {
            $this->generateClassDiagram($className, $testMethods);
        }
    }

    private function analyzeTestMethod(string $methodBody): array
    {
        $steps = [];
        
        // Common Codeception patterns
        $patterns = [
            '/\$I->amOnPage\([\'"]([^\'"]*)[\'"]/' => 'Navigate to {1}',
            '/\$I->see\([\'"]([^\'"]*)[\'"]/' => 'Verify text: {1}',
            '/\$I->seeElement\([\'"]([^\'"]*)[\'"]/' => 'Verify element: {1}',
            '/\$I->click\([\'"]([^\'"]*)[\'"]/' => 'Click: {1}',
            '/\$I->clickLink\([\'"]([^\'"]*)[\'"]/' => 'Click link: {1}',
            '/\$I->seeCurrentUrlEquals\([\'"]([^\'"]*)[\'"]/' => 'Verify URL: {1}',
            '/\$I->seeInTitle\([\'"]([^\'"]*)[\'"]/' => 'Verify title: {1}',
            '/\$I->fillField\([\'"]([^\'"]*)[\'"]\s*,\s*[\'"]([^\'"]*)[\'"]/' => 'Fill {1}: {2}',
            '/\$I->submitForm/' => 'Submit form',
            '/\$I->wait\((\d+)\)/' => 'Wait {1} seconds',
            '/\$I->seeResponseCodeIs\((\d+)\)/' => 'Verify HTTP {1}',
        ];
        
        foreach ($patterns as $pattern => $description) {
            if (preg_match_all($pattern, $methodBody, $matches)) {
                foreach ($matches[0] as $index => $match) {
                    $desc = $description;
                    for ($i = 1; $i < count($matches); $i++) {
                        $desc = str_replace("{{$i}}", $matches[$i][$index], $desc);
                    }
                    $steps[] = $desc;
                }
            }
        }
        
        return $steps;
    }

    private function generateClassDiagram(string $className, array $testMethods): void
    {
        $diagram = "```mermaid\nsequenceDiagram\n";
        $diagram .= "    participant User\n";
        $diagram .= "    participant Browser\n";
        $diagram .= "    participant App as PHP App\n";
        $diagram .= "    participant DB as Database\n\n";
        
        foreach ($testMethods as $methodName => $steps) {
            $diagram .= "    %% Test: " . $this->formatMethodName($methodName) . "\n";
            $diagram .= "    Note over User,DB: " . $this->formatMethodName($methodName) . "\n";
            
            foreach ($steps as $step) {
                $diagram .= $this->convertStepToMermaid($step);
            }
            
            $diagram .= "\n";
        }
        
        $diagram .= "```\n";
        
        $fileName = $this->outputDir . '/' . strtolower($className) . '.md';
        $content = "# {$className} Test Flow\n\n";
        $content .= "This diagram shows the test flow for {$className}.\n\n";
        $content .= $diagram;
        
        file_put_contents($fileName, $content);
        
        $this->diagrams[$className] = [
            'file' => $fileName,
            'methods' => array_keys($testMethods),
            'stepCount' => array_sum(array_map('count', $testMethods))
        ];
    }

    private function convertStepToMermaid(string $step): string
    {
        if (strpos($step, 'Navigate to') === 0) {
            return "    User->>Browser: {$step}\n    Browser->>App: HTTP Request\n    App-->>Browser: Page Response\n";
        }
        
        if (strpos($step, 'Verify') === 0) {
            return "    Browser->>App: {$step}\n    App-->>Browser: Validation Result\n";
        }
        
        if (strpos($step, 'Click') === 0) {
            return "    User->>Browser: {$step}\n    Browser->>App: Action Request\n";
        }
        
        if (strpos($step, 'Fill') === 0) {
            return "    User->>Browser: {$step}\n";
        }
        
        if (strpos($step, 'Submit') === 0) {
            return "    User->>Browser: {$step}\n    Browser->>App: Form Data\n    App->>DB: Store Data\n    DB-->>App: Confirmation\n    App-->>Browser: Success Response\n";
        }
        
        return "    Note over User,DB: {$step}\n";
    }

    private function formatMethodName(string $methodName): string
    {
        // Convert camelCase to readable format
        $formatted = preg_replace('/([a-z])([A-Z])/', '$1 $2', $methodName);
        return ucfirst($formatted);
    }

    private function generateOverviewDiagram(): void
    {
        $diagram = "```mermaid\nflowchart TD\n";
        $diagram .= "    Start([Test Suite Start])\n";
        
        foreach ($this->diagrams as $className => $info) {
            $sanitizedName = str_replace(['Cest', 'Test'], '', $className);
            $diagram .= "    {$sanitizedName}[{$sanitizedName}<br/>{$info['stepCount']} steps]\n";
            $diagram .= "    Start --> {$sanitizedName}\n";
            
            foreach ($info['methods'] as $method) {
                $methodId = $sanitizedName . '_' . $method;
                $diagram .= "    {$methodId}[{$this->formatMethodName($method)}]\n";
                $diagram .= "    {$sanitizedName} --> {$methodId}\n";
            }
        }
        
        $diagram .= "    End([Test Suite End])\n";
        foreach ($this->diagrams as $className => $info) {
            $sanitizedName = str_replace(['Cest', 'Test'], '', $className);
            foreach ($info['methods'] as $method) {
                $methodId = $sanitizedName . '_' . $method;
                $diagram .= "    {$methodId} --> End\n";
            }
        }
        
        $diagram .= "```\n";
        
        $content = "# Test Suite Overview\n\n";
        $content .= "This diagram shows the complete test suite structure.\n\n";
        $content .= $diagram;
        
        file_put_contents($this->outputDir . '/overview.md', $content);
    }

    private function generateReadme(): void
    {
        $readme = "# Test Documentation Diagrams\n\n";
        $readme .= "Auto-generated Mermaid diagrams for test cases.\n\n";
        $readme .= "## Test Classes\n\n";
        
        foreach ($this->diagrams as $className => $info) {
            $fileName = basename($info['file']);
            $readme .= "- [{$className}]({$fileName}) - {$info['stepCount']} test steps across " . count($info['methods']) . " methods\n";
        }
        
        $readme .= "\n## Overview\n\n";
        $readme .= "- [Test Suite Overview](overview.md) - Complete test flow\n\n";
        $readme .= "---\n\n";
        $readme .= "*Generated on: " . date('Y-m-d H:i:s') . "*\n";
        $readme .= "*Source: Codeception test files in `tests/Acceptance/`*\n";
        
        file_put_contents($this->outputDir . '/README.md', $readme);
    }
}

// Run the generator
if (php_sapi_name() === 'cli') {
    $generator = new TestDiagramGenerator();
    $generator->generateAll();
}
