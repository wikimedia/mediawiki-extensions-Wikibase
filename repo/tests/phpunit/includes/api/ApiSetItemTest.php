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
 * @group ApiSetItemTest
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

	public function setUp() {
		global $wgUser;
		parent::setUp();

		\Wikibase\Utils::insertSitesForTests();

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
			'lgpassword' => self::$users['wbeditor']->password
		) );

		$token = $data[0]['login']['token'];

		$this->doApiRequest(
			array(
				'action' => 'login',
				'lgtoken' => $token,
				'lgname' => self::$users['wbeditor']->username,
				'lgpassword' => self::$users['wbeditor']->password
			),
			$data
		);
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
	function testSetItemGetTokenGetItems( $id, $op, $data ) {
		$data = $this->doApiRequest(
			array(
				'action' => 'wbgetitems',
				'ids' => $id,
				'gettoken' => '' ),
			null,
			false,
			self::$users['wbeditor']->user
		);
		// this should always hold for a logged in user
		// unless we do some additional tricks with the token
		$this->assertEquals(
			34, strlen( $data[0]["wbgetitems"]["itemtoken"] ),
			"The length of the token is not 34 chars"
		);
		$this->assertRegExp(
			'/\+\\\\$/', $data[0]["wbgetitems"]["itemtoken"],
			"The final chars of the token is not '+\\'"
		);
	}

	/**
	 * @group API
	 * @depends testTokensAndRights
	 * @dataProvider provideSetItemIdDataOp
	 */
	function testSetItemGetTokenSetItem( $id, $op, $data ) {
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
			34, strlen( $data[0]["wbsetitem"]["itemtoken"] ),
			"The length of the token is not 34 chars"
		);
		$this->assertRegExp(
			'/\+\\\\$/', $data[0]["wbsetitem"]["itemtoken"],
			"The final chars of the token is not '+\\'"
		);
	}

	/**
	 * Attempting to set item without a token should give a UsageException with
	 * error message:
	 *   "The token parameter must be set"
	 *
	 * @group API
	 * @depends testSetItemGetTokenSetItem
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
		catch ( \UsageException $e ) {
			$this->assertTrue( ($e->getCode() == 'session-failure'), "Got a wrong exception" );
			return;
		}
		$this->assertTrue( self::$usetoken, "Missing an exception" );
	}

	/**
	 * @group API
	 * @depends testSetItemGetTokenSetItem
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

			$req['token'] = $first[0]['wbsetitem']['itemtoken'];
		}
		$req = array_merge(
			$req,
			array(
				'action' => 'wbsetitem',
				'summary' => 'Some reason',
				'data' => '{}',
			)
		);

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
					'gettoken' => ''
				),
				null,
				false,
				self::$users['wbeditor']->user
			);

			$req['token'] = $first[0]['wbsetitem']['itemtoken'];
		}

		$req = array_merge(
			$req,
			array(
				'action' => 'wbsetitem',
				'summary' => 'Some reason',
				'data' => $data,
				'item' => $op
			)
		);

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
			"Must have an 'id' key in the 'item' from the API" );
		$myid =  self::$baseOfItemIds + $id;
		$this->assertEquals( $myid, $first[0]['items']["{$myid}"]['id'],
			"Must have the value '{$myid}' for the 'id' in the 'item' from the result from the API" );
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
	 * Testing if we can get missing items if we do lookup with single fake ids.
	 * Note that this makes assumptions about which ids they have been assigned.
	 *
	 * @group API
	 * @depends testSetItemGetTokenSetData
	 */
	public function testGetItemsMissingId( ) {
		$myid =  self::$baseOfItemIds + 123456789;
		$first = $this->doApiRequest( array(
			'action' => 'wbgetitems',
			'ids' => "{$myid}",
		) );
		$this->assertArrayHasKey( 'success', $first[0],
			"Must have an 'success' key in the result from the API" );
		$this->assertArrayHasKey( 'items', $first[0],
			"Must have an 'items' key in the result from the API" );
		$items = array_values( $first[0]['items'] );
		$this->assertEquals( 1, count( $items ),
			"Must have an item count of '1' in the result from the API" );
		$this->assertArrayHasKey( 'missing', $items[0],
			"Must have a 'missing' key in the result from the API" );
	}

	/**
	 * Testing if we can get missing items if we do lookup with failing titles.
	 * Note that this makes assumptions about which ids they have been assigned.
	 *
	 * @group API
	 * @depends testSetItemGetTokenSetData
	 * @dataProvider providerGetItemsMissingTitle
	 */
	public function testGetItemsMissingTitle( $id, $sites, $titles ) {
		$first = $this->doApiRequest( array(
			'action' => 'wbgetitems',
			'sites' => $sites,
			'titles' => $titles,
		) );
		$this->assertArrayHasKey( 'success', $first[0],
			"Must have an 'success' key in the result from the API" );
		$this->assertArrayHasKey( 'items', $first[0],
			"Must have an 'items' key in the result from the API" );
		$items = array_values( $first[0]['items'] );
		foreach ( $first[0]['items'] as $k => $v ) {
			$this->assertArrayHasKey( 'missing', $v,
				"Must have a 'missing' key in the result from the API" );
		}
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
	public function testLinkSiteIdUpdate( $id, $site, $title, $linksite, $linktitle, $badge ) {
		$this->linkSiteId( $id, $site, $title, $linksite, $linktitle, $badge, 'update' );
	}

	/**
	 * @group API
	 * @dataProvider providerLinkSiteId
	 * @depends testSetItemGetTokenSetData
	 */
	public function testLinkSiteIdRemove( $id, $site, $title, $linksite, $linktitle, $badge ) {
		$this->linkSiteId( $id, $site, $title, $linksite, $linktitle, $badge, 'remove' );
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
			$req['token'] = $data[0]['wbsetitem']['itemtoken'];
		}

		$req = array_merge( $req, array(
			'action' => 'wbsetsitelink',
			'id' => $myid,
			'linksite' => $linksite,
			'linktitle' => ($op === 'remove' ? '' : $linktitle),
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
		if ( $op === 'remove' ) {
			$this->assertCount( 1, $second[0]['items'],
				"Must have a number of count of 1 in the 'items' result from the second call to the API" );
			$this->assertArrayHasKey( "-1", $second[0]['items'],
				"Must have an '-1' key in the 'items' result from the second call to the API" );
			$this->assertArrayHasKey( 'missing', $second[0]['items']["-1"],
				"Must have an 'missing' key in the '-1' result from the second call to the API" );
		}
		else {
			$this->assertCount( 1, $second[0]['items'],
				"Must have a number of count of 1 in the 'items' result from the second call to the API" );
			$this->assertArrayHasKey( "{$myid}", $second[0]['items'],
				"Must have an '{$myid}' key in the 'items' result from the second call to the API" );
			$this->assertArrayHasKey( 'id', $second[0]['items']["{$myid}"],
				"Must have an 'id' key in the '{$myid}' result from the second call to the API" );
			$this->assertEquals( $myid, $second[0]['items']["{$myid}"]['id'],
				"Must have the value '{$myid}' for the 'id' in the result from the second call to the API" );
		}

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
	public function testLinkSitePairUpdate( $id, $site, $title, $linksite, $linktitle, $badge ) {
		$this->linkSitePair( $id, $site, $title, $linksite, $linktitle, $badge, 'update' );
	}

	/**
	 * @group API
	 * @dataProvider providerLinkSitePair
	 * @depends testSetItemGetTokenSetData
	 */
	public function testLinkSitePairRemove( $id, $site, $title, $linksite, $linktitle, $badge ) {
		$this->linkSitePair( $id, $site, $title, $linksite, $linktitle, $badge, 'remove' );
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

			$req['token'] = $data[0]['wbsetitem']['itemtoken'];
		}

		$req = array_merge( $req, array(
			'action' => 'wbsetsitelink',
			'site' => $site,
			'title' => $title,
			'linksite' => $linksite,
			'linktitle' => ($op === 'remove' ? '' : $linktitle),
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
		if ( $op === 'remove' ) {
			$this->assertCount( 1, $second[0]['items'],
				"Must have a number of count of 1 in the 'items' result from the second call to the API" );
			$this->assertArrayHasKey( "-1", $second[0]['items'],
				"Must have an '-1' key in the 'items' result from the second call to the API" );
			$this->assertArrayHasKey( 'missing', $second[0]['items']["-1"],
				"Must have an 'missing' key in the '-1' result from the second call to the API" );
		}
		else {
			$this->assertCount( 1, $second[0]['items'],
				"Must have a number of count of 1 in the 'items' result from the second call to the API" );
			$this->assertArrayHasKey( "{$myid}", $second[0]['items'],
				"Must have an '{$myid}' key in the 'items' result from the second call to the API" );
			$this->assertArrayHasKey( 'id', $second[0]['items']["{$myid}"],
				"Must have an 'id' key in the '{$myid}' result from the second call to the API" );
			$this->assertEquals( $myid, $second[0]['items']["{$myid}"]['id'],
				"Must have the value '{$myid}' for the 'id' in the result from the second call to the API" );
		}

		// now check if we can find them by their old site-title pairs
		// that is, they should not have lost teir old pairs
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
	 * This tests if the site links for the items can be found by using 'id' from the provider.
	 * That is the updating should not have moved them around or deleted old content.
	 * 
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
	public function testSetLanguageAttributeRemove( $id, $site, $title, $language, $label, $description ) {
		$this->setLanguageAttribute( $id, $site, $title, $language, $label, $description, 'remove' );
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

			$req['token'] = $data[0]['wbsetitem']['itemtoken'];
		}
		$req = array_merge( $req, array(
			'action' => 'wbsetlanguageattribute',
			'id' => "{$myid}",
			'label' => ($op === 'remove' ? '' : $label),
			'description' => ($op === 'remove' ? '' : $description),
			'language' => $language,
			'usekeys' => true,
			'format' => 'jsonfm',
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
		if ( $op === 'remove' ) {
			$this->assertArrayHasKey( $language, $first[0]['item']['labels'],
				"Must have an '{$language}' key in the 'labels' result in the first call to the API" );
			$this->assertArrayHasKey( 'removed', $first[0]['item']['labels'][$language],
				"Must have a key 'removed' in the '{$language}' for labels' set in the result in the first call to the API" );
			$this->assertArrayHasKey( $language, $first[0]['item']['descriptions'],
				"Must have an '{$language}' key in the 'descriptions' result in the first call to the API" );
			$this->assertArrayHasKey( 'removed', $first[0]['item']['descriptions'][$language],
				"Must have the value 'removed' in the '{$language}' for 'descriptions' set in the result in the first call to the API" );
		}
		else {
			$this->assertArrayHasKey( $language, $first[0]['item']['labels'],
				"Must have an '{$language}' key in the 'labels' result in the first call to the API" );
			$this->assertEquals( $label, $first[0]['item']['labels'][$language]['value'],
				"Must have the value '{$label}' for the value of '{$language}' in the 'labels' set in the result in the first call to the API" );
			$this->assertArrayHasKey( $language, $first[0]['item']['descriptions'],
				"Must have an '{$language}' key in the 'descriptions' result in the first call to the API" );
			$this->assertEquals( $description, $first[0]['item']['descriptions'][$language]['value'],
				"Must have the value '{$description}' for the value of '{$language}' in the 'descriptions' set in the result in the first call to the API" );
		}

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
		if ( $op === 'remove' ) {
			$this->assertFalse( array_key_exists( $language, $second[0]['items']["{$myid}"]['labels'] ),
				"Must have a '{$language}' key in the 'labels' result in the second call to the API" );
			$this->assertFalse( array_key_exists( $language, $second[0]['items']["{$myid}"]['descriptions'] ),
				"Must have an '{$language}' key in the 'descriptions' result in the second call to the API" );
		}
		else {
			$this->assertTrue( array_key_exists( $language, $second[0]['items']["{$myid}"]['labels'] ),
				"Must have an '{$language}' key in the 'labels' result in the second call to the API" );
			$this->assertTrue( array_key_exists( $language, $second[0]['items']["{$myid}"]['descriptions'] ),
				"Must have an '{$language}' key in the 'descriptions' result in the second call to the API" );
		}
	}

	/**
	 * This tests if the site links for the items can be found by using 'id' from the provider,
	 * and then deletes the content of the given language attribute because its empty.
	 * That is the updating should not have moved them around or deleted old content.
	 *
	 * @group API
	 * @dataProvider providerEmptyLabelDescription
	 * @depends testSetItemGetTokenSetData
	 */
	public function testEmptyLanguageAttributeLabel( $id, $site, $title, $language ) {
		$this->emptyLanguageAttribute( $id, $site, $title, $language, 'label' );
	}

	/**
	 * @group API
	 * @dataProvider providerEmptyLabelDescription
	 * @depends testSetItemGetTokenSetData
	 */
	public function testEmptyLanguageAttributeDescription( $id, $site, $title, $language ) {
		$this->emptyLanguageAttribute( $id, $site, $title, $language, 'description' );
	}

	public function emptyLanguageAttribute( $id, $site, $title, $language, $op ) {
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
			$req['token'] = $data[0]['wbsetitem']['itemtoken'];
		}

		$req = array_merge( $req, array(
			'action' => 'wbsetlanguageattribute',
			'id' => $myid,
			$op => '',
			'language' => $language,
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

		if ( isset($second[0]['items']["{$myid}"][$op . 's'][$language]) ) {
			$this->fail( "Must not have an '{$language}' key in the '{$op}s' result in the second call to the API" );
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
					"sitelinks": {
						"dewiki": "Berlin",
						"enwiki": "Berlin",
						"nlwiki": "Berlin",
						"nnwiki": "Berlin"
					},
					"labels": {
						"de": "Berlin",
						"en": "Berlin",
						"no": "Berlin",
						"nn": "Berlin"
					},
					"descriptions": {
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
					"sitelinks": {
						"enwiki": "London",
						"dewiki": "London",
						"nlwiki": "London",
						"nnwiki": "London"
					},
					"labels": {
						"de": "London",
						"en": "London",
						"no": "London",
						"nn": "London"
					},
					"descriptions": {
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
					"sitelinks": {
						"dewiki": "Oslo",
						"enwiki": "Oslo",
						"nlwiki": "Oslo",
						"nnwiki": "Oslo"
					},
					"labels": {
						"de": "Oslo",
						"en": "Oslo",
						"no": "Oslo",
						"nn": "Oslo"
					},
					"descriptions": {
						"de" : "Hauptstadt der Norwegen.",
						"en" : "Capital city in Norway.",
						"no" : "Hovedsted i Norge.",
						"nn" : "Hovudstad i Noreg."
					}
				}'
			),
			array(
				++$idx,
				'add',
				'{
					"sitelinks": {
						"dewiki": "Episkopi Cantonment",
						"enwiki": "Episkopi Cantonment",
						"nlwiki": "Episkopi Cantonment"
					},
					"labels": {
						"de": "Episkopi Cantonment",
						"en": "Episkopi Cantonment",
						"nl": "Episkopi Cantonment"
					},
					"descriptions": {
						"de" : "Sitz der Verwaltung der Mittelmeerinsel Zypern.",
						"en" : "The capital of Akrotiri and Dhekelia.",
						"nl" : "Het bestuurlijke centrum van Akrotiri en Dhekelia."
					}
				}'
			),
		);
	}

	public function providerGetItemsMissingTitle() {

		$idx = self::$baseOfItemIds;
		return array(
			array( $idx, 'dewiki', 'sugarinthemorningtothefriedkittens' ),
			array( $idx, 'dewiki', 'sugarinthemorningtothefriedkittens|sugarintheeveningtothefriedkittens' ),
			array( $idx, 'dewiki|enwiki', 'sugarinthemorningtothefriedkittens' ),
		);
	}

	public function providerGetItemId() {

		$idx = self::$baseOfItemIds;
		return array(
			array( ++$idx, 'dewiki', 'Berlin' ),
			array( $idx, 'enwiki', 'Berlin' ),
			array( $idx, 'nlwiki', 'Berlin' ),
			array( $idx, 'nnwiki', 'Berlin' ),
			array( $idx, 'dewiki|enwiki|nlwiki|nnwiki', 'Berlin' ),
			array( $idx, 'dewiki', ' Berlin' ),
			array( $idx, 'enwiki', '  Berlin' ),
			array( $idx, 'nlwiki', 'Berlin ' ),
			array( $idx, 'nnwiki', 'Berlin  ' ),
			array( ++$idx, 'dewiki', 'London' ),
			array( $idx, 'enwiki', 'London' ),
			array( $idx, 'nlwiki', 'London' ),
			array( $idx, 'dewiki|enwiki|nlwiki|nnwiki', 'London' ),
			array( $idx, 'nnwiki', 'London' ),
			array( $idx, 'dewiki', ' London ' ),
			array( $idx, 'enwiki', '  London  ' ),
			array( $idx, 'nlwiki', '   London   ' ),
			array( $idx, 'nnwiki', '    London    ' ),
			array( ++$idx, 'enwiki', 'Oslo' ),
			array( $idx, 'dewiki', 'Oslo' ),
			array( $idx, 'nlwiki', 'Oslo' ),
			array( $idx, 'nnwiki', 'Oslo' ),
			array( $idx, 'enwiki|dewiki|nlwiki|nnwiki', 'Oslo' ),
			array( ++$idx, 'enwiki', 'Episkopi Cantonment' ),
			array( $idx, 'dewiki', 'Episkopi  Cantonment' ),
			array( $idx, 'nlwiki', 'Episkopi   Cantonment' ),
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
			array( ++$idx, 'nlwiki', 'Berlin', 'no', 'Berlin', 'Hovedstad i Tyskland' ),
			array( ++$idx, 'nlwiki', 'London', 'nn', 'London', 'Hovudstad i England' ),
			array( ++$idx, 'nlwiki', 'Oslo', 'en', 'Oslo', 'Capitol in Norway' ),
		);
	}

	public function providerEmptyLabelDescription() {
		$idx = self::$baseOfItemIds;
		return array(
			array( ++$idx, 'nlwiki', 'Episkopi Cantonment', 'nl' ),
			array( ++$idx, 'nlwiki', 'Episkopi Cantonment', 'en' ),
			array( ++$idx, 'nlwiki', 'Episkopi Cantonment', 'de' ),
		);
	}

}
