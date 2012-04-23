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

	/**
	 * This is to set up the environment
	 */
	function setUp() {
		parent::setUp();
		$this->doLogin();
	}
		
	/**
	 * @group _Broken
	 */
	function testGetItemId() {
		$data = $this->doApiRequest(
			array(
				'action' => 'wbgetitemid',
				'site' => 'en',
				'title' => 'Berlin'
			)
		);
		print_r($data);
		$this->markTestIncomplete( "This has probably failed" );
	}
	
	
	/**
	 * Check that we have the help link
	 */
	public function testGetHelpUrls() {
		$data = $this->doApiRequest(
			array(
				'action' => 'help',
				'modules' => 'wbgetitemid',
			)
		);
		print_r($data);
		$this->assertInternalType(
			'string',
			$this->getHelpUrls(),
			'Checking getHelpUrls for a valid string.'
		);
		$this->assertRegExp(
			'/^(http|https):/i',
			$this->getHelpUrls(),
			'Checking getHelpUrls for a valid protocol.'
		);
		$this->assertRegExp(
			'/\/\/[^\.]+\.[^\.]+\.[^\.]+\//i',
			$this->getHelpUrls(),
			'Checking getHelpUrls for something that looks vaguely like a domain.'
		);
		//$this->markTestIncomplete( "This has probably failed" );
	}
}
	
