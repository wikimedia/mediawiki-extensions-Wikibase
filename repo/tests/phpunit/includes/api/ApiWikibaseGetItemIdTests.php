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
	protected $item;

	function setUp() {
		parent::setUp();
		$this->doLogin();		
		$input = '{
					"links": { "54": { "site": 54, "title": "Berlin" }, "62": { "site": 62, "title": "Berlin" }, "182": { "site": 182, "title": "Berlin" } },
					"label": { "de": { "language": "de", "value": "de-value" }, "en": { "language": "en", "value": "en-value" } },
					"description": { "de": { "language": "de", "value": "de-value" }, "en": { "language": "en", "value": "en-value" } }
				}';
		$ch = new WikibaseContentHandler();
		$this->item = $ch->unserializeContent( $input,'application/json' );
		$ret = $this->item->save();
	}
	
	function tearDown() {
		// there should be some way to remove the item under test
	}
	
	/**
	 * @group _Broken
	 */
	function testGetItemId() {
		
		//try {
			$data = $this->doApiRequest( array(
				'action' => 'wbgetitemid',
				'site' => 'no',
				'title' => 'Berlin',
			) );
		//}
		/*
		catch (UsageException $e) {
			$this->fail('The method getItemId throws an exception while it is expected to return an array during test');
			return;
		}
		*/
		print_r($data);
		$this->assertIsInternal( 'array', $data,
			"Must be an array as the main structure" );
	}
	
	
	/**
	 * Check that we have the help link
	 * @group Broken
	 */
	public function testGetHelpUrls() {
		/*
		$data = $this->doApiRequest( array(
			'action' => 'help',
			'modules' => 'wbgetitemid',
		) );
		
		$this->assertIsInternal( 'array', $data,
			"Must be an array as the main structure" );
		$this->assertInternalType(
			'string',
			$data,
			'Checking getHelpUrls for a valid string.'
		);
		$this->assertRegExp(
			'/^(http|https):/i',
			$data,
			'Checking getHelpUrls for a valid protocol.'
		);
		$this->assertRegExp(
			'/\/\/[^\.]+\.[^\.]+\.[^\.]+\//i',
			$data(),
			'Checking getHelpUrls for something that looks vaguely like a domain.'
		);
		*/
	}
}
	
