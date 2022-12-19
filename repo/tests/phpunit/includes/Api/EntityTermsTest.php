<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Api;

use ApiMain;
use ApiPageSet;
use ApiQuery;
use FauxRequest;
use MediaWikiLangTestCase;
use RequestContext;
use Title;
use Wikibase\DataAccess\Tests\FakePrefetchingTermLookup;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\Lib\ContentLanguages;
use Wikibase\Lib\StaticContentLanguages;
use Wikibase\Lib\Store\EntityIdLookup;
use Wikibase\Repo\Api\EntityTerms;

/**
 * @covers \Wikibase\Repo\Api\EntityTerms
 *
 * @group API
 * @group Wikibase
 * @group WikibaseAPI
 * @group WikibaseRepo
 *
 * @license GPL-2.0-or-later
 */
class EntityTermsTest extends MediaWikiLangTestCase {

	/**
	 * @param array $params
	 * @param Title[] $titles
	 *
	 * @return ApiQuery
	 */
	private function getQueryModule( array $params, array $titles ): ApiQuery {
		$context = new RequestContext();
		$context->setRequest( new FauxRequest( $params, true ) );

		$main = new ApiMain( $context );

		$pageSet = $this->createMock( ApiPageSet::class );

		$pageSet->method( 'getGoodTitles' )
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
	private function makeTitles( array $names ): array {
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
	private function makeEntityIds( array $pageIds ): array {
		$entityIds = [];

		foreach ( $pageIds as $pid ) {
			$entityIds[$pid] = $this->newEntityId( $pid );
		}

		return $entityIds;
	}

	public function newEntityId( int $pageId ): EntityId {
		if ( $pageId > 1000 ) {
			return new NumericPropertyId( "P$pageId" );
		} else {
			return new ItemId( "Q$pageId" );
		}
	}

	/**
	 * @param EntityId[] $entityIds
	 *
	 * @return EntityIdLookup
	 */
	private function getEntityIdLookup( array $entityIds ): EntityIdLookup {
		$idLookup = $this->createMock( EntityIdLookup::class );
		$idLookup->method( 'getEntityIds' )
			->willReturn( $entityIds );

		return $idLookup;
	}

	/**
	 * @return ContentLanguages
	 */
	private function getContentLanguages() {
		return new StaticContentLanguages(
			[ 'de', 'en' ]
		);
	}

	/**
	 * @param array $params
	 * @param array[] $terms
	 *
	 * @return array[]
	 */
	private function callApiModule( array $params, array $terms ): array {
		$titles = $this->makeTitles( explode( '|', $params['titles'] ) );
		$entityIds = $this->makeEntityIds( array_keys( $terms ) );

		$termLookup = new FakePrefetchingTermLookup();
		$module = new EntityTerms(
			$this->getQueryModule( $params, $titles ),
			'entityterms',
			$termLookup,
			$this->getEntityIdLookup( $entityIds ),
			$termLookup,
			$this->getContentLanguages()
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

	public function entityTermsProvider() {
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

		yield 'by title' => [
			[
				'action' => 'query',
				'prop' => 'entityterms',
				'titles' => 'Q11|Q22|P3333',
			],
			$terms,
			[
				11 => [
					'entityterms' => [
						'label' => [ 'Q11 en label' ],
						'description' => [ 'Q11 en description' ],
						'alias' => [ 'Q11 en alias 1', 'Q11 en alias 2' ],
					],
				],
				22 => [
					'entityterms' => [
						'label' => [ 'Q22 en label' ],
						'description' => [ 'Q22 en description' ],
						'alias' => [ 'Q22 en alias 1', 'Q22 en alias 2' ],
					],
				],
				3333 => [
					'entityterms' => [
						'label' => [ 'P3333 en label' ],
						'description' => [ 'P3333 en description' ],
						'alias' => [ 'P3333 en alias 1', 'P3333 en alias 2' ],
					],
				],
			],
		];

		yield 'descriptions only' => [
			[
				'action' => 'query',
				'prop' => 'entityterms',
				'titles' => 'Q11|Q22',
				'wbetterms' => 'description',
			],
			$terms,
			[
				11 => [
					'entityterms' => [
						'description' => [ 'Q11 en description' ],
					],
				],
				22 => [
					'entityterms' => [
						'description' => [ 'Q22 en description' ],
					],
				],
			],
		];

		yield 'with language' => [
			[
				'action' => 'query',
				'prop' => 'entityterms',
				'titles' => 'Q11|Q22',
				'wbetlanguage' => 'de',
				'wbetterms' => 'label|description',
			],
			$terms,
			[
				11 => [
					'entityterms' => [
						'label' => [ 'Q11 de label' ],
						'description' => [ 'Q11 de description' ],
					],
				],
				22 => [
					'entityterms' => [
						'label' => [ 'Q22 de label' ],
						'description' => [ 'Q22 de description' ],
					],
				],
			],
		];

		yield 'with uselang' => [
			[
				'action' => 'query',
				'prop' => 'entityterms',
				'titles' => 'Q11|Q22',
				'uselang' => 'de',
				'wbetterms' => 'label|description',
			],
			$terms,
			[
				11 => [
					'entityterms' => [
						'label' => [ 'Q11 de label' ],
						'description' => [ 'Q11 de description' ],
					],
				],
				22 => [
					'entityterms' => [
						'label' => [ 'Q22 de label' ],
						'description' => [ 'Q22 de description' ],
					],
				],
			],
		];

		yield 'with language and uselang (language overrides uselang)' => [
			[
				'action' => 'query',
				'prop' => 'entityterms',
				'titles' => 'Q11|Q22',
				'wbetlanguage' => 'de',
				'uselang' => 'en',
				'wbetterms' => 'label|description',
			],
			$terms,
			[
				11 => [
					'entityterms' => [
						'label' => [ 'Q11 de label' ],
						'description' => [ 'Q11 de description' ],
					],
				],
				22 => [
					'entityterms' => [
						'label' => [ 'Q22 de label' ],
						'description' => [ 'Q22 de description' ],
					],
				],
			],
		];

		yield 'title without entity' => [
			[
				'action' => 'query',
				'prop' => 'entityterms',
				'titles' => 'Q11|SomeTitleWithoutEntity',
				'wbetterms' => 'label|description',
			],
			$terms,
			[
				11 => [
					'entityterms' => [
						'label' => [ 'Q11 en label' ],
						'description' => [ 'Q11 en description' ],
					],
				],
			],
		];

		yield 'continue' => [
			[
				'action' => 'query',
				'prop' => 'entityterms',
				'titles' => 'Q11|Q22',
				'wbetcontinue' => '20',
				'wbetterms' => 'label|description',
			],
			$terms,
			[
				22 => [
					'entityterms' => [
						'label' => [ 'Q22 en label' ],
						'description' => [ 'Q22 en description' ],
					],
				],
			],
		];
	}

	/**
	 * @dataProvider entityTermsProvider
	 */
	public function testEntityTerms( array $params, array $terms, array $expected ): void {
		$result = $this->callApiModule( $params, $terms );

		if ( isset( $result['error'] ) ) {
			$this->fail( 'API error: ' . print_r( $result['error'], true ) );
		}

		$this->assertArrayHasKey( 'query', $result );
		$this->assertArrayHasKey( 'pages', $result['query'] );
		$this->assertEquals( $expected, $result['query']['pages'] );
	}

}
