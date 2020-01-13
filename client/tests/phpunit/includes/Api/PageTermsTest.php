<?php

namespace Wikibase\Client\Tests\Api;

use ApiMain;
use ApiPageSet;
use ApiQuery;
use FauxRequest;
use MediaWikiLangTestCase;
use RequestContext;
use Title;
use Wikibase\Client\Api\PageTerms;
use Wikibase\DataAccess\Tests\FakePrefetchingTermLookup;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Store\EntityIdLookup;

/**
 * @covers \Wikibase\Client\Api\PageTerms
 *
 * @group API
 * @group Wikibase
 * @group WikibaseAPI
 * @group WikibaseClient
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class PageTermsTest extends MediaWikiLangTestCase {

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
			->disableOriginalConstructor()
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
			if ( !preg_match( '/^\D+/', $name ) ) {
				continue;
			}

			$title = Title::makeTitle( NS_MAIN, $name );

			$pid = (int)preg_replace( '/^\D+/', '', $name );
			$title->resetArticleID( $pid );

			$titles[$pid] = $title;
		}

		return $titles;
	}

	/**
	 * @param int[] $pageIds
	 *
	 * @return EntityId[]
	 */
	private function makeEntityIds( array $pageIds ) {
		$entityIds = [];

		foreach ( $pageIds as $pid ) {
			$entityIds[$pid] = $this->newEntityId( $pid );
		}

		return $entityIds;
	}

	public function newEntityId( $pageId ) {
		if ( $pageId > 1000 ) {
			return new PropertyId( "P$pageId" );
		} else {
			return new ItemId( "Q$pageId" );
		}
	}

	/**
	 * @param EntityId[] $entityIds
	 *
	 * @return EntityIdLookup
	 */
	private function getEntityIdLookup( array $entityIds ) {
		$idLookup = $this->createMock( EntityIdLookup::class );
		$idLookup->expects( $this->any() )
			->method( 'getEntityIds' )
			->will( $this->returnValue( $entityIds ) );

		return $idLookup;
	}

	/**
	 * @param array $params
	 * @param array[] $terms
	 *
	 * @return array[]
	 */
	private function callApiModule( array $params, array $terms ) {
		$titles = $this->makeTitles( explode( '|', $params['titles'] ) );
		$entityIds = $this->makeEntityIds( array_keys( $terms ) );

		$module = new PageTerms(
			new FakePrefetchingTermLookup(),
			$this->getEntityIdLookup( $entityIds ),
			$this->getQueryModule( $params, $titles ),
			'pageterms'
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

	public function pageTermsProvider() {
		$terms = [
			11 => [
				'label' => [
					'en' => 'Q11 en label',
					'de' => 'Q11 de label',
				],
				'description' => [
					'en' => 'Q11 en description',
					'de' => 'Q11 de description',
				],
			],
			22 => [
				'label' => [
					'en' => 'Q22 en label',
					'de' => 'Q22 de label',
				],
				'description' => [
					'en' => 'Q22 en description',
					'de' => 'Q22 de description',
				],
			],
			3333 => [
				'label' => [
					'en' => 'P3333 en label',
					'de' => 'P3333 de label',
				],
				'description' => [
					'en' => 'P3333 en description',
					'de' => 'P3333 de description',
				],
			],
		];

		return [
			'by title' => [
				[
					'action' => 'query',
					'query' => 'pageterms',
					'titles' => 'No11|No22|No3333',
				],
				$terms,
				[
					11 => [
						'terms' => [
							'label' => [ 'Q11 en label' ],
							'description' => [ 'Q11 en description' ],
							'alias' => [ 'Q11 en alias 1', 'Q11 en alias 2' ],
						]
					],
					22 => [
						'terms' => [
							'label' => [ 'Q22 en label' ],
							'description' => [ 'Q22 en description' ],
							'alias' => [ 'Q22 en alias 1', 'Q22 en alias 2' ],
						]
					],
					3333 => [
						'terms' => [
							'label' => [ 'P3333 en label' ],
							'description' => [ 'P3333 en description' ],
							'alias' => [ 'P3333 en alias 1', 'P3333 en alias 2' ],
						]
					],
				]
			],
			'descriptions only' => [
				[
					'action' => 'query',
					'query' => 'pageterms',
					'titles' => 'No11|No22',
					'wbptterms' => 'description',
				],
				$terms,
				[
					11 => [
						'terms' => [
							'description' => [ 'Q11 en description' ],
						]
					],
					22 => [
						'terms' => [
							'description' => [ 'Q22 en description' ],
						]
					],
				]
			],
			'with uselang' => [
				[
					'action' => 'query',
					'query' => 'pageterms',
					'titles' => 'No11|No22',
					'uselang' => 'de',
					'wbptterms' => 'label|description',
				],
				$terms,
				[
					11 => [
						'terms' => [
							'label' => [ 'Q11 de label' ],
							'description' => [ 'Q11 de description' ],
						]
					],
					22 => [
						'terms' => [
							'label' => [ 'Q22 de label' ],
							'description' => [ 'Q22 de description' ],
						]
					],
				]
			],
			'title without entity' => [
				[
					'action' => 'query',
					'query' => 'pageterms',
					'titles' => 'No11|SomeTitleWithoutEntity',
					'wbptterms' => 'label|description',
				],
				$terms,
				[
					11 => [
						'terms' => [
							'label' => [ 'Q11 en label' ],
							'description' => [ 'Q11 en description' ],
						]
					],
				]
			],
			'continue' => [
				[
					'action' => 'query',
					'query' => 'pageterms',
					'titles' => 'No11|No22',
					'wbptcontinue' => '20',
					'wbptterms' => 'label|description',
				],
				$terms,
				[
					22 => [
						'terms' => [
							'label' => [ 'Q22 en label' ],
							'description' => [ 'Q22 en description' ],
						]
					],
				]
			],
		];
	}

	/**
	 * @dataProvider pageTermsProvider
	 */
	public function testPageTerms( array $params, array $terms, array $expected ) {
		$result = $this->callApiModule( $params, $terms );

		if ( isset( $result['error'] ) ) {
			$this->fail( 'API error: ' . print_r( $result['error'], true ) );
		}

		$this->assertArrayHasKey( 'query', $result );
		$this->assertArrayHasKey( 'pages', $result['query'] );
		$this->assertEquals( $expected, $result['query']['pages'] );
	}

}
