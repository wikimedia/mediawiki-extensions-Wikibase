<?php

namespace Wikibase\Lib\Tests\Store;

use HashBagOStuff;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\Term\PropertyLabelResolver;
use Wikibase\TermIndexEntry;
use Wikibase\TermPropertyLabelResolver;

/**
 * @covers Wikibase\TermPropertyLabelResolver
 *
 * @group Wikibase
 * @group WikibaseLib
 * @group WikibaseStore
 *
 * @license GPL-2.0+
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
		$terms = array(
			// de
			new TermIndexEntry( array(
				'termType' => 'label',
				'termLanguage' => 'de',
				'entityId' => 1,
				'entityType' => Property::ENTITY_TYPE,
				'termText' => 'Eins',
			) ),
			new TermIndexEntry( array(
				'termType' => 'label',
				'termLanguage' => 'de',
				'entityId' => 2,
				'entityType' => Property::ENTITY_TYPE,
				'termText' => 'Zwei',
			) ),
			new TermIndexEntry( array(
				'termType' => 'label',
				'termLanguage' => 'de',
				'entityId' => 3,
				'entityType' => Property::ENTITY_TYPE,
				'termText' => 'Drei',
			) ),
			new TermIndexEntry( array(
				'termType' => 'label',
				'termLanguage' => 'de',
				'entityId' => 4,
				'entityType' => Property::ENTITY_TYPE,
				'termText' => 'vier', // lower case
			) ),

			// en
			new TermIndexEntry( array(
				'termType' => 'label',
				'termLanguage' => 'en',
				'entityId' => 1,
				'entityType' => Property::ENTITY_TYPE,
				'termText' => 'One',
			) ),
			new TermIndexEntry( array(
				'termType' => 'label',
				'termLanguage' => 'en',
				'entityId' => 2,
				'entityType' => Item::ENTITY_TYPE, // not a property
				'termText' => 'Two',
			) ),
			new TermIndexEntry( array(
				'termType' => 'alias', // not a label
				'termLanguage' => 'en',
				'entityId' => 3,
				'entityType' => Property::ENTITY_TYPE,
				'termText' => 'Three',
			) ),
			new TermIndexEntry( array(
				'termType' => 'description', // not a label
				'termLanguage' => 'en',
				'entityId' => 4,
				'entityType' => Property::ENTITY_TYPE,
				'termText' => 'Four',
			) ),
		);

		return array(
			array( // #0
				'de',
				$terms,
				array(), // labels
				array(), // expected
			),
			array( // #1
				'de',
				$terms,
				array( // labels
					'Eins',
					'Zwei'
				),
				array( // expected
					'Eins' => new PropertyId( 'P1' ),
					'Zwei' => new PropertyId( 'P2' ),
				)
			),
			array( // #2
				'de',
				$terms,
				array( // labels
					'Drei',
					'Vier'
				),
				array( // expected
					'Drei' => new PropertyId( 'P3' ),
				)
			),
			array( // #3
				'en',
				$terms,
				array( // labels
					'Eins',
					'Zwei'
				),
				array() // expected
			),
			array( // #4
				'en',
				$terms,
				array( // labels
					'One',
					'Two',
					'Three',
					'Four'
				),
				array( // expected
					'One' => new PropertyId( 'P1' ),
				)
			),
		);
	}

}
