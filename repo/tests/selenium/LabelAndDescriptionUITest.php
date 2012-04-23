<?php
require_once 'selenium_tests_config.php';
require_once 'SeleniumTestCase.php';

class LabelAndDescriptionUITest extends SeleniumTestCase {
	protected $targetUrl;

	public function setUp() {
		$this->driver = WebDriver_Driver::InitAtLocal("4444", "firefox");

		//$itemId = $this->createNewWikidataItem("Demo Item");
		$itemId = $this->createNewWikidataItem();
		$this->setItemDescription( $itemId, "demo description" );
		$this->assertTrue( is_numeric( $itemId ) );
		$this->targetUrl = WIKI_URL."/index.php?title=Data:q$itemId" . "&uselang=" . WIKI_USELANG;
	}
	
	/**
	 * Tests the Heading and the Title of the page
	 */
	public function testPageTitle() {
		$this->set_implicit_wait( 5000 );
		$this->load( $this->targetUrl );
		
		$itemLabel = $this->get_element( "css=h1#firstHeading > span" )->get_text();
		$this->assertRegExp( "/".$itemLabel."/", $this->driver->get_title() );
	}

	/**
	 * Tests the functionality of the UI for displaying and editing the label
	 */
	public function testLabelUI() {
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
		$valueInputFieldSelector = "css=h1#firstHeading > span > input.wb-ui-propertyedittoolbar-editablevalue";
		 
		$this->set_implicit_wait( 1000 );
		$this->load( $this->targetUrl );
		 
		$targetLabel = $this->get_element( $labelElementSelector )->get_text();
		$changedLabel = $targetLabel."_foo";
		 
		// doing the test stuff
		$this->get_element( $editLinkSelector )->assert_text( "edit" );
		$this->assertFalse( $this->is_element_present( $valueInputFieldSelector ) );
		$this->get_element( $editLinkSelector )->click();
		$this->assertTrue( $this->is_element_present( $valueInputFieldSelector ) );
		$this->get_element( $saveLinkDisabledSelector )->assert_text( "save" );
		$this->assertFalse( $this->is_element_present( $saveLinkSelector ) );
		$this->get_element( $cancelLinkSelector )->assert_text( "cancel" );
		$this->get_element( $valueInputFieldSelector )->assert_value( $targetLabel );
		$this->get_element( $valueInputFieldSelector )->clear();
		$this->get_element( $saveLinkDisabledSelector )->assert_text( "save" );
		$this->assertFalse( $this->is_element_present( $saveLinkSelector ) );
		$this->get_element( $valueInputFieldSelector )->send_keys( $changedLabel );
		$this->get_element( $cancelLinkSelector )->click();
		$this->get_element( $labelElementSelector )->assert_text( $targetLabel );
		$this->get_element( $editLinkSelector )->click();
		$this->get_element( $valueInputFieldSelector )->clear();
		$this->get_element( $valueInputFieldSelector )->send_keys( $changedLabel );
		$this->get_element( $saveLinkSelector )->click();
		$this->waitForAjax();
		$this->get_element( $labelElementSelector )->assert_text( $changedLabel );
		$this->reload();
		$this->get_element( $labelElementSelector )->assert_text( $changedLabel );
		$this->assertRegExp( "/".$changedLabel."/", $this->driver->get_title() );
		$this->get_element( $editLinkSelector )->assert_text( "edit" );
		$this->get_element( $editLinkSelector )->click();
		$this->assertTrue( $this->is_element_present( $valueInputFieldSelector ) );
		$this->get_element( $valueInputFieldSelector )->assert_value( $changedLabel );
		$this->get_element( $cancelLinkSelector )->click();
		$this->get_element( $labelElementSelector )->assert_text( $changedLabel );
		$this->get_element( $editLinkSelector )->assert_text( "edit" );
		$this->get_element( $editLinkSelector )->click();
		$this->get_element( $valueInputFieldSelector )->clear();
		$this->get_element( $valueInputFieldSelector )->send_keys( $targetLabel );
		$this->get_element( $saveLinkSelector )->click();
		$this->waitForAjax();
		$this->reload();
		$this->get_element( $labelElementSelector )->assert_text( $targetLabel );
		$this->assertRegExp( "/".$targetLabel."/", $this->driver->get_title() );
	}

	/*
	 * Tests the functionality of the UI for displaying and editing the description
	 */
	public function testDescriptionUI() {
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
		$cancelLinkSelector = "css=div.wb-ui-propertyedittool-subject > div.wb-ui-propertyedittoolbar >
		div.wb-ui-propertyedittoolbar-group > div.wb-ui-propertyedittoolbar-group >
		a.wb-ui-propertyedittoolbar-button:nth-child(2)";
		$valueInputFieldSelector = "css=div.wb-ui-propertyedittool-subject > span > input.wb-ui-propertyedittoolbar-editablevalue";
		
		$this->set_implicit_wait( 1000 );
		$this->load( $this->targetUrl );
		 
		$targetDescription = $this->get_element( $descriptionElementSelector )->get_text();
		$changedDescription = $targetDescription." Adding stuff.";
		
		// doing the test stuff
		$this->get_element( $editLinkSelector )->assert_text( "edit" );
		$this->assertFalse( $this->is_element_present( $valueInputFieldSelector ) );
		$this->get_element( $editLinkSelector )->click();
		$this->assertTrue( $this->is_element_present( $valueInputFieldSelector ) );
		$this->get_element( $valueInputFieldSelector )->assert_value( $targetDescription );
		$this->get_element( $saveLinkDisabledSelector )->assert_text( "save" );
		$this->assertFalse( $this->is_element_present( $saveLinkSelector ) );
		$this->get_element( $cancelLinkSelector )->assert_text( "cancel" );
		$this->get_element( $valueInputFieldSelector )->clear();
		$this->get_element( $saveLinkDisabledSelector )->assert_text( "save" );
		$this->assertFalse( $this->is_element_present( $saveLinkSelector ) );
		$this->get_element( $valueInputFieldSelector )->send_keys( $changedDescription );
		$this->get_element( $cancelLinkSelector )->click();
		$this->get_element( $descriptionElementSelector )->assert_text( $targetDescription );
		$this->get_element( $editLinkSelector )->click();
		$this->get_element( $valueInputFieldSelector )->clear();
		$this->get_element( $valueInputFieldSelector )->send_keys( $changedDescription );
		$this->get_element( $saveLinkSelector )->click();
		$this->get_element( $descriptionElementSelector )->assert_text( $changedDescription );
		$this->waitForAjax();
		$this->reload();
		$this->get_element( $descriptionElementSelector )->assert_text( $changedDescription );
		$this->get_element( $editLinkSelector )->assert_text( "edit" );
		$this->get_element( $editLinkSelector )->click();
		$this->assertTrue( $this->is_element_present( $valueInputFieldSelector ) );
		$this->get_element( $valueInputFieldSelector )->assert_value( $changedDescription );
		$this->get_element( $cancelLinkSelector )->click();
		$this->get_element( $descriptionElementSelector )->assert_text( $changedDescription );		
		$this->get_element( $editLinkSelector )->assert_text( "edit" );
		$this->get_element( $editLinkSelector )->click();
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
			if ($this->hasFailed()) {
				$this->driver->set_sauce_context("passed", false);
			} else {
				$this->driver->set_sauce_context("passed", true);
			}
			$this->driver->quit();
		}
		parent::tearDown();
	}
}
