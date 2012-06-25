<?php

namespace Wikibase\Test;
use ApiTestCase;
use Wikibase\ApiLinkSite;

/**
 * Additional tests for ApiLinkSite API module.
 *
 * The tests are using "Database" to get its own set of temporal tables.
 * This is nice so we avoid poisoning an existing database.
 *
 * @group API
 *
 * @file
 * @since 0.1
 *
 * @ingroup Wikibase
 * @ingroup Test
 *
 * The database group has as a side effect that temporal database tables are created. This makes
 * it possible to test without poisoning a production database.
 * @group Database
 *
 * Some of the tests takes more time, and needs therefor longer time before they can be aborted
 * as non-functional. The reason why tests are aborted is assumed to be set up of temporal databases
 * that hold the first tests in a pending state awaiting access to the database.
 * @group medium
 *
 * @group Wikibase
 * @group WikibaseAPI
 *
 * @licence GNU GPL v2+
 * @author JOhn Erling Blad < jeblad@gmail.com >
 */
class ApiLinkSiteTest extends ApiTestCase {

	public static $jsonData;

	function setUp() {
		global $wgUser;
		parent::setUp();

		$dbw = wfGetDB( DB_MASTER );
		$dbw->query( 'TRUNCATE TABLE ' . $dbw->tableName( 'sites' ) );
		\Wikibase\Utils::insertSitesForTests();
	}

	/**
	 * @dataProvider providerQueryPageAtSite
	 */
	function testQueryPageAtSite( $globalSiteId, $pageTitle, $jsonData, $expected ) {
		ApiLinkSiteTest::$jsonData = $jsonData;
		$mock = new ApiMockLinkSite();
		$this->assertEquals( $expected, $mock->queryPageAtSite( $globalSiteId, $pageTitle ) );
	}

	/**
	 * @dataProvider providerTitleToLabel
	 */
	function testTitleToPage( $externalData, $pageTitle, $expected ) {
		$mock = new ApiMockLinkSite();
		$this->assertEquals( $expected, $mock->titleToPage( $externalData, $pageTitle ) );
	}

	public function providerQueryPageAtSite() {
		return array(
			array(
				null,
				'Bergen',
				"{}",
				false,
			),
			array(
				'enwiki',
				null,
				"{}",
				false,
			),
			array(
				'somewhereineverlandwiki',
				null,
				"{}",
				false,
			),
			array(
				'enwiki',
				'Bergen',
				"{\"query\":{\"pages\":{}}}",
				array(
					'query' => array(
						'pages' => array()
					)
				),
			),
			array(
				'enwiki',
				'Bergen',
				"{\"query\":{\"pages\":{\"0\":{\"title\":\"Bergen\"}}}}",
				array(
					'query' => array(
						'pages' => array( array( 'title' => 'Bergen' ) )
					)
				),
			),
			array(
				'enwiki',
				'Bergen',
				"{\"query\":{\"pages\":{\"0\":{\"title\":\"Skien\"},\"1\":{\"title\":\"Haugesund\"}}}}",
				array(
					'query' => array(
						'pages' => array( array( 'title' => 'Skien' ), array( 'title' => 'Haugesund' ) )
					)
				),
			),
			array(
				'enwiki',
				'Bergen',
				"\"query\":{\"pages\":{\"0\":{\"title\":\"Skien\"},\"1\":{\"title\":\"Haugesund\"}}}}",
				false,
			)
		);
	}

	public function providerTitleToLabel() {
		return array(
			array(
				array(
					'query' => array(
						'pages' => array()
					)
				),
				'Bergen',
				false,
			),
			array(
				array(
					'query' => array(
						'pages' => array( array( 'title' => 'Bergen' ) )
					)
				),
				'Bergen',
				array( 'title' => 'Bergen' ),
			),
			array(
				array(
					'query' => array(
						'pages' => array( array( 'title' => 'Skien' ), array( 'title' => 'Haugesund' ) )
					)
				),
				'Bergen',
				false,
			),
			array(
				array(
					'query' => array(
						'pages' => array( array( 'title' => 'Skien' ), array( 'title' => 'Bergen' ), array( 'title' => 'Haugesund' ) )
					)
				),
				'Bergen',
				array( 'title' => 'Bergen' ),
			),
			array(
				array(
					'query' => array(
						'normalized' => array( array( 'from' => 'oslo', 'to' => 'Oslo' ) ),
						'pages' => array( array( 'title' => 'Oslo' ) )
					)
				),
				'oslo',
				array( 'title' => 'Oslo' ),
			),
			array(
				array(
					'query' => array(
						'normalized' => array( array( 'from' => 'oslo', 'to' => 'Oslo' ), array( 'from' => 'gol', 'to' => 'Gol' ) ),
						'pages' => array( array( 'title' => 'Oslo' ) )
					)
				),
				'oslo',
				array( 'title' => 'Oslo' ),
			),
			array(
				array(
					'query' => array(
						'normalized' => array( array( 'from' => 'gol', 'to' => 'Gol' ) ),
						'pages' => array( array( 'title' => 'Oslo' ) )
					)
				),
				'oslo',
				false,
			),
			array(
				array(
					'query' => array(
						'normalized' => array( array( 'from' => 'kristiania', 'to' => 'Kristiania' ) ),
						'redirects' => array( array( 'from' => 'Kristiania', 'to' => 'Oslo' ) ),
						'pages' => array( array( 'title' => 'Oslo' ) )
					)
				),
				'kristiania',
				array( 'title' => 'Oslo' ),
			),
			array(
				array(
					'query' => array(
						//'normalized' => array( array( 'from' => 'oslo', 'to' => 'Oslo' ) ),
						'converted' => array( array( 'from' => 'hammerlille', 'to' => 'Lillehammer' ) ),
						'pages' => array( array( 'title' => 'Lillehammer' ) )
					)
				),
				'hammerlille',
				array( 'title' => 'Lillehammer' ),
			),
			array(
				array(
					'query' => array(
						'converted' => array( array( 'from' => 'hammerlille', 'to' => 'Hammerlille' ) ),
						'redirects' => array( array( 'from' => 'Hammerlille', 'to' => 'Lillehammer' ) ),
						'pages' => array( array( 'title' => 'Lillehammer' ) )
					)
				),
				'hammerlille',
				array( 'title' => 'Lillehammer' ),
			),
			array(
				array(
					'query' => array(
						'normalized' => array( array( 'from' => 'festhammer', 'to' => 'Festhammer' ) ),
						'converted' => array( array( 'from' => 'Festhammer', 'to' => 'Hammerfest' ) ),
						'redirects' => array( array( 'from' => 'Hammerfest', 'to' => 'Kirkenes' ) ),
						'pages' => array( array( 'title' => 'Kirkenes' ) )
					)
				),
				'festhammer',
				array( 'title' => 'Kirkenes' ),
			),
		);
	}
}

class ApiMockLinkSite extends ApiLinkSite {

	public function __construct() { }
	public function http_get($url, $pageTitle) {
		return ApiLinkSiteTest::$jsonData;
	}
}

