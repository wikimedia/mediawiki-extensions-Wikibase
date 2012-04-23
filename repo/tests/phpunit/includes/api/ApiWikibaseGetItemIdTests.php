<?php

/**
 * Tests for the ApiWikibaseGetItemId class.
 *
 * @file
 * @since 0.1
 *
 * @ingroup Wikibase
 * @ingroup Test
 *
 * @licence GNU GPL v2+
 * @author John Erling Blad < jeblad@gmail.com >
 * 
 */
class ApiWikibaseGetItemIdTests extends ApiTestCase {
	// TODO: Missing tests, missing log in, missing a lot...
	
	/**
	 * Instance of API to test against
	 * @var ApiWikibaseGetItemId
	 */
	protected $api;
	
	/**
	 * This is to set up the environment
	 */
	protected function setUp() {
		$this->api = new ApiWikibaseGetItemId( null, null); //well...
	}
	
	/**
	 * Check that we have the help link
	 */
	public function testGetHelpUrls() {
		
		$this->assertInternalType(
			'string',
			$this->api->getHelpUrls(),
			'Checking getHelpUrls for a valid string.'
		);
		$this->assertRegExp(
			'/^(http|https):/i',
			$this->api->getHelpUrls(),
			'Checking getHelpUrls for a valid protocol.'
		);
		$this->assertRegExp(
			'/\/\/[^\.]+\.[^\.]+\.[^\.]+\//i',
			$this->api->getHelpUrls(),
			'Checking getHelpUrls for something that looks vaguely like a domain.'
		);
	}
}
	
