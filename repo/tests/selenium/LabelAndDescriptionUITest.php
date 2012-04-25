<?php

/**
 * Selenium Tests for the Description and Label UI
 *
 * @licence GNU GPL v2+
 * @author Tobias Gritschacher < tobias.gritschacher at wikimedia.de >
 */

require_once 'selenium_tests_config.php';
require_once 'SeleniumTestCase.php';

class LabelAndDescriptionUITest extends SeleniumTestCase {
	protected $targetUrl;
	protected $targetItemId;

	public function setUp() {
		$this->driver = WebDriver_Driver::InitAtLocal( "4444", "firefox" );
		$this->targetItemId = $this->createNewWikidataItem(); // create random item
		$this->assertTrue( is_numeric( $this->targetItemId ) );
		$this->targetUrl = WIKI_URL."/index.php?title=Data:q$this->targetItemId" . "&uselang=" . WIKI_USELANG;
		$this->set_implicit_wait( 1000 );
	}
	
	/**
	 * Tests the Heading and the Title of the page
	 */
	public function testPageTitle() {
		$this->load( $this->targetUrl );
		
		$itemLabel = $this->get_element( "css=h1#firstHeading > span" )->get_text();
		$this->assertRegExp( "/".$itemLabel."/", $this->driver->get_title() );
	}

	/**
	 * Tests the functionality of the UI for displaying and editing the label
	 */
	public function testLabelUI() {
		$this->load( $this->targetUrl );
		
		// defining selectors for elements being tested
		$labelElementSelector = "css=h1#firstHeading > span";
		$editLinkSelector = "css=h1#firstHeading > 
							div.wb-ui-propertyedittoolbar >
							div.wb-ui-propertyedittoolbar-group > 
							div.wb-ui-propertyedittoolbar-group >
							a.wb-ui-propertyedittoolbar-button:nth-child(1)";
		$saveLinkDisabledSelector = "css=h1#firstHeading > 
									div.wb-ui-propertyedittoolbar >
									div.wb-ui-propertyedittoolbar-group > 
									div.wb-ui-propertyedittoolbar-group >
									span.wb-ui-propertyedittoolbar-button-disabled:nth-child(1)";
		$saveLinkSelector = "css=h1#firstHeading > 
							div.wb-ui-propertyedittoolbar >
							div.wb-ui-propertyedittoolbar-group > 
							div.wb-ui-propertyedittoolbar-group >
							a.wb-ui-propertyedittoolbar-button:nth-child(1)";
		$cancelLinkSelector = "css=h1#firstHeading > 
								div.wb-ui-propertyedittoolbar >
								div.wb-ui-propertyedittoolbar-group > 
								div.wb-ui-propertyedittoolbar-group >
								a.wb-ui-propertyedittoolbar-button:nth-child(2)";
		$valueInputFieldSelector = "css=h1#firstHeading > span > input.wb-ui-propertyedittoolbar-editablevalueinterface";
		 
		$targetLabel = $this->get_element( $labelElementSelector )->get_text();
		$changedLabel = $targetLabel."_foo";
		 
		// doing the test stuff
		// let's change the label
		$this->assertTrue( $this->is_element_present( $editLinkSelector ) );
		$this->assertFalse( $this->is_element_present( $valueInputFieldSelector ) );
		$this->get_element( $editLinkSelector )->click();
		$this->assertTrue( $this->is_element_present( $valueInputFieldSelector ) );
		$this->assertTrue( $this->is_element_present( $saveLinkDisabledSelector ) );
		$this->assertTrue( $this->is_element_present( $cancelLinkSelector ) );
		$this->assertFalse( $this->is_element_present( $saveLinkSelector ) );
		$this->assertFalse( $this->is_element_present( $editLinkSelector ) );
		$this->get_element( $valueInputFieldSelector )->assert_value( $targetLabel );
		$this->get_element( $valueInputFieldSelector )->clear();
		$this->assertTrue( $this->is_element_present( $saveLinkDisabledSelector ) );
		$this->assertTrue( $this->is_element_present( $cancelLinkSelector ) );
		$this->get_element( $valueInputFieldSelector )->send_keys( $changedLabel );
		$this->assertTrue( $this->is_element_present( $saveLinkSelector ) );
		$this->assertTrue( $this->is_element_present( $cancelLinkSelector ) );
		$this->get_element( $cancelLinkSelector )->click();
		$this->assertTrue( $this->is_element_present( $editLinkSelector ) );
		$this->assertFalse( $this->is_element_present( $valueInputFieldSelector ) );
		$this->get_element( $labelElementSelector )->assert_text( $targetLabel );
		$this->get_element( $editLinkSelector )->click();
		$this->get_element( $valueInputFieldSelector )->clear();
		$this->get_element( $valueInputFieldSelector )->send_keys( $changedLabel );
		$this->get_element( $saveLinkSelector )->click();
		$this->waitForAjax();
		$this->assertTrue( $this->is_element_present( $editLinkSelector ) );
		$this->assertFalse( $this->is_element_present( $valueInputFieldSelector ) );
		$this->get_element( $labelElementSelector )->assert_text( $changedLabel );
		$this->reload();
		$this->get_element( $labelElementSelector )->assert_text( $changedLabel );
		$this->assertRegExp( "/".$changedLabel."/", $this->get_title() );
		
		// change the label back to the initial value
		$this->get_element( $editLinkSelector )->click();
		$this->get_element( $valueInputFieldSelector )->assert_value( $changedLabel );
		$this->get_element( $valueInputFieldSelector )->clear();
		$this->get_element( $valueInputFieldSelector )->send_keys( $targetLabel );
		$this->get_element( $saveLinkSelector )->click();
		$this->waitForAjax();
		$this->reload();
		$this->get_element( $labelElementSelector )->assert_text( $targetLabel );
		$this->assertRegExp( "/".$targetLabel."/", $this->get_title() );
	}

	/*
	 * Tests the functionality of the UI for displaying and editing the description
	 */
	public function testDescriptionUI() {
		$this->load( $this->targetUrl );
		
		$targetDescription = "Some description for this item.";
		$changedDescription = $targetDescription." Adding stuff.";
		
		// defining selectors for elements beeing tested
		$descriptionElementSelector = "css=div.wb-ui-propertyedittool-subject > span.wb-property-container-value";
		$editLinkSelector = "css=div.wb-ui-propertyedittool-subject > div.wb-ui-propertyedittoolbar >
		div.wb-ui-propertyedittoolbar-group > div.wb-ui-propertyedittoolbar-group >
		a.wb-ui-propertyedittoolbar-button:nth-child(1)";
		$saveLinkSelector = "css=div.wb-ui-propertyedittool-subject > div.wb-ui-propertyedittoolbar >
		div.wb-ui-propertyedittoolbar-group > div.wb-ui-propertyedittoolbar-group >
		a.wb-ui-propertyedittoolbar-button:nth-child(1)";
		$saveLinkDisabledSelector = "css=div.wb-ui-propertyedittool-subject > div.wb-ui-propertyedittoolbar >
		div.wb-ui-propertyedittoolbar-group > div.wb-ui-propertyedittoolbar-group >
		span.wb-ui-propertyedittoolbar-button:nth-child(1)";
		$cancelLinkDisabledSelector = "css=div.wb-ui-propertyedittool-subject > div.wb-ui-propertyedittoolbar >
		div.wb-ui-propertyedittoolbar-group > div.wb-ui-propertyedittoolbar-group >
		span.wb-ui-propertyedittoolbar-button:nth-child(2)";
		$cancelLinkSelector = "css=div.wb-ui-propertyedittool-subject > div.wb-ui-propertyedittoolbar >
		div.wb-ui-propertyedittoolbar-group > div.wb-ui-propertyedittoolbar-group >
		a.wb-ui-propertyedittoolbar-button:nth-child(2)";
		$valueInputFieldSelector = "css=div.wb-ui-propertyedittool-subject > span > input.wb-ui-propertyedittoolbar-editablevalueinterface";
		
		// doing the test stuff
		// no description should be there for a newly created item and the input box should be displayed instantly 
		$this->assertFalse( $this->is_element_present( $saveLinkSelector ) );
		$this->assertFalse( $this->is_element_present( $cancelLinkSelector ) );
		$this->assertTrue( $this->is_element_present( $saveLinkDisabledSelector ) );
		$this->assertTrue( $this->is_element_present( $cancelLinkDisabledSelector ) );
		$this->assertTrue( $this->is_element_present( $valueInputFieldSelector ) );
		$this->get_element( $valueInputFieldSelector )->clear();
		$this->get_element( $valueInputFieldSelector )->send_keys( $targetDescription );
		$this->assertTrue( $this->is_element_present( $saveLinkSelector ) );
		$this->assertTrue( $this->is_element_present( $cancelLinkDisabledSelector ) );
		$this->get_element( $saveLinkSelector )->click();
		$this->get_element( $descriptionElementSelector )->assert_text( $targetDescription );
		$this->waitForAjax();
		$this->reload();
		
		// now there should be a description - let's change it
		$this->get_element( $descriptionElementSelector )->assert_text( $targetDescription );
		$this->assertTrue( $this->is_element_present( $editLinkSelector ) );
		$this->assertFalse( $this->is_element_present( $valueInputFieldSelector ) );
		$this->get_element( $editLinkSelector )->click();
		$this->assertTrue( $this->is_element_present( $valueInputFieldSelector ) );
		$this->get_element( $valueInputFieldSelector )->assert_value( $targetDescription );
		$this->assertTrue( $this->is_element_present( $saveLinkDisabledSelector ) );
		$this->assertTrue( $this->is_element_present( $cancelLinkSelector ) );
		$this->get_element( $valueInputFieldSelector )->clear();
		$this->assertTrue( $this->is_element_present( $saveLinkDisabledSelector ) );
		$this->get_element( $valueInputFieldSelector )->send_keys( $changedDescription );
		$this->assertTrue( $this->is_element_present( $saveLinkSelector ) );
		$this->assertTrue( $this->is_element_present( $cancelLinkSelector ) );
		$this->get_element( $cancelLinkSelector )->click();
		$this->get_element( $descriptionElementSelector )->assert_text( $targetDescription );
		$this->assertTrue( $this->is_element_present( $editLinkSelector ) );
		$this->get_element( $editLinkSelector )->click();
		$this->get_element( $valueInputFieldSelector )->clear();
		$this->get_element( $valueInputFieldSelector )->send_keys( $changedDescription );
		$this->get_element( $saveLinkSelector )->click();
		$this->get_element( $descriptionElementSelector )->assert_text( $changedDescription );
		$this->waitForAjax();
		$this->reload();
		$this->get_element( $descriptionElementSelector )->assert_text( $changedDescription );
		
		// restore the initial description again
		$this->assertTrue( $this->is_element_present( $editLinkSelector ) );
		$this->get_element( $editLinkSelector )->click();
		$this->assertTrue( $this->is_element_present( $valueInputFieldSelector ) );
		$this->get_element( $valueInputFieldSelector )->assert_value( $changedDescription );
		$this->get_element( $valueInputFieldSelector )->clear();
		$this->get_element( $valueInputFieldSelector )->send_keys( $targetDescription );
		$this->get_element( $saveLinkSelector )->click();
		$this->get_element( $descriptionElementSelector )->assert_text( $targetDescription );
		$this->waitForAjax();
		$this->reload();
		$this->get_element( $descriptionElementSelector )->assert_text( $targetDescription );
	}

	public function tearDown() {
		if ($this->driver) {
			$this->driver->quit();
		}
		parent::tearDown();
	}
}
