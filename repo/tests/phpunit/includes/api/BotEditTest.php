<?php

namespace Wikibase\Test\Api;

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
 * @licence GNU GPL v2+
 *
 * @author John Erling Blad < jeblad@gmail.com >
 * @author Daniel Kinzler < daniel.kinzler@wikimedia.de >
 * @author Anja Jentzsch < anja.jentzsch@wikimedia.de >
 * @author Adam Shorland
 */
class BotEditTest extends WikibaseApiTestCase {

	private static $hasSetup;

	/**
	 * @var TestUser
	 */
	private static $wbBotUser;

	public function setUp() {
		parent::setUp();

		if ( !isset( self::$wbBotUser ) ) {
			self::$wbBotUser = new TestUser(
				'Apitestbot',
				'Api Test Bot',
				'api_test_bot@example.com',
				array( 'bot' )
			);
		}

		ApiTestCase::$users['wbbot'] = self::$wbBotUser;

		if( !isset( self::$hasSetup ) ){
			$this->initTestEntities( array( 'Empty' ) );
		}

		self::$hasSetup = true;
	}

	public static function provideData() {
		return array(
			array(//0
				'p' => array( 'handle' => 'Empty', 'bot' => '', 'action' => 'wbsetlabel', 'language' => 'en', 'value' => 'ALabel' ),
				'e' => array( 'bot' => true ) ),
			array(//1
				'p' => array( 'handle' => 'Empty', 'action' => 'wbsetlabel', 'language' => 'en', 'value' => 'ALabel2' ),
				'e' => array( 'bot' => false ) ),
			array(//2
				'p' => array( 'handle' => 'Empty', 'bot' => '', 'action' => 'wbsetdescription', 'language' => 'de', 'value' => 'ADesc' ),
				'e' => array( 'bot' => true ) ),
			array(//3
				'p' => array( 'handle' => 'Empty', 'action' => 'wbsetdescription', 'language' => 'de', 'value' => 'ADesc2' ),
				'e' => array( 'bot' => false ) ),
			array(//4
				'p' => array( 'handle' => 'Empty', 'bot' => '', 'action' => 'wbsetaliases', 'language' => 'de', 'set' => 'ali1' ),
				'e' => array( 'bot' => true ) ),
			array(//5
				'p' => array( 'handle' => 'Empty', 'action' => 'wbsetaliases', 'language' => 'de', 'set' => 'ali2' ),
				'e' => array( 'bot' => false ) ),
			array(//6
				'p' => array( 'handle' => 'Empty', 'bot' => '', 'action' => 'wbsetsitelink', 'linksite' => 'enwiki', 'linktitle' => 'PageEn' ),
				'e' => array( 'bot' => true ) ),
			array(//7
				'p' => array( 'handle' => 'Empty', 'action' => 'wbsetsitelink', 'linksite' => 'dewiki', 'linktitle' => 'PageDe' ),
				'e' => array( 'bot' => false ) ),
			array(//8
				'p' => array( 'bot' => '', 'action' => 'wblinktitles', 'tosite' => 'enwiki', 'totitle' => 'PageEn', 'fromsite' => 'svwiki', 'fromtitle' => 'SvPage' ),
				'e' => array( 'bot' => true ) ),
			array(//9
				'p' => array( 'action' => 'wblinktitles', 'tosite' => 'dewiki', 'totitle' => 'PageDe', 'fromsite' => 'nowiki', 'fromtitle' => 'NoPage' ),
				'e' => array( 'bot' => false ) ),
			array(//10
				'p' => array( 'bot' => '', 'action' => 'wbeditentity', 'new' => 'item', 'data' => '{}' ),
				'e' => array( 'bot' => true, 'new' => true ) ),
			array(//11
				'p' => array( 'action' => 'wbeditentity', 'new' => 'item', 'data' => '{}' ),
				'e' => array( 'bot' => false, 'new' => true ) ),
			//todo claims, references, qualifiers
		);
	}

	/**
	 * @dataProvider provideData
	 */
	public function testBotEdits( $params, $expected ) {
		$this->login( 'wbbot' );

		// -- do the request --------------------------------------------------
		if( array_key_exists( 'handle', $params ) ){
			$params['id'] = EntityTestHelper::getId( $params['handle'] );
			unset( $params['handle'] );
		}
		list( $result,, ) = $this->doApiRequestWithToken( $params, null, self::$users['wbbot']->user );

		// -- check the result ------------------------------------------------
		$this->assertArrayHasKey( 'success', $result, "Missing 'success' marker in response." );
		$this->assertResultHasEntityType( $result );
		$this->assertArrayHasKey( 'entity', $result, "Missing 'entity' section in response." );
		$this->assertArrayHasKey( 'lastrevid', $result['entity'] , 'entity should contain lastrevid key' );
		$myid = $result['entity']['id'];

		// -- get the recentchanges -------------------------------------------
		$rcRequest = array(
			'action' => 'query',
			'list' => 'recentchanges',
			'rcprop' => 'title|flags',
			'rctoponly' => '1',
			'rclimit' => 5, // hope that no more than 50 edits where made in the last second
		);

		//@todo this really makes this test slow, is there a better way?
		$rcResult = $this->doApiRequest( $rcRequest, null, false, self::$users['wbbot']->user );

		// -- check the recent changes result ---------------------------------
		$this->assertArrayHasKey( 'query', $rcResult[0], "Must have a 'query' key in the result from the API" );
		$this->assertArrayHasKey( 'recentchanges', $rcResult[0]['query'],
			"Must have a 'recentchanges' key in 'query' subset of the result from the API" );

		//NOTE: the order of the entries in recentchanges is undefined if multiple
		//      edits were done in the same second.
		$change = null;

		$entityNamespaceLookup = WikibaseRepo::getDefaultInstance()->getEntityNamespaceLookup();
		$itemNs = $entityNamespaceLookup->getEntityNamespace( CONTENT_MODEL_WIKIBASE_ITEM );

		foreach ( $rcResult[0]['query']['recentchanges'] as $rc ) {
			$title = Title::newFromText( $rc['title'] );
			// XXX: strtoupper is a bit arcane, would ne nice to have a utility function for prefixed id -> title.
			if ( ( $title->getNamespace() == $itemNs ) && ( $title->getText() === strtoupper( $myid ) ) ) {
				$change = $rc;
				break;
			}
		}

		$this->assertNotNull( $change, 'no change matching ID ' . $myid . ' found in recentchanges feed!' );

		if( array_key_exists( 'new', $expected ) ){
			$this->assertTrue( $expected['new'] == array_key_exists( 'new', $change ),
				"Must" . ( $expected['new'] ? '' : ' not ' ) . "have a 'new' key in the rc-entry of the result from the API" );
		}
		if( array_key_exists( 'bot', $expected ) ){
			$this->assertTrue( $expected['bot'] == array_key_exists( 'bot', $change ),
				"Must" . ( $expected['bot'] ? '' : ' not ' ) . "have a 'bot' key in the rc-entry of the result from the API" );
		}
	}

}
