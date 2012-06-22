<?php

namespace Wikibase\Test;
use ApiTestCase, ApiTestUser;
use Wikibase\Settings as Settings;

/**
 * Tests for the ApiWikibase class.
 * 
 * This testset only checks the validity of the calls and correct handling of tokens and users.
 * Note that we creates an empty database and then starts manipulating testusers.
 *
 * BE WARNED: the tests depend on execution order of the methods and the methods are interdependent,
 * so stuff will fail if you move methods around or make changes to them without matching changes in
 * all methods depending on them.
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
 * @group Wikibase
 * @group WikibaseAPI
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
class ApiSetItemTest extends \ApiTestCase {
	
	protected static $baseOfItemIds = 0;
	protected static $usepost;
	protected static $usetoken;
	protected static $userights;
	
	function setUp() {
		global $wgUser;
		parent::setUp();

		\Wikibase\Utils::insertDefaultSites();
		
		self::$usepost = Settings::get( 'apiInDebug' ) ? Settings::get( 'apiDebugWithPost' ) : true;
		self::$usetoken = Settings::get( 'apiInDebug' ) ? Settings::get( 'apiDebugWithTokens' ) : true;
		self::$userights = Settings::get( 'apiInDebug' ) ? Settings::get( 'apiDebugWithRights' ) : true;
		
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

		$this->doApiRequest( array(
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
	 * This initiates a cascade that fails if there are no
	 * production-like environment
	 * @group API
	 */
	function testTokensAndRights() {
		// check if there is a production-like environment available
		if (!self::$usetoken || !self::$userights) {
			$this->markTestSkipped(
				"The current setup does not include use of tokens or user rights"
			);
		}
		// make sure execution pass through at least one assertion
		else {
			$this->assertTrue( true, "Make phpunit happy" );
		}
	}
	
	/**
	 * @group API
	 * @depends testTokensAndRights
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
		// this should always hold for a logged in user
		// unless we do some additional tricks with the token
		$this->assertEquals(
			34, strlen( $data[0]["wbsetitem"]["setitemtoken"] ),
			"The length of the token is not 34 chars"
		);
		$this->assertRegExp(
			'/\+\\\\$/', $data[0]["wbsetitem"]["setitemtoken"],
			"The final chars of the token is not '+\\'"
		);
	}

	/**
	 * Attempting to set item without a token should give a UsageException with
	 * error message:
	 *   "The token parameter must be set"
	 *
	 * @group API
	 * @depends testSetItemGetToken
	 * @dataProvider provideSetItemIdDataOp
	 */
	function testSetItemWithNoToken( $id, $op, $data ) {
		try {
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
		catch (\UsageException $e) {
			$this->assertTrue( ($e->getCode() == 'session-failure'), "Got a wrong exception" );
			return;
		}
		$this->assertTrue( (self::$usetoken), "Missing an exception" );
	}

	/**
	 * @group API
	 * @depends testSetItemGetToken
	 * @depends testSetItemWithNoToken
	 */
	function testSetItemTop() {
		$req = array();
		if ( Settings::get( 'apiInDebug' ) ? Settings::get( 'apiDebugWithTokens', false ) : true ) {
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
				'data' => '{}',
				'item' => 'add' ) );
		
		$second = $this->doApiRequest( $req, null, false, self::$users['wbeditor']->user );
		$this->assertArrayHasKey( 'success', $second[0],
			"Must have an 'success' key in the second result from the API" );
		$this->assertArrayHasKey( 'item', $second[0],
			"Must have an 'item' key in the second result from the API" );
		$this->assertArrayHasKey( 'id', $second[0]['item'],
			"Must have an 'id' key in the 'item' from the second result from the API" );
		self::$baseOfItemIds = $second[0]['item']['id'];
	}
	
	/**
	 * @group API
	 * @depends testSetItemTop
	 * @dataProvider provideSetItemIdDataOp
	 */
	function testSetItemGetTokenSetData( $id, $op, $data ) {
		$req = array();
		if ( Settings::get( 'apiInDebug' ) ? Settings::get( 'apiDebugWithTokens', false ) : true ) {
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
		$myid =  self::$baseOfItemIds + $id;
		$this->assertEquals( $myid, $second[0]['item']['id'],
			"Must have the value '{$myid}' for the 'id' in the result from the API" );
	}
	
	/**
	 * Test basic lookup of items to get the id.
	 * This is really a fast lookup without reparsing the stringified item.
	 * 
	 * @group API
	 * @depends testSetItemGetTokenSetData
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
		$myid =  self::$baseOfItemIds + $id;
		$this->assertEquals( $myid, $first[0]['item']['id'],
			"Must have the value '{$myid}' for the 'id' in the result from the API" );
	}
	
	/**
	 * Testing if we can get individual complete stringified items if we do lookup with single ids.
	 * Note that this makes assumptions about which ids they have been assigned.
	 * 
	 * @group API
	 * @dataProvider provideSetItemIdDataOp
	 * @depends testSetItemGetTokenSetData
	 */
	public function testGetItems( $id, $op, $data ) {
		$myid =  self::$baseOfItemIds + $id;
		$first = $this->doApiRequest( array(
			'action' => 'wbgetitems',
			'ids' => "{$myid}",
		) );
		
		$this->assertArrayHasKey( 'success', $first[0],
			"Must have an 'success' key in the result from the API" );
		$this->assertArrayHasKey( 'items', $first[0],
			"Must have an 'items' key in the result from the API" );
		$this->assertArrayHasKey( "{$myid}", $first[0]['items'],
			"Must have an '{$myid}' key in the 'items' result from the API" );
		$this->assertArrayHasKey( 'id', $first[0]['items']["{$myid}"],
			"Must have an 'id' key in the '{$myid}' result from the API" );
		$this->assertArrayHasKey( 'sitelinks', $first[0]['items']["{$myid}"],
			"Must have an 'sitelinks' key in the '{$myid}' result from the API" );
		$this->assertArrayHasKey( 'labels', $first[0]['items']["{$myid}"],
			"Must have an 'labels' key in the '{$myid}' result from the API" );
		$this->assertArrayHasKey( 'descriptions', $first[0]['items']["{$myid}"],
			"Must have an 'descriptions' key in the '{$myid}' result from the API" );
	}
		
	/**
	 * Testing if we can get all the complete stringified items if we do lookup with multiple ids.
	 * 
	 * @group API
	 * @depends testSetItemGetTokenSetData
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
	 * @depends testSetItemGetTokenSetData
	 */
	public function testGetItemsSiteTitle($id, $site, $title) {
		$myid =  self::$baseOfItemIds + $id;
		$first = $this->doApiRequest( array(
			'action' => 'wbgetitems',
			'sites' => $site,
			'titles' => $title,
		) );
		
		$this->assertArrayHasKey( 'success', $first[0],
			"Must have an 'success' key in the result from the API" );
		$this->assertArrayHasKey( 'items', $first[0],
			"Must have an 'items' key in the result from the API" );
		$this->assertArrayHasKey( "{$myid}", $first[0]['items'],
			"Must have an '{$myid}' key in the 'items' result from the API" );
		$this->assertArrayHasKey( 'id', $first[0]['items']["{$myid}"],
			"Must have an 'id' key in the '{$myid}' result from the API" );
		$this->assertArrayHasKey( 'sitelinks', $first[0]['items']["{$myid}"],
			"Must have an 'sitelinks' key in the '{$myid}' result from the API" );
		$this->assertArrayHasKey( 'labels', $first[0]['items']["{$myid}"],
			"Must have an 'labels' key in the '{$myid}' result from the API" );
		$this->assertArrayHasKey( 'descriptions', $first[0]['items']["{$myid}"],
			"Must have an 'descriptions' key in the '{$myid}' result from the API" );
	}
	
	/**
	 * This tests are entering links to sites by giving 'id' for the fiorst lookup, then setting 'linksite' and 'linktitle'.
	 * In these cases the ids returned should also match up with the ids from the provider.
	 * Note that we must use a new provider to avoid having multiple links to the same external page.
	 * 
	 * @group API
	 * @dataProvider providerLinkSiteId
	 * @depends testSetItemGetTokenSetData
	 */
	public function testLinkSiteIdAdd( $id, $site, $title, $linksite, $linktitle, $badge ) {
		$this->linkSiteId( $id, $site, $title, $linksite, $linktitle, $badge, 'add' );
	}

	/**
	 * @group API
	 * @dataProvider providerLinkSiteId
	 * @depends testSetItemGetTokenSetData
	 */
	public function testLinkSiteIdUpdate( $id, $site, $title, $linksite, $linktitle, $badge ) {
		$this->linkSiteId( $id, $site, $title, $linksite, $linktitle, $badge, 'update' );
	}

	/**
	 * @group API
	 * @dataProvider providerLinkSiteId
	 * @depends testSetItemGetTokenSetData
	 */
	public function testLinkSiteIdSet( $id, $site, $title, $linksite, $linktitle, $badge ) {
		$this->linkSiteId( $id, $site, $title, $linksite, $linktitle, $badge, 'set' );
	}

	public function linkSiteId( $id, $site, $title, $linksite, $linktitle, $badge, $op ) {
		$myid =  self::$baseOfItemIds + $id;
		$req = array();
		if ( Settings::get( 'apiInDebug' ) ? Settings::get( 'apiDebugWithTokens', false ) : true ) {
			$data = $this->doApiRequest(
				array(
					'action' => 'wbsetitem',
					'gettoken' => '' ),
				null,
				false,
				self::$users['wbeditor']->user
			);
			$req['token'] = $data[0]['wbsetitem']['setitemtoken'];
		}

		$req = array_merge( $req, array(
			'action' => 'wblinksite',
			'id' => $myid,
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
		$this->assertEquals( $myid, $first[0]['item']['id'],
			"Must have the value '{$myid}' for the 'id' in the result from the first call to the API" );
		
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
		$this->assertArrayHasKey( "{$myid}", $second[0]['items'],
			"Must have an '{$myid}' key in the 'items' result from the second call to the API" );
		$this->assertArrayHasKey( 'id', $second[0]['items']["{$myid}"],
			"Must have an 'id' key in the '{$myid}' result from the second call to the API" );
		$this->assertEquals( $myid, $second[0]['items']["{$myid}"]['id'],
			"Must have the value '{$myid}' for the 'id' in the result from the second call to the API" );
		
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
		$this->assertArrayHasKey( "{$myid}", $third[0]['items'],
			"Must have an '{$myid}' key in the 'items' result from the third call to the API" );
		$this->assertArrayHasKey( 'id', $third[0]['items']["{$myid}"],
			"Must have an 'id' key in the '{$myid}' result from the third call to the API" );
		$this->assertEquals( $myid, $third[0]['items']["{$myid}"]['id'],
			"Must have the value '{$myid}' for the 'id' in the result from the third call to the API" );
	}

	/**
	 * This tests are entering links to sites by giving 'site' and 'title' pairs instead of id, then setting 'linksite' and 'linktitle'.
	 * In these cases the ids returned should also match up with the ids from the provider.
	 * Note that we must use a new provider to avoid having multiple links to the same external page.
	 * 
	 * @group API
	 * @dataProvider providerLinkSitePair
	 * @depends testSetItemGetTokenSetData
	 */
	public function testLinkSitePairAdd( $id, $site, $title, $linksite, $linktitle, $badge ) {
		$this->linkSitePair( $id, $site, $title, $linksite, $linktitle, $badge, 'add' );
	}

	/**
	 * @group API
	 * @dataProvider providerLinkSitePair
	 * @depends testSetItemGetTokenSetData
	 */
	public function testLinkSitePairUpdate( $id, $site, $title, $linksite, $linktitle, $badge ) {
		$this->linkSitePair( $id, $site, $title, $linksite, $linktitle, $badge, 'update' );
	}

	/**
	 * @group API
	 * @dataProvider providerLinkSitePair
	 * @depends testSetItemGetTokenSetData
	 */
	public function testLinkSitePairSet( $id, $site, $title, $linksite, $linktitle, $badge ) {
		$this->linkSitePair( $id, $site, $title, $linksite, $linktitle, $badge, 'set' );
	}

	public function linkSitePair( $id, $site, $title, $linksite, $linktitle, $badge, $op ) {
		$myid =  self::$baseOfItemIds + $id;
		$req = array();
		if ( Settings::get( 'apiInDebug' ) ? Settings::get( 'apiDebugWithTokens', false ) : true ) {
			$data = $this->doApiRequest(
				array(
					'action' => 'wbsetitem',
					'gettoken' => '' ),
				null,
				false,
				self::$users['wbeditor']->user
			);
			
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
		$this->assertEquals( $myid, $first[0]['item']['id'],
			"Must have the value '{$myid}' for the 'id' in the result from the first call to the API" );
		
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
		$this->assertArrayHasKey( "{$myid}", $second[0]['items'],
			"Must have an '{$myid}' key in the 'items' result from the second call to the API" );
		$this->assertArrayHasKey( 'id', $second[0]['items']["{$myid}"],
			"Must have an 'id' key in the '{$myid}' result from the second call to the API" );
		$this->assertEquals( $myid, $second[0]['items']["{$myid}"]['id'],
			"Must have the value '{$myid}' for the 'id' in the result from the second call to the API" );
		
		// now check if we can find them by their old site-title pairs
		// that is, they should not have lost teir old pairs
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
		$this->assertArrayHasKey( "{$myid}", $third[0]['items'],
			"Must have an '{$myid}' key in the 'items' result from the second call to the API" );
		$this->assertArrayHasKey( 'id', $third[0]['items']["{$myid}"],
			"Must have an 'id' key in the '{$myid}' result from the second call to the API" );
		$this->assertEquals( $myid, $third[0]['items']["{$myid}"]['id'],
			"Must have the value '{$myid}' for the 'id' in the result from the second call to the API" );
		
	}

	/**
	 * This tests if the site links for the items can be found by using 'id' from the provider.
	 * That is the updating should not have moved them around or deleted old content.
	 * 
	 * @group API
	 * @dataProvider providerLabelDescription
	 * @depends testSetItemGetTokenSetData
	 */
	public function testSetLanguageAttributeAdd( $id, $site, $title, $language, $label, $description ) {
		$this->setLanguageAttribute( $id+3, $site, $title, $language, $label, $description, 'add' );
	}
	
	/**
	 * @group API
	 * @dataProvider providerLabelDescription
	 * @depends testSetItemGetTokenSetData
	 */
	public function testSetLanguageAttributeUpdate( $id, $site, $title, $language, $label, $description ) {
		$this->setLanguageAttribute( $id, $site, $title, $language, $label, $description, 'update' );
	}
	
	/**
	 * @group API
	 * @dataProvider providerLabelDescription
	 * @depends testSetItemGetTokenSetData
	 */
	public function testSetLanguageAttributeSet( $id, $site, $title, $language, $label, $description ) {
		$this->setLanguageAttribute( $id, $site, $title, $language, $label, $description, 'set' );
	}
	
	public function setLanguageAttribute( $id, $site, $title, $language, $label, $description, $op ) {
		$myid =  self::$baseOfItemIds + $id;
		$req = array();
		if ( Settings::get( 'apiInDebug' ) ? Settings::get( 'apiDebugWithTokens', false ) : true ) {
			$data = $this->doApiRequest(
				array(
					'action' => 'wbsetitem',
					'gettoken' => '' ),
				null,
				false,
				self::$users['wbeditor']->user
			);
			
			$req['token'] = $data[0]['wbsetitem']['setitemtoken'];
		}
		if ( $op !== 'add') {
			$req['id'] = $myid;
		}
		$req = array_merge( $req, array(
			'action' => 'wbsetlanguageattribute',
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
		$this->assertEquals( $myid, $first[0]['item']['id'],
			"Must have the value '{$myid}' for the 'id' in the result from the API" );
		
		$second = $this->doApiRequest( array(
			'action' => 'wbgetitems',
			'ids' => $myid,
			'usekeys' => true,
			'format' => 'jsonfm',
			'language' => $language,
		) );
		
		$this->assertArrayHasKey( 'success', $second[0],
			"Must have an 'success' key in the result in the second call to the API" );
		$this->assertArrayHasKey( 'items', $second[0],
			"Must have an 'items' key in the result in the second call to the API" );
		$this->assertCount( 1, $second[0]['items'],
			"Must have a number of count of 1 in the 'items' result in the second call to the API" );
		$this->assertArrayHasKey( "{$myid}", $second[0]['items'],
			"Must have an '{$myid}' key in the 'items' result in the second call to the API" );
		$this->assertArrayHasKey( 'id', $second[0]['items']["{$myid}"],
			"Must have an 'id' key in the '{$myid}' result in the second call to the API" );
		$this->assertEquals( $myid, $second[0]['items']["{$myid}"]['id'],
			"Must have the value '{$myid}' for the 'id' in the result in the second call to the API" );
		
		$this->assertArrayHasKey( $language, $second[0]['items']["{$myid}"]['labels'],
			"Must have an '{$language}' key in the 'labels' second result in the second call to the API" );
		$this->assertEquals( $label, $second[0]['items']["{$myid}"]['labels'][$language]['value'],
			"Must have the value '{$label}' for the value of '{$language}' in the 'labels' set in the result in the second call to the API" );
		
		$this->assertArrayHasKey( $language, $second[0]['items']["{$myid}"]['descriptions'],
			"Must have an '{$language}' key in the 'descriptions' result in the second to the API" );
		$this->assertEquals( $description, $second[0]['items']["{$myid}"]['descriptions'][$language]['value'],
			"Must have the value '{$description}' for the value of '{$language}' in the 'descriptions' set in the result in the second call to the API" );
		
	}
	
	/**
	 * This tests if the site links for the items can be found by using 'id' from the provider.
	 * That is the updating should not have moved them around or deleted old content.
	 *
	 * @group API
	 * @dataProvider providerRemoveLabelDescription
	 * @depends testSetItemGetTokenSetData
	 */
  public function testDeleteLanguageAttributeLabel( $id, $site, $title, $language ) {
	  $this->deleteLanguageAttribute( $id, $site, $title, $language, 'label' );
  }
	
	/**
	 * @group API
	 * @dataProvider providerRemoveLabelDescription
	 * @depends testSetItemGetTokenSetData
	 */
   public function testDeleteLanguageAttributeDescription( $id, $site, $title, $language ) {
 	  $this->deleteLanguageAttribute( $id, $site, $title, $language, 'description' );
   }

	public function deleteLanguageAttribute( $id, $site, $title, $language, $op ) {
		$myid =  self::$baseOfItemIds + $id;
		$req = array();
		if ( Settings::get( 'apiInDebug' ) ? Settings::get( 'apiDebugWithTokens', false ) : true ) {
			$data = $this->doApiRequest(
				array(
					'action' => 'wbsetitem',
					'gettoken' => '' ),
				null,
				false,
				self::$users['wbeditor']->user
			);
			$req['token'] = $data[0]['wbsetitem']['setitemtoken'];
		}
		
		$req = array_merge( $req, array(
			'action' => 'wbdeletelanguageattribute',
			'id' => $myid,
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
		$this->assertEquals( $myid, $first[0]['item']['id'],
			"Must have the value '{$myid}' for the 'id' in the result from the API" );
		
		$second = $this->doApiRequest( array(
			'action' => 'wbgetitems',
			'ids' => $myid,
			'language' => $language,
		) );
		
		$this->assertArrayHasKey( 'success', $second[0],
			"Must have an 'success' key in the result in the second call to the API" );
		$this->assertArrayHasKey( 'items', $second[0],
			"Must have an 'items' key in the result in the second call to the API" );
		$this->assertCount( 1, $second[0]['items'],
			"Must have a number of count of 1 in the 'items' result in the second call to the API" );
		$this->assertArrayHasKey( "{$myid}", $second[0]['items'],
			"Must have an '{$myid}' key in the 'items' result in the second call to the API" );
		$this->assertArrayHasKey( 'id', $second[0]['items']["{$myid}"],
			"Must have an 'id' key in the '{$myid}' result in the second call to the API" );
		$this->assertEquals( $myid, $second[0]['items']["{$myid}"]['id'],
			"Must have the value '{$myid}' for the 'id' in the result in the second call to the API" );
		
		if ( isset($second[0]['items']["{$myid}"][$op][$language]) ) {
			$this->fail( "Must not have an '{$language}' key in the 'labels' result in the second call to the API" );
		}
	}
	
	/**
	 * Just provide the actions to test the API calls
	 */
	function provideSetItemIdDataOp() {
		$idx = self::$baseOfItemIds;
		return array(
			array(
				++$idx,
				'add',
				'{
					"links": {
						"dewiki": "Berlin",
						"enwiki": "Berlin",
						"nlwiki": "Berlin",
						"nnwiki": "Berlin"
					},
					"label": {
						"de": "Berlin",
						"en": "Berlin",
						"no": "Berlin",
						"nn": "Berlin"
					},
					"description": { 
						"de" : "Bundeshauptstadt und Regierungssitz der Bundesrepublik Deutschland.",
						"en" : "Capital city and a federated state of the Federal Republic of Germany.",
						"no" : "Hovedsted og delstat og i Forbundsrepublikken Tyskland.",
						"nn" : "Hovudstad og delstat i Forbundsrepublikken Tyskland."
					}
				}'
			),
			array(
				++$idx,
				'add',
				'{
					"links": {
						"enwiki": "London",
						"dewiki": "London",
						"nlwiki": "London",
						"nnwiki": "London"
					},
					"label": {
						"de": "London",
						"en": "London",
						"no": "London",
						"nn": "London"
					},				
					"description": { 
						"de" : "Hauptstadt Englands und des Vereinigten Königreiches.",
						"en" : "Capital city of England and the United Kingdom.",
						"no" : "Hovedsted i England og Storbritannia.",
						"nn" : "Hovudstad i England og Storbritannia."
					}
				}'
			),
			array(
				++$idx,
				'add',
				'{
					"links": {
						"dewiki": "Oslo",
						"enwiki": "Oslo",
						"nlwiki": "Oslo",
						"nnwiki": "Oslo"
					},
					"label": {
						"de": "Oslo",
						"en": "Oslo",
						"no": "Oslo",
						"nn": "Oslo"
					},				
					"description": { 
						"de" : "Hauptstadt der Norwegen.",
						"en" : "Capital city in Norway.",
						"no" : "Hovedsted i Norge.",
						"nn" : "Hovudstad i Noreg."
					}
				}'
			),
		);
	}
	
	public function providerGetItemId() {

		$idx = self::$baseOfItemIds;
		return array(
			array( ++$idx, 'dewiki', 'Berlin'),
			array( $idx, 'enwiki', 'Berlin'),
			array( $idx, 'nlwiki', 'Berlin'),
			array( $idx, 'nnwiki', 'Berlin'),
			array( ++$idx, 'dewiki', 'London'),
			array( $idx, 'enwiki', 'London'),
			array( $idx, 'nlwiki', 'London'),
			array( $idx, 'nnwiki', 'London'),
			array( ++$idx, 'enwiki', 'Oslo'),
			array( $idx, 'dewiki', 'Oslo'),
			array( $idx, 'nlwiki', 'Oslo'),
			array( $idx, 'nnwiki', 'Oslo'),
		);
	}
	
	public function providerLinkSiteId() {
		$idx = self::$baseOfItemIds;
		return array(
			array( ++$idx, 'dewiki', 'Berlin', 'enwiktionary', 'Berlin', 1 ),
			array( ++$idx, 'enwiki', 'London', 'enwiktionary', 'London', 2 ),
			array( ++$idx, 'nlwiki', 'Oslo', 'enwiktionary', 'Oslo', 3 ),
		);
	}
	
	public function providerLinkSitePair() {
		$idx = self::$baseOfItemIds;
		return array(
			array( ++$idx, 'dewiki', 'Berlin', 'svwiki', 'Berlin', 1 ),
			array( ++$idx, 'enwiki', 'London', 'svwiki', 'London', 2 ),
			array( ++$idx, 'nlwiki', 'Oslo', 'svwiki', 'Oslo', 3 ),
		);
	}
	
	public function providerLabelDescription() {
		$idx = self::$baseOfItemIds;
		return array(
			array( ++$idx, 'nlwiki', 'Berlin', 'da', 'Berlin', 'Hovedstad i Tyskland' ),
			array( ++$idx, 'nlwiki', 'London', 'da', 'London', 'Hovedstad i England' ),
			array( ++$idx, 'nlwiki', 'Oslo', 'da', 'Oslo', 'Hovedstad i Norge' ),
		);
	}

public function providerRemoveLabelDescription() {
	$idx = self::$baseOfItemIds;
	return array(
		array( ++$idx, 'nlwiki', 'Berlin', 'da' ),
		array( ++$idx, 'nlwiki', 'London', 'da' ),
		array( ++$idx, 'nlwiki', 'Oslo', 'da' ),
	);
}
	
}
