<?php

namespace Wikibase\Test\Repo\Api;

use ApiTestCase;
use TestUser;
use Title;
use Wikibase\Repo\WikibaseRepo;

/**
 * Tests for the ApiWikibase class.
 *
 * This testset only checks the validity of the calls and correct handling of tokens and users.
 * Note that we creates an empty database and then starts manipulating testusers.
 *
 * @group API
 * @group Wikibase
 * @group WikibaseAPI
 * @gorup WikibaseRepo
 * @group BotEditTest
 * @group BreakingTheSlownessBarrier
 * @group Database
 * @group medium
 *
 * @license GPL-2.0+
 * @author John Erling Blad < jeblad@gmail.com >
 * @author Daniel Kinzler < daniel.kinzler@wikimedia.de >
 * @author Anja Jentzsch < anja.jentzsch@wikimedia.de >
 * @author Addshore
 */
class BotEditTest extends WikibaseApiTestCase {

	private static $hasSetup;

	protected function setUp() {
		parent::setUp();

		ApiTestCase::$users['wbbot'] = new TestUser(
			'Apitestbot',
			'Api Test Bot',
			'api_test_bot@example.com',
			array( 'bot' )
		);
		$this->mergeMwGlobalArrayValue( 'wgGroupPermissions', array(
			'user' => array( 'item-merge' => true, 'item-redirect' => true ),
		) );

		if ( !isset( self::$hasSetup ) ) {
			$this->initTestEntities( array( 'Empty', 'Leipzig', 'Osaka' ) );
		}

		self::$hasSetup = true;
	}

	public function provideData() {
		return array(
			array( //0
				'p' => array( 'handle' => 'Empty', 'bot' => '', 'action' => 'wbsetlabel',
					'language' => 'en', 'value' => 'ALabel' ),
				'e' => array( 'bot' => true, 'new' => false ) ),
			array( //1
				'p' => array( 'handle' => 'Empty', 'action' => 'wbsetlabel', 'language' => 'en',
					'value' => 'ALabel2' ),
				'e' => array( 'bot' => false, 'new' => false ) ),
			array( //2
				'p' => array( 'handle' => 'Empty', 'bot' => '', 'action' => 'wbsetdescription',
					'language' => 'de', 'value' => 'ADesc' ),
				'e' => array( 'bot' => true, 'new' => false ) ),
			array( //3
				'p' => array( 'handle' => 'Empty', 'action' => 'wbsetdescription',
					'language' => 'de', 'value' => 'ADesc2' ),
				'e' => array( 'bot' => false, 'new' => false ) ),
			array( //4
				'p' => array( 'handle' => 'Empty', 'bot' => '', 'action' => 'wbsetaliases',
					'language' => 'de', 'set' => 'ali1' ),
				'e' => array( 'bot' => true, 'new' => false ) ),
			array( //5
				'p' => array( 'handle' => 'Empty', 'action' => 'wbsetaliases', 'language' => 'de',
					'set' => 'ali2' ),
				'e' => array( 'bot' => false, 'new' => false ) ),
			array( //6
				'p' => array( 'handle' => 'Empty', 'bot' => '', 'action' => 'wbsetsitelink',
					'linksite' => 'enwiki', 'linktitle' => 'PageEn' ),
				'e' => array( 'bot' => true, 'new' => false ) ),
			array( //7
				'p' => array( 'handle' => 'Empty', 'action' => 'wbsetsitelink',
					'linksite' => 'dewiki', 'linktitle' => 'PageDe' ),
				'e' => array( 'bot' => false, 'new' => false ) ),
			array( //8
				'p' => array( 'bot' => '', 'action' => 'wblinktitles', 'tosite' => 'enwiki',
					'totitle' => 'PageEn', 'fromsite' => 'svwiki', 'fromtitle' => 'SvPage' ),
				'e' => array( 'bot' => true, 'new' => false ) ),
			array( //9
				'p' => array( 'action' => 'wblinktitles', 'tosite' => 'dewiki',
					'totitle' => 'PageDe', 'fromsite' => 'nowiki', 'fromtitle' => 'NoPage' ),
				'e' => array( 'bot' => false, 'new' => false ) ),
			array( //10
				'p' => array( 'bot' => '', 'action' => 'wbeditentity', 'new' => 'item',
					'data' => '{}' ),
				'e' => array( 'bot' => true, 'new' => true ) ),
			array( //11
				'p' => array( 'action' => 'wbeditentity', 'new' => 'item', 'data' => '{}' ),
				'e' => array( 'bot' => false, 'new' => true ) ),
			array( //12
				'p' => array( 'action' => 'wbmergeitems', 'fromid' => 'Osaka', 'toid' => 'Empty',
					'bot' => '' ),
				'e' => array( 'bot' => true, 'new' => false ) ),
			array( //13
				'p' => array( 'action' => 'wbmergeitems', 'fromid' => 'Leipzig', 'toid' => 'Empty',
					'ignoreconflicts' => 'description' ),
				'e' => array( 'bot' => false, 'new' => false ) ),
			// TODO: Claims, references, qualifiers.
		);
	}

	/**
	 * @dataProvider provideData
	 */
	public function testBotEdits( $params, $expected ) {
		$this->doLogin( 'wbbot' );

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
		$rcRequest = array(
			'action' => 'query',
			'list' => 'recentchanges',
			'rcprop' => 'title|flags',
			'rctoponly' => '1',
			'rclimit' => 5, // hope that no more than 50 edits where made in the last second
		);

		//@todo this really makes this test slow, is there a better way?
		$rcResult = $this->doApiRequest( $rcRequest, null, false, self::$users['wbbot']->getUser() );

		// -- check the recent changes result ---------------------------------
		$this->assertArrayHasKey( 'query', $rcResult[0], "Must have a 'query' key in the result from the API" );
		$this->assertArrayHasKey( 'recentchanges', $rcResult[0]['query'],
			"Must have a 'recentchanges' key in 'query' subset of the result from the API" );

		//NOTE: the order of the entries in recentchanges is undefined if multiple
		//      edits were done in the same second.
		$change = null;

		$entityNamespaceLookup = WikibaseRepo::getDefaultInstance()->getEntityNamespaceLookup();
		$itemNs = $entityNamespaceLookup->getEntityNamespace( 'item' );

		foreach ( $rcResult[0]['query']['recentchanges'] as $rc ) {
			$title = Title::newFromText( $rc['title'] );
			// XXX: strtoupper is a bit arcane, would ne nice to have a utility function for prefixed id -> title.
			if ( ( $title->getNamespace() == $itemNs ) && ( $title->getText() === strtoupper( $myid ) ) ) {
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
