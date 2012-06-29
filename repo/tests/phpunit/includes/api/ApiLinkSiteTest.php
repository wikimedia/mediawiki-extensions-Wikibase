<?php

namespace Wikibase\Test;
use ApiTestCase;
use Wikibase\ApiLinkSite;

/**
 * Additional tests for ApiLinkSite API module.
 *
 * @group API
 *
 * @file
 * @since 0.1
 *
 * @ingroup Wikibase
 * @ingroup Test
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
			// #0
			array(
				array(
					'query' => array(
						'pages' => array()
					)
				),
				'Bergen',
				false,
			),
			// #1
			array(
				array(
					'query' => array(
						'pages' => array( array( 'title' => 'Bergen' ), array( 'title' => 'Neverland' ) )
					)
				),
				'Bergen',
				array( 'title' => 'Bergen' ),
			),
			// #2
			array(
				array(
					'query' => array(
						'pages' => array( array( 'title' => 'Skien' ), array( 'title' => 'Haugesund' ) )
					)
				),
				'Bergen',
				false,
			),
			// #3
			array(
				array(
					'query' => array(
						'pages' => array( array( 'title' => 'Skien' ), array( 'title' => 'Bergen' ), array( 'title' => 'Haugesund' ) )
					)
				),
				'Bergen',
				array( 'title' => 'Bergen' ),
			),
			// #4
			array(
				array(
					'query' => array(
						'normalized' => array( array( 'from' => 'oslo', 'to' => 'Oslo' ) ),
						'pages' => array( array( 'title' => 'Oslo' ), array( 'title' => 'Neverland' ) )
					)
				),
				'oslo',
				array( 'title' => 'Oslo' ),
			),
			// #5
			array(
				array(
					'query' => array(
						'normalized' => array( array( 'from' => 'oslo', 'to' => 'Oslo' ), array( 'from' => 'gol', 'to' => 'Gol' ) ),
						'pages' => array( array( 'title' => 'Oslo' ), array( 'title' => 'Neverland' ) )
					)
				),
				'oslo',
				array( 'title' => 'Oslo' ),
			),
			// #6
			array(
				array(
					'query' => array(
						'normalized' => array( array( 'from' => 'gol', 'to' => 'Gol' ) ),
						'pages' => array( array( 'title' => 'Oslo' ), array( 'title' => 'Neverland' ) )
					)
				),
				'oslo',
				false,
			),
			// #7
			array(
				array(
					'query' => array(
						'normalized' => array( array( 'from' => 'kristiania', 'to' => 'Kristiania' ) ),
						'redirects' => array( array( 'from' => 'Kristiania', 'to' => 'Oslo' ) ),
						'pages' => array( array( 'title' => 'Oslo' ), array( 'title' => 'Neverland' ) )
					)
				),
				'kristiania',
				array( 'title' => 'Oslo' ),
			),
			// #8
			array(
				array(
					'query' => array(
						//'normalized' => array( array( 'from' => 'oslo', 'to' => 'Oslo' ) ),
						'converted' => array( array( 'from' => 'hammerlille', 'to' => 'Lillehammer' ) ),
						'pages' => array( array( 'title' => 'Lillehammer' ), array( 'title' => 'Neverland' ) )
					)
				),
				'hammerlille',
				array( 'title' => 'Lillehammer' ),
			),
			// #9
			array(
				array(
					'query' => array(
						'converted' => array( array( 'from' => 'hammerlille', 'to' => 'Hammerlille' ) ),
						'redirects' => array( array( 'from' => 'Hammerlille', 'to' => 'Lillehammer' ) ),
						'pages' => array( array( 'title' => 'Lillehammer' ), array( 'title' => 'Neverland' ) )
					)
				),
				'hammerlille',
				array( 'title' => 'Lillehammer' ),
			),
			// #10
			array(
				array(
					'query' => array(
						'normalized' => array( array( 'from' => 'festhammer', 'to' => 'Festhammer' ) ),
						'converted' => array( array( 'from' => 'Festhammer', 'to' => 'Hammerfest' ) ),
						'redirects' => array( array( 'from' => 'Hammerfest', 'to' => 'Kirkenes' ) ),
						'pages' => array( array( 'title' => 'Kirkenes' ), array( 'title' => 'Neverland' ) )
					)
				),
				'festhammer',
				array( 'title' => 'Kirkenes' ),
			),
			// #11
			array(
				array(
					'query' => array(
						'normalized' => array( array( 'from' => 'gol', 'to' => 'Gol' ) ),
						'pages' => array( array( 'title' => 'Oslo' ) )
					)
				),
				'oslo',
				// now we skip (its only one page) and finds these one anyhow
				array( 'title' => 'Oslo' )
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

