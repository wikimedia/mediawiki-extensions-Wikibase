<?php

/**
 * Some common functions for Selenium Tests of the WikibaseRepo
 *
 * @licence GNU GPL v2+
 * @author Tobias Gritschacher < tobias.gritschacher at wikimedia.de >
 */

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
	
	/**
	 * Waits for AJAX calls to finish
	 * @param int $timeout Timeout for the AJAX call in seconds, default: 10
	 */
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
	
	/**
	 * Makes a call to the API to create a new Item
	 * @param String $item The item's Title/Label. If this parameter is not set a random item will be created.
	 */
	public function createNewWikidataItem( $item = "" ) {
		if( !$item ) {
			$item = $this->generateRandomWord( 8 );
		}
		$params = array(
				"format" => "json",
				"action" => "wbsetlabel",
				"site" => WIKI_USELANG,
				"title" => $item,
				"item" => "set",
				"language" => WIKI_USELANG,
				"label" => $item
				);

		$result = $this->doCurlApiCall( $params );
		$this->assertTrue( isset($result["item"][1] ));
		return $result["item"][1];
	}
	
	/**
	 * Makes a call to the API to create a new Item
	 * @param String $item The item's Title/Label
	 */
	public function setItemDescription( $itemId, $description ) {
		$params = array(
				"format" => "json",
				"action" => "wbsetdescription",
				"id" => $itemId,
				"item" => "set",
				"language" => WIKI_USELANG,
				"description" => $description
		);

		$result = $this->doCurlApiCall( $params );
		$this->assertEquals( 1, $result["success"] );
		return $result["success"];
	}
	
	/**
	 * Does the call to the API
	 * @param Array $params POST params for the API call as associative array
	 */
	private function doCurlApiCall( $params ) {
		$params_string = http_build_query( $params );
		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL, WIKI_URL."/api.php" );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, $params_string );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		$this->assertNotNUll( $result = json_decode( curl_exec( $ch ), true ) );
		$this->assertTrue( isset( $result["success"] ) );
		curl_close( $ch );
		
		return $result;
	}
	
	private function generateRandomWord( $length ) {
		$chars = "abcdefghijklmnopqrstuvwxyz ";
		$string = "";
		for ( $p=0; $p<$length; $p++ ) {
			$string .= $chars[mt_rand( 0, strlen( $chars )-1 )];
		}
		return $string;
	}
	
}
