<?php

namespace Wikibase\Client\Tests\Api;

use ApiMain;
use ApiPageSet;
use ApiQuery;
use FauxRequest;
use MediaWikiLangTestCase;
use RequestContext;
use Title;
use Wikibase\Client\Api\ApiPropsEntityUsage;

/**
 * @covers Wikibase\Client\Api\ApiPropsEntityUsage
 *
 * @group API
 * @group Wikibase
 * @group WikibaseAPI
 * @group WikibaseClient
 * @group Database
 *
 * @license GPL-2.0+
 * @author Amir Sarabadani
 */
class EntityUsageTest extends MediaWikiLangTestCase {

	protected function setUp() {
		$this->tablesUsed[] = 'wbc_entity_usage';
		$this->tablesUsed[] = 'page';
		parent::setUp();
	}

	private function insertEntityUsage( \DatabaseBase $db, array $dump ) {
		foreach ( $dump as $table => $rows ) {
			// Clean everything
			$db->delete( $table, '*' );
			foreach ( $rows as $row ) {
				$db->insert(
					$table,
					$row
				);
			}
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
			->getMock();

		$pageSet->expects( $this->any() )
			->method( 'getGoodTitles' )
			->will( $this->returnValue( $titles ) );

		$query = $this->getMockBuilder( ApiQuery::class )
			->setConstructorArgs( [ $main, $params['action'] ] )
			->setMethods( [ 'getPageSet' ] )
			->getMock();

		$query->expects( $this->any() )
			->method( 'getPageSet' )
			->will( $this->returnValue( $pageSet ) );

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
	 * @param array[] $dump
	 *
	 * @return array[]
	 */
	private function callApiModule( array $params, array $dump ) {
		$titles = $this->makeTitles( explode( '|', $params['titles'] ) );

		$module = new ApiPropsEntityUsage(
			$this->getQueryModule( $params, $titles ),
			'entityusage'
		);

		$db = wfGetDB( DB_MASTER );
		$this->insertEntityUsage( $db, $dump );

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
			],
		];

		return [
			'by title' => [
				[
					'action' => 'query',
					'query' => 'entityusage',
					'titles' => 'Vienna11|Berlin22',
				],
				["11" => [
					"entityusage" => [
						["aspect" => "O", "*" => "Q3"],
						["aspect" => "S", "*" => "Q3"]
					]
				],
				"22" => [
					"entityusage" => [
						["aspect" => "S", "*" => "Q4"]
					]
				] ],
				$dump
			],
			'by entity' => [
				[
					'action' => 'query',
					'query' => 'entityusage',
					'titles' => 'Vienna11|Berlin22',
					'entities' => 'Q3|Q4',
				],
				["11" => [
					"entityusage" => [
						["aspect" => "O", "*" => "Q3"],
						["aspect" => "S", "*" => "Q3"]
					]
				],
				"22" => [
					"entityusage" => [
						["aspect" => "S", "*" => "Q4"]
					]
				] ],
				$dump
			],
		];
	}


	/**
	 * @dataProvider entityUsageProvider
	 */
	public function testEntityUsage( array $params, array $expected, array $dump ) {
		$result = $this->callApiModule( $params, $dump );

		if ( isset( $result['error'] ) ) {
			$this->fail( 'API error: ' . print_r( $result['error'], true ) );
		}

		$this->assertArrayHasKey( 'query', $result );
		$this->assertArrayHasKey( 'pages', $result['query'] );
		$this->assertEquals( $expected, $result['query']['pages'] );
	}

}
