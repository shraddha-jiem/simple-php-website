<?php

declare(strict_types=1);

namespace Tests\Acceptance;

use Tests\Support\AcceptanceTester;

/**
 * Website Navigation Tests
 * 
 * Tests website navigation functionality, content display, and user experience
 * Converted from Gherkin scenarios to Codeception format
 */
final class WebsiteNavigationCest
{
    public function _before(AcceptanceTester $I): void
    {
        // This runs before each test
    }

    /**
     * Test: Display home page by default
     * 
     * Scenario: Display home page by default
     *   Given I am on the website
     *   When I visit the home page
     *   Then I should see the page title "Home | Simple PHP Website (Master)"
     *   And I should see the main heading "Simple PHP Website (Master)"
     *   And I should see the page content heading "Home"
     *   And I should see content containing "This is home page"
     */
    public function displayHomePageByDefault(AcceptanceTester $I): void
    {
        $I->amOnPage('/');
        $I->seeInTitle('Home | Simple PHP Website (Master)');
        $I->see('Simple PHP Website (Master)', 'h1');
        $I->see('Home', 'h2');
        $I->see('This is home page');
    }

    /**
     * Test: Navigate to About Us page
     * 
     * Scenario: Navigate to About Us page
     *   Given I am on the website
     *   When I click on the "About Us" navigation link
     *   Then I should be on the about-us page
     *   And I should see the page title "About Us | Simple PHP Website (Master)"
     *   And I should see the page content heading "About Us"
     */
    public function navigateToAboutUsPage(AcceptanceTester $I): void
    {
        $I->amOnPage('/');
        $I->click('About Us');
        $I->seeCurrentUrlMatches('/\?page=about-us/');
        $I->seeInTitle('About Us | Simple PHP Website (Master)');
        $I->see('About Us', 'h2');
    }

    /**
     * Test: Navigate to Products page
     */
    public function navigateToProductsPage(AcceptanceTester $I): void
    {
        $I->amOnPage('/');
        $I->click('Products');
        $I->seeCurrentUrlMatches('/\?page=products/');
        $I->seeInTitle('Products | Simple PHP Website (Master)');
        $I->see('Products', 'h2');
    }

    /**
     * Test: Navigate to Contact page
     */
    public function navigateToContactPage(AcceptanceTester $I): void
    {
        $I->amOnPage('/');
        $I->click('Contact');
        $I->seeCurrentUrlMatches('/\?page=contact/');
        $I->seeInTitle('Contact | Simple PHP Website (Master)');
        $I->see('Contact', 'h2');
    }


    /**
     * Test: Navigate to Check page
     */
    public function navigateToCheckPage(AcceptanceTester $I): void
    {
        $I->amOnPage('/');
        $I->click('Check');
        $I->seeCurrentUrlMatches('/\?page=check/');
        $I->seeInTitle('Check | Simple PHP Website (Master)');
        $I->see('Check', 'h2');
    }


    /**
     * Test: Access Status page with system information
     * 
     * Scenario: Access Status page with system information
     *   Given I am on the website
     *   When I click on the "Status" navigation link
     *   Then I should be on the status page
     *   And I should see the page title "Status | Simple PHP Website (Master)"
     *   And I should see the page content heading "Status"
     *   And I should see "Database Status" section
     *   And I should see "Environment Information" section
     *   And I should see "Infrastructure Features" section
     */
    public function accessStatusPageWithSystemInformation(AcceptanceTester $I): void
    {
        $I->amOnPage('/');
        $I->click('Status');
        $I->seeCurrentUrlMatches('/\?page=status/');
        $I->seeInTitle('Status | Simple PHP Website (Master)');
        $I->see('Status', 'h2');
        $I->see('Database Status');
        $I->see('Environment Information');
        $I->see('Infrastructure Features');
    }

    /**
     * Test: Handle non-existent page with 404 error
     * 
     * Scenario: Handle non-existent page with 404 error
     *   Given I am on the website
     *   When I visit a non-existent page "invalid-page"
     *   Then I should see the 404 error page
     */
    public function handleNonExistentPageWith404Error(AcceptanceTester $I): void
    {
        $I->amOnPage('/?page=invalid-page');
        $I->seeInTitle('Invalid Page | Simple PHP Website (Master)');
        // The page should load the 404.phtml content
        $I->see('Invalid Page', 'h2');
    }

    /**
     * Test: Verify navigation menu is present on all pages
     * 
     * Scenario: Verify navigation menu is present on all pages
     *   Given I am on the website
     *   When I visit any page from the navigation menu
     *   Then I should see all navigation items
     *   And I should be able to navigate to any other page
     */
    public function verifyNavigationMenuIsPresentOnAllPages(AcceptanceTester $I): void
    {
        $pages = ['/', '/?page=about-us', '/?page=products', '/?page=contact', '/?page=status'];
        
        foreach ($pages as $page) {
            $I->amOnPage($page);
            
            // Check that all navigation links are present
            $I->seeLink('Home');
            $I->seeLink('About Us');
            $I->seeLink('Products');
            $I->seeLink('Contact');
            $I->seeLink('Status');
        }
        
        // Test navigation between pages
        $I->amOnPage('/');
        $I->click('Products');
        $I->seeCurrentUrlMatches('/\?page=products/');
        $I->click('Home');
        $I->seeCurrentUrlEquals('/');
    }

    /**
     * Test: Verify website footer information
     * 
     * Scenario: Verify website footer information
     *   Given I am on the website
     *   When I visit any page
     *   Then I should see the footer with current year copyright
     *   And I should see the website version "v3.1"
     *   And I should see "Simple PHP Website (Master)" in the footer
     */
    public function verifyWebsiteFooterInformation(AcceptanceTester $I): void
    {
        $I->amOnPage('/');
        
        $currentYear = date('Y');
        $I->see("©{$currentYear}", 'footer');
        $I->see('v3.1', 'footer');
        $I->see('Simple PHP Website (Master)', 'footer');
    }

    /**
     * Test: Verify active navigation state
     * 
     * Tests that the current page's navigation item has the active class
     */
    public function verifyActiveNavigationState(AcceptanceTester $I): void
    {
        // Test home page active state
        $I->amOnPage('/');
        $I->seeElement('nav.menu a.item.active');
        $I->see('Home', 'nav.menu a.item.active');
        
        // Test other pages active state
        $I->amOnPage('/?page=about-us');
        $I->seeElement('nav.menu a.item.active');
        $I->see('About Us', 'nav.menu a.item.active');
        
        $I->amOnPage('/?page=status');
        $I->seeElement('nav.menu a.item.active');
        $I->see('Status', 'nav.menu a.item.active');
    }

    /**
     * Test: Verify page structure consistency
     * 
     * Tests that all pages have consistent structure
     */
    public function verifyPageStructureConsistency(AcceptanceTester $I): void
    {
        $pages = ['/', '/?page=about-us', '/?page=products', '/?page=contact', '/?page=status'];
        
        foreach ($pages as $page) {
            $I->amOnPage($page);
            
            // Check basic page structure
            $I->seeElement('header');
            $I->seeElement('header h1');
            $I->seeElement('nav.menu');
            $I->seeElement('article');
            $I->seeElement('article h2');
            $I->seeElement('footer');
        }
    }
}
