<?php

namespace Wikibase\Test;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Store\BufferingTermLookup;
use Wikibase\TermIndex;
use Wikibase\TermIndexEntry;

/**
 * @covers Wikibase\Store\BufferingTermLookup
 * @covers Wikibase\Lib\Store\EntityTermLookupBase
 *
 * @group Wikibase
 * @group WikibaseLib
 * @group WikibaseStore
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class BufferingTermLookupTest extends EntityTermLookupTest {

	protected function getEntityTermLookup() {
		$termIndex = $this->getTermIndex();
		return new BufferingTermLookup( $termIndex, 10 );
	}

	public function testPrefetchTerms() {
		$termLookup = $this->getEntityTermLookup();

		$q116 = new ItemId( 'Q116' );
		$q117 = new ItemId( 'Q117' );

		$items = array( $q116, $q117 );
		$types = array( 'label' );
		$languages = array( 'en', 'de' );

		$termLookup->prefetchTerms( $items, $types, $languages );

		$this->assertEquals( 'New York City', $termLookup->getPrefetchedTerm( $q116, 'label', 'en' ) );
		$this->assertFalse(
			$termLookup->getPrefetchedTerm( $q116, 'label', 'de' ),
			'A term that was checked but not found should yield false'
		);
		$this->assertNull(
			$termLookup->getPrefetchedTerm( $q116, 'description', 'en' ),
			'A term that was never checked should yield null'
		);
	}

	/**
	 * Returns a TermIndex that expects a specific number of calls to getTermsOfEntity and
	 * getTermsOfEntities. These calls will filter the result correctly by language, but ignore the
	 * term type or item id. Terms in three languages are defined: en, de, and fr.
	 *
	 * @param int $getTermsOfEntityCalls
	 * @param int $getTermsOfEntitiesCalls
	 *
	 * @return TermIndex
	 */
	private function getRestrictedTermIndex( $getTermsOfEntityCalls, $getTermsOfEntitiesCalls ) {
		$terms = array(
			'en' => new TermIndexEntry( array(
				'termType' => 'label',
				'termLanguage' => 'en',
				'termText' => 'Vienna',
				'entityType' => 'item',
				'entityId' => 123
			) ),
			'de' => new TermIndexEntry( array(
				'termType' => 'label',
				'termLanguage' => 'de',
				'termText' => 'Wien',
				'entityType' => 'item',
				'entityId' => 123
			) ),
			'fr' => new TermIndexEntry( array(
				'termType' => 'label',
				'termLanguage' => 'fr',
				'termText' => 'Vienne',
				'entityType' => 'item',
				'entityId' => 123
			) ),
		);

		$termIndex = $this->getMock( TermIndex::class );

		$termIndex->expects( $this->exactly( $getTermsOfEntityCalls ) )
			->method( 'getTermsOfEntity' )
			->will( $this->returnCallback( function(
				EntityId $id,
				array $termTypes = null,
				array $languageCodes = null
			) use ( $terms ) {
				return array_intersect_key( $terms, array_flip( $languageCodes ) );
			} ) );

		$termIndex->expects( $this->exactly( $getTermsOfEntitiesCalls ) )
			->method( 'getTermsOfEntities' )
			->will( $this->returnCallback( function(
				array $entityIds,
				array $termTypes = null,
				array $languageCodes = null
			) use ( $terms ) {
				return array_intersect_key( $terms, array_flip( $languageCodes ) );
			} ) );

		return $termIndex;
	}

	public function testGetLabels_prefetch() {
		$termIndex = $this->getRestrictedTermIndex( 1, 1 );
		$lookup = new BufferingTermLookup( $termIndex, 10 );

		// This should trigger a call to getTermsOfEntities
		$q116 = new ItemId( 'Q123' );
		$lookup->prefetchTerms( array( $q116 ), array( 'label' ), array( 'en', 'de' ) );

		// This should trigger no call to the TermIndex
		$expected = array( 'de' => 'Wien' );
		$this->assertEquals( $expected, $lookup->getLabels( $q116, array( 'de' ) ) );

		// This should trigger a call to getTermsOfEntity
		$expected = array( 'de' => 'Wien', 'en' => 'Vienna', 'fr' => 'Vienne' );
		$this->assertEquals( $expected, $lookup->getLabels( $q116, array( 'de', 'en', 'fr' ) ) );

		// This should trigger no more calls, since all languages are in the buffer now.
		$expected = array( 'de' => 'Wien', 'fr' => 'Vienne' );
		$this->assertEquals( $expected, $lookup->getLabels( $q116, array( 'de', 'fr' ) ) );
	}

	public function testGetLabels_buffer() {
		$termIndex = $this->getRestrictedTermIndex( 2, 0 );
		$lookup = new BufferingTermLookup( $termIndex, 10 );
		$q116 = new ItemId( 'Q123' );

		// This should trigger one call to getTermsOfEntity
		$expected = array( 'de' => 'Wien', 'en' => 'Vienna' );
		$this->assertEquals( $expected, $lookup->getLabels( $q116, array( 'de', 'en', 'it' ) ) );

		// This should trigger no more calls, since the label for 'en' is in the buffer
		$this->assertEquals( 'Vienna', $lookup->getLabel( $q116, 'en' ) );

		// This should trigger no more calls to the TermIndex, since the label for 'it' is in the
		// buffer as a negative entry.
		$this->assertEquals( array(), $lookup->getLabels( $q116, array( 'it' ) ) );

		// This should trigger one call to getTermsOfEntity
		$this->assertEquals( 'Vienne', $lookup->getLabel( $q116, 'fr' ) );

		// This should trigger no more calls to the TermIndex, since all languages are in the buffer
		// now.
		$expected = array( 'de' => 'Wien', 'fr' => 'Vienne' );
		$this->assertEquals( $expected, $lookup->getLabels( $q116, array( 'de', 'fr' ) ) );
	}

}
