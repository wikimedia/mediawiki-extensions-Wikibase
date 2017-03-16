<?php

namespace Wikibase\Lib\Tests\Store;

use HashBagOStuff;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\Term\PropertyLabelResolver;
use Wikibase\Edrsf\TermIndexEntry;
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
				'entityId' => new PropertyId( 'P1' ),
				'termText' => 'Eins',
			) ),
			new TermIndexEntry( array(
				'termType' => 'label',
				'termLanguage' => 'de',
				'entityId' => new PropertyId( 'P2' ),
				'termText' => 'Zwei',
			) ),
			new TermIndexEntry( array(
				'termType' => 'label',
				'termLanguage' => 'de',
				'entityId' => new PropertyId( 'P3' ),
				'termText' => 'Drei',
			) ),
			new TermIndexEntry( array(
				'termType' => 'label',
				'termLanguage' => 'de',
				'entityId' => new PropertyId( 'P4' ),
				'termText' => 'vier', // lower case
			) ),

			// en
			new TermIndexEntry( array(
				'termType' => 'label',
				'termLanguage' => 'en',
				'entityId' => new PropertyId( 'P1' ),
				'termText' => 'One',
			) ),
			new TermIndexEntry( array(
				'termType' => 'label',
				'termLanguage' => 'en',
				'entityId' => new ItemId( 'Q2' ), // not a property
				'termText' => 'Two',
			) ),
			new TermIndexEntry( array(
				'termType' => 'alias', // not a label
				'termLanguage' => 'en',
				'entityId' => new PropertyId( 'P3' ),
				'termText' => 'Three',
			) ),
			new TermIndexEntry( array(
				'termType' => 'description', // not a label
				'termLanguage' => 'en',
				'entityId' => new PropertyId( 'P4' ),
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
