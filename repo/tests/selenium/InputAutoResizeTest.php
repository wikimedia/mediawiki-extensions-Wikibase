<?php

/**
 * Selenium Tests for the Tooltip UI
 *
 * @licence GNU GPL v2+
 * @author Tobias Gritschacher < tobias.gritschacher at wikimedia.de >
 */

require_once 'selenium_tests_config.php';
require_once 'SeleniumTestCase.php';

class InputAutoResizeTest extends SeleniumTestCase {
	protected $targetUrl;
	protected $targetItemId;

	public function setUp() {
		$this->driver = WebDriver_Driver::InitAtLocal( "4444", "firefox" );
		$this->targetItemId = $this->createNewWikidataItem( "Test Item" );
		$this->setItemDescription( $this->targetItemId, "Some description for testing." );
		$this->assertTrue( is_numeric( $this->targetItemId ) );
		$this->targetUrl = WIKI_URL."/index.php?title=Data:q$this->targetItemId" . "&uselang=" . WIKI_USELANG;
		$this->set_implicit_wait( 1000 );
	}
	
	/**
	 * Tests the auto resize of the input box 
	 */
	public function testInputAutoResize() {
		$this->load( $this->targetUrl );
		
		$editLabelLink = "css=h1#firstHeading >
		div.wb-ui-propertyedittoolbar >
		div.wb-ui-propertyedittoolbar-group >
		div.wb-ui-propertyedittoolbar-group >
		a.wb-ui-propertyedittoolbar-button:nth-child(1)";
		$valueInputField = "css=h1#firstHeading > span > input.wb-ui-propertyedittoolbar-editablevalueinterface";
		$valueInputFieldRuler = "css=h1#firstHeading > span > span.ruler";
		
		$this->get_element( $editLabelLink )->click();
		$initialInputValue = $this->get_element( $valueInputField )->get_value();
		$longInputValue = $initialInputValue." Just making the label loooonger.";
		$inputFieldSize = $this->get_element( $valueInputField )->get_size();
		$inputFieldWidthBefore = $inputFieldSize["width"];
		$this->get_element( $valueInputField )->clear();
		$this->get_element( $valueInputField )->send_keys( $longInputValue );
		$inputFieldSize = $this->get_element( $valueInputField )->get_size();
		$inputFieldWidthAfter = $inputFieldSize["width"];
		$this->assertGreaterThan( $inputFieldWidthBefore, $inputFieldWidthAfter );
		$this->get_element( $valueInputField )->clear();
		$this->get_element( $valueInputField )->send_keys( $initialInputValue );
		$inputFieldSize = $this->get_element( $valueInputField )->get_size();
		$inputFieldWidthAfter = $inputFieldSize["width"];
		$this->assertEquals( $inputFieldWidthBefore, $inputFieldWidthAfter );
	}

	public function tearDown() {
		if ($this->driver) {
			$this->driver->quit();
		}
		parent::tearDown();
	}
}
