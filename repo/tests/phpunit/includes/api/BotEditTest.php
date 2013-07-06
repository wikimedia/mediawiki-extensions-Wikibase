<?php

namespace Wikibase\Test\Api;
use ApiTestCase, TestUser, Title;
use Wikibase\Settings;
use Wikibase\NamespaceUtils;

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
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 * @since 0.1
 *
 * @ingroup WikibaseRepoTest
 * @ingroup Test
 *
 * @licence GNU GPL v2+
 * @author John Erling Blad < jeblad@gmail.com >
 * @author Daniel Kinzler < daniel.kinzler@wikimedia.de >
 * @author Anja Jentzsch < anja.jentzsch@wikimedia.de >
 *
 * @group API
 * @group Wikibase
 * @group WikibaseAPI
 * @group BotEditTest
 * @group BreakingTheSlownessBarrier
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
class BotEditTest extends ModifyEntityTestBase {

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
		if ( !self::$usetoken || !self::$userights ) {
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
		$token = $this->getEditToken();

		$req = array(
			'action' => 'wbeditentity',
			'summary' => 'Some reason',
			'data' => '{}',
			'token' => $token,
			'new' => 'item',
		);

		$second = $this->doApiRequest( $req, null, false, self::$users['wbbot']->user );

		$this->assertArrayHasKey( 'success', $second[0],
			"Must have an 'success' key in the second result from the API" );
		$this->assertArrayHasKey( 'entity', $second[0],
			"Must have an 'entity' key in the second result from the API" );
		$this->assertArrayHasKey( 'id', $second[0]['entity'],
			"Must have an 'id' key in the 'entity' from the second result from the API" );
		self::$baseOfItemIds = preg_replace( '/^[^\d]+/', '', $second[0]['entity']['id'] );
	}

	/**
	 * @group API
	 * @depends testTokensAndRights
	 * @dataProvider providerCreateItem
	 */
	function testCreateItem( $handle, $bot, $new, $data ) {
		$token = $this->getEditToken();
		$myid = null;

		$req = array(
			'action' => 'wbeditentity',
			'summary' => 'Some reason',
			'data' => $data,
			'token' => $token,
		);

		if ( !$new ) {
			$myid = $this->getEntityId( $handle );
			$req['id'] = $myid;
		} else {
			$req['new'] = 'item';
		}
		if ( $bot ) {
			$req['bot'] = true;
		}

		$second = $this->doApiRequest( $req, null, false, self::$users['wbbot']->user );

		$this->assertArrayHasKey( 'success', $second[0],
			"Must have an 'success' key in the second result from the API" );
		$this->assertArrayHasKey( 'entity', $second[0],
			"Must have an 'entity' key in the second result from the API" );
		$this->assertArrayHasKey( 'id', $second[0]['entity'],
			"Must have an 'id' key in the 'entity' from the second result from the API" );

		if ( $myid ) {
			$this->assertEquals( $myid, $second[0]['entity']['id'],
				"Must have the value '{$myid}' for the 'id' in the result from the API" );
		}

		if ( $new ) {
			// register new object for use by subsequent test cases
			$this->createEntities(); // make sure self::$entityOutput is initialized first.
			self::$entityOutput[$handle] = $second[0]['entity'];
			$myid = $second[0]['entity']['id'];
		}

		$req = array(
				'action' => 'query',
				'list' => 'recentchanges',
				'rcprop' => 'title|flags',
				'rctoponly' => '1',
				'rclimit' => 50, // hope that no more than 50 edits where made in the last second
		);
		$third = $this->doApiRequest( $req, null, false, self::$users['wbbot']->user );

		$this->assertArrayHasKey( 'query', $third[0],
			"Must have a 'query' key in the result from the API" );
		$this->assertArrayHasKey( 'recentchanges', $third[0]['query'],
			"Must have a 'recentchanges' key in 'query' subset of the result from the API" );

		//NOTE: the order of the entries in recentchanges is undefined if multiple
		//      edits were done in the same second.
		$change = null;
		$itemNs = NamespaceUtils::getEntityNamespace( CONTENT_MODEL_WIKIBASE_ITEM );
		foreach ( $third[0]['query']['recentchanges'] as $rc ) {
			$title = Title::newFromText( $rc['title'] );
			// XXX: strtoupper is a bit arcane, would ne nice to have a utility function for prefixed id -> title.
			if ( ( $title->getNamespace() == $itemNs ) && ( $title->getText() === strtoupper( $myid ) ) ) {
				$change = $rc;
				break;
			}
		}

		$this->assertNotNull( $change, 'no change matching ID ' . $myid . ' found in recentchanges feed!' );

		$this->assertTrue( $new == array_key_exists( 'new', $change ),
			"Must" . ( $new ? '' : ' not ' ) . "have a 'new' key in the rc-entry of the result from the API" );
		$this->assertTrue( $bot == array_key_exists( 'bot', $change ),
			"Must" . ( $bot ? '' : ' not ' ) . "have a 'bot' key in the rc-entry of the result from the API" );
	}

	function providerCreateItem() {
		return array(
			array( 'One', false, true, "{}" ),
			array( 'One', true, false, '{ "labels": { "nn": { "language": "nn", "value": "Karasjok" } } }' ),
			array( 'One', false, false, '{ "descriptions": { "nn": { "language": "nn", "value": "Small place in Finnmark" } } }' ),
			array( 'Two', true, true, "{}" ),
			array( 'Two', true, false, '{ "labels": { "nn": { "language": "nn", "value": "Kautokeino" } } }' ),
			array( 'Two', false, false, '{ "descriptions": { "nn": { "language": "nn", "value": "Small place in Finnmark" } } }' ),
		);
	}

}
