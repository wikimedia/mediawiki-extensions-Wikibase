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
 */
class ApiWikibaseTests extends ApiTestCase {

	/**
	 * This is to set up the environment
	 */
	protected $input, $index;

	function setUp() {
		parent::setUp();
		//$this->doLogin();	
		$this->index = array();	
		$this->input = array(
		array('data' => '{
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
		array('data' => '{
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
		array('data' => '{
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
	
	function tearDown() {
		// there should be some way to remove the item under test
	}
	
	/**
	 * @group API
	 */
	function testSetItem() {
		$idx = 0;
		foreach ($this->input as $item) {
			$data = $this->doApiRequest( array(
				'action' => 'wbsetitem',
				'data' => $item['data'],
			) );
			print_r($item['data']);
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
			$item['id'] = $data[0]['item']['id'];
			$this->index["{$item->id}"] = $idx++; // not quite sure what the index will be if id is zero
		}
	}
	
	/**
	 * @group API
	 */
	function testGetItemId() {
		$data = $this->doApiRequest( array(
			'action' => 'wbgetitemid',
			'site' => 'no',
			'title' => 'London',
		) );
		print_r($data);
		$this->assertArrayHasKey( 'item', $data[0],
			"Must have an 'item' key in the result from the API" );
		$this->assertArrayHasKey( 'id', $data[0]['item'],
			"Must have an 'id' key in the 'item' result from the API" );
		$this->assertEquals( 1, $data[0]['item']['id'],
			"Must have an 'id' key in the 'item' result from the API that is 1" );
		if (isset($item->index[$data[0]['item']['id']])) {
			if (isset($item->input[$item->index[$data[0]['item']['id']]])) {
				if ($item->input[$item->index[$data[0]['item']['id']]['id']] !== $data[0]['item']['id']) {
					$item->fail("Didn't found the original object");
				}
			}
		}
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
	
