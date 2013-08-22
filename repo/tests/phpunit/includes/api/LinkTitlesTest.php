<?php

namespace Wikibase\Test\Api;

use ApiTestCase;

/**
 * Tests for setting sitelinks throug from-to-pairs.
 *
 * @file
 * @since 0.1
 *
 * @ingroup WikibaseRepoTest
 * @ingroup Test
 *
 * @licence GNU GPL v2+
 * @author John Erling Blad < jeblad@gmail.com >
 * @author Adam Shorland
 *
 * @group API
 * @group Wikibase
 * @group WikibaseAPI
 * @group LinkTitlesTest
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
class LinkTitlesTest extends WikibaseApiTestCase {

	private static $hasSetup;

	public function setUp() {
		parent::setUp();

		if( !isset( self::$hasSetup ) ){
			$this->initTestEntities( array( 'Oslo', 'Berlin' ) );
		}
		self::$hasSetup = true;
	}

	public static function provideLinkTitles() {
		return array(
			array( //0 add nowiki as fromsite
				'p' => array( 'tosite' => 'nnwiki', 'totitle' => 'Oslo', 'fromsite' => 'nowiki', 'fromtitle' => 'Oslo'),
				'e' => array( 'inresult' => 1 ) ),
			array( //1 add svwiki as tosite
				'p' => array( 'tosite' => 'svwiki', 'totitle' => 'Oslo', 'fromsite' => 'nowiki', 'fromtitle' => 'Oslo' ),
				'e' => array( 'inresult' => 1 ) ),
			array( //2 Create a link between 2 new pages
				'p' => array( 'tosite' => 'svwiki', 'totitle' => 'NewTitle', 'fromsite' => 'nowiki', 'fromtitle' => 'NewTitle' ),
				'e' => array( 'inresult' => 2 ) ),
			array( //4 Create a link between 2 new pages
				'p' => array( 'tosite' => 'svwiki', 'totitle' => 'ATitle', 'fromsite' => 'nowiki', 'fromtitle' => 'ATitle' ),
				'e' => array( 'inresult' => 2 ) ),
		);
	}

	/**
	 * @dataProvider provideLinkTitles
	 */
	public function testLinkTitles( $params, $expected ) {
		// -- set any defaults ------------------------------------
		$params['action'] = 'wblinktitles';

		// -- do the request --------------------------------------------------
		list( $result,, ) = $this->doApiRequestWithToken( $params );

		// -- check the result ------------------------------------------------
		$this->assertArrayHasKey( 'success', $result, "Missing 'success' marker in response." );
		$this->assertResultHasEntityType( $result );
		$this->assertArrayHasKey( 'entity', $result, "Missing 'entity' section in response." );
		$this->assertArrayHasKey( 'lastrevid', $result['entity'] , 'entity should contain lastrevid key' );

		$this->assertEquals( $expected['inresult'] , count( $result['entity']['sitelinks'] ), "Result has wrong number of sitelinks" );
		foreach ( $result['entity']['sitelinks'] as $link ) {
			$this->assertTrue( $params['fromsite'] === $link['site'] || $params['tosite'] === $link['site'] );
			$this->assertTrue( $params['fromtitle'] === $link['title'] || $params['totitle'] === $link['title'] );
		}

		// check the item in the database -------------------------------
		if ( array_key_exists( 'id', $result['entity'] )  ) {
			$item = $this->loadEntity( 'q'.$result['entity']['id'] );
			$links = self::flattenArray( $item['sitelinks'], 'site', 'title' );
			$this->assertEquals( $params['fromtitle'], $links[ $params['fromsite'] ], 'wrong link target' );
			$this->assertEquals( $params['totitle'], $links[ $params['tosite'] ], 'wrong link target' );
		}

		// -- check the edit summary --------------------------------------------
		if( array_key_exists( 'summary', $params) ){
			$this->assertRevisionSummary( "/{$params['summary']}/" , $result['entity']['lastrevid'] );
		}
	}

	public static function provideLinkTitleExceptions(){
		return array(
			array( //0 badtoken
				'p' => array( 'tosite' => 'nnwiki', 'totitle' => 'Oslo', 'fromsite' => 'nowiki', 'fromtitle' => 'AnotherPage' ),
				'e' => array( 'exception' => array( 'type' => 'UsageException', 'code' => 'badtoken', 'message' => 'loss of session data' ) ) ),
			array( //1 add two links already exist together
				'p' => array( 'tosite' => 'nnwiki', 'totitle' => 'Oslo', 'fromsite' => 'nowiki', 'fromtitle' => 'Oslo' ),
				'e' => array( 'exception' => array( 'type' => 'UsageException', 'code' => 'common-item') ) ),
			array( //2 add two links already exist together
				'p' => array( 'tosite' => 'dewiki', 'totitle' => 'Berlin', 'fromsite' => 'nlwiki', 'fromtitle' => 'Oslo' ),
				'e' => array( 'exception' => array( 'type' => 'UsageException', 'code' => 'no-common-item') ) ),
			array( //3 add two links from the same site
				'p' => array( 'tosite' => 'nnwiki', 'totitle' => 'Hammerfest', 'fromsite' => 'nnwiki', 'fromtitle' => 'Hammerfest' ),
				'e' => array( 'exception' => array( 'type' => 'UsageException', 'code' => 'param-illegal') ) ),
			array( //4 missing title
				'p' => array( 'tosite' => 'nnwiki', 'totitle' => '', 'fromsite' => 'dewiki', 'fromtitle' => 'Hammerfest' ),
				'e' => array( 'exception' => array( 'type' => 'UsageException', 'code' => 'param-illegal') ) ),
			array( //5 bad tosite
				'p' => array( 'tosite' => 'qwerty', 'totitle' => 'Hammerfest', 'fromsite' => 'nnwiki', 'fromtitle' => 'Hammerfest' ),
				'e' => array( 'exception' => array( 'type' => 'UsageException', 'code' => 'unknown_tosite') ) ),
			array( //6 bad fromsite
				'p' => array( 'tosite' => 'nnwiki', 'totitle' => 'Hammerfest', 'fromsite' => 'qwerty', 'fromtitle' => 'Hammerfest' ),
				'e' => array( 'exception' => array( 'type' => 'UsageException', 'code' => 'unknown_fromsite') ) ),
			array( //7 missing site
				'p' => array( 'tosite' => 'nnwiki', 'totitle' => 'APage', 'fromsite' => '', 'fromtitle' => 'Hammerfest' ),
				'e' => array( 'exception' => array( 'type' => 'UsageException', 'code' => 'unknown_fromsite') ) ),
		);
	}

	/**
	 * @dataProvider provideLinkTitleExceptions
	 */
	public function testLinkTitlesExceptions( $params, $expected ){
		// -- set any defaults ------------------------------------
		$params['action'] = 'wblinktitles';
		$this->doTestQueryExceptions( $params, $expected['exception'] );
	}

}
