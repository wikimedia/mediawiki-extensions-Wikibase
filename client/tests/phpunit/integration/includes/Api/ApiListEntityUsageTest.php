<?php

declare( strict_types=1 );

namespace Wikibase\Client\Tests\Integration\Api;

use MediaWiki\Api\ApiContinuationManager;
use MediaWiki\Api\ApiMain;
use MediaWiki\Api\ApiPageSet;
use MediaWiki\Api\ApiUsageException;
use MediaWiki\Context\RequestContext;
use MediaWiki\Request\FauxRequest;
use MediaWiki\Title\Title;
use MediaWikiLangTestCase;
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
				$page->insertOn( $this->getDb(), $row['page_id'] );
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
			$this->getDb()->newInsertQueryBuilder()
				->insertInto( $table )
				->rows( $rows )
				->caller( __METHOD__ )
				->execute();
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

	/** @return array( ApiPageSet $pageSet, array $data ) */
	private function callApiModuleAsGenerator( array $params ): array {
		$module = $this->getListEntityUsageModule( $params );
		$pageSet = new ApiPageSet( $module );

		$module->executeGenerator( $pageSet );

		$result = $module->getResult();
		$data = $result->getResultData( null, [
			'BC' => [],
			'Types' => [],
			'Strip' => 'all',
		] );

		return [ $pageSet, $data ];
	}

	public static function entityUsageProvider(): iterable {
		return [
			'only Q3' => [
				[
					'wbleuentities' => 'Q3',
				],
				[
					[
						"ns" => 0,
						"title" => "Vienna",
						"pageid" => 11,
						"entityusage" => [
							"Q3" => [ "aspects" => [ "O", "S" ] ],
						],
					],
				],
			],
			'two entities in two pages' => [
				[
					'wbleuentities' => 'Q3|Q5',
				],
				[
					[
						"ns" => 0,
						"title" => "Vienna",
						"pageid" => 11,
						"entityusage" => [
							"Q3" => [ "aspects" => [ "O", "S" ] ],
						],
					],
					[
						"ns" => 0,
						"title" => "Berlin",
						"pageid" => 22,
						"entityusage" => [
							"Q5" => [ "aspects" => [ "S" ] ],
						],
					],
				],
			],
			'continue' => [
				[
					'wbleuentities' => 'Q3|Q5',
					'wbleucontinue' => '11|Q3|S',
				],
				[
					[
						"ns" => 0,
						"title" => "Vienna",
						"pageid" => 11,
						"entityusage" => [
							"Q3" => [ "aspects" => [ "S" ] ],
						],
					],
					[
						"ns" => 0,
						"title" => "Berlin",
						"pageid" => 22,
						"entityusage" => [
							"Q5" => [ "aspects" => [ "S" ] ],
						],
					],
				],
			],
			'correctly finish pageination step between two pages' => [
				[
					'wbleuentities' => 'Q3|Q4|Q5',
					'wbleulimit' => 2,
				],
				[
					[
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
	public function testEntityUsage(
		array $params,
		array $expected
	): void {
		$result = $this->callApiModule( $params );
		$this->assertArrayNotHasKey( 'warnings', $result );

		if ( isset( $result['error'] ) ) {
			$this->fail( 'API error: ' . print_r( $result['error'], true ) );
		}

		$this->assertArrayHasKey( 'query', $result );
		$this->assertArrayHasKey( 'entityusage', $result['query'] );
		$this->assertSame( $expected, $result['query']['entityusage'] );
	}

	/** @dataProvider entityUsageProvider */
	public function testEntityUsageAsGenerator(
		array $params,
		array $expected
	): void {
		[ $pageSet, $result ] = $this->callApiModuleAsGenerator( $params );
		$this->assertArrayNotHasKey( 'warnings', $result );

		$pages = $pageSet->getGoodPages();
		$this->assertSameSize( $expected, $pages );
		foreach ( $pages as $page ) {
			foreach ( $expected as $expectedPage ) {
				if ( $expectedPage['pageid'] === $page->getId() ) {
					break;
				}
			}
			$this->assertSame( $expectedPage['ns'], $page->getNamespace() );
			$this->assertSame( $expectedPage['title'], $page->getDBkey() );
		}
	}

	public function testEntityUsageInvalidContinue(): void {
		$this->expectException( ApiUsageException::class );
		$this->callApiModule( [
			'wbleuentities' => 'Q3|Q5',
			'wbleucontinue' => '-',
		] );
	}

}
