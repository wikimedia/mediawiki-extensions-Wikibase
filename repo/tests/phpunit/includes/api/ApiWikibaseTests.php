<?php

/**
 * Tests for the ApiWikibase class.
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
 * @group Database
 * @group medium
 */
class ApiWikibaseTests extends ApiTestCase {

	/**
	 * This is to set up the environment
	 */
	protected $input, $index;

	public function setUp() {
		parent::setUp();
		//$this->doLogin();	
		$this->input = array(
		array(
		'id' => 1,
		'data' => '{
			"links": {
				"de": { "site": "de", "title": "Berlin" },
				"en": { "site": "en", "title": "Berlin" },
				"no": { "site": "no", "title": "Berlin" },
				"nn": { "site": "nn", "title": "Berlin" }
			},
			"label": {
				"de": { "language": "de", "value": "Berlin" },
				"en": { "language": "en", "value": "Berlin" },
				"no": { "language": "no", "value": "Berlin" },
				"nn": { "language": "nn", "value": "Berlin" }
			},				
			"description": { 
				"de" : { "language": "de", "value": "Bundeshauptstadt und Regierungssitz der Bundesrepublik Deutschland." },
				"en" : { "language": "en", "value": "Capital city and a federated state of the Federal Republic of Germany." },
				"no" : { "language": "no", "value": "Hovedsted og delstat og i Forbundsrepublikken Tyskland." },
				"nn" : { "language": "nn", "value": "Hovudstad og delstat i Forbundsrepublikken Tyskland." }
			}
		}'),
		array(
		'id' => 2,
		'data' => '{
			"links": {
				"de": { "site": "de", "title": "London" },
				"en": { "site": "en", "title": "London" },
				"no": { "site": "no", "title": "London" },
				"nn": { "site": "nn", "title": "London" }
			},
			"label": {
				"de": { "language": "de", "value": "London" },
				"en": { "language": "en", "value": "London" },
				"no": { "language": "no", "value": "London" },
				"nn": { "language": "nn", "value": "London" }
			},				
			"description": { 
				"de" : { "language": "de", "value": "Hauptstadt Englands und des Vereinigten Königreiches." },
				"en" : { "language": "en", "value": "Capital city of England and the United Kingdom." },
				"no" : { "language": "no", "value": "Hovedsted i England og Storbritannia." },
				"nn" : { "language": "nn", "value": "Hovudstad i England og Storbritannia." }
			}
		}'),
		array(
		'id' => 3,
		'data' => '{
			"links": {
				"de": { "site": "de", "title": "Oslo" },
				"en": { "site": "en", "title": "Oslo" },
				"no": { "site": "no", "title": "Oslo" },
				"nn": { "site": "nn", "title": "Oslo" }
			},
			"label": {
				"de": { "language": "de", "value": "Oslo" },
				"en": { "language": "en", "value": "Oslo" },
				"no": { "language": "no", "value": "Oslo" },
				"nn": { "language": "nn", "value": "Oslo" }
			},				
			"description": { 
				"de" : { "language": "de", "value": "Hauptstadt der Norwegen." },
				"en" : { "language": "en", "value": "Capital city in Norway." },
				"no" : { "language": "no", "value": "Hovedsted i Norge." },
				"nn" : { "language": "nn", "value": "Hovudstad i Noreg." }
			}
		}'));
		
	}
	
	public function tearDown() {
		// there should be some way to remove the item under test
	}
	
	/**
	 * @group API
	 */
	public function testSetItem() {
		foreach ($this->input as $item) {
			$data = $this->doApiRequest( array(
				'action' => 'wbsetitem',
				'data' => $item['data'],
			) );
			$this->assertArrayHasKey( 'success', $data[0],
				"Must have an 'success' key in the result from the API" );
			$this->assertArrayHasKey( 'item', $data[0],
				"Must have an 'items' key in the result from the API" );
			$this->assertArrayHasKey( 'id', $data[0]['item'],
				"Must have an 'id' key in the 'item' result from the API" );
			$this->assertArrayHasKey( 'sitelinks', $data[0]['item'],
				"Must have an 'sitelinks' key in the 'item' result from the API" );
			$this->assertArrayHasKey( 'labels', $data[0]['item'],
				"Must have an 'labels' key in the 'item' result from the API" );
			$this->assertArrayHasKey( 'descriptions', $data[0]['item'],
				"Must have an 'descriptions' key in the 'item' result from the API" );
			// we should store and reuse but its thrown away on each iteration
			$this->assertEquals( $item['id'], $data[0]['item']['id'],
				"Must have an 'id' key in the 'item' result from the API that is equal to the expected" );
		}
	}
		
	/**
	 * @group API
	 */
	public function testGetItemId() {
		$data = $this->doApiRequest( array(
			'action' => 'wbgetitemid',
			'site' => 'no',
			'title' => 'London',
		) );
		$this->assertArrayHasKey( 'success', $data[0],
			"Must have an 'success' key in the result from the API" );
		$this->assertArrayHasKey( 'item', $data[0],
			"Must have an 'item' key in the result from the API" );
		$this->assertArrayHasKey( 'id', $data[0]['item'],
			"Must have an 'id' key in the 'item' result from the API" );
	}
	
	/**
	 * @group API
	 */
	public function testGetItems1() {
		foreach ($this->input as $item) {
			$data = $this->doApiRequest( array(
				'action' => 'wbgetitems',
				'ids' => "{$item['id']}",
			) );
			$this->assertArrayHasKey( 'success', $data[0],
				"Must have an 'success' key in the result from the API" );
			$this->assertArrayHasKey( 'items', $data[0],
				"Must have an 'items' key in the result from the API" );
			$this->assertArrayHasKey( "{$item['id']}", $data[0]['items'],
				"Must have an '{$item['id']}' key in the 'items' result from the API" );
			$this->assertArrayHasKey( 'id', $data[0]['items']["{$item['id']}"],
				"Must have an 'id' key in the '{$item['id']}' result from the API" );
			$this->assertArrayHasKey( 'sitelinks', $data[0]['items']["{$item['id']}"],
				"Must have an 'sitelinks' key in the '{$item['id']}' result from the API" );
			$this->assertArrayHasKey( 'labels', $data[0]['items']["{$item['id']}"],
				"Must have an 'labels' key in the '{$item['id']}' result from the API" );
			$this->assertArrayHasKey( 'descriptions', $data[0]['items']["{$item['id']}"],
				"Must have an 'descriptions' key in the '{$item['id']}' result from the API" );
		}
	}
	
	/**
	 * @group API
	 */
	public function testGetItems2() {
		$ids = array();
		foreach ($this->input as $item) {
			array_push($ids, "{$item['id']}");
		}
		$data = $this->doApiRequest( array(
			'action' => 'wbgetitems',
			'ids' => join($ids, '|')
		) );
		$this->assertArrayHasKey( 'success', $data[0],
			"Must have an 'success' key in the result from the API" );
		$this->assertArrayHasKey( 'items', $data[0],
			"Must have an 'items' key in the result from the API" );
		$this->assertCount( 3, $data[0]['items'],
			"Must have a number of count of 3 in the 'items' result from the API" );
	}
	
	/**
	 * @group API
	 * @dataProvider providerSiteTitle1
	 */
	public function testLinkSite1( $id, $site, $title, $linksite, $linktitle ) {
		$data = $this->doApiRequest( array(
			'action' => 'wblinksite',
			'id' => $id,
			'linksite' => $linksite,
			'linktitle' => $linktitle,
			'link' => 'set',
		) );
		//print_r($data);
		$this->assertArrayHasKey( 'success', $data[0],
			"Must have an 'success' key in the result from the API" );
		$this->assertArrayHasKey( 'item', $data[0],
			"Must have an 'item' key in the result from the API" );
	}

	public function providerSiteTitle1() {
		return array(
			array( 1, 'nn', 'Berlin', 'da', 'Berlin' ),
			array( 2, 'en', 'London', 'fi', 'London' ),
			array( 3, 'no', 'Oslo', 'nl', 'Oslo' ),
		);
	}
	
	/**
	 * @group API
	 * @dataProvider providerSiteTitle1
	 */
	public function testGetItems3( $id, $site, $title, $linksite, $linktitle ) {
		
		$data = $this->doApiRequest( array(
			'action' => 'wbgetitems',
			'sites' => $linksite,
			'titles' => $linktitle,
		) );
		$this->assertArrayHasKey( 'success', $data[0],
			"Must have an 'success' key in the result from the API" );
		$this->assertArrayHasKey( 'items', $data[0],
			"Must have an 'items' key in the result from the API" );
		$this->assertCount( 1, $data[0]['items'],
			"Must have a number of count of 1 in the 'items' result from the API" );
		$this->assertArrayHasKey( 'id', $data[0]['items']["{$id}"],
			"Must have an '{$id}' key in the 'items' result from the API" );
		$this->assertEquals( "{$id}", $data[0]['items']["{$id}"]['id'],
			"Must have a number '{$id}' in the 'id' result from the API" );
	}

	/**
	 * @group API
	 * @dataProvider providerSiteTitle1
	 */
	public function testGetItems4( $id, $site, $title, $linksite, $linktitle ) {
		
		$data = $this->doApiRequest( array(
			'action' => 'wbgetitems',
			'sites' => $site,
			'titles' => $title,
		) );
		$this->assertArrayHasKey( 'success', $data[0],
			"Must have an 'success' key in the result from the API" );
		$this->assertArrayHasKey( 'items', $data[0],
			"Must have an 'items' key in the result from the API" );
		$this->assertCount( 1, $data[0]['items'],
			"Must have a number of count of 1 in the 'items' result from the API" );
	}
	
	/**
	 * @group API
	 * @dataProvider providerSiteTitle2
	 */
	public function testLinkSite2( $id, $site, $title, $linksite, $linktitle, $badge ) {
		$data = $this->doApiRequest( array(
			'action' => 'wblinksite',
			'site' => $site,
			'title' => $title,
			'linksite' => $linksite,
			'linktitle' => $linktitle,
			'badge' => $badge,
			'link' => 'set',
		) );
		//print_r($data);
		$this->assertArrayHasKey( 'success', $data[0],
			"Must have an 'success' key in the result from the API" );
		$this->assertArrayHasKey( 'item', $data[0],
			"Must have an 'item' key in the result from the API" );
	}

	public function providerSiteTitle2() {
		return array(
			array( 1, 'nn', 'Berlin', 'sv', 'Berlin', 1 ),
			array( 2, 'en', 'London', 'nl', 'London', 2 ),
			array( 3, 'no', 'Oslo', 'da', 'Oslo', 3 ),
		);
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
	
