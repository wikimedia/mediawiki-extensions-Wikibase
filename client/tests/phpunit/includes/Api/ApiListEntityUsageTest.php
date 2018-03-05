<?php

namespace Wikibase\Client\Tests\Api;

use ApiMain;
use ApiQuery;
use FauxRequest;
use MediaWikiLangTestCase;
use RequestContext;
use Title;
use Wikibase\Client\Api\ApiListEntityUsage;
use Wikibase\Client\WikibaseClient;
use WikiPage;

/**
 * @covers Wikibase\Client\Api\ApiListEntityUsage
 *
 * @group API
 * @group Wikibase
 * @group WikibaseAPI
 * @group WikibaseClient
 * @group Database
 *
 * @license GPL-2.0-or-later
 * @author Amir Sarabadani
 */
class ApiListEntityUsageTest extends MediaWikiLangTestCase {

	protected function setUp() {
		$this->tablesUsed[] = 'wbc_entity_usage';
		parent::setUp();

		self::insertEntityUsageData();
	}

	public function addDBDataOnce() {
		$db = wfGetDB( DB_MASTER );
		$dump = [
			'page' => [
				[
					'page_title' => 'Vienna',
					'page_namespace' => 0,
					'page_id' => 11,
				],
				[
					'page_title' => 'Berlin',
					'page_namespace' => 0,
					'page_id' => 22,
				],
			],
		];

		foreach ( $dump as $table => $rows ) {
			// Clean everything
			$db->delete( $table, '*' );

			foreach ( $rows as $row ) {
				$title = Title::newFromText( $row['page_title'], $row['page_namespace'] );
				$page = WikiPage::factory( $title );
				$page->insertOn( $db, $row['page_id'] );
			}
		}
	}

	public static function insertEntityUsageData() {
		$db = wfGetDB( DB_MASTER );
		$dump = [
			'wbc_entity_usage' => [
				[
					'eu_page_id' => 11,
					'eu_entity_id' => 'Q3',
					'eu_aspect' => 'S'
				],
				[
					'eu_page_id' => 11,
					'eu_entity_id' => 'Q3',
					'eu_aspect' => 'O'
				],
				[
					'eu_page_id' => 22,
					'eu_entity_id' => 'Q4',
					'eu_aspect' => 'S'
				],
				[
					'eu_page_id' => 22,
					'eu_entity_id' => 'Q5',
					'eu_aspect' => 'S'
				],
			],
		];

		foreach ( $dump as $table => $rows ) {
			// Clean everything
			$db->delete( $table, '*' );

			foreach ( $rows as $row ) {
				$db->insert( $table, $row );
			}
		}
	}

	/**
	 * @param array $params
	 *
	 * @return ApiQuery
	 */
	private function getQueryModule( array $params ) {
		$context = new RequestContext();
		$context->setRequest( new FauxRequest( $params, true ) );

		$main = new ApiMain( $context );

		$query = new ApiQuery( $main, $params['action'] );

		return $query;
	}

	/**
	 * @param array $params
	 *
	 * @return array[]
	 */
	private function callApiModule( array $params ) {
		$repoLinker = WikibaseClient::getDefaultInstance()->newRepoLinker();
		$module = new ApiListEntityUsage(
			$this->getQueryModule( $params ),
			'entityusage',
			$repoLinker
		);

		$module->execute();

		$result = $module->getResult();
		$data = $result->getResultData( null, [
			'BC' => [],
			'Types' => [],
			'Strip' => 'all',
		] );
		return $data;
	}

	public function entityUsageProvider() {
		return [
			'only Q3' => [
				[
					'action' => 'query',
					'query' => 'entityusage',
					'wbeuentities' => 'Q3',
				],
				[ "11" => [
					"ns" => 0,
					"title" => "Vienna",
					"pageid" => 11,
					"entityusage" => [
						"Q3" => [ "aspects" => [ "O", "S" ] ],
					]
				] ],
			],
			'two entities in two pages' => [
				[
					'action' => 'query',
					'query' => 'entityusage',
					'wbeuentities' => 'Q3|Q5',
				],
				[ "11" => [
					"ns" => 0,
					"title" => "Vienna",
					"pageid" => 11,
					"entityusage" => [
						"Q3" => [ "aspects" => [ "O", "S" ] ],
					]
				],
				"22" => [
					"ns" => 0,
					"title" => "Berlin",
					"pageid" => 22,
					"entityusage" => [
						"Q5" => [ "aspects" => [ "S" ] ],
					]
				] ],
			],
			'continue' => [
				[
					'action' => 'query',
					'query' => 'entityusage',
					'wbeuentities' => 'Q3|Q5',
					'wbeucontinue' => '11|Q3|S',
				],
				[ "11" => [
					"ns" => 0,
					"title" => "Vienna",
					"pageid" => 11,
					"entityusage" => [
						"Q3" => [ "aspects" => [ "S" ] ],
					]
				],
				"22" => [
					"ns" => 0,
					"title" => "Berlin",
					"pageid" => 22,
					"entityusage" => [
						"Q5" => [ "aspects" => [ "S" ] ],
					]
				] ],
			],
		];
	}

	/**
	 * @dataProvider entityUsageProvider
	 */
	public function testEntityUsage( array $params, array $expected ) {
		$result = $this->callApiModule( $params );

		if ( isset( $result['error'] ) ) {
			$this->fail( 'API error: ' . print_r( $result['error'], true ) );
		}

		$this->assertArrayHasKey( 'query', $result );
		$this->assertArrayHasKey( 'pages', $result['query'] );
		$this->assertSame( $expected, $result['query']['pages'] );
	}

}
