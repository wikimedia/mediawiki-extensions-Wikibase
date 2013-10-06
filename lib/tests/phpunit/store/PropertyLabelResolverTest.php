<?php

namespace Wikibase\Test;

use Wikibase\EntityId;
use Wikibase\Item;
use Wikibase\Property;
use Wikibase\PropertyLabelResolver;
use Wikibase\Term;

/**
 * Base classes for testing implementations of PropertyLabelResolver
 *
 * @since 0.4
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
abstract class PropertyLabelResolverTest extends \MediaWikiTestCase {

	/**
	 * @param string $lang
	 * @param Term[] $terms
	 *
	 * @return PropertyLabelResolver
	 */
	protected abstract function getResolver( $lang, $terms );

	public static function provideGetPropertyIdsForLabels() {
		$terms = array(
			// de
			new Term( array(
				'termType' => 'label',
				'termLanguage' => 'de',
				'entityId' => 1,
				'entityType' => Property::ENTITY_TYPE,
				'termText' => 'Eins',
			) ),
			new Term( array(
				'termType' => 'label',
				'termLanguage' => 'de',
				'entityId' => 2,
				'entityType' => Property::ENTITY_TYPE,
				'termText' => 'Zwei',
			) ),
			new Term( array(
				'termType' => 'label',
				'termLanguage' => 'de',
				'entityId' => 3,
				'entityType' => Property::ENTITY_TYPE,
				'termText' => 'Drei',
			) ),
			new Term( array(
				'termType' => 'label',
				'termLanguage' => 'de',
				'entityId' => 4,
				'entityType' => Property::ENTITY_TYPE,
				'termText' => 'vier', // lower case
			) ),

			// en
			new Term( array(
				'termType' => 'label',
				'termLanguage' => 'en',
				'entityId' => 1,
				'entityType' => Property::ENTITY_TYPE,
				'termText' => 'One',
			) ),
			new Term( array(
				'termType' => 'label',
				'termLanguage' => 'en',
				'entityId' => 2,
				'entityType' => Item::ENTITY_TYPE, // not a property
				'termText' => 'Two',
			) ),
			new Term( array(
				'termType' => 'alias', // not a label
				'termLanguage' => 'en',
				'entityId' => 3,
				'entityType' => Property::ENTITY_TYPE,
				'termText' => 'Three',
			) ),
			new Term( array(
				'termType' => 'description', // not a label
				'termLanguage' => 'en',
				'entityId' => 4,
				'entityType' => Property::ENTITY_TYPE,
				'termText' => 'Four',
			) ),
		);

		return array(
			array( // #0
				'de',   // lang
				$terms, // terms
				array(),  // labels
				array(),  // expected
			),
			array( // #1
				'de',   // lang
				$terms, // terms
				array(  // labels
					'Eins',
					'Zwei'
				),
				array(  // expected
					'Eins' => new EntityId( Property::ENTITY_TYPE, 1 ),
					'Zwei' => new EntityId( Property::ENTITY_TYPE, 2 ),
				)
			),
			array( // #2
				'de',   // lang
				$terms, // terms
				array(  // labels
					'Drei',
					'Vier'
				),
				array(  // expected
					'Drei' => new EntityId( Property::ENTITY_TYPE, 3 ),
				)
			),
			array( // #3
				'en',   // lang
				$terms, // terms
				array(  // labels
					'Eins',
					'Zwei'
				),
				array()  // expected
			),
			array( // #4
				'en',   // lang
				$terms, // terms
				array(  // labels
					'One',
					'Two',
					'Three',
					'Four'
				),
				array(  // expected
					'One' => new EntityId( Property::ENTITY_TYPE, 1 ),
				)
			),
		);
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
}