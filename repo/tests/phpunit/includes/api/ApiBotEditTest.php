<?php

namespace Wikibase\Test;
use ApiTestCase, TestUser;
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
 * @group ApiBotEditTest
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
class ApiBotEditTest extends ApiModifyItemBase {

	protected static $baseOfItemIds = 1;
	protected static $usepost;
	protected static $usetoken;
	protected static $userights;

	public function setUp() {
		global $wgUser;
		parent::setUp();

		static $hasSites = false;

		if ( !$hasSites ) {
			\TestSites::insertIntoDb();
			$hasSites = true;
		}

		self::$usepost = Settings::get( 'apiInDebug' ) ? Settings::get( 'apiDebugWithPost' ) : true;
		self::$usetoken = Settings::get( 'apiInDebug' ) ? Settings::get( 'apiDebugWithTokens' ) : true;
		self::$userights = Settings::get( 'apiInDebug' ) ? Settings::get( 'apiDebugWithRights' ) : true;

		ApiTestCase::$users['wbbot'] = new TestUser(
			'Apitestbot',
			'Api Test Bot',
			'api_test_bot@example.com',
			array( 'bot' )
		);
		$wgUser = self::$users['wbbot']->user;

		$this->login( 'wbbot' );
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
	 */
	function testSetItemTop() {
		$token = $this->getItemToken();

		$req = array(
			'action' => 'wbsetitem',
			'summary' => 'Some reason',
			'data' => '{}',
			'token' => $token,
		);

		$second = $this->doApiRequest( $req, null, false, self::$users['wbbot']->user );

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
	 * @depends testTokensAndRights
	 * @dataProvider providerCreateItem
	 */
	function testCreateItem( $id, $bot, $new, $data ) {
		$myid = self::$baseOfItemIds + $id;
		$token = $this->getItemToken();

		$req = array(
			'action' => 'wbsetitem',
			'summary' => 'Some reason',
			'data' => $data,
			'token' => $token,
		);

		if ( !$new ) {
			$req['id'] = $myid;
		}
		if ( $bot ) {
			$req['bot'] = true;
		}

		$second = $this->doApiRequest( $req, null, false, self::$users['wbbot']->user );

		$this->assertArrayHasKey( 'success', $second[0],
			"Must have an 'success' key in the second result from the API" );
		$this->assertArrayHasKey( 'item', $second[0],
			"Must have an 'item' key in the second result from the API" );
		$this->assertArrayHasKey( 'id', $second[0]['item'],
			"Must have an 'id' key in the 'item' from the second result from the API" );
		$this->assertEquals( $myid, $second[0]['item']['id'],
			"Must have the value '{$myid}' for the 'id' in the result from the API" );

		$req = array(
				'action' => 'query',
				'list' => 'recentchanges',
				'rcprop' => 'title|flags'
		);
		$third = $this->doApiRequest( $req, null, false, self::$users['wbbot']->user );

		$this->assertArrayHasKey( 'query', $third[0],
			"Must have a 'query' key in the third result from the API" );
		$this->assertArrayHasKey( 'recentchanges', $third[0]['query'],
			"Must have a 'recentchanges' key in 'query' subset of the third result from the API" );
		$this->assertArrayHasKey( '0', $third[0]['query']['recentchanges'],
			"Must have a '0' key in 'recentchanges' subset of the third result from the API" );
		$this->assertTrue( $new == array_key_exists( 'new', $third[0]['query']['recentchanges']['0'] ),
			"Must" . ( $new ? '' : ' not ' ) . "have a 'new' key in the rc-entry of the third result from the API" );
		$this->assertTrue( $bot == array_key_exists( 'bot', $third[0]['query']['recentchanges']['0'] ),
			"Must" . ( $bot ? '' : ' not ' ) . "have a 'bot' key in the rc-entry of the third result from the API" );
	}

	function providerCreateItem() {
		$idx = 0;
		return array(
			array( ++$idx, false, true, "{}" ),
			array( $idx, true, false, '{ "labels": { "nn": { "language": "nn", "value": "Karasjok" } } }' ),
			array( $idx, false, false, '{ "descriptions": { "nn": { "language": "nn", "value": "Small place in Finnmark" } } }' ),
			array( ++$idx, true, true, "{}" ),
			array( $idx, true, false, '{ "labels": { "nn": { "language": "nn", "value": "Kautokeino" } } }' ),
			array( $idx, false, false, '{ "descriptions": { "nn": { "language": "nn", "value": "Small place in Finnmark" } } }' ),
		);
	}

}