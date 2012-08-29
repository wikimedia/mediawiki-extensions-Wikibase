<?php

namespace Wikibase\Test;
use ApiTestCase, ApiTestUser;
use Wikibase\Settings as Settings;

/**
 * Tests for blocking of direct editing.
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
 * @group ApiEditPageTest
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
class ApiEditPageTest extends ApiTestCase {

	protected static $baseOfItemIds = 0;
	protected static $usepost;
	protected static $usetoken;
	protected static $userights;
	protected static $data;
	protected static $edittoken;

	function setUp() {
		parent::setUp();
		$this->doLogin();

		\TestSites::insertIntoDb();

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
			self::$users['sysop']->user
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

		$data = $this->doApiRequest(
			array(
				'action' => 'edit',
				'title' => 'Main Page',
				'text' => 'new text',
				'token' => $pageinfo["edittoken"]
			),
			null,
			false,
			self::$users['sysop']->user
		);

		$this->assertArrayHasKey( 'edit', $data[0], "Check whatever" );
		$this->assertArrayHasKey( 'result', $data[0]['edit'], "Check result" );
		$this->assertEquals( 'Success', $data[0]['edit']['result'], "Check success report" );

		return $data;
	}
}