<?php

namespace Wikibase\Lib\Tests\Store\Sql\Terms;

use HashBagOStuff;
use MediaWikiIntegrationTestCase;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Services\Term\PropertyLabelResolver;
use Wikibase\Lib\Store\Sql\Terms\CachedDatabasePropertyLabelResolver;
use Wikibase\Lib\Store\Sql\Terms\DatabaseTermInLangIdsResolver;
use Wikibase\Lib\TermIndexEntry;

/**
 * @covers \Wikibase\Lib\Store\Sql\Terms\CachedDatabasePropertyLabelResolver
 *
 * @group Wikibase
 * @group WikibaseStore
 * @license GPL-2.0-or-later
 */
class CachedDatabasePropertyLabelResolverTest extends MediaWikiIntegrationTestCase {

	/**
	 * @param string $lang
	 * @param TermIndexEntry[] $terms
	 *
	 * @return PropertyLabelResolver
	 */
	public function getResolver( $lang, array $termsArray ) {
		$dbTermIdsResolverMock = $this->getMockedDatabaseTermIdsResolver( $termsArray );

		$resolver = new CachedDatabasePropertyLabelResolver(
			$lang,
			$dbTermIdsResolverMock,
			new HashBagOStuff(),
			3600,
			'testrepo:WBL\0.5alpha'
		);

		return $resolver;
	}

	/**
	 * @dataProvider provideGetPropertyIdsForLabels
	 */
	public function testGetPropertyIdsForLabels( $lang, array $terms, array $labels, array $expected ) {
		$resolver = $this->getResolver( $lang, $terms );

		// check we are getting back the expected map of labels to IDs
		$actual = $resolver->getPropertyIdsForLabels( $labels );
		$this->assertArrayEquals( $expected, $actual, false, true );

		// check again, so we also hit the "stuff it cached" code path
		$actual = $resolver->getPropertyIdsForLabels( $labels );
		$this->assertArrayEquals( $expected, $actual, false, true );
	}

	public function provideGetPropertyIdsForLabels() {
		$p1TermsArray = [
			'label' => [
				'de' => [ 'Eins' ],
				'en' => [ 'One' ],
			],
			'alias' => [],
			'description' => [],
		];
		$p2TermsArray = [
			'label' => [
				'de' => [ 'Zwei' ],
			],
			'alias' => [],
			'description' => [],
		];
		$p3TermsArray = [
			'label' => [
				'de' => [ 'Drei' ],
			],
			'alias' => [
				'en' => [ 'Three' ],
			],
			'description' => [],
		];
		$p4TermsArray = [
			'label' => [
				'de' => [ 'vier' ],
			],
			'alias' => [],
			'description' => [ 'en' => [ 'Four' ] ],
		];

		$termsArrayPerPropertyId = [
			1 => $p1TermsArray,
			2 => $p2TermsArray,
			3 => $p3TermsArray,
			4 => $p4TermsArray,
		];

		return [
			[ // #0
				'de',
				$termsArrayPerPropertyId,
				[], // labels
				[], // expected
			],
			[ // #1
				'de',
				$termsArrayPerPropertyId,
				[ // labels
					'Eins',
					'Zwei',
				],
				[ // expected
					'Eins' => new NumericPropertyId( 'P1' ),
					'Zwei' => new NumericPropertyId( 'P2' ),
				],
			],
			[ // #2
				'de',
				$termsArrayPerPropertyId,
				[ // labels
					'Drei',
					'Vier',
				],
				[ // expected
					'Drei' => new NumericPropertyId( 'P3' ),
				],
			],
			[ // #3
				'en',
				$termsArrayPerPropertyId,
				[ // labels
					'Eins',
					'Zwei',
				],
				[], // expected
			],
			[ // #4
				'en',
				$termsArrayPerPropertyId,
				[ // labels
					'One',
					'Two',
					'Three',
					'Four',
				],
				[ // expected
					'One' => new NumericPropertyId( 'P1' ),
				],
			],
		];
	}

	private function getMockedDatabaseTermIdsResolver( $termsArrayPerPropertyId ) {
		$dbTermIdsResolver = $this->getMockBuilder( DatabaseTermInLangIdsResolver::class )
						   ->disableOriginalConstructor()
						   ->addMethods( [
							   'resolveTermIds',
							   'resolveGroupedTermIds',
						   ] )
						   ->onlyMethods( [
							   'resolveTermsViaJoin',
						   ] )
						   ->getMock();

		// Current implementation will use the join functionality provided by
		// DatabaseTermIdsResolver::resolveTermsViaJoin for performance gain,
		// hence all other public methods are not expected to be used at all
		$dbTermIdsResolver->expects( $this->never() )
			->method( 'resolveTermIds' );
		$dbTermIdsResolver->expects( $this->never() )
			->method( 'resolveGroupedTermIds' );

		$dbTermIdsResolver->expects( $this->once() )
			->method( 'resolveTermsViaJoin' )
			->willReturn( $termsArrayPerPropertyId );

		return $dbTermIdsResolver;
	}

}
