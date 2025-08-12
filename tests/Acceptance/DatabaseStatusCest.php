<?php

declare(strict_types=1);

namespace Tests\Acceptance;

use Tests\Support\AcceptanceTester;

/**
 * Database and Infrastructure Status Tests
 * 
 * Tests database connection status and infrastructure information display
 * Converted from Gherkin scenarios to Codeception format
 */
final class DatabaseStatusCest
{
    public function _before(AcceptanceTester $I): void
    {
        // This runs before each test
    }

    /**
     * Test: Check database connection status display
     * 
     * Scenario: Check database connection status when not connected
     *   Given the database is not available
     *   When I visit the status page
     *   Then I should see "Database Connection: ✗ Not Connected" in red
     *   And I should see the database host information
     *   And I should see the database name information
     *   And I should see the database port information
     *   And I should see a note about database availability after AWS deployment
     */
    public function checkDatabaseConnectionStatus(AcceptanceTester $I): void
    {
        $I->amOnPage('/?page=status');
        
        // Should see database status section
        $I->see('Database Status');
        $I->see('Database Connection:');
        
        // Should see database configuration details
        $I->see('Database Host:');
        $I->see('Database Name:');
        $I->see('Database Port:');
        
        // Should see either connected or not connected status
        // For local testing, it's likely not connected
        $I->seeElement('span', ['style' => 'color: red;']);
    }

    /**
     * Test: Verify environment information display
     * 
     * Scenario: Verify environment information display
     *   Given I am on the status page
     *   When the page loads completely
     *   Then I should see "Environment Information" section
     *   And I should see the current environment type
     *   And I should see the PHP version information
     *   And I should see the server software information
     */
    public function verifyEnvironmentInformationDisplay(AcceptanceTester $I): void
    {
        $I->amOnPage('/?page=status');
        
        $I->see('Environment Information');
        $I->see('Environment:');
        $I->see('PHP Version:');
        $I->see('Server Software:');
    }

    /**
     * Test: Verify infrastructure features are listed
     * 
     * Scenario: Verify infrastructure features are listed
     *   Given I am on the status page
     *   When I view the infrastructure features section
     *   Then I should see "Infrastructure Features" heading
     *   And I should see the infrastructure components
     */
    public function verifyInfrastructureFeaturesAreListed(AcceptanceTester $I): void
    {
        $I->amOnPage('/?page=status');
        
        $I->see('Infrastructure Features');
        
        // Check for infrastructure components
        $I->see('VPC with public and private subnets');
        $I->see('Auto Scaling Group with EC2 instances');
        $I->see('Application Load Balancer for high availability');
        $I->see('RDS MySQL database in private subnets');
        $I->see('CodePipeline for automated deployments');
        $I->see('Security groups for network isolation');
    }

    /**
     * Test: Check status page accessibility
     * 
     * Scenario: Check status page accessibility
     *   Given I am anywhere on the website
     *   When I click on the "Status" navigation link
     *   Then I should be able to access the status page
     *   And the page should load without errors
     *   And all status information should be displayed
     */
    public function checkStatusPageAccessibility(AcceptanceTester $I): void
    {
        // Test access from home page
        $I->amOnPage('/');
        $I->click('Status');
        $I->seeCurrentUrlMatches('/\?page=status/');
        
        // Verify all status sections are displayed
        $I->see('Database Status');
        $I->see('Environment Information');
        $I->see('Infrastructure Features');
    }

    /**
     * Test: Verify deployment readiness information
     * 
     * Scenario: Verify deployment readiness information
     *   Given I am on the status page
     *   When I scroll to the bottom of the page
     *   Then I should see information about AWS deployment readiness
     *   And I should see mention of Terraform and Terragrunt
     *   And I should see infrastructure management information
     */
    public function verifyDeploymentReadinessInformation(AcceptanceTester $I): void
    {
        $I->amOnPage('/?page=status');
        
        $I->see('ready for deployment to AWS');
        $I->see('Terraform');
        $I->see('Terragrunt');
        $I->see('infrastructure management');
    }

    /**
     * Test: Verify database configuration display
     * 
     * Tests that database configuration values are displayed correctly
     */
    public function verifyDatabaseConfigurationDisplay(AcceptanceTester $I): void
    {
        $I->amOnPage('/?page=status');
        
        // Default values should be displayed when no environment variables are set
        $I->see('localhost'); // Default DB_HOST
        $I->see('simpleapp'); // Default DB_NAME
        $I->see('3306');      // Default DB_PORT
    }

    /**
     * Test: Verify status page layout and structure
     * 
     * Tests that the status page has proper layout and all required sections
     */
    public function verifyStatusPageLayoutAndStructure(AcceptanceTester $I): void
    {
        $I->amOnPage('/?page=status');
        
        // Check page structure
        $I->seeElement('h3'); // Section headings
        $I->seeElement('div', ['style' => 'padding: 20px; margin: 20px 0; border: 1px solid #ddd; border-radius: 5px;']);
        $I->seeElement('ul'); // Infrastructure features list
        
        // Check that the page has proper headings hierarchy
        $I->see('Status', 'h2'); // Main page heading
        $I->see('Database Status', 'h3');
        $I->see('Environment Information', 'h3');
        $I->see('Infrastructure Features', 'h3');
    }

    /**
     * Test: Verify environment variables usage
     * 
     * Tests that the application properly uses environment variables for configuration
     */
    public function verifyEnvironmentVariablesUsage(AcceptanceTester $I): void
    {
        $I->amOnPage('/?page=status');
        
        // Should see current environment (defaults to 'local' if ENVIRONMENT not set)
        $I->see('Environment:');
        
        // Should see PHP version information
        $I->see('PHP Version:');
        
        // Should see server software information
        $I->see('Server Software:');
    }
}
