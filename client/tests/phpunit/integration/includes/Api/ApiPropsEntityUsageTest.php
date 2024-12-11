<?php

namespace Wikibase\Client\Tests\Integration\Api;

use MediaWiki\Api\ApiMain;
use MediaWiki\Api\ApiPageSet;
use MediaWiki\Api\ApiQuery;
use MediaWiki\Context\RequestContext;
use MediaWiki\Request\FauxRequest;
use MediaWiki\Title\Title;
use MediaWikiLangTestCase;
use Wikibase\Client\Api\ApiPropsEntityUsage;
use Wikibase\Client\WikibaseClient;
use Wikimedia\Rdbms\IDatabase;

/**
 * @covers \Wikibase\Client\Api\ApiPropsEntityUsage
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
class ApiPropsEntityUsageTest extends MediaWikiLangTestCase {

	protected function setUp(): void {
		parent::setUp();

		$this->insertEntityUsageData();
	}

	public function addDBDataOnce() {
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
			// Clean everything
			$this->getDb()->newDeleteQueryBuilder()
				->deleteFrom( $table )
				->where( IDatabase::ALL_ROWS )
				->caller( __METHOD__ )
				->execute();

			foreach ( $rows as $row ) {
				$title = Title::makeTitle( $row['page_namespace'], $row['page_title'] );
				$page = $this->getServiceContainer()->getWikiPageFactory()->newFromTitle( $title );
				$page->insertOn( $this->getDb(), $row['page_id'] );
			}
		}
	}

	private function insertEntityUsageData() {
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
			// Clean everything
			$this->getDb()->newDeleteQueryBuilder()
				->deleteFrom( $table )
				->where( IDatabase::ALL_ROWS )
				->caller( __METHOD__ )
				->execute();

			$this->getDb()->newInsertQueryBuilder()
				->insertInto( $table )
				->rows( $rows )
				->caller( __METHOD__ )
				->execute();
		}
	}

	/**
	 * @param array $params
	 * @param Title[] $titles
	 *
	 * @return ApiQuery
	 */
	private function getQueryModule( array $params, array $titles ) {
		$context = new RequestContext();
		$context->setRequest( new FauxRequest( $params, true ) );

		$main = new ApiMain( $context );

		$pageSet = $this->getMockBuilder( ApiPageSet::class )
			->setConstructorArgs( [ $main ] )
			->setMethodsExcept( [ 'encodeParamName', 'extractRequestParams', 'getAllowedParams', 'getFinalParams', 'getMain' ] )
			->getMock();

		$pageSet->method( 'getGoodPages' )
			->willReturn( $titles );

		$query = $this->getMockBuilder( ApiQuery::class )
			->disableOriginalConstructor()
			->onlyMethods( [ 'getPageSet', 'getMain' ] )
			->getMock();

		$query->method( 'getPageSet' )
			->willReturn( $pageSet );
		$query->method( 'getMain' )
			->willReturn( $main );

		return $query;
	}

	/**
	 * @param string[] $names
	 *
	 * @return Title[]
	 */
	private function makeTitles( array $names ) {
		$titles = [];

		foreach ( $names as $name ) {
			$title = Title::makeTitle( NS_MAIN, $name );

			$pid = (int)preg_replace( '/^\D+/', '', $name );
			$title->resetArticleID( $pid );

			$titles[$pid] = $title;
		}

		return $titles;
	}

	/**
	 * @param array $params
	 *
	 * @return array[]
	 */
	private function callApiModule( array $params ) {
		$titles = $this->makeTitles( explode( '|', $params['titles'] ) );

		$module = new ApiPropsEntityUsage(
			$this->getQueryModule( $params, $titles ),
			'entityusage',
			WikibaseClient::getRepoLinker(),
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

	public static function entityUsageProvider() {
		return [
			'by title' => [
				[
					'action' => 'query',
					'prop' => 'wbentityusage',
					'titles' => 'Vienna11|Berlin22',
				],
				[ "11" => [
					"entityusage" => [
						"Q3" => [ "aspects" => [ "O", "S" ] ],
					],
				],
				"22" => [
					"entityusage" => [
						"Q4" => [ "aspects" => [ "S" ] ],
						"Q5" => [ "aspects" => [ "S" ] ],
					],
				] ],
				false,
			],
			'by entity' => [
				[
					'action' => 'query',
					'prop' => 'wbentityusage',
					'titles' => 'Vienna11|Berlin22',
					'entities' => 'Q3|Q4',
				],
				[ "11" => [
					"entityusage" => [
						"Q3" => [ "aspects" => [ "O", "S" ] ],
					],
				],
				"22" => [
					"entityusage" => [
						"Q4" => [ "aspects" => [ "S" ] ],
						"Q5" => [ "aspects" => [ "S" ] ],
					],
				] ],
				false,
			],
			'continue' => [
				[
					'action' => 'query',
					'prop' => 'wbentityusage',
					'titles' => 'Vienna11|Berlin22',
					'entities' => 'Q3|Q4',
					'wbeucontinue' => '11|Q3|S',
				],
				[ "11" => [
					"entityusage" => [
						"Q3" => [ "aspects" => [ "S" ] ],
					],
				],
				"22" => [
					"entityusage" => [
						"Q4" => [ "aspects" => [ "S" ] ],
						"Q5" => [ "aspects" => [ "S" ] ],
					],
				] ],
				false,
			],
			'invalidcontinue' => [
				[
					'action' => 'query',
					'prop' => 'wbentityusage',
					'titles' => 'Vienna11|Berlin22',
					'entities' => 'Q3|Q4',
					'wbeucontinue' => '-',
				],
				[ "11" => [
					"entityusage" => [
						"Q3" => [ "aspects" => [ "O", "S" ] ],
					],
				],
				"22" => [
					"entityusage" => [
						"Q4" => [ "aspects" => [ "S" ] ],
						"Q5" => [ "aspects" => [ "S" ] ],
					],
				] ],
				true,
			],
		];
	}

	/**
	 * @dataProvider entityUsageProvider
	 */
	public function testEntityUsage( array $params, array $expected, bool $expectWarning ) {
		$result = $this->callApiModule( $params );

		if ( $expectWarning ) {
			$this->assertCount( 1, $result['warnings'] );
		} else {
			$this->assertArrayNotHasKey( 'warnings', $result );
		}

		if ( isset( $result['error'] ) ) {
			$this->fail( 'API error: ' . print_r( $result['error'], true ) );
		}

		$this->assertArrayHasKey( 'query', $result );
		$this->assertArrayHasKey( 'pages', $result['query'] );
		$this->assertSame( $expected, $result['query']['pages'] );
	}

}
