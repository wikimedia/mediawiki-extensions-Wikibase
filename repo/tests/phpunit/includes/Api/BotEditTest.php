<?php

namespace Wikibase\Repo\Tests\Api;

use ApiTestCase;
use TestUser;
use Title;
use Wikibase\Repo\WikibaseRepo;

/**
 * This testset only checks the validity of the calls and correct handling of tokens and users.
 * Note that we creates an empty database and then starts manipulating testusers.
 *
 * @group API
 * @group Wikibase
 * @group WikibaseAPI
 * @group WikibaseRepo
 * @group BreakingTheSlownessBarrier
 * @group Database
 * @group medium
 *
 * @license GPL-2.0-or-later
 * @coversNothing
 */
class BotEditTest extends WikibaseApiTestCase {

	private static $hasSetup;

	protected function setUp(): void {
		parent::setUp();

		ApiTestCase::$users['wbbot'] = new TestUser(
			'Apitestbot',
			'Api Test Bot',
			'api_test_bot@example.com',
			[ 'bot' ]
		);
		$this->mergeMwGlobalArrayValue( 'wgGroupPermissions', [
			'user' => [ 'item-merge' => true, 'item-redirect' => true ],
		] );

		if ( !isset( self::$hasSetup ) ) {
			$this->initTestEntities( [ 'Empty', 'Leipzig', 'Osaka' ] );
		}

		self::$hasSetup = true;
	}

	public function provideData() {
		return [
			[ //0
				'p' => [ 'handle' => 'Empty', 'bot' => '', 'action' => 'wbsetlabel',
					'language' => 'en', 'value' => 'ALabel' ],
				'e' => [ 'bot' => true, 'new' => false ] ],
			[ //1
				'p' => [ 'handle' => 'Empty', 'action' => 'wbsetlabel', 'language' => 'en',
					'value' => 'ALabel2' ],
				'e' => [ 'bot' => false, 'new' => false ] ],
			[ //2
				'p' => [ 'handle' => 'Empty', 'bot' => '', 'action' => 'wbsetdescription',
					'language' => 'de', 'value' => 'ADesc' ],
				'e' => [ 'bot' => true, 'new' => false ] ],
			[ //3
				'p' => [ 'handle' => 'Empty', 'action' => 'wbsetdescription',
					'language' => 'de', 'value' => 'ADesc2' ],
				'e' => [ 'bot' => false, 'new' => false ] ],
			[ //4
				'p' => [ 'handle' => 'Empty', 'bot' => '', 'action' => 'wbsetaliases',
					'language' => 'de', 'set' => 'ali1' ],
				'e' => [ 'bot' => true, 'new' => false ] ],
			[ //5
				'p' => [ 'handle' => 'Empty', 'action' => 'wbsetaliases', 'language' => 'de',
					'set' => 'ali2' ],
				'e' => [ 'bot' => false, 'new' => false ] ],
			[ //6
				'p' => [ 'handle' => 'Empty', 'bot' => '', 'action' => 'wbsetsitelink',
					'linksite' => 'enwiki', 'linktitle' => 'PageEn' ],
				'e' => [ 'bot' => true, 'new' => false ] ],
			[ //7
				'p' => [ 'handle' => 'Empty', 'action' => 'wbsetsitelink',
					'linksite' => 'dewiki', 'linktitle' => 'PageDe' ],
				'e' => [ 'bot' => false, 'new' => false ] ],
			[ //8
				'p' => [ 'bot' => '', 'action' => 'wblinktitles', 'tosite' => 'enwiki',
					'totitle' => 'PageEn', 'fromsite' => 'svwiki', 'fromtitle' => 'SvPage' ],
				'e' => [ 'bot' => true, 'new' => false ] ],
			[ //9
				'p' => [ 'action' => 'wblinktitles', 'tosite' => 'dewiki',
					'totitle' => 'PageDe', 'fromsite' => 'nowiki', 'fromtitle' => 'NoPage' ],
				'e' => [ 'bot' => false, 'new' => false ] ],
			[ //10
				'p' => [ 'bot' => '', 'action' => 'wbeditentity', 'new' => 'item',
					'data' => '{}' ],
				'e' => [ 'bot' => true, 'new' => true ] ],
			[ //11
				'p' => [ 'action' => 'wbeditentity', 'new' => 'item', 'data' => '{}' ],
				'e' => [ 'bot' => false, 'new' => true ] ],
			[ //12
				'p' => [ 'action' => 'wbmergeitems', 'fromid' => 'Osaka', 'toid' => 'Empty',
					'bot' => '' ],
				'e' => [ 'bot' => true, 'new' => false ] ],
			[ //13
				'p' => [ 'action' => 'wbmergeitems', 'fromid' => 'Leipzig', 'toid' => 'Empty',
					'ignoreconflicts' => 'description' ],
				'e' => [ 'bot' => false, 'new' => false ] ],
			// TODO: Claims, references, qualifiers.
		];
	}

	/**
	 * @dataProvider provideData
	 */
	public function testBotEdits( $params, $expected ) {
		// -- do the request --------------------------------------------------
		if ( array_key_exists( 'handle', $params ) ) {
			$params['id'] = EntityTestHelper::getId( $params['handle'] );
			unset( $params['handle'] );
		}

		// wbmergeitems needs special treatment as it takes two entities
		if ( $params['action'] === 'wbmergeitems' ) {
			$params['fromid'] = EntityTestHelper::getId( $params['fromid'] );
			$params['toid'] = EntityTestHelper::getId( $params['toid'] );
		}
		list( $result, , ) = $this->doApiRequestWithToken( $params, null, self::$users['wbbot']->getUser() );

		// -- check the result ------------------------------------------------
		$this->assertArrayHasKey( 'success', $result, "Missing 'success' marker in response." );
		$this->assertResultHasEntityType( $result );
		if ( $params['action'] !== 'wbmergeitems' ) {
			$this->assertArrayHasKey( 'entity', $result, "Missing 'entity' section in response." );
			$this->assertArrayHasKey( 'lastrevid', $result['entity'], 'entity should contain lastrevid key' );
			$myid = $result['entity']['id'];
		} else {
			$this->assertArrayHasKey( 'from', $result, "Missing 'from' section in response." );
			$myid = $result['from']['id'];
		}

		// -- get the recentchanges -------------------------------------------
		$rcRequest = [
			'action' => 'query',
			'list' => 'recentchanges',
			'rcprop' => 'title|flags',
			'rctoponly' => '1',
			'rclimit' => 5, // hope that no more than 50 edits where made in the last second
		];

		//@todo this really makes this test slow, is there a better way?
		$rcResult = $this->doApiRequest( $rcRequest, null, false, self::$users['wbbot']->getUser() );

		// -- check the recent changes result ---------------------------------
		$this->assertArrayHasKey( 'query', $rcResult[0], "Must have a 'query' key in the result from the API" );
		$this->assertArrayHasKey( 'recentchanges', $rcResult[0]['query'],
			"Must have a 'recentchanges' key in 'query' subset of the result from the API" );

		//NOTE: the order of the entries in recentchanges is undefined if multiple
		//      edits were done in the same second.
		$change = null;

		$entityNamespaceLookup = WikibaseRepo::getEntityNamespaceLookup();
		$itemNs = $entityNamespaceLookup->getEntityNamespace( 'item' );

		foreach ( $rcResult[0]['query']['recentchanges'] as $rc ) {
			$title = Title::newFromTextThrow( $rc['title'] );
			// XXX: strtoupper is a bit arcane, would ne nice to have a utility function for prefixed id -> title.
			if ( $title->getNamespace() === $itemNs && $title->getText() === strtoupper( $myid ) ) {
				$change = $rc;
				break;
			}
		}

		$this->assertNotNull( $change, 'no change matching ID ' . $myid . ' found in recentchanges feed!' );

		$this->assertResultValue( $expected, 'new', $change );
		$this->assertResultValue( $expected, 'bot', $change );
	}

	private function assertResultValue( $expected, $key, $change ) {
		if ( $expected[$key] === true ) {
			$this->assertResultValueTrue( $key, $change );
		} else {
			$this->assertResultValueFalse( $key, $change );
		}
	}

	private function assertResultValueTrue( $key, $change ) {
		$this->assertTrue( $change[$key], "Value of '$key' key in the in the rc-entry"
			. ' of the result was expected to be true, but was ' . $change[$key] );
	}

	private function assertResultValueFalse( $key, $change ) {
		$this->assertFalse( $change[$key], "Value of '$key' key in the in the rc-entry"
			. ' of the result was expected to be false, but was ' . $change[$key] );
	}

}
