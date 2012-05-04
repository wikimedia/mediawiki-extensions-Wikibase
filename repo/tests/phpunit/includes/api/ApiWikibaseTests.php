<?php

/**
 * Tests for the ApiWikibase class.
 * 
 * The tests are using "Database" to get its own set of temporal tables.
 * This is nice so we avoid poisoning an existing database.
 * 
 * The tests are using "medium" so they are able to run alittle longer before they are killed.
 * Without this they will be killed after 1 second, but the setup of the tables takes so long
 * time that the first few tests get killed.
 * 
 * The tests are doing some assumptions on the id numbers. If the database isn't empty when
 * when its filled with test items the ids will most likely get out of sync and the tests will
 * fail. It seems impossible to store the item ids back somehow and at the same time not being
 * dependant on some magically correct solution. That is we could use GetItemId but then we
 * would imply that this module in fact is correct.
 *
 * @file
 * @since 0.1
 *
 * @ingroup Wikibase
 * @ingroup Test
 * 
 * The database group has as a side effect that temporal database tables are created. This makes
 * it possible to test without poisoning a production database.
 * @group Database
 * 
 * Some of the tests takes more time, and needs therefor longer time before they can be aborted
 * as non-functional. The reason why tests are aborted is assumed to be set up of temporal databases
 * that hold the first tests in a pending state awaiting access to the database.
 * @group medium
 *
 * @group Wikibase
 * @group WikibaseAPI
 *
 * @licence GNU GPL v2+
 * @author John Erling Blad < jeblad@gmail.com >
 */
class ApiWikibaseTests extends ApiTestCase {
	
	protected static $top = 0;

	public function providerSetItem() {
		$idx = ApiWikibaseTests::$top;
		return array(
			array(
				++$idx,
				'{
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
				++$idx,
				'{
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
				++$idx,
				'{
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
				}')
		);
	}
	
	/**
	 * Testing SetItem first as this is central to be able to test the rest of the functions.
	 * We also build the items for the rest of the tests here.
	 * 
	 * @group API
	 * @dataProvider providerSetItem
	 */
	public function testSetItem( $id, $data ) {
		$first = $this->doApiRequest( array(
			'action' => 'wbsetitem',
			'data' => $data,
		) );
		$this->assertArrayHasKey( 'success', $first[0],
			"Must have an 'success' key in the result from the first call to the API" );
		$this->assertArrayHasKey( 'item', $first[0],
			"Must have an 'items' key in the result from the first call to the API" );
		$this->assertArrayHasKey( 'id', $first[0]['item'],
			"Must have an 'id' key in the 'item' result from the first call to the API" );
		$this->assertArrayHasKey( 'sitelinks', $first[0]['item'],
			"Must have an 'sitelinks' key in the 'item' result from the first call to the API" );
		$this->assertArrayHasKey( 'labels', $first[0]['item'],
			"Must have an 'labels' key in the 'item' result from the first call to the API" );
		$this->assertArrayHasKey( 'descriptions', $first[0]['item'],
			"Must have an 'descriptions' key in the 'item' result from the first call to the API" );
		// we should store and reuse but its thrown away on each iteration
		$this->assertEquals( $id, $first[0]['item']['id'],
			"Must have an 'id' key in the 'item' result from the first call to the API that is equal to the expected" );
		//print_r($first);
	}
	
	public function providerGetItemId() {
		$idx = ApiWikibaseTests::$top;
		return array(
			array( ++$idx, 'de', 'Berlin'),
			array( $idx, 'en', 'Berlin'),
			array( $idx, 'no', 'Berlin'),
			array( $idx, 'nn', 'Berlin'),
			array( ++$idx, 'de', 'London'),
			array( $idx, 'en', 'London'),
			array( $idx, 'no', 'London'),
			array( $idx, 'nn', 'London'),
			array( ++$idx, 'de', 'Oslo'),
			array( $idx, 'en', 'Oslo'),
			array( $idx, 'no', 'Oslo'),
			array( $idx, 'nn', 'Oslo'),
		);
	}
	
	/**
	 * Test basic lookup of items to get the id.
	 * This is really a fast lookup without reparsing the stringified item.
	 * 
	 * @group API
	 * @Depends testSetItem
	 * @dataProvider providerGetItemId
	 */
	public function testGetItemId($id, $site, $title) {
		$first = $this->doApiRequest( array(
			'action' => 'wbgetitemid',
			'site' => $site,
			'title' => $title,
		) );
		$this->assertArrayHasKey( 'success', $first[0],
			"Must have an 'success' key in the result from the API" );
		$this->assertArrayHasKey( 'item', $first[0],
			"Must have an 'item' key in the result from the API" );
		$this->assertArrayHasKey( 'id', $first[0]['item'],
			"Must have an 'id' key in the 'item' result from the API" );
		$this->assertEquals( $id, $first[0]['item']['id'],
			"Must have the value '{$id}' for the 'id' in the result from the API" );
	}
	
	public function providerGetItems() {
		$idx = ApiWikibaseTests::$top;
		return array(
			array( ++$idx ),
			array( ++$idx ),
			array( ++$idx ),
		);
	}
	
	/**
	 * Testing if we can get individual complete stringified items if we do lookup with single ids.
	 * Note that this makes assumptions about which ids they have been assigned.
	 * 
	 * @group API
	 * @dataProvider providerGetItems
	 * @Depends testSetItem
	 */
	public function testGetItems( $id ) {
		$first = $this->doApiRequest( array(
			'action' => 'wbgetitems',
			'ids' => "{$id}",
		) );
		$this->assertArrayHasKey( 'success', $first[0],
			"Must have an 'success' key in the result from the API" );
		$this->assertArrayHasKey( 'items', $first[0],
			"Must have an 'items' key in the result from the API" );
		$this->assertArrayHasKey( "{$id}", $first[0]['items'],
			"Must have an '{$id}' key in the 'items' result from the API" );
		$this->assertArrayHasKey( 'id', $first[0]['items']["{$id}"],
			"Must have an 'id' key in the '{$id}' result from the API" );
		$this->assertArrayHasKey( 'sitelinks', $first[0]['items']["{$id}"],
			"Must have an 'sitelinks' key in the '{$id}' result from the API" );
		$this->assertArrayHasKey( 'labels', $first[0]['items']["{$id}"],
			"Must have an 'labels' key in the '{$id}' result from the API" );
		$this->assertArrayHasKey( 'descriptions', $first[0]['items']["{$id}"],
			"Must have an 'descriptions' key in the '{$id}' result from the API" );
	}
		
	/**
	 * Testing if we can get all the complete stringified items if we do lookup with multiple ids.
	 * 
	 * @group API
	 * @Depends testSetItem
	 */
	public function testGetItemsMultiple() {
		$first = $this->doApiRequest( array(
			'action' => 'wbgetitems',
			'ids' => '1|2|3'
		) );
		$this->assertArrayHasKey( 'success', $first[0],
			"Must have an 'success' key in the result from the API" );
		$this->assertArrayHasKey( 'items', $first[0],
			"Must have an 'items' key in the result from the API" );
		$this->assertCount( 3, $first[0]['items'],
			"Must have a number of count of 3 in the 'items' result from the API" );
	}
	
	/**
	 * Testing if we can get individual complete stringified items if we do lookup with site-title pairs
	 * Note that this makes assumptions about which ids they have been assigned.
	 * 
	 * @group API
	 * @dataProvider providerGetItemId
	 * @Depends testSetItem
	 */
	public function testGetItemsSiteTitle($id, $site, $title) {
		$first = $this->doApiRequest( array(
			'action' => 'wbgetitems',
			'sites' => $site,
			'titles' => $title,
		) );
		$this->assertArrayHasKey( 'success', $first[0],
			"Must have an 'success' key in the result from the API" );
		$this->assertArrayHasKey( 'items', $first[0],
			"Must have an 'items' key in the result from the API" );
		$this->assertArrayHasKey( "{$id}", $first[0]['items'],
			"Must have an '{$id}' key in the 'items' result from the API" );
		$this->assertArrayHasKey( 'id', $first[0]['items']["{$id}"],
			"Must have an 'id' key in the '{$id}' result from the API" );
		$this->assertArrayHasKey( 'sitelinks', $first[0]['items']["{$id}"],
			"Must have an 'sitelinks' key in the '{$id}' result from the API" );
		$this->assertArrayHasKey( 'labels', $first[0]['items']["{$id}"],
			"Must have an 'labels' key in the '{$id}' result from the API" );
		$this->assertArrayHasKey( 'descriptions', $first[0]['items']["{$id}"],
			"Must have an 'descriptions' key in the '{$id}' result from the API" );
	}
	
	public function providerLinkSiteId() {
		$idx = ApiWikibaseTests::$top;
		return array(
			array( ++$idx, 'nn', 'Berlin', 'fi', 'Berlin', 1 ),
			array( ++$idx, 'en', 'London', 'fi', 'London', 2 ),
			array( ++$idx, 'no', 'Oslo', 'fi', 'Oslo', 3 ),
		);
	}
		
	/**
	 * This tests are entering links to sites by giving 'id' for the fiorst lookup, then setting 'linksite' and 'linktitle'.
	 * In these cases the ids returned should also match up with the ids from the provider.
	 * Note that we must use a new provider to avoid having multiple links to the same external page.
	 * 
	 * @group API
	 * @dataProvider providerLinkSiteId
	 */
	public function testLinkSiteIdAdd( $id, $site, $title, $linksite, $linktitle, $badge ) {
		$this->linkSiteId( $id, $site, $title, $linksite, $linktitle, $badge, 'add' );
	}

	/**
	 * @group API
	 * @dataProvider providerLinkSiteId
	 * @Depends testSetItem
	 */
	public function testLinkSiteIdUpdate( $id, $site, $title, $linksite, $linktitle, $badge ) {
		$this->linkSiteId( $id, $site, $title, $linksite, $linktitle, $badge, 'update' );
	}

	/**
	 * @group API
	 * @dataProvider providerLinkSiteId
	 * @Depends testSetItem
	 */
	public function testLinkSiteIdSet( $id, $site, $title, $linksite, $linktitle, $badge ) {
		$this->linkSiteId( $id, $site, $title, $linksite, $linktitle, $badge, 'set' );
	}

	public function linkSiteId( $id, $site, $title, $linksite, $linktitle, $badge, $op ) {
		$first = $this->doApiRequest( array(
			'action' => 'wblinksite',
			'id' => $id,
			'linksite' => $linksite,
			'linktitle' => $linktitle,
			'link' => $op, // this is an odd name
		) );
		
		$this->assertArrayHasKey( 'success', $first[0],
			"Must have an 'success' key in the result from the first call to the API" );
		$this->assertArrayHasKey( 'item', $first[0],
			"Must have an 'item' key in the result from the first call to the API" );
		$this->assertArrayHasKey( 'id', $first[0]['item'],
			"Must have an 'id' key in the 'item' result from the first call to the API" );
		$this->assertEquals( $id, $first[0]['item']['id'],
			"Must have the value '{$id}' for the 'id' in the result from the first call to the API" );
		
		$second = $this->doApiRequest( array(
			'action' => 'wbgetitems',
			'sites' => $linksite,
			'titles' => $linktitle,
		) );
		
		$this->assertArrayHasKey( 'success', $second[0],
			"Must have an 'success' key in the result from the second call to the API" );
		$this->assertArrayHasKey( 'items', $second[0],
			"Must have an 'items' key in the result from the second call to the API" );
		$this->assertCount( 1, $second[0]['items'],
			"Must have a number of count of 1 in the 'items' result from the second call to the API" );
		$this->assertArrayHasKey( "{$id}", $second[0]['items'],
			"Must have an '{$id}' key in the 'items' result from the second call to the API" );
		$this->assertArrayHasKey( 'id', $second[0]['items']["{$id}"],
			"Must have an 'id' key in the '{$id}' result from the second call to the API" );
		$this->assertEquals( $id, $second[0]['items']["{$id}"]['id'],
			"Must have the value '{$id}' for the 'id' in the result from the second call to the API" );
		
		$third = $this->doApiRequest( array(
			'action' => 'wbgetitems',
			'sites' => $site,
			'titles' => $title,
		) );
		$this->assertArrayHasKey( 'success', $third[0],
			"Must have an 'success' key in the result from the third call to the API" );
		$this->assertArrayHasKey( 'items', $third[0],
			"Must have an 'items' key in the result from the third call to the API" );
		$this->assertCount( 1, $third[0]['items'],
			"Must have a number of count of 1 in the 'items' result from the third call to the API" );
		$this->assertArrayHasKey( "{$id}", $third[0]['items'],
			"Must have an '{$id}' key in the 'items' result from the third call to the API" );
		$this->assertArrayHasKey( 'id', $third[0]['items']["{$id}"],
			"Must have an 'id' key in the '{$id}' result from the third call to the API" );
		$this->assertEquals( $id, $third[0]['items']["{$id}"]['id'],
			"Must have the value '{$id}' for the 'id' in the result from the third call to the API" );
	}

	public function providerLinkSitePair() {
		$idx = ApiWikibaseTests::$top;
		return array(
			array( ++$idx, 'nn', 'Berlin', 'sv', 'Berlin', 1 ),
			array( ++$idx, 'en', 'London', 'sv', 'London', 2 ),
			array( ++$idx, 'no', 'Oslo', 'sv', 'Oslo', 3 ),
		);
	}
		
	/**
	 * This tests are entering links to sites by giving 'site' and 'title' pairs instead of id, then setting 'linksite' and 'linktitle'.
	 * In these cases the ids returned should also match up with the ids from the provider.
	 * Note that we must use a new provider to avoid having multiple links to the same external page.
	 * 
	 * @group API
	 * @dataProvider providerLinkSitePair
	 * @Depends testSetItem
	 */
	public function testLinkSitePairAdd( $id, $site, $title, $linksite, $linktitle, $badge ) {
		$this->linkSitePair( $id, $site, $title, $linksite, $linktitle, $badge, 'add' );
	}

	/**
	 * @group API
	 * @dataProvider providerLinkSitePair
	 * @Depends testSetItem
	 */
	public function testLinkSitePairUpdate( $id, $site, $title, $linksite, $linktitle, $badge ) {
		$this->linkSitePair( $id, $site, $title, $linksite, $linktitle, $badge, 'update' );
	}

	/**
	 * @group API
	 * @dataProvider providerLinkSitePair
	 * @Depends testSetItem
	 */
	public function testLinkSitePairSet( $id, $site, $title, $linksite, $linktitle, $badge ) {
		$this->linkSitePair( $id, $site, $title, $linksite, $linktitle, $badge, 'set' );
	}

	public function linkSitePair( $id, $site, $title, $linksite, $linktitle, $badge, $op ) {
		$first = $this->doApiRequest( array(
			'action' => 'wblinksite',
			'site' => $site,
			'title' => $title,
			'linksite' => $linksite,
			'linktitle' => $linktitle,
			'badge' => $badge,
			'link' => $op,
		) );
		
		$this->assertArrayHasKey( 'success', $first[0],
			"Must have an 'success' key in the result from the first call to the API" );
		$this->assertArrayHasKey( 'item', $first[0],
			"Must have an 'item' key in the result from the first call to the API" );
		$this->assertArrayHasKey( 'id', $first[0]['item'],
			"Must have an 'id' key in the 'item' result from the first call to the API" );
		$this->assertEquals( $id, $first[0]['item']['id'],
			"Must have the value '{$id}' for the 'id' in the result from the first call to the API" );
		
		// now check if we can find them by their new site-title pairs
		$second = $this->doApiRequest( array(
			'action' => 'wbgetitems',
			'sites' => $linksite,
			'titles' => $linktitle,
		) );
		
		$this->assertArrayHasKey( 'success', $second[0],
			"Must have an 'success' key in the result from the second call to the API" );
		$this->assertArrayHasKey( 'items', $second[0],
			"Must have an 'items' key in the result from the second call to the API" );
		$this->assertCount( 1, $second[0]['items'],
			"Must have a number of count of 1 in the 'items' result from the second call to the API" );
		$this->assertArrayHasKey( "{$id}", $second[0]['items'],
			"Must have an '{$id}' key in the 'items' result from the second call to the API" );
		$this->assertArrayHasKey( 'id', $second[0]['items']["{$id}"],
			"Must have an 'id' key in the '{$id}' result from the second call to the API" );
		$this->assertEquals( $id, $second[0]['items']["{$id}"]['id'],
			"Must have the value '{$id}' for the 'id' in the result from the second call to the API" );
		
		// now check if we can find them by their old site-title pairs
		// that is they should not have lost teir old pairs
		$third = $this->doApiRequest( array(
			'action' => 'wbgetitems',
			'sites' => $linksite,
			'titles' => $linktitle,
		) );
		
		$this->assertArrayHasKey( 'success', $third[0],
			"Must have an 'success' key in the result from the second call to the API" );
		$this->assertArrayHasKey( 'items', $third[0],
			"Must have an 'items' key in the result from the second call to the API" );
		$this->assertCount( 1, $third[0]['items'],
			"Must have a number of count of 1 in the 'items' result from the second call to the API" );
		$this->assertArrayHasKey( "{$id}", $third[0]['items'],
			"Must have an '{$id}' key in the 'items' result from the second call to the API" );
		$this->assertArrayHasKey( 'id', $third[0]['items']["{$id}"],
			"Must have an 'id' key in the '{$id}' result from the second call to the API" );
		$this->assertEquals( $id, $third[0]['items']["{$id}"]['id'],
			"Must have the value '{$id}' for the 'id' in the result from the second call to the API" );
		
	}

	public function providerLabelDescription() {
		$idx = ApiWikibaseTests::$top;
		return array(
			array( ++$idx, 'nn', 'Berlin', 'da', 'Berlin', 'Hovedstad i Tyskland' ),
			array( ++$idx, 'nn', 'London', 'da', 'London', 'Hovedstad i England' ),
			array( ++$idx, 'nn', 'Oslo', 'da', 'Oslo', 'Hovedstad i Norge' ),
		);
	}
	
	/**
	 * This tests if the site links for the items can be found by using 'id' from the provider.
	 * That is the updating should not have moved them around or deleted old content.
	 * 
	 * @group API
	 * @dataProvider providerLabelDescription
	 * @Depends testSetItem
	 */
	public function testSetLanguageAttributeAdd( $id, $site, $title, $language, $label, $description ) {
		$this->setLanguageAttribute( $id, $site, $title, $language, $label, $description, 'add' );
	}
	
	/**
	 * @group API
	 * @dataProvider providerLabelDescription
	 * @Depends testSetItem
	 */
	public function testSetLanguageAttributeUpdate( $id, $site, $title, $language, $label, $description ) {
		$this->setLanguageAttribute( $id, $site, $title, $language, $label, $description, 'update' );
	}
	
	/**
	 * @group API
	 * @dataProvider providerLabelDescription
	 * @Depends testSetItem
	 */
	public function testSetLanguageAttributeSet( $id, $site, $title, $language, $label, $description ) {
		$this->setLanguageAttribute( $id, $site, $title, $language, $label, $description, 'set' );
	}
	
	public function setLanguageAttribute( $id, $site, $title, $language, $label, $description, $op ) {
		
		$first = $this->doApiRequest( array(
			'action' => 'wbsetlanguageattribute',
			'id' => $id,
			'label' => $label,
			'description' => $description,
			'language' => $language,
			'item' => $op
		) );
		
		$this->assertArrayHasKey( 'success', $first[0],
			"Must have an 'success' key in the result from the API" );
		$this->assertArrayHasKey( 'item', $first[0],
			"Must have an 'item' key in the result from the API" );
		$this->assertArrayHasKey( 'id', $first[0]['item'],
			"Must have an 'id' key in the 'item' result from the API" );
		$this->assertEquals( $id, $first[0]['item']['id'],
			"Must have the value '{$id}' for the 'id' in the result from the API" );
		
		$second = $this->doApiRequest( array(
			'action' => 'wbgetitems',
			'ids' => $id,
			'language' => $language,
		) );
		$this->assertArrayHasKey( 'success', $second[0],
			"Must have an 'success' key in the result in the second call to the API" );
		$this->assertArrayHasKey( 'items', $second[0],
			"Must have an 'items' key in the result in the second call to the API" );
		$this->assertCount( 1, $second[0]['items'],
			"Must have a number of count of 1 in the 'items' result in the second call to the API" );
		$this->assertArrayHasKey( "{$id}", $second[0]['items'],
			"Must have an '{$id}' key in the 'items' result in the second call to the API" );
		$this->assertArrayHasKey( 'id', $second[0]['items']["{$id}"],
			"Must have an 'id' key in the '{$id}' result in the second call to the API" );
		$this->assertEquals( $id, $second[0]['items']["{$id}"]['id'],
			"Must have the value '{$id}' for the 'id' in the result in the second call to the API" );
		
		$this->assertArrayHasKey( $language, $second[0]['items']["{$id}"]['labels'],
			"Must have an '{$language}' key in the 'labels' second result in the second call to the API" );
		$this->assertEquals( $label, $second[0]['items']["{$id}"]['labels'][$language],
			"Must have the value '{$label}' for the '{$language}' in the 'labels' set in the result in the second call to the API" );
		
		$this->assertArrayHasKey( $language, $second[0]['items']["{$id}"]['descriptions'],
			"Must have an '{$language}' key in the 'descriptions' result in the second to the API" );
		$this->assertEquals( $description, $second[0]['items']["{$id}"]['descriptions'][$language],
			"Must have the value '{$description}' for the '{$language}' in the 'descriptions' set in the result in the second call to the API" );
		
	}
	
	public function providerRemoveLabelDescription() {
		$idx = ApiWikibaseTests::$top;
		return array(
			array( ++$idx, 'nn', 'Berlin', 'da' ),
			array( ++$idx, 'nn', 'London', 'da' ),
			array( ++$idx, 'nn', 'Oslo', 'da' ),
		);
	}
	
	/**
	 * This tests if the site links for the items can be found by using 'id' from the provider.
	 * That is the updating should not have moved them around or deleted old content.
	 * 
	 * @group API
	 * @dataProvider providerRemoveLabelDescription
	 * @Depends testSetItem
	 */
	public function testDeleteLanguageAttributeLabel( $id, $site, $title, $language ) {
		$this->deleteLanguageAttribute( $id, $site, $title, $language, 'label' );
	}
	
	/**
	 * @group API
	 * @group Broken
	 * @dataProvider providerRemoveLabelDescription
	 * @Depends testSetItem
	 */
	public function testDeleteLanguageAttributeDescription( $id, $site, $title, $language ) {
		// FIXME: $attribute is not set!
		$this->deleteLanguageAttribute( $id, $site, $title, $language, $attribute, 'description' );
	}
	
	public function deleteLanguageAttribute( $id, $site, $title, $language, $op ) {
		
		$first = $this->doApiRequest( array(
			'action' => 'wbdeletelanguageattribute',
			'id' => $id,
			'language' => $language,
			'attribute' => $op,
			//'item' => $op
		) );
		
		$this->assertArrayHasKey( 'success', $first[0],
			"Must have an 'success' key in the result from the API" );
		$this->assertArrayHasKey( 'item', $first[0],
			"Must have an 'item' key in the result from the API" );
		$this->assertArrayHasKey( 'id', $first[0]['item'],
			"Must have an 'id' key in the 'item' result from the API" );
		$this->assertEquals( $id, $first[0]['item']['id'],
			"Must have the value '{$id}' for the 'id' in the result from the API" );
		
		$second = $this->doApiRequest( array(
			'action' => 'wbgetitems',
			'ids' => $id,
			'language' => $language,
		) );
		
		$this->assertArrayHasKey( 'success', $second[0],
			"Must have an 'success' key in the result in the second call to the API" );
		$this->assertArrayHasKey( 'items', $second[0],
			"Must have an 'items' key in the result in the second call to the API" );
		$this->assertCount( 1, $second[0]['items'],
			"Must have a number of count of 1 in the 'items' result in the second call to the API" );
		$this->assertArrayHasKey( "{$id}", $second[0]['items'],
			"Must have an '{$id}' key in the 'items' result in the second call to the API" );
		$this->assertArrayHasKey( 'id', $second[0]['items']["{$id}"],
			"Must have an 'id' key in the '{$id}' result in the second call to the API" );
		$this->assertEquals( $id, $second[0]['items']["{$id}"]['id'],
			"Must have the value '{$id}' for the 'id' in the result in the second call to the API" );
		
		if ( isset($second[0]['items']["{$id}"][$op][$language]) ) {
			$this->fail( "Must not have an '{$language}' key in the 'labels' result in the second call to the API" );
		}
	}
	
	/**
	 * TODO: Implement this
	 * Check that we have the help link
	 * @group ApiHelp
	 * @group Broken
	 */
	public function testGetHelpUrls() {
		
		$first = $this->doApiRequest( array(
			'action' => 'help',
			'modules' => 'wbgetitemid',
		) );
		
		return;
		print_r($first);
		return;
		$this->assertArrayHasKey( 'success', $first[0],
			"Must have an 'success' key in the result in the second call to the API" );
		
		return;
		
		$this->assertIsInternal( 'array', $first,
			"Must be an array as the main structure" );
		$this->assertInternalType(
			'string',
			$first,
			'Checking getHelpUrls for a valid string.'
		);
		$this->assertRegExp(
			'/^(http|https):/i',
			$first,
			'Checking getHelpUrls for a valid protocol.'
		);
		$this->assertRegExp(
			'/\/\/[^\.]+\.[^\.]+\.[^\.]+\//i',
			$first,
			'Checking getHelpUrls for something that looks vaguely like a domain.'
		);
	}
}
	
