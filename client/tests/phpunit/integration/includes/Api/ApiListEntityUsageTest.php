<?php

declare( strict_types=1 );

namespace Wikibase\Client\Tests\Integration\Api;

use ApiContinuationManager;
use ApiMain;
use ApiPageSet;
use MediaWiki\Request\FauxRequest;
use MediaWikiLangTestCase;
use RequestContext;
use Title;
use Wikibase\Client\Api\ApiListEntityUsage;
use Wikibase\Client\WikibaseClient;

/**
 * @covers \Wikibase\Client\Api\ApiListEntityUsage
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

	protected $tablesUsed = [
		'page',
		'wbc_entity_usage',
	];

	public function addDBData(): void {
		$this->insertPages();
		$this->insertEntityUsageData();
	}

	private function insertPages(): void {
		$dump = [
			'page' => [
				[
					'page_namespace' => NS_MAIN,
					'page_title' => 'Vienna',
					'page_id' => 11,
				],
				[
					'page_namespace' => NS_MAIN,
					'page_title' => 'Berlin',
					'page_id' => 22,
				],
			],
		];

		foreach ( $dump as $table => $rows ) {
			foreach ( $rows as $row ) {
				$title = Title::makeTitle( $row['page_namespace'], $row['page_title'] );
				$page = $this->getServiceContainer()->getWikiPageFactory()->newFromTitle( $title );
				$page->insertOn( $this->db, $row['page_id'] );
			}
		}
	}

	private function insertEntityUsageData(): void {
		$dump = [
			'wbc_entity_usage' => [
				[
					'eu_page_id' => 11,
					'eu_entity_id' => 'Q3',
					'eu_aspect' => 'S',
				],
				[
					'eu_page_id' => 11,
					'eu_entity_id' => 'Q3',
					'eu_aspect' => 'O',
				],
				[
					'eu_page_id' => 22,
					'eu_entity_id' => 'Q4',
					'eu_aspect' => 'S',
				],
				[
					'eu_page_id' => 22,
					'eu_entity_id' => 'Q5',
					'eu_aspect' => 'S',
				],
			],
		];

		foreach ( $dump as $table => $rows ) {
			foreach ( $rows as $row ) {
				$this->db->insert( $table, $row );
			}
		}
	}

	private function getListEntityUsageModule( array $params ): ApiListEntityUsage {
		$repoLinker = WikibaseClient::getRepoLinker();
		$context = new RequestContext();
		$context->setRequest( new FauxRequest( $params, true ) );

		$main = new ApiMain( $context );

		$listEntityUsageModule = new ApiListEntityUsage(
			$main->getModuleManager()->getModule( 'query' ),
			'entityusage',
			$repoLinker
		);

		$continuationManager = new ApiContinuationManager( $main, [ $listEntityUsageModule ] );
		$main->setContinuationManager( $continuationManager );

		return $listEntityUsageModule;
	}

	private function callApiModule( array $params ): array {
		$module = $this->getListEntityUsageModule( $params );

		$module->execute();

		$result = $module->getResult();
		$data = $result->getResultData( null, [
			'BC' => [],
			'Types' => [],
			'Strip' => 'all',
		] );
		return $data;
	}

	private function callApiModuleAsGenerator( array $params ): ApiPageSet {
		$module = $this->getListEntityUsageModule( $params );
		$pageSet = new ApiPageSet( $module );

		$module->executeGenerator( $pageSet );

		return $pageSet;
	}

	public function entityUsageProvider(): array {
		return [
			'only Q3' => [
				[
					'wbeuentities' => 'Q3',
				],
				[ "11" => [
					"ns" => 0,
					"title" => "Vienna",
					"pageid" => 11,
					"entityusage" => [
						"Q3" => [ "aspects" => [ "O", "S" ] ],
					],
				] ],
			],
			'two entities in two pages' => [
				[
					'wbeuentities' => 'Q3|Q5',
				],
				[ "11" => [
					"ns" => 0,
					"title" => "Vienna",
					"pageid" => 11,
					"entityusage" => [
						"Q3" => [ "aspects" => [ "O", "S" ] ],
					],
				],
				"22" => [
					"ns" => 0,
					"title" => "Berlin",
					"pageid" => 22,
					"entityusage" => [
						"Q5" => [ "aspects" => [ "S" ] ],
					],
				] ],
			],
			'continue' => [
				[
					'wbeuentities' => 'Q3|Q5',
					'wbeucontinue' => '11|Q3|S',
				],
				[ "11" => [
					"ns" => 0,
					"title" => "Vienna",
					"pageid" => 11,
					"entityusage" => [
						"Q3" => [ "aspects" => [ "S" ] ],
					],
				],
				"22" => [
					"ns" => 0,
					"title" => "Berlin",
					"pageid" => 22,
					"entityusage" => [
						"Q5" => [ "aspects" => [ "S" ] ],
					],
				] ],
			],
			'correctly finish pageination step between two pages' => [
				[
					'wbeuentities' => 'Q3|Q4|Q5',
					'wbeulimit' => 2,
				],
				[
					"11" => [
						"ns" => 0,
						"title" => "Vienna",
						"pageid" => 11,
						"entityusage" => [
							"Q3" => [ "aspects" => [
								"O",
								"S",
							] ],
						],
					],
				],
			],
		];
	}

	/**
	 * @dataProvider entityUsageProvider
	 */
	public function testEntityUsage( array $params, array $expected ): void {
		$result = $this->callApiModule( $params );

		if ( isset( $result['error'] ) ) {
			$this->fail( 'API error: ' . print_r( $result['error'], true ) );
		}

		$this->assertArrayHasKey( 'query', $result );
		$this->assertArrayHasKey( 'pages', $result['query'] );
		$this->assertSame( $expected, $result['query']['pages'] );
	}

	/** @dataProvider entityUsageProvider */
	public function testEntityUsageAsGenerator( array $params, array $expected ): void {
		$pageSet = $this->callApiModuleAsGenerator( $params );

		$pages = $pageSet->getGoodPages();
		$this->assertCount( count( $expected ), $pages );
		foreach ( $pages as $page ) {
			$expectedPage = $expected[$page->getId()];
			$this->assertSame( $expectedPage['ns'], $page->getNamespace() );
			$this->assertSame( $expectedPage['title'], $page->getDBkey() );
		}
	}

}
