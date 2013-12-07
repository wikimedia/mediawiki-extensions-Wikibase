<?php

namespace Wikibase\Test;

use Exception;
use HashBagOStuff;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Entity;
use Wikibase\EntityId;
use Wikibase\Item;
use Wikibase\Property;
use Wikibase\Term;
use Wikibase\TermIndex;
use Wikibase\TermPropertyLabelResolver;

/**
 * @covers Wikibase\TermPropertyLabelResolver
 *
 * @since 0.4
 *
 * @group Wikibase
 * @group WikibaseLib
 * @group WikibaseStore
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class TermPropertyLabelResolverTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @var int
	 */
	protected $cacheCounter = 0;

	/**
	 * @param string $lang
	 * @param Term[] $terms
	 *
	 * @return PropertyLabelResolver
	 */
	public function getResolver( $lang, $terms ) {
		$cache = $this->getMockBuilder( 'HashBagOStuff' )
			->getMock();

		$cache->expects( $this->any() )
			->method( 'get' )
			->will( $this->returnCallback( array( $this, 'cacheGetCallback' ) ) );

		$resolver = new TermPropertyLabelResolver(
			$lang,
			new MockTermIndex( $terms ),
			$cache,
			60 * 60,
			'testrepo:WBL/0.5alpha'
		);

		return $resolver;
	}

	public function cacheGetCallback( $cacheKey ) {
		++$this->cacheCounter;

		if ( $this->cacheCounter === 1 ) {
			return false;
		} else {
			return $this->getCachedLabelMap( $cacheKey );
		}
	}

	/**
	 * @dataProvider getPropertyIdsForLabelsProvider
	 */
	public function testGetPropertyIdsForLabels( $lang, array $terms, array $labels,
		array $expected, array $expectedCached
	) {
		$resolver = $this->getResolver( $lang, $terms );

		// check we are getting back the expected map of labels to IDs
		$actual = $resolver->getPropertyIdsForLabels( $labels );
		$this->assertEquals( $expected, $actual, 'not cached' );

		// check again, so we also hit the "stuff it cached" code path
		$actual = $resolver->getPropertyIdsForLabels( $labels );
		$this->assertEquals( $expected, $actual, 'in process cache' );

		// memcached
		$resolver = $this->getResolver( $lang, $terms );
		$actual = $resolver->getPropertyIdsForLabels( $labels );
		$this->assertEquals( $expectedCached, $actual, 'memcached' );

		// force recache
		$actual = $resolver->getPropertyIdsForLabels( $labels, 'recache' );
		$this->assertEquals( $expected, $actual, 'recache' );
	}

	public function getPropertyIdsForLabelsProvider() {
		$terms = $this->getTerms();

		return array(
			array( // #0
				'de',   // lang
				$terms, // terms
				array(),  // labels
				array(),  // expected
				array(), // cache expected
			),
			array( // #1
				'de',   // lang
				$terms, // terms
				array(  // labels
					'Eins',
					'Zwei'
				),
				array(  // expected
					'Eins' => new PropertyId( 'P1' ),
					'Zwei' => new PropertyId( 'P2' ),
				),
				array( // cache expected
					'Eins' => new PropertyId( 'P1' )
				),
			),
			array( // #2
				'de',   // lang
				$terms, // terms
				array(  // labels
					'Drei',
					'Vier'
				),
				array(  // expected
					'Drei' => new PropertyId( 'P3' ),
				),
				array( // cache expected
					'Drei' => new PropertyId( 'P3' )
				)
			),
			array( // #3
				'en',   // lang
				$terms, // terms
				array(  // labels
					'Eins',
					'Zwei'
				),
				array(),  // expected
				array() // cache expected
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
					'One' => new PropertyId( 'P1' ),
				),
				array( // cache expected
					'One' => new PropertyId( 'P1' ),
					'Two' => new PropertyId( 'P2' )
				)
			),
			array(
				'es',
				$terms,
				array(
					'Uno',
					'Dos'
				),
				array(),
				array()
			)
		);
	}

	private function getTerms() {
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

		return $terms;
	}

	private function getCachedLabelMap( $cacheKey ) {
		if ( $cacheKey === 'testrepo:WBL/0.5alpha/en' ) {
			$labelMap = array(
				'One' => new PropertyId( 'P1' ),
				'Two' => new PropertyId( 'P2' ),
				'Cat' => new PropertyId( 'P3' )
			);
		} elseif ( $cacheKey === 'testrepo:WBL/0.5alpha/de' ) {
			$labelMap = array(
				'Eins' => new PropertyId( 'P1' ),
				'Drei' => new PropertyId( 'P3' )
			);
		} else {
			// cache key not found
			$labelMap = false;
		}

		return $labelMap;
	}

}

