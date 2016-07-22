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
 *
 * @todo More tests, specially on database side
 * @license GPL-2.0+
 * @author Amir Sarabadani
 */
class EntityUsageTest extends \MediaWikiLangTestCase {

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
	 * @param array[] $terms
	 *
	 * @return array[]
	 */
	private function callApiModule( array $params, array $res ) {
		$titles = $this->makeTitles( explode( '|', $params['titles'] ) );
		$module = $this->getMockBuilder( ApiPropsEntityUsage::class )
			->setConstructorArgs( [ $this->getQueryModule( $params, $titles ),
				'entityusage' ] )
			->setMethods( [ 'doQuery' ] )
			->getMock();
		// Inject a fake doQuery method
		$module->expects( $this->any() )
			->method( 'doQuery' )
			->will( $this->returnValue( $this->doQuery( $res ) ) );
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
		$res = [
			['page_title' => 'Vienna',
			'page_namespace' => 0,
			'page_id' => 11,
			'eu_page_id' => 11,
			'eu_entity_id' => 'Q3',
			'eu_aspect' => 'S'
			],
			['page_title' => 'Vienna',
			'page_namespace' => 0,
			'page_id' => 11,
			'eu_page_id' => 11,
			'eu_entity_id' => 'Q3',
			'eu_aspect' => 'O'
			],
			['page_title' => 'Berlin',
			'page_namespace' => 0,
			'page_id' => 22,
			'eu_page_id' => 22,
			'eu_entity_id' => 'Q4',
			'eu_aspect' => 'S'
			],
		];

		return [
			'by title' => [
				[
					'action' => 'query',
					'query' => 'entityusage',
					'titles' => 'Vienna|Berlin',
				],
				["11" => [
					"entityusage" => [
						["aspect" => "S", "*" => "Q3"],
						["aspect" => "O", "*" => "Q3"]
					]
				],
				"22" => [
					"entityusage" => [
						["aspect" => "S", "*" => "Q4"]
					]
				] ],
				$res
			],
			'by entity' => [
				[
					'action' => 'query',
					'query' => 'entityusage',
					'titles' => 'Vienna|Berlin',
					'entities' => 'Q3|Q4',
				],
				["11" => [
					"entityusage" => [
						["aspect" => "S", "*" => "Q3"],
						["aspect" => "O", "*" => "Q3"]
					]
				],
				"22" => [
					"entityusage" => [
						["aspect" => "S", "*" => "Q4"]
					]
				] ],
				$res
			],
		];
	}

	public function doQuery( $res ) {
		return json_decode( json_encode( $res ), false );
	}

	/**
	 * @dataProvider entityUsageProvider
	 */
	public function testEntityUsage( array $params, array $expected, array $res ) {
		$result = $this->callApiModule( $params, $res );

		if ( isset( $result['error'] ) ) {
			$this->fail( 'API error: ' . print_r( $result['error'], true ) );
		}

		$this->assertArrayHasKey( 'query', $result );
		$this->assertArrayHasKey( 'pages', $result['query'] );
		$this->assertEquals( $expected, $result['query']['pages'] );
	}

}
