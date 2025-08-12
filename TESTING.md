# Automated Testing with Codeception for Simple PHP Website

This document explains how to set up and run automated tests using **Codeception** - a modern full-stack testing framework for PHP applications.

## Overview

**Codeception** provides:
- ✅ **Simple setup** - No complex driver configurations
- ✅ **Multiple testing approaches** - Acceptance, Functional, Unit tests
- ✅ **Clean syntax** - Easy to read and write tests
- ✅ **Built-in modules** - PhpBrowser, WebDriver, REST, Database modules
- ✅ **Detailed reporting** - HTML reports and screenshots
- ✅ **Stable framework** - Well-maintained and widely used

## Quick Start

### 1. Install Dependencies and Setup

Make the setup script executable and run it:

```bash
chmod +x setup-testing.sh run-tests.sh
./setup-testing.sh
```

This will:
- Install Codeception and required modules
- Build test actors with available methods
- Create test environment configuration
- Set up reporting directories

### 2. Start the Application

In one terminal, start the PHP development server:

```bash
composer serve
# or manually: php -S localhost:8000
```

### 3. Run Tests

In another terminal, run the tests:

```bash
# Run all tests
composer test

# Or use the test runner script:
./run-tests.sh test                    # All tests
./run-tests.sh acceptance              # Acceptance tests only
./run-tests.sh navigation              # Navigation tests only
./run-tests.sh database                # Database status tests only
./run-tests.sh steps                   # With detailed steps
./run-tests.sh debug                   # With debug output
```

## Test Structure

### Test Organization
```
tests/
├── Acceptance/
│   ├── WebsiteNavigationCest.php     # Navigation and content tests
│   └── DatabaseStatusCest.php        # Database and system status tests
├── Support/
│   ├── AcceptanceTester.php           # Generated actor class
│   └── _generated/                    # Auto-generated support files
├── _output/                           # Test reports and logs
├── Acceptance.suite.yml               # Acceptance test configuration
├── Functional.suite.yml               # Functional test configuration
└── Unit.suite.yml                     # Unit test configuration
```

### Available Test Commands

```bash
# Basic test execution
vendor/bin/codecept run                           # All tests
vendor/bin/codecept run Acceptance                # Acceptance tests only
vendor/bin/codecept run Acceptance WebsiteNavigationCest  # Specific test class

# Detailed output options
vendor/bin/codecept run --steps                   # Show detailed steps
vendor/bin/codecept run --debug                   # Debug output
vendor/bin/codecept run --html                    # Generate HTML report

# Run specific test methods
vendor/bin/codecept run Acceptance WebsiteNavigationCest:displayHomePageByDefault
```

## Test Coverage

### 🌐 Website Navigation Tests (`WebsiteNavigationCest`)

**Converted from your original Gherkin scenarios:**

1. **displayHomePageByDefault** - Tests home page display and content
2. **navigateToAboutUsPage** - Tests About Us page navigation and content
3. **navigateToProductsPage** - Tests Products page navigation
4. **navigateToContactPage** - Tests Contact page navigation
5. **accessStatusPageWithSystemInformation** - Tests Status page with all sections
6. **handleNonExistentPageWith404Error** - Tests 404 error page handling
7. **verifyNavigationMenuIsPresentOnAllPages** - Tests navigation consistency
8. **verifyWebsiteFooterInformation** - Tests footer content and copyright
9. **verifyActiveNavigationState** - Tests active navigation highlighting
10. **verifyPageStructureConsistency** - Tests consistent page structure

### 🗄️ Database Status Tests (`DatabaseStatusCest`)

**Converted from your original Gherkin scenarios:**

1. **checkDatabaseConnectionStatus** - Tests database connection display
2. **verifyEnvironmentInformationDisplay** - Tests environment info section
3. **verifyInfrastructureFeaturesAreListed** - Tests infrastructure components
4. **checkStatusPageAccessibility** - Tests status page accessibility
5. **verifyDeploymentReadinessInformation** - Tests AWS deployment info
6. **verifyDatabaseConfigurationDisplay** - Tests database config display
7. **verifyStatusPageLayoutAndStructure** - Tests page structure
8. **verifyEnvironmentVariablesUsage** - Tests environment variable usage

## Configuration

### Acceptance Tests Configuration (`tests/Acceptance.suite.yml`)

```yaml
actor: AcceptanceTester
modules:
    enabled:
        - PhpBrowser:
            url: http://localhost:8000
        - \Codeception\Module\Asserts
step_decorators:
    - Codeception\Step\ConditionalAssertion
    - Codeception\Step\TryTo
    - Codeception\Step\Retry
```

### Main Configuration (`codeception.yml`)

```yaml
namespace: Tests
paths:
    tests: tests
    output: tests/_output
    data: tests/_data
    support: tests/Support
actor_suffix: Tester
extensions:
    enabled:
        - Codeception\Extension\RunFailed
```

## Writing New Tests

### 1. Generate New Test Class

```bash
vendor/bin/codecept generate:cest Acceptance NewFeature
```

### 2. Basic Test Structure

```php
<?php

declare(strict_types=1);

namespace Tests\Acceptance;

use Tests\Support\AcceptanceTester;

final class NewFeatureCest
{
    public function testSomething(AcceptanceTester $I): void
    {
        $I->amOnPage('/');
        $I->see('Expected Content');
        $I->click('Some Link');
        $I->seeCurrentUrlEquals('/expected-page');
    }
}
```

### 3. Common Codeception Methods

```php
// Navigation
$I->amOnPage('/path');
$I->click('Link Text');
$I->clickLink('Link Text');

// Assertions
$I->see('Text');
$I->see('Text', 'css-selector');
$I->seeElement('css-selector');
$I->seeCurrentUrlEquals('/path');
$I->seeCurrentUrlMatches('/pattern/');
$I->seeInTitle('Page Title');

// Form interactions
$I->fillField('field_name', 'value');
$I->selectOption('select_name', 'option');
$I->checkOption('checkbox_name');
$I->submitForm('#form-id', []);

// Wait and retry
$I->wait(2); // Wait 2 seconds
$I->waitForElement('.element', 10); // Wait up to 10 seconds
```

## Continuous Integration

### GitHub Actions Example

```yaml
name: Codeception Tests
on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: 8.0
          
      - name: Install dependencies
        run: composer install
        
      - name: Start PHP server
        run: php -S localhost:8000 &
        
      - name: Wait for server
        run: sleep 3
        
      - name: Run tests
        run: vendor/bin/codecept run --xml --html
        
      - name: Upload test results
        uses: actions/upload-artifact@v3
        if: always()
        with:
          name: test-results
          path: tests/_output/
```

## Test Reports

Codeception generates various reports:

- **HTML Reports**: `tests/_output/report.html` - Visual test results
- **XML Reports**: `tests/_output/result.xml` - JUnit format for CI
- **Screenshots**: Automatic screenshots on failures (with WebDriver)
- **Logs**: Detailed test execution logs

### Generate Reports

```bash
# Generate HTML report
vendor/bin/codecept run --html

# Generate XML report (for CI)
vendor/bin/codecept run --xml

# Generate both
vendor/bin/codecept run --html --xml
```

## Advantages of Codeception over Behat

1. **Simpler Setup** - No complex driver configurations
2. **Better Documentation** - Extensive documentation and examples
3. **Multiple Test Types** - Acceptance, Functional, Unit in one framework
4. **Active Development** - Well-maintained with regular updates
5. **Better Error Messages** - Clear, actionable error messages
6. **Built-in Modules** - Many modules available out of the box
7. **PHP-Native** - Designed specifically for PHP applications

## Troubleshooting

### Common Issues

1. **Server not running**
   ```bash
   ./run-tests.sh start  # Start server manually
   ```

2. **Tests failing due to timing**
   ```php
   $I->wait(2); // Add wait between actions
   ```

3. **Element not found**
   ```php
   $I->waitForElement('.element', 10); // Wait for element to appear
   ```

### Debug Tests

```bash
# Run with debug output
vendor/bin/codecept run --debug

# Run specific test with steps
vendor/bin/codecept run acceptance WebsiteNavigationCest:displayHomePageByDefault --steps
```

## Next Steps

1. **Add more test scenarios** - Extend existing test classes
2. **Add Functional tests** - Test application logic without browser
3. **Add Unit tests** - Test individual PHP functions/classes
4. **Set up WebDriver** - For JavaScript testing with real browsers
5. **Add API tests** - Test any REST APIs your application might have

---

**🎉 Your Gherkin scenarios have been successfully converted to Codeception tests!**

The tests maintain the same coverage and intent as your original BDD scenarios but are now implemented in a more stable and maintainable testing framework.
