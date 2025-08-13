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
    private array $gitChanges = [];
    private int $commitsToCheck;

    public function __construct(string $testDir = 'tests/Acceptance', string $outputDir = 'docs/diagrams', int $commitsToCheck = 5)
    {
        $this->testDir = $testDir;
        $this->outputDir = $outputDir;
        $this->commitsToCheck = $commitsToCheck;
        
        // Ensure output directory exists
        if (!is_dir($this->outputDir)) {
            mkdir($this->outputDir, 0755, true);
        }
        
        // Analyze Git changes
        $this->analyzeGitChanges();
    }

    public function generateAll(): void
    {
        echo "🔍 Analyzing test files in {$this->testDir}/\n";
        $actualCommitCount = $this->gitChanges['actualCommitCount'] ?? $this->commitsToCheck;
        echo "📊 Checking last {$actualCommitCount} commits for changes (excluding auto-generated)\n";
        
        $testFiles = glob($this->testDir . '/*Cest.php');
        
        foreach ($testFiles as $testFile) {
            $this->analyzeTestFile($testFile);
        }
        
        $this->generateOverviewDiagram();
        $this->generateChangelogDiagram();
        $this->generateReadme();
        
        echo "✅ Generated " . count($this->diagrams) . " diagrams in {$this->outputDir}/\n";
        if (!empty($this->gitChanges['files'])) {
            echo "🔄 Highlighted changes from last {$actualCommitCount} commits\n";
        }
    }

    private function analyzeGitChanges(): void
    {
        // Check if we're in a Git repository
        if (!is_dir('.git')) {
            echo "⚠️  Not in a Git repository, skipping change analysis\n";
            return;
        }

        try {
            // Get all recent commits (more than needed to filter out auto-commits)
            $allCommits = shell_exec("git log --oneline -n " . ($this->commitsToCheck * 3));
            $allCommitsArray = $allCommits ? array_filter(explode("\n", trim($allCommits))) : [];
            
            // Filter out auto-generated commits
            $realCommits = [];
            foreach ($allCommitsArray as $commit) {
                // Skip commits that are auto-generated documentation updates
                if (!preg_match('/🤖.*Auto-update.*diagrams?/', $commit)) {
                    $realCommits[] = $commit;
                    if (count($realCommits) >= $this->commitsToCheck) {
                        break;
                    }
                }
            }
            
            // If we don't have enough real commits, use what we have
            $commitsToUse = min(count($realCommits), $this->commitsToCheck);
            
            if ($commitsToUse === 0) {
                echo "⚠️  No non-auto-generated commits found\n";
                $this->gitChanges = ['files' => [], 'commits' => [], 'testChanges' => []];
                return;
            }
            
            echo "🔍 Found {$commitsToUse} real commits (excluding auto-generated)\n";
            
            // Get changed files using the filtered commit range
            $changedFiles = shell_exec("git diff --name-only HEAD~{$commitsToUse}..HEAD");
            $changedFiles = $changedFiles ? array_filter(explode("\n", trim($changedFiles))) : [];
            
            // Get commit information (use the filtered commits)
            $commits = array_slice($realCommits, 0, $commitsToUse);
            
            // Get detailed changes for test files
            $testFileChanges = [];
            foreach ($changedFiles as $file) {
                if (strpos($file, 'tests/') === 0 && strpos($file, 'Cest.php') !== false) {
                    $changes = shell_exec("git diff HEAD~{$commitsToUse}..HEAD --unified=3 '$file'");
                    $testFileChanges[$file] = $this->parseGitDiff($changes);
                }
            }
            
            $this->gitChanges = [
                'files' => $changedFiles,
                'commits' => $commits,
                'testChanges' => $testFileChanges,
                'timestamp' => date('Y-m-d H:i:s'),
                'actualCommitCount' => $commitsToUse
            ];
            
        } catch (Exception $e) {
            echo "⚠️  Error analyzing Git changes: {$e->getMessage()}\n";
            $this->gitChanges = ['files' => [], 'commits' => [], 'testChanges' => []];
        }
    }

    private function parseGitDiff(string $diff): array
    {
        $changes = ['added' => [], 'modified' => [], 'removed' => []];
        
        if (empty($diff)) return $changes;
        
        $lines = explode("\n", $diff);
        foreach ($lines as $line) {
            if (strpos($line, '+') === 0 && strpos($line, '+++') !== 0) {
                $changes['added'][] = trim(substr($line, 1));
            } elseif (strpos($line, '-') === 0 && strpos($line, '---') !== 0) {
                $changes['removed'][] = trim(substr($line, 1));
            }
        }
        
        return $changes;
    }

    private function analyzeTestFile(string $filePath): void
    {
        $content = file_get_contents($filePath);
        $className = basename($filePath, '.php');
        $relativePath = str_replace(getcwd() . '/', '', $filePath);
        
        echo "📝 Processing {$className}...\n";
        
        // Check if this file was changed in recent commits
        $isChanged = in_array($relativePath, $this->gitChanges['files'] ?? []);
        $fileChanges = $this->gitChanges['testChanges'][$relativePath] ?? null;
        
        // Extract test methods
        preg_match_all('/public function (\w+)\(.*?\): void\s*\{(.*?)\}/s', $content, $matches);
        
        $testMethods = [];
        for ($i = 0; $i < count($matches[1]); $i++) {
            $methodName = $matches[1][$i];
            $methodBody = $matches[2][$i];
            
            if (strpos($methodName, 'test') === 0 || !in_array($methodName, ['_before', '_after'])) {
                $steps = $this->analyzeTestMethod($methodBody);
                $isMethodChanged = $this->isMethodChanged($methodName, $methodBody, $fileChanges);
                
                $testMethods[$methodName] = [
                    'steps' => $steps,
                    'changed' => $isMethodChanged,
                    'isNew' => $isMethodChanged && $fileChanges
                ];
            }
        }
        
        if (!empty($testMethods)) {
            $this->generateClassDiagram($className, $testMethods, $isChanged);
        }
    }

    private function isMethodChanged(string $methodName, string $methodBody, ?array $fileChanges): bool
    {
        if (!$fileChanges) return false;
        
        // Check if method name appears in added lines (indicates new method)
        foreach ($fileChanges['added'] as $line) {
            if (strpos($line, "public function {$methodName}") !== false) {
                return true;
            }
        }
        
        // Check if any significant method content appears in changes (indicates modification)
        $methodLines = explode("\n", $methodBody);
        foreach ($methodLines as $methodLine) {
            $trimmedLine = trim($methodLine);
            if (empty($trimmedLine) || strpos($trimmedLine, '//') === 0 || strlen($trimmedLine) < 15) continue;
            
            foreach ($fileChanges['added'] as $addedLine) {
                // Look for exact matches of significant code lines
                if (strpos($addedLine, $trimmedLine) !== false) {
                    return true;
                }
            }
        }
        
        return false;
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

    private function generateClassDiagram(string $className, array $testMethods, bool $isFileChanged = false): void
    {
        $diagram = "```mermaid\nsequenceDiagram\n";
        $diagram .= "    participant User\n";
        $diagram .= "    participant Browser\n";
        $diagram .= "    participant App as PHP App\n";
        $diagram .= "    participant DB as Database\n\n";
        
        // Add change indicator if file was modified
        if ($isFileChanged) {
            $diagram .= "    Note over User,DB: 🔄 RECENTLY MODIFIED\n\n";
        }
        
        foreach ($testMethods as $methodName => $methodData) {
            $steps = $methodData['steps'];
            $isChanged = $methodData['changed'];
            $isNew = $methodData['isNew'];
            
            $changeIndicator = '';
            if ($isNew) {
                $changeIndicator = ' 🆕 NEW';
            } elseif ($isChanged) {
                $changeIndicator = ' 🔄 MODIFIED';
            }
            
            $diagram .= "    %% Test: " . $this->formatMethodName($methodName) . $changeIndicator . "\n";
            
            if ($isChanged) {
                $diagram .= "    rect rgb(255, 245, 230)\n"; // Light orange background for changed tests
            }
            
            $diagram .= "    Note over User,DB: " . $this->formatMethodName($methodName) . $changeIndicator . "\n";
            
            foreach ($steps as $step) {
                $diagram .= $this->convertStepToMermaid($step, $isChanged);
            }
            
            if ($isChanged) {
                $diagram .= "    end\n"; // Close the rect
            }
            
            $diagram .= "\n";
        }
        
        $diagram .= "```\n";
        
        $fileName = $this->outputDir . '/' . strtolower($className) . '.md';
        $content = "# {$className} Test Flow\n\n";
        
        if ($isFileChanged) {
            $content .= "🔄 **This test class was recently modified**\n\n";
        }
        
        $content .= "This diagram shows the test flow for {$className}.\n\n";
        
        // Add legend for changes
        if ($isFileChanged) {
            $content .= "## Legend\n";
            $content .= "- 🆕 **NEW** - Recently added test method\n";
            $content .= "- 🔄 **MODIFIED** - Recently changed test method\n";
            $content .= "- Orange background - Indicates recent changes\n\n";
        }
        
        $content .= $diagram;
        
        file_put_contents($fileName, $content);
        
        $changedMethodsCount = count(array_filter($testMethods, fn($m) => $m['changed']));
        
        $this->diagrams[$className] = [
            'file' => $fileName,
            'methods' => array_keys($testMethods),
            'stepCount' => array_sum(array_map(fn($m) => count($m['steps']), $testMethods)),
            'changed' => $isFileChanged,
            'changedMethods' => $changedMethodsCount
        ];
    }

    private function convertStepToMermaid(string $step, bool $isChanged = false): string
    {
        $highlight = $isChanged ? ' 🔄' : '';
        
        if (strpos($step, 'Navigate to') === 0) {
            return "    User->>Browser: {$step}{$highlight}\n    Browser->>App: HTTP Request\n    App-->>Browser: Page Response\n";
        }
        
        if (strpos($step, 'Verify') === 0) {
            return "    Browser->>App: {$step}{$highlight}\n    App-->>Browser: Validation Result\n";
        }
        
        if (strpos($step, 'Click') === 0) {
            return "    User->>Browser: {$step}{$highlight}\n    Browser->>App: Action Request\n";
        }
        
        if (strpos($step, 'Fill') === 0) {
            return "    User->>Browser: {$step}{$highlight}\n";
        }
        
        if (strpos($step, 'Submit') === 0) {
            return "    User->>Browser: {$step}{$highlight}\n    Browser->>App: Form Data\n    App->>DB: Store Data\n    DB-->>App: Confirmation\n    App-->>Browser: Success Response\n";
        }
        
        return "    Note over User,DB: {$step}{$highlight}\n";
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
            $changeIndicator = $info['changed'] ? ' 🔄' : '';
            $stepInfo = "{$info['stepCount']} steps";
            
            if ($info['changed']) {
                $stepInfo .= ", {$info['changedMethods']} modified";
                $diagram .= "    {$sanitizedName}[{$sanitizedName}{$changeIndicator}<br/>{$stepInfo}]\n";
                $diagram .= "    style {$sanitizedName} fill:#fff5e6,stroke:#ff8c00,stroke-width:2px\n"; // Orange styling for changed classes
            } else {
                $diagram .= "    {$sanitizedName}[{$sanitizedName}<br/>{$stepInfo}]\n";
            }
            
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
        
        $actualCommitCount = $this->gitChanges['actualCommitCount'] ?? $this->commitsToCheck;
        
        $content = "# Test Suite Overview\n\n";
        $content .= "This diagram shows the complete test suite structure.\n\n";
        
        // Add recent changes summary
        if (!empty($this->gitChanges['commits'])) {
            $content .= "## Recent Changes\n\n";
            $content .= "Last {$actualCommitCount} commits (excluding auto-generated):\n";
            foreach ($this->gitChanges['commits'] as $commit) {
                $content .= "- `{$commit}`\n";
            }
            $content .= "\n🔄 Orange highlighted items indicate recent modifications.\n\n";
        }
        
        $content .= $diagram;
        
        file_put_contents($this->outputDir . '/overview.md', $content);
    }

    private function generateChangelogDiagram(): void
    {
        if (empty($this->gitChanges['commits'])) {
            return; // No changes to document
        }

        $diagram = "```mermaid\ngitgraph\n";
        $diagram .= "    commit id: \"Baseline\"\n";
        
        foreach (array_reverse($this->gitChanges['commits']) as $index => $commit) {
            $commitHash = explode(' ', $commit)[0];
            $commitMsg = substr($commit, strlen($commitHash) + 1);
            $shortMsg = strlen($commitMsg) > 30 ? substr($commitMsg, 0, 30) . '...' : $commitMsg;
            
            $diagram .= "    commit id: \"{$shortMsg}\"\n";
        }
        
        $diagram .= "```\n";
        
        $content = "# Test Changes Timeline\n\n";
        $actualCommitCount = $this->gitChanges['actualCommitCount'] ?? $this->commitsToCheck;
        $content .= "Recent changes to test files in the last {$actualCommitCount} commits (excluding auto-generated).\n\n";
        $content .= "Generated: {$this->gitChanges['timestamp']}\n\n";
        
        $content .= $diagram;
        
        // Add detailed changes
        if (!empty($this->gitChanges['testChanges'])) {
            $content .= "\n## Detailed Changes\n\n";
            foreach ($this->gitChanges['testChanges'] as $file => $changes) {
                $content .= "### {$file}\n\n";
                
                if (!empty($changes['added'])) {
                    $content .= "**Added lines:**\n";
                    foreach (array_slice($changes['added'], 0, 5) as $line) { // Show first 5 additions
                        $content .= "- `{$line}`\n";
                    }
                    if (count($changes['added']) > 5) {
                        $content .= "- ... and " . (count($changes['added']) - 5) . " more\n";
                    }
                    $content .= "\n";
                }
                
                if (!empty($changes['removed'])) {
                    $content .= "**Removed lines:**\n";
                    foreach (array_slice($changes['removed'], 0, 5) as $line) { // Show first 5 removals
                        $content .= "- ~~`{$line}`~~\n";
                    }
                    if (count($changes['removed']) > 5) {
                        $content .= "- ... and " . (count($changes['removed']) - 5) . " more\n";
                    }
                    $content .= "\n";
                }
            }
        }
        
        file_put_contents($this->outputDir . '/changelog.md', $content);
    }

    private function generateReadme(): void
    {
        $readme = "# Test Documentation Diagrams\n\n";
        $readme .= "Auto-generated Mermaid diagrams for test cases with change tracking.\n\n";
        
        // Add changes summary
        if (!empty($this->gitChanges['commits'])) {
            $actualCommitCount = $this->gitChanges['actualCommitCount'] ?? $this->commitsToCheck;
            $readme .= "## 🔄 Recent Changes\n\n";
            $readme .= "Tracking changes from last {$actualCommitCount} commits (excluding auto-generated):\n";
            foreach (array_slice($this->gitChanges['commits'], 0, 3) as $commit) {
                $readme .= "- `{$commit}`\n";
            }
            if (count($this->gitChanges['commits']) > 3) {
                $readme .= "- ... and " . (count($this->gitChanges['commits']) - 3) . " more\n";
            }
            $readme .= "\n";
        }
        
        $readme .= "## Test Classes\n\n";
        
        foreach ($this->diagrams as $className => $info) {
            $fileName = basename($info['file']);
            $changeIndicator = $info['changed'] ? ' 🔄' : '';
            $changeInfo = $info['changed'] ? " ({$info['changedMethods']} methods modified)" : '';
            
            $readme .= "- [{$className}]({$fileName}){$changeIndicator} - {$info['stepCount']} test steps across " . count($info['methods']) . " methods{$changeInfo}\n";
        }
        
        $readme .= "\n## Documentation\n\n";
        $readme .= "- [Test Suite Overview](overview.md) - Complete test flow\n";
        
        if (!empty($this->gitChanges['commits'])) {
            $readme .= "- [Change Timeline](changelog.md) - Recent modifications\n";
        }
        
        $actualCommitCount = $this->gitChanges['actualCommitCount'] ?? $this->commitsToCheck;
        
        $readme .= "\n## Legend\n\n";
        $readme .= "- 🔄 **Recently Modified** - Changed in last {$actualCommitCount} commits (excluding auto-generated)\n";
        $readme .= "- 🆕 **New** - Recently added test methods\n";
        $readme .= "- Orange highlighting - Indicates recent changes\n";
        
        $readme .= "\n---\n\n";
        $readme .= "*Generated on: " . date('Y-m-d H:i:s') . "*\n";
        $readme .= "*Source: Codeception test files in `tests/Acceptance/`*\n";
        
        if (!empty($this->gitChanges['commits'])) {
            $readme .= "*Change tracking: Last {$actualCommitCount} commits (excluding auto-generated)*\n";
        }
        
        file_put_contents($this->outputDir . '/README.md', $readme);
    }
}

// Run the generator
if (php_sapi_name() === 'cli') {
    // Allow customization via command line arguments
    $commitsToCheck = isset($argv[1]) ? (int)$argv[1] : 5;
    $testDir = isset($argv[2]) ? $argv[2] : 'tests/Acceptance';
    $outputDir = isset($argv[3]) ? $argv[3] : 'docs/diagrams';
    
    echo "🚀 Starting Test Diagram Generator\n";
    echo "📂 Test Directory: {$testDir}\n";
    echo "📁 Output Directory: {$outputDir}\n";
    echo "📊 Checking last {$commitsToCheck} commits\n\n";
    
    $generator = new TestDiagramGenerator($testDir, $outputDir, $commitsToCheck);
    $generator->generateAll();
    
    echo "\n🎉 Documentation generation complete!\n";
}
