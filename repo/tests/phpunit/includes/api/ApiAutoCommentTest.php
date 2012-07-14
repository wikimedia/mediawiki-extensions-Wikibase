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
class ApiAutoCommentTest extends \ApiTestCase {

	protected static $id;
	protected static $token;
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

		$req = array(
				'action' => 'wbsetitem',
				'data' => '{}',
			);

		$data = $this->doApiRequest(
			array_merge(
				$req,
				array( 'gettoken' => true )
			),
			null,
			false,
			self::$users['wbeditor']->user
		);

		self::$token = $data[0]['wbsetitem']['itemtoken'];
		if ( isset( self::$id ) ) {
			return;
		}

		$data = $this->doApiRequest(
			array_merge(
				$req,
				array( 'token' => self::$token )
			),
			null,
			false,
			self::$users['wbeditor']->user
		);

		self::$id = $data[0]['item']['id'];
	}

	/**
	 * @dataProvider modifyProvider
	 */
	function testModifyItem( $args, $comment, $parsed ) {
		$req = array(
			'token' => self::$token,
			'id' => self::$id
		);

		$data = $this->doApiRequest(
			array_merge(
				$req,
				$args
			),
			null,
			false,
			self::$users['wbeditor']->user
		);

		$this->assertArrayHasKey( 'item', $data[0], "item not found" );
		$this->assertArrayHasKey( 'id', $data[0]['item'], "id not found" );

		$req2 = array(
			'action' => 'query',
			'list' => 'recentchanges',
			'rcprop' => 'comment|parsedcomment',
			'rctype' => 'edit'
		);

		$data = $this->doApiRequest(
			$req2,
			null,
			false,
			self::$users['wbeditor']->user
		);

		//print_r($data[0]);
		$this->assertEquals( $comment, $data[0]['query']['recentchanges'][0]['comment'], "comment does not match" );
		$this->assertRegExp( $parsed, $data[0]['query']['recentchanges'][0]['parsedcomment'], "parsed comment does not match" );

	}

	public function modifyProvider() {
		return array(
			// args - expected
			array(
				array(
					'action' => 'wbsetlanguageattribute',
					'label' => 'foobar',
					'language' => 'en'
				),
				'/* set-language-label:en|1 */ foobar',
				'/<span.*?><span.*?class="autocomment".*?>.*?<\/span>\s*foobar\s*<\/span>/'
			),
			array(
				array(
					'action' => 'wbsetlanguageattribute',
					'label' => '',
					'language' => 'en'
				),
				'/* remove-language-label:en|0 */',
				'/<span.*?><span.*?class="autocomment".*?>.*?<\/span>\s*<\/span>/'
			),
			array(
				array(
					'action' => 'wbsetlanguageattribute',
					'description' => 'foobar',
					'language' => 'en'
				),
				'/* set-language-description:en|1 */ foobar',
				'/<span.*?><span.*?class="autocomment".*?>.*?<\/span>\s*foobar\s*<\/span>/'
			),
			array(
				array(
					'action' => 'wbsetlanguageattribute',
					'description' => '',
					'language' => 'en'
				),
				'/* remove-language-description:en|0 */',
				'/<span.*?><span.*?class="autocomment".*?>.*?<\/span>\s*<\/span>/'
			),
			array(
				array(
					'action' => 'wbsetsitelink',
					'linksite' => 'enwiki',
					'linktitle' => 'Manchester'
				),
				'/* set-sitelink:enwiki|1 */ Manchester',
				'/<span.*?><span.*?class="autocomment".*?>.*?<\/span>\s*Manchester\s*<\/span>/'
			),
			array(
				array(
					'action' => 'wbsetsitelink',
					'linksite' => 'enwiki',
					'linktitle' => ''
				),
				'/* remove-sitelink:enwiki|0 */',
				'/<span.*?><span.*?class="autocomment".*?>.*?<\/span>\s*<\/span>/'
			),
			array(
				array(
					'action' => 'wbsetaliases',
					'language' => 'en',
					'set' => 'Foo|Bar'
				),
				'/* set-aliases:en|2 */ Foo, Bar',
				'/<span.*?><span.*?class="autocomment".*?>.*?<\/span>\s*Foo,\s*Bar\s*<\/span>/'
			),
			array(
				array(
					'action' => 'wbsetaliases',
					'language' => 'en',
					'add' => 'Test'
				),
				'/* add-aliases:en|1 */ Test',
				'/<span.*?><span.*?class="autocomment".*?>.*?<\/span>\s*Test\s*<\/span>/'
			),
			array(
				array(
					'action' => 'wbsetaliases',
					'language' => 'en',
					'add' => 'Test'
				),
				'/* add-aliases:en|1 */ Test',
				'/<span.*?><span.*?class="autocomment".*?>.*?<\/span>\s*Test\s*<\/span>/'
			),
			array(
				array(
					'action' => 'wbsetaliases',
					'language' => 'en',
					'remove' => 'Test'
				),
				'/* remove-aliases:en|1 */ Test',
				'/<span.*?><span.*?class="autocomment".*?>.*?<\/span>\s*Test\s*<\/span>/'
			),
			// FIXME: There are no test for wbsetitem as it is not clear how the autocommit should work for this module
		);
	}
}