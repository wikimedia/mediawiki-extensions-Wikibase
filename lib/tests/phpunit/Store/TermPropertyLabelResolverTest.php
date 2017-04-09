<?php

namespace Wikibase\Lib\Tests\Store;

use HashBagOStuff;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\Term\PropertyLabelResolver;
use Wikibase\TermIndex;
use Wikibase\TermIndexEntry;
use Wikibase\TermPropertyLabelResolver;

/**
 * @covers Wikibase\TermPropertyLabelResolver
 *
 * @group Wikibase
 * @group WikibaseStore
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class TermPropertyLabelResolverTest extends \MediaWikiTestCase {

	/**
	 * @dataProvider provideGetPropertyIdsForLabels
	 */
	public function testGetPropertyIdsForLabels(
		$languageCode,
		array $terms,
		array $labels,
		array $expected
	) {
		$resolver = $this->getResolver( $languageCode, $terms );

		// check we are getting back the expected map of labels to IDs
		$actual = $resolver->getPropertyIdsForLabels( $labels );
		$this->assertEquals( $expected, $actual );

		// check again, so we also hit the "stuff it cached" code path
		$actual = $resolver->getPropertyIdsForLabels( $labels );
		$this->assertEquals( $expected, $actual );
	}

	public function provideGetPropertyIdsForLabels() {
		return [
			[ // #0
				'de',
				$this->getTermIndexEntries( 'de' ),
				[], // labels
				[], // expected
			],
			[ // #1
				'de',
				$this->getTermIndexEntries( 'de' ),
				[ // labels
					'Eins',
					'Zwei'
				],
				[ // expected
					'Eins' => new PropertyId( 'P1' ),
					'Zwei' => new PropertyId( 'P2' ),
				]
			],
			[ // #2
				'de',
				$this->getTermIndexEntries( 'de' ),
				[ // labels
					'Drei',
					'Vier'
				],
				[ // expected
					'Drei' => new PropertyId( 'P3' ),
				]
			],
			[ // #3
				'en',
				$this->getTermIndexEntries( 'en' ),
				[ // labels
					'Eins',
					'Zwei'
				],
				[] // expected
			],
			[ // #4
				'en',
				$this->getTermIndexEntries( 'en' ),
				[ // labels
					'One',
					'Two',
					'Three',
					'Four'
				],
				[ // expected
					'One' => new PropertyId( 'P1' ),
				]
			],
		];
	}

	private function getTermIndexEntries( $languageCode ) {
		$terms = [
			'de' => [
				new TermIndexEntry( [
					'termType' => 'label',
					'termLanguage' => 'de',
					'entityId' => new PropertyId( 'P1' ),
					'termText' => 'Eins',
				] ),
				new TermIndexEntry( [
					'termType' => 'label',
					'termLanguage' => 'de',
					'entityId' => new PropertyId( 'P2' ),
					'termText' => 'Zwei',
				] ),
				new TermIndexEntry( [
					'termType' => 'label',
					'termLanguage' => 'de',
					'entityId' => new PropertyId( 'P3' ),
					'termText' => 'Drei',
				] ),
				new TermIndexEntry( [
					'termType' => 'label',
					'termLanguage' => 'de',
					'entityId' => new PropertyId( 'P4' ),
					'termText' => 'vier', // lower case
				] )
			],
			'en' => [
				new TermIndexEntry( [
					'termType' => 'label',
					'termLanguage' => 'en',
					'entityId' => new PropertyId( 'P1' ),
					'termText' => 'One',
				] ),
			]
		];

		return $terms[$languageCode];
	}

	/**
	 * @param string $languageCode
	 * @param TermIndexEntry[] $terms
	 *
	 * @return PropertyLabelResolver
	 */
	private function getResolver( $languageCode, array $terms ) {
		$resolver = new TermPropertyLabelResolver(
			$languageCode,
			$this->getMockTermIndex( $terms ),
			new HashBagOStuff(),
			3600,
			'testrepo:WBL\0.5alpha'
		);

		return $resolver;
	}

	private function getMockTermIndex( array $terms ) {
		$termIndex = $this->getMockBuilder( TermIndex::class )
			->disableOriginalConstructor()
			->getMock();

		$termIndex->expects( $this->any() )
			->method( 'getMatchingTerms' )
			->will( $this->returnValue( $terms ) );

		return $termIndex;
	}
}