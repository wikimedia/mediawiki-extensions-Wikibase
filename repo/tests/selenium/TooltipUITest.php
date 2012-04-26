<?php

/**
 * Selenium Tests for the Tooltip UI
 *
 * @licence GNU GPL v2+
 * @author Tobias Gritschacher < tobias.gritschacher at wikimedia.de >
 */

require_once 'selenium_tests_config.php';
require_once 'SeleniumTestCase.php';

class TooltipUITest extends SeleniumTestCase {
	protected $targetUrl;
	protected $targetItemId;

	public function setUp() {
		$this->driver = WebDriver_Driver::InitAtLocal( "4444", SELENIUM_BROWSER );
		$this->targetItemId = $this->createNewWikidataItem( "Test Item" );
		$this->setItemDescription( $this->targetItemId, "Some description for testing." );
		$this->assertTrue( is_numeric( $this->targetItemId ) );
		$this->targetUrl = WIKI_URL."/index.php?title=Data:q$this->targetItemId" . "&uselang=" . WIKI_USELANG;
		$this->set_implicit_wait( 1000 );
	}

	/**
	 * Tests the functionality of the UI for displaying and editing the label
	 */
	public function testLabelTooltipUI() {
		$this->load( $this->targetUrl );
		
		// defining selectors for elements being tested
		$editLabelTooltip= "css=h1#firstHeading > 
							div.wb-ui-propertyedittoolbar >
							div.wb-ui-propertyedittoolbar-group > 
							span.wb-ui-propertyedittoolbar-tooltip >
							span.mw-help-field-hint";
		$editLabelLink = "css=h1#firstHeading > 
						div.wb-ui-propertyedittoolbar >
						div.wb-ui-propertyedittoolbar-group > 
						div.wb-ui-propertyedittoolbar-group >
						a.wb-ui-propertyedittoolbar-button:nth-child(1)";
		$cancelEditLabelLink = "css=h1#firstHeading >
							div.wb-ui-propertyedittoolbar >
							div.wb-ui-propertyedittoolbar-group >
							div.wb-ui-propertyedittoolbar-group >
							a.wb-ui-propertyedittoolbar-button:nth-child(2)";
		$editDescriptionTooltip= "css=div.wb-ui-propertyedittool-subject > 
							div.wb-ui-propertyedittoolbar >
							div.wb-ui-propertyedittoolbar-group > 
							span.wb-ui-propertyedittoolbar-tooltip >
							span.mw-help-field-hint";
		$editDescriptionLink = "css=div.wb-ui-propertyedittool-subject > 
							div.wb-ui-propertyedittoolbar >
							div.wb-ui-propertyedittoolbar-group > 
							div.wb-ui-propertyedittoolbar-group >
							a.wb-ui-propertyedittoolbar-button:nth-child(1)";
		$cancelEditDescriptionLink = "css=div.wb-ui-propertyedittool-subject > 
								div.wb-ui-propertyedittoolbar >
								div.wb-ui-propertyedittoolbar-group > 
								div.wb-ui-propertyedittoolbar-group >
								a.wb-ui-propertyedittoolbar-button:nth-child(2)";
		
		// doing the test stuff
		$this->assertFalse( $this->is_element_present( $editLabelTooltip ) );
		$this->assertTrue( $this->is_element_present($editLabelLink ) );
		$this->get_element( $editLabelLink )->click();
		$this->assertTrue( $this->is_element_present( $editLabelTooltip ) );
		$this->get_element( $cancelEditLabelLink )->click();
		$this->assertFalse( $this->is_element_present( $editLabelTooltip ) );
		
		$this->assertTrue( $this->is_element_present($editDescriptionLink ) );
		$this->get_element( $editDescriptionLink )->click();
		$this->assertTrue( $this->is_element_present( $editDescriptionTooltip ) );
		$this->get_element( $cancelEditDescriptionLink )->click();
		$this->assertFalse( $this->is_element_present( $editDescriptionTooltip ) );
	}

	public function tearDown() {
		if ($this->driver) {
			$this->driver->quit();
		}
		parent::tearDown();
	}
}
