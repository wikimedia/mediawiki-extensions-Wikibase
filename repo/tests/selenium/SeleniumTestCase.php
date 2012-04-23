<?php

require_once 'WebDriver.php';
require_once 'WebDriver/Driver.php';
require_once 'WebDriver/MockDriver.php';
require_once 'WebDriver/WebElement.php';
require_once 'WebDriver/MockElement.php';

class SeleniumTestCase extends PHPUnit_Framework_TestCase {
	
	protected $driver;
	
	public function __construct() {
	}
	
	// Forward calls to main driver
	public function __call($name, $arguments) {
		if (method_exists($this->driver, $name)) {
			return call_user_func_array(array($this->driver, $name), $arguments);
		} else {
			throw new Exception("Tried to call nonexistent method $name with arguments:\n" . print_r($arguments, true));
		}
	}
	
	// Waits for AJAX calls to finish
	public function waitForAjax( $timeout = 10 ) {
		$tries = 0;
		while( true ) {
			$jsReturnString = $this->driver->execute_js_sync( "return jQuery.active;", array() );
			$jsReturnArray = json_decode( trim( $jsReturnString["body"] ), true );
			$jsReturnValue = $jsReturnArray["value"];			
			if( $jsReturnValue == 0 || $tries++ >= $timeout ) {
				break;
			}
			sleep( 1 );
		}
	}
	
}
