<?php

require_once 'WebDriver.php';
require_once 'WebDriver/Driver.php';
require_once 'WebDriver/MockDriver.php';
require_once 'WebDriver/WebElement.php';
require_once 'WebDriver/MockElement.php';

class SampleMediawikiTest extends PHPUnit_Framework_TestCase {
  protected $driver;
  protected $targetItem;
  protected $targetDescription;
  protected $targetUseLang;
  protected $targetUrl;
  
  public function setUp() {
    // Choose one of the following
    
    // For tests running at Sauce Labs
//     $this->driver = WebDriver_Driver::InitAtSauce("my-sauce-username", "my-sauce-api-key", "WINDOWS", "firefox", "3.6");
//     $sauce_job_name = get_class($this);
//     $this->driver->set_sauce_context("name", $sauce_job_name);
    
    // For a mock driver (for debugging)
//     $this->driver = new WebDriver_MockDriver();
//     define('kFestDebug', true);

    // For a local driver
    $this->driver = WebDriver_Driver::InitAtLocal("4444", "firefox");
    
    $this->targetItem = "Georgia";
    $this->targetDescription = "A central-asian country";
    $this->targetUseLang = "en";
    $this->targetUrl = "http://localhost/mediawiki/index.php?title=Data:" . $this->targetItem . "&uselang=" . $this->targetUseLang;
  }
  
  // Forward calls to main driver 
  public function __call($name, $arguments) {
    if (method_exists($this->driver, $name)) {
      return call_user_func_array(array($this->driver, $name), $arguments);
    } else {
      throw new Exception("Tried to call nonexistent method $name with arguments:\n" . print_r($arguments, true));
    }
  }

  public function testMainPageTitle() {
    $this->set_implicit_wait(5000);
    $this->load("http://localhost/mediawiki/");
    $this->assert_title("mediawiki-dev");
  }

  public function testWikidataPageTitle() {
  	$this->set_implicit_wait( 5000 );
  	$this->load( $this->targetUrl );
  	$this->assertRegExp( "/".$this->targetItem."/", $this->driver->get_title() );
  	$this->driver->get_element( "css=h1#firstHeading > span")->assert_text($this->targetItem );
  }
  
  public function testWikidataLabelUI() {
  	$changedLabel = $this->targetItem."_foo";
  	$this->set_implicit_wait( 1000 );
  	$this->load( $this->targetUrl );
  	
  	// defining selectors for elements being tested
  	$labelElementSelector = "css=h1#firstHeading > span";
  	$editLinkSelector = "css=h1#firstHeading > div.wb-ui-propertyedittoolbar > 
  						div.wb-ui-propertyedittoolbar-group > div.wb-ui-propertyedittoolbar-group > 
  						a.wb-ui-propertyedittoolbar-button:nth-child(1)";
  	$saveLinkDisabledSelector = "css=h1#firstHeading > div.wb-ui-propertyedittoolbar >
  								div.wb-ui-propertyedittoolbar-group > div.wb-ui-propertyedittoolbar-group > 
  								span.wb-ui-propertyedittoolbar-button-disabled:nth-child(1)";
  	$saveLinkSelector = "css=h1#firstHeading > div.wb-ui-propertyedittoolbar > 
  						div.wb-ui-propertyedittoolbar-group > div.wb-ui-propertyedittoolbar-group > 
  						a.wb-ui-propertyedittoolbar-button:nth-child(1)";
  	$cancelLinkSelector = "css=h1#firstHeading > div.wb-ui-propertyedittoolbar > 
  						div.wb-ui-propertyedittoolbar-group > div.wb-ui-propertyedittoolbar-group > 
  						a.wb-ui-propertyedittoolbar-button:nth-child(2)";
  	$valueInputFieldSelector = "css=h1#firstHeading > span > input.wb-ui-propertyedittoolbar-editablevalue";
  	
  	// doing the test stuff
  	$this->get_element( $labelElementSelector )->assert_text( $this->targetItem );
  	$this->get_element( $editLinkSelector )->assert_text( "edit" );
  	$this->assertFalse( $this->is_element_present( $valueInputFieldSelector ) );
  	$this->get_element( $editLinkSelector )->click();
  	$this->assertTrue( $this->is_element_present( $valueInputFieldSelector ) );
  	$this->get_element( $saveLinkDisabledSelector )->assert_text( "save" );
  	$this->get_element( $cancelLinkSelector )->assert_text( "cancel" );
  	$this->get_element( $valueInputFieldSelector )->assert_value( $this->targetItem );
  	$this->get_element( $valueInputFieldSelector )->clear();
  	$this->get_element( $valueInputFieldSelector )->send_keys( $changedLabel );
  	$this->get_element( $cancelLinkSelector )->click();
  	$this->get_element( $labelElementSelector )->assert_text( $this->targetItem );
  	$this->get_element( $editLinkSelector )->click();
  	$this->get_element( $valueInputFieldSelector )->clear();
  	$this->get_element( $valueInputFieldSelector )->send_keys( $changedLabel );
  	$this->get_element( $saveLinkSelector )->click();
  	$this->get_element( $labelElementSelector )->assert_text( $changedLabel );
  }
  
  
  
  public function testWikidataDescriptionUI() {
  	$changedDescription = $this->targetDescription.". Adding foo.";
  	$this->set_implicit_wait( 1000 );
  	$this->load( $this->targetUrl );
  	
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
  	
  	// doing the test stuff
  	$this->get_element( $descriptionElementSelector )->assert_text( $this->targetDescription );
  	$this->get_element( $editLinkSelector )->assert_text( "edit" );
  	$this->assertFalse( $this->is_element_present( $valueInputFieldSelector ) );
  	$this->get_element( $editLinkSelector )->click();
  	$this->assertTrue( $this->is_element_present( $valueInputFieldSelector ) );
  	$this->get_element( $valueInputFieldSelector )->assert_value( $this->targetDescription );
  	$this->get_element( $saveLinkDisabledSelector )->assert_text( "save" );
  	$this->get_element( $cancelLinkSelector )->assert_text( "cancel" );
  	$this->get_element( $valueInputFieldSelector )->clear();
  	$this->get_element( $valueInputFieldSelector )->send_keys( $changedDescription );
  	$this->get_element( $cancelLinkSelector )->click();
  	$this->get_element( $descriptionElementSelector )->assert_text( $this->targetDescription );
  	$this->get_element( $editLinkSelector )->click();
  	$this->get_element( $valueInputFieldSelector )->clear();
  	$this->get_element( $valueInputFieldSelector )->send_keys( $changedDescription );
  	$this->get_element( $saveLinkSelector )->click();
  	$this->get_element( $descriptionElementSelector )->assert_text( $changedDescription );
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
