<?php

namespace Wikibase\Test;
use ApiTestCase, ApiTestUser;
use Wikibase\Settings as Settings;

/**
 * Tests for setting sitelinks throug from-to -pairs.
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
 * @group ApiLinkTitlesTest
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
class ApiLinkTitlesTest extends ApiTestCase {

	protected static $baseOfItemIds = 0;
	protected static $usepost;
	protected static $usetoken;
	protected static $userights;
	protected static $data;
	protected static $edittoken;

	function setUp() {
		parent::setUp();
		$this->doLogin();

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
	}

	/**
	 * @group API
	 */
	function testEdit() {
		$data = $this->doApiRequest(
			array(
				'action' => 'login',
				'lgname' => self::$users['wbeditor']->username,
				'lgpassword' => self::$users['wbeditor']->password
			),
			null,
			false,
			self::$users['wbeditor']->user
		);

		$this->doApiRequest(
			array(
				'action' => 'login',
				'lgtoken' => $data[0]['login']['token'],
				'lgname' => self::$users['wbeditor']->username,
				'lgpassword' => self::$users['wbeditor']->password
			),
			null,
			false,
			self::$users['wbeditor']->user
		);

		$data = $this->doApiRequest(
			array(
				'action' => 'query',
				'titles' => 'Main Page',
				'intoken' => 'edit',
				'prop' => 'info'
			),
			null,
			false,
			self::$users['wbeditor']->user
		);

		$pages = array_values( $data[0]['query']['pages'] );
		$pageinfo = array_pop( $pages );

		$this->assertEquals(
			34, strlen( $pageinfo["edittoken"] ),
			"The length of the token is not 34 chars"
		);
		$this->assertRegExp(
			'/\+\\\\$/', $pageinfo["edittoken"],
			"The final chars of the token is not '+\\'"
		);

		// In the following remember that there will be no normalization and no verification
		// that the pages actually exist, but there will be check on the existence of valid
		// site ids.

		$data = $this->doApiRequest(
			array(
				'action' => 'wblinktitles',
				'fromtitle' => 'Hamar', // Note that fromtitle and fromsite is used here
				'fromsite' => 'enwiki',
				'totitle' => 'Hamar',
				'tosite' => 'dewiki',
				'token' => $pageinfo["edittoken"] // should use itemtoken, but so far they are the same
			),
			null,
			false,
			self::$users['wbeditor']->user
		);

		$this->assertArrayHasKey( 'item', $data[0], "Check that there is an item" );
		$this->assertArrayHasKey( 'sitelinks', $data[0]['item'], "Check that there are sitelinks in the item" );

		// Checks for existence of the two sites, and that because they are missing there will be created an item
		$count = 2;
		$checks = array(
			'enwiki' => 'Hamar',
			'dewiki' => 'Hamar',
		);
		foreach ( $data[0]['item']['sitelinks'] as $k => $v ) {
			if ( isset( $checks[$data[0]['item']['sitelinks'][$k]['site']] )
				&& $checks[$data[0]['item']['sitelinks'][$k]['site']] === $data[0]['item']['sitelinks'][$k]['title'] ) {
				$count--;
			}
		}

		$this->assertEquals( 0, $count, "Number of checks doesn't decrement to zero" );

		// This call throws an error due to the fromsite value, which is already used in the first API call
		// or rather its an error on get_class() because its expects parameter 1 to be object, and a boolean
		// is given. Check line 63 of ApiLinkTitles.php
		$data = $this->doApiRequest(
			array(
				'action' => 'wblinktitles',
				'fromtitle' => 'Hamar', // Note that fromtitle and fromsite is also used here
				'fromsite' => 'enwiki',
				'totitle' => 'Hamar',
				'tosite' => 'nlwiki',
				'token' => $pageinfo["edittoken"] // should use itemtoken, but so far they are the same
			),
			null,
			false,
			self::$users['wbeditor']->user
		);
		print_r($data[0]);

		return;
	}
}