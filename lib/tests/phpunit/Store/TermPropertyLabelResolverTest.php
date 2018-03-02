<?php

namespace Wikibase\Lib\Tests\Store;

use HashBagOStuff;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\Term\PropertyLabelResolver;
use Wikibase\TermIndexEntry;
use Wikibase\Lib\Store\TermPropertyLabelResolver;

/**
 * @covers Wikibase\Lib\Store\TermPropertyLabelResolver
 *
 * @group Wikibase
 * @group WikibaseStore
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class TermPropertyLabelResolverTest extends \MediaWikiTestCase {

	/**
	 * @param string $lang
	 * @param TermIndexEntry[] $terms
	 *
	 * @return PropertyLabelResolver
	 */
	public function getResolver( $lang, array $terms ) {
		$resolver = new TermPropertyLabelResolver(
			$lang,
			new MockTermIndex( $terms ),
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
		$terms = [
			// de
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
			] ),

			// en
			new TermIndexEntry( [
				'termType' => 'label',
				'termLanguage' => 'en',
				'entityId' => new PropertyId( 'P1' ),
				'termText' => 'One',
			] ),
			new TermIndexEntry( [
				'termType' => 'label',
				'termLanguage' => 'en',
				'entityId' => new ItemId( 'Q2' ), // not a property
				'termText' => 'Two',
			] ),
			new TermIndexEntry( [
				'termType' => 'alias', // not a label
				'termLanguage' => 'en',
				'entityId' => new PropertyId( 'P3' ),
				'termText' => 'Three',
			] ),
			new TermIndexEntry( [
				'termType' => 'description', // not a label
				'termLanguage' => 'en',
				'entityId' => new PropertyId( 'P4' ),
				'termText' => 'Four',
			] ),
		];

		return [
			[ // #0
				'de',
				$terms,
				[], // labels
				[], // expected
			],
			[ // #1
				'de',
				$terms,
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
				$terms,
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
				$terms,
				[ // labels
					'Eins',
					'Zwei'
				],
				[] // expected
			],
			[ // #4
				'en',
				$terms,
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

}
