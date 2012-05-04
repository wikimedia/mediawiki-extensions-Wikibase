<?php

/**
 * Tests for the ApiWikibase class.
 * 
 * This testset only checks the validity of the calls and correct handling of tokens and users.
 * Note that we creates an empty database and then starts manipulating testusers.
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
 * @group API
 * 
 * The database group has as a side effect that temporal database tables are created. This makes
 * it possible to test without poisoning a production database.
 * @group Database
 * 
 * Some of the tests takes more time, and needs therefor longer time before they can be aborted
 * as non-functional. The reason why tests are aborted is assumed to be set up of temporal databases
 * that hold the first tests in a pending state awaiting access to the database.
 * @group medium
 */
class ApiWikibaseSetItemTests extends ApiTestCase {
	
	protected static $top = 0;
	
	function setUp() {
		global $wgUser;
		parent::setUp();
		
		ApiTestCase::$users['wbeditor'] = new ApiTestUser(
				'Apitesteditor',
				'Api Test Editor',
				'api_test_editor@example.com',
				array( 'wbeditor' )
			);
		$wgUser = self::$users['wbeditor']->user;
		
		// now we have to do the login with the previous user
		$data = $this->doApiRequest( array(
			'action' => 'login',
			'lgname' => self::$users['wbeditor']->username,
			'lgpassword' => self::$users['wbeditor']->password ) );

		$token = $data[0]['login']['token'];

		$data = $this->doApiRequest( array(
			'action' => 'login',
			'lgtoken' => $token,
			'lgname' => self::$users['wbeditor']->username,
			'lgpassword' => self::$users['wbeditor']->password
			),
			$data );
	}

	function getTokens() {
		return $this->getTokenList( self::$users['sysop'] );
	}
	
	/**
	 * @group API
	 * @dataProvider provideSetItemIdDataOp
	 */
	function testSetItemGetToken( $id, $op, $data ) {
		$data = $this->doApiRequest(
			array(
				'action' => 'wbsetitem',
				'gettoken' => '' ),
			null,
			false,
			self::$users['wbeditor']->user
		);
		$this->assertEquals( 34, strlen( $data[0]["wbsetitem"]["setitemtoken"] ) );
	}

	/**
	 * Attempting to set item without a token should give a UsageException with
	 * error message:
	 *   "The token parameter must be set"
	 *
	 * @group API
	 * @dataProvider provideSetItemIdDataOp
	 * @expectedException UsageException
	 */
	function testSetItemWithNoToken( $id, $op, $data ) {
		$this->doApiRequest(
			array(
				'action' => 'wbsetitem',
				'reason' => 'Some reason',
				'data' => $data
				),
			null,
			false,
			self::$users['wbeditor']->user
		);
	}

	/**
	 * @group API
	 * @depends testSetItemGetToken
	 * @depends testSetItemWithNoToken
	 * @dataProvider provideSetItemIdDataOp
	 */
	function testSetItemGetTokenSetData( $id, $op, $data ) {
		$req = array();
		if (WBSettings::get( 'apiInDebug' ) ? WBSettings::get( 'apiDebugWithTokens', false ) : true) {
			$first = $this->doApiRequest(
				array(
					'action' => 'wbsetitem',
					'gettoken' => '' ),
				null,
				false,
				self::$users['wbeditor']->user
			);
			
			$req['token'] = $first[0]['wbsetitem']['setitemtoken'];
		}
		
		$req = array_merge( $req, array(
				'action' => 'wbsetitem',
				'summary' => 'Some reason',
				'data' => $data,
				'item' => $op ) );
		
		$second = $this->doApiRequest( $req, null, false, self::$users['wbeditor']->user );
		$this->assertArrayHasKey( 'success', $second[0],
			"Must have an 'success' key in the second result from the API" );
		$this->assertArrayHasKey( 'item', $second[0],
			"Must have an 'item' key in the second result from the API" );
		$this->assertArrayHasKey( 'id', $second[0]['item'],
			"Must have an 'id' key in the 'item' from the second result from the API" );
		$this->assertArrayHasKey( 'sitelinks', $second[0]['item'],
			"Must have an 'sitelinks' key in the 'item' result from the second call to the API" );
		$this->assertArrayHasKey( 'labels', $second[0]['item'],
			"Must have an 'labels' key in the 'item' result from the second call to the API" );
		$this->assertArrayHasKey( 'descriptions', $second[0]['item'],
			"Must have an 'descriptions' key in the 'item' result from the second call to the API" );
		// we should store and reuse but its thrown away on each iteration
		$this->assertEquals( $id, $second[0]['item']['id'],
			"Must have an 'id' key in the 'item' result from the second call to the API that is equal to the expected" );
	}
	
	/**
	 * Test basic lookup of items to get the id.
	 * This is really a fast lookup without reparsing the stringified item.
	 * 
	 * @group API
	 * @Depends testSetItemGetTokenSetData
	 * @dataProvider providerGetItemId
	 */
	public function testGetItemId( $id, $site, $title ) {
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
	
	/**
	 * Testing if we can get individual complete stringified items if we do lookup with single ids.
	 * Note that this makes assumptions about which ids they have been assigned.
	 * 
	 * @group API
	 * @dataProvider provideSetItemIdDataOp
	 * @Depends testSetItemGetTokenSetData
	 */
	public function testGetItems( $id, $op, $data ) {
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
	 * @Depends testSetItemGetTokenSetData
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
	 * @Depends testSetItemGetTokenSetData
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
	
	/**
	 * This tests are entering links to sites by giving 'id' for the fiorst lookup, then setting 'linksite' and 'linktitle'.
	 * In these cases the ids returned should also match up with the ids from the provider.
	 * Note that we must use a new provider to avoid having multiple links to the same external page.
	 * 
	 * @group API
	 * @dataProvider providerLinkSiteId
	 * @Depends testSetItemGetTokenSetData
	 */
	public function testLinkSiteIdAdd( $id, $site, $title, $linksite, $linktitle, $badge ) {
		$this->linkSiteId( $id, $site, $title, $linksite, $linktitle, $badge, 'add' );
	}

	/**
	 * @group API
	 * @dataProvider providerLinkSiteId
	 * @Depends testSetItemGetTokenSetData
	 */
	public function testLinkSiteIdUpdate( $id, $site, $title, $linksite, $linktitle, $badge ) {
		$this->linkSiteId( $id, $site, $title, $linksite, $linktitle, $badge, 'update' );
	}

	/**
	 * @group API
	 * @dataProvider providerLinkSiteId
	 * @Depends testSetItemGetTokenSetData
	 */
	public function testLinkSiteIdSet( $id, $site, $title, $linksite, $linktitle, $badge ) {
		$this->linkSiteId( $id, $site, $title, $linksite, $linktitle, $badge, 'set' );
	}

	public function linkSiteId( $id, $site, $title, $linksite, $linktitle, $badge, $op ) {
		$req = array();
		if (WBSettings::get( 'apiInDebug' ) ? WBSettings::get( 'apiDebugWithTokens', false ) : true) {
			$data = $this->doApiRequest(
				array(
					'action' => 'wbsetitem',
					'gettoken' => '' ),
				null,
				false,
				self::$users['wbeditor']->user
			);
			//print_r($data[0]);
			$req['token'] = $data[0]['wbsetitem']['setitemtoken'];
		}
		
		$req = array_merge( $req, array(
			'action' => 'wblinksite',
			'id' => $id,
			'linksite' => $linksite,
			'linktitle' => $linktitle,
			'link' => $op, // this is an odd name
		) );
		
		$first = $this->doApiRequest( $req, null, false, self::$users['wbeditor']->user );
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

	/**
	 * This tests are entering links to sites by giving 'site' and 'title' pairs instead of id, then setting 'linksite' and 'linktitle'.
	 * In these cases the ids returned should also match up with the ids from the provider.
	 * Note that we must use a new provider to avoid having multiple links to the same external page.
	 * 
	 * @group API
	 * @dataProvider providerLinkSitePair
	 * @Depends testSetItemGetTokenSetData
	 */
	public function testLinkSitePairAdd( $id, $site, $title, $linksite, $linktitle, $badge ) {
		$this->linkSitePair( $id, $site, $title, $linksite, $linktitle, $badge, 'add' );
	}

	/**
	 * @group API
	 * @dataProvider providerLinkSitePair
	 * @Depends testSetItemGetTokenSetData
	 */
	public function testLinkSitePairUpdate( $id, $site, $title, $linksite, $linktitle, $badge ) {
		$this->linkSitePair( $id, $site, $title, $linksite, $linktitle, $badge, 'update' );
	}

	/**
	 * @group API
	 * @dataProvider providerLinkSitePair
	 * @Depends testSetItemGetTokenSetData
	 */
	public function testLinkSitePairSet( $id, $site, $title, $linksite, $linktitle, $badge ) {
		$this->linkSitePair( $id, $site, $title, $linksite, $linktitle, $badge, 'set' );
	}

	public function linkSitePair( $id, $site, $title, $linksite, $linktitle, $badge, $op ) {
		$req = array();
		if (WBSettings::get( 'apiInDebug' ) ? WBSettings::get( 'apiDebugWithTokens', false ) : true) {
			$data = $this->doApiRequest(
				array(
					'action' => 'wbsetitem',
					'gettoken' => '' ),
				null,
				false,
				self::$users['wbeditor']->user
			);
			//print_r($data[0]);
			$req['token'] = $data[0]['wbsetitem']['setitemtoken'];
		}
		
		$req = array_merge( $req, array(
			'action' => 'wblinksite',
			'site' => $site,
			'title' => $title,
			'linksite' => $linksite,
			'linktitle' => $linktitle,
			'badge' => $badge,
			'link' => $op, // this is an odd name
		) );
		
		$first = $this->doApiRequest( $req, null, false, self::$users['wbeditor']->user );
		
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

	/**
	 * This tests if the site links for the items can be found by using 'id' from the provider.
	 * That is the updating should not have moved them around or deleted old content.
	 * 
	 * @group API
	 * @dataProvider providerLabelDescription
	 * @Depends testSetItemGetTokenSetData
	 */
	public function testSetLanguageAttributeAdd( $id, $site, $title, $language, $label, $description ) {
		$this->setLanguageAttribute( $id, $site, $title, $language, $label, $description, 'add' );
	}
	
	/**
	 * @group API
	 * @dataProvider providerLabelDescription
	 * @Depends testSetItemGetTokenSetData
	 */
	public function testSetLanguageAttributeUpdate( $id, $site, $title, $language, $label, $description ) {
		$this->setLanguageAttribute( $id, $site, $title, $language, $label, $description, 'update' );
	}
	
	/**
	 * @group API
	 * @dataProvider providerLabelDescription
	 * @Depends testSetItemGetTokenSetData
	 */
	public function testSetLanguageAttributeSet( $id, $site, $title, $language, $label, $description ) {
		$this->setLanguageAttribute( $id, $site, $title, $language, $label, $description, 'set' );
	}
	
	public function setLanguageAttribute( $id, $site, $title, $language, $label, $description, $op ) {
		
		$req = array();
		if (WBSettings::get( 'apiInDebug' ) ? WBSettings::get( 'apiDebugWithTokens', false ) : true) {
			$data = $this->doApiRequest(
				array(
					'action' => 'wbsetitem',
					'gettoken' => '' ),
				null,
				false,
				self::$users['wbeditor']->user
			);
			//print_r($data[0]);
			$req['token'] = $data[0]['wbsetitem']['setitemtoken'];
		}
		
		$req = array_merge( $req, array(
			'action' => 'wbsetlanguageattribute',
			'id' => $id,
			'label' => $label,
			'description' => $description,
			'language' => $language,
			'item' => $op
		) );
		
		$first = $this->doApiRequest( $req, null, false, self::$users['wbeditor']->user );
		
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
	
	/**
	 * This tests if the site links for the items can be found by using 'id' from the provider.
	 * That is the updating should not have moved them around or deleted old content.
	 * 
	 * @group API
	 * @dataProvider providerRemoveLabelDescription
	 * @Depends testSetItemGetTokenSetData
	 */
	public function testDeleteLanguageAttributeLabel( $id, $site, $title, $language ) {
		$this->deleteLanguageAttribute( $id, $site, $title, $language, 'label' );
	}
	
	/**
	 * @group API
	 * @dataProvider providerRemoveLabelDescription
	 * @Depends testSetItemGetTokenSetData
	 */
	public function testDeleteLanguageAttributeDescription( $id, $site, $title, $language ) {
		$this->deleteLanguageAttribute( $id, $site, $title, $language, 'description' );
	}
	
	public function deleteLanguageAttribute( $id, $site, $title, $language, $op ) {
		
		$req = array();
		if (WBSettings::get( 'apiInDebug' ) ? WBSettings::get( 'apiDebugWithTokens', false ) : true) {
			$data = $this->doApiRequest(
				array(
					'action' => 'wbsetitem',
					'gettoken' => '' ),
				null,
				false,
				self::$users['wbeditor']->user
			);
			//print_r($data[0]);
			$req['token'] = $data[0]['wbsetitem']['setitemtoken'];
		}
		
		$req = array_merge( $req, array(
			'action' => 'wbdeletelanguageattribute',
			'id' => $id,
			'language' => $language,
			'attribute' => $op,
		) );
		
		$first = $this->doApiRequest( $req, null, false, self::$users['wbeditor']->user );
		
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
	 * Just provide the actions to test the API calls
	 */
	function provideSetItemIdDataOp() {
		$idx = self::$top;
		return array(
			array(
				++$idx,
				'add',
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
				}'
			),
			array(
				++$idx,
				'add',
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
				}'
			),
			array(
				++$idx,
				'add',
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
				}'
			),
		);
	}
	
	public function providerGetItemId() {
		$idx = self::$top;
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
	
	public function providerLinkSiteId() {
		$idx = self::$top;
		return array(
			array( ++$idx, 'nn', 'Berlin', 'fi', 'Berlin', 1 ),
			array( ++$idx, 'en', 'London', 'fi', 'London', 2 ),
			array( ++$idx, 'no', 'Oslo', 'fi', 'Oslo', 3 ),
		);
	}
	
	public function providerLinkSitePair() {
		$idx = self::$top;
		return array(
			array( ++$idx, 'nn', 'Berlin', 'sv', 'Berlin', 1 ),
			array( ++$idx, 'en', 'London', 'sv', 'London', 2 ),
			array( ++$idx, 'no', 'Oslo', 'sv', 'Oslo', 3 ),
		);
	}
	
	public function providerLabelDescription() {
		$idx = self::$top;
		return array(
			array( ++$idx, 'nn', 'Berlin', 'da', 'Berlin', 'Hovedstad i Tyskland' ),
			array( ++$idx, 'nn', 'London', 'da', 'London', 'Hovedstad i England' ),
			array( ++$idx, 'nn', 'Oslo', 'da', 'Oslo', 'Hovedstad i Norge' ),
		);
	}
	
	public function providerRemoveLabelDescription() {
		$idx = self::$top;
		return array(
			array( ++$idx, 'nn', 'Berlin', 'de' ),
			array( ++$idx, 'nn', 'London', 'de' ),
			array( ++$idx, 'nn', 'Oslo', 'de' ),
		);
	}
	
	
}
