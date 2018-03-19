<?php

namespace Wikibase\Lib\Tests\Store;

use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Lib\Store\TermIndexSearchCriteria;
use Wikibase\TermIndex;
use Wikibase\TermIndexEntry;

/**
 * Base class for tests for classes implementing Wikibase\TermIndex.
 *
 * @covers \Wikibase\TermIndex
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Anja Jentzsch < anja.jentzsch@wikimedia.de >
 * @author Daniel Kinzler
 */
abstract class TermIndexTestCase extends \MediaWikiTestCase {

	/**
	 * @return TermIndex
	 */
	abstract public function getTermIndex();

	public function getTermKey( TermIndexEntry $term ) {
		$key = '';

		if ( $term->getEntityId() !== null ) {
			$key .= $term->getEntityId()->getSerialization();
		}

		$key .= '/';

		if ( $term->getTermType() !== null ) {
			$key .= $term->getTermType();
		}

		$key .= '.';

		if ( $term->getLanguage() !== null ) {
			$key .= $term->getLanguage();
		}

		$key .= ':';

		if ( $term->getText() !== null ) {
			$key .= $term->getText();
		}

		return $key;
	}

	private function getTestItems() {
		$item0 = new Item( new ItemId( 'Q10' ) );
		$item0->setLabel( 'en', 'kittens' );

		$item1 = new Item( new ItemId( 'Q11' ) );
		$item1->setLabel( 'nl', 'mittens' );
		$item1->setLabel( 'de', 'Mittens' );
		$item1->setLabel( 'fr', 'kittens love mittens' );

		$item2 = new Item( new ItemId( 'Q22' ) );
		$item2->setLabel( 'sv', 'kittens should have mittens' );
		$item2->setLabel( 'en', 'KITTENS should have mittens' );

		return [ $item0, $item1, $item2 ];
	}

	public function provideGetMatchingTerms() {
		list( $item0, $item1, $item2 ) = $this->getTestItems();

		return [
			'cross-language match' => [
				[ // $entities
					$item0, $item1
				],
				[ // $criteria
					new TermIndexSearchCriteria( [
						'termText' => 'mittens', // should match
					] ),
					new TermIndexSearchCriteria( [
						'termText' => 'Kittens', // case doesn't match
					] ),
					new TermIndexSearchCriteria( [
						'termText' => 'Mitt', // prefix isn't sufficient
					] ),
					new TermIndexSearchCriteria( [
						'termLanguage' => 'en', // language mismatch
						'termText' => 'Mittens',
					] ),
					new TermIndexSearchCriteria( [
						'termType' => 'alias', // type mismatch
						'termText' => 'Mittens',
					] ),
				],
				null, // $termTypes
				null, // $entityTypes
				[], // $options
				[ // $expectedTermKeys
					'Q11/label.nl:mittens',
				]
			],
			'case insensitive prefix' => [
				[ // $entities
					$item0, $item1
				],
				[ // $criteria
					new TermIndexSearchCriteria( [
						'termLanguage' => 'de', // language mismatch
						'termText' => 'kitt',
					] ),
					new TermIndexSearchCriteria( [
						'termText' => 'mitt', // prefix should match regardless of case
					] ),
				],
				null, // $termTypes
				null, // $entityTypes
				[ // $options
					'caseSensitive' => false,
					'prefixSearch' => true,
				],
				[ // $expectedTermKeys
					'Q11/label.nl:mittens',
					'Q11/label.de:Mittens',
				]
			],
			'restrict by term type' => [
				[ // $entities
					$item0, $item1
				],
				[ // $criteria
					new TermIndexSearchCriteria( [
						'termText' => 'mittens',
					] ),
				],
				TermIndexEntry::TYPE_ALIAS, // $termTypes
				null, // $entityTypes
				[], // $options
				[], // $expectedTermKeys
			],
			'allow multiple term type' => [
				[ // $entities
					$item0, $item1
				],
				[ // $criteria
					new TermIndexSearchCriteria( [
						'termText' => 'mittens',
					] ),
				],
				[ TermIndexEntry::TYPE_ALIAS, TermIndexEntry::TYPE_LABEL ], // $termTypes
				null, // $entityTypes
				[], // $options
				[ // $expectedTermKeys
					'Q11/label.nl:mittens',
				]
			],
			'restrict by entity type' => [
				[ // $entities
					$item0, $item1
				],
				[ // $criteria
					new TermIndexSearchCriteria( [
						'termText' => 'mittens',
					] ),
				],
				null, // $termTypes
				Property::ENTITY_TYPE, // $entityTypes
				[], // $options
				[], // $expectedTermKeys
			],
			'allow multiple entity type' => [
				[ // $entities
					$item0, $item1
				],
				[ // $criteria
					new TermIndexSearchCriteria( [
						'termText' => 'mittens',
					] ),
				],
				null, // $termTypes
				[ Property::ENTITY_TYPE, Item::ENTITY_TYPE ], // $entityTypes
				[], // $options
				[ // $expectedTermKeys
					'Q11/label.nl:mittens',
				]
			],
			'orderByWeight, prefixSearch and caseSensitive options' => [
				[ // $entities
					$item0, $item1, $item2
				],
				[ // $criteria
					new TermIndexSearchCriteria( [
						'termText' => 'KiTTeNS',
					] ),
				],
				null, // $termTypes
				null, // $entityTypes
				[
					'orderByWeight' => true,
					'prefixSearch' => true,
					'caseSensitive' => false,
				], // $options
				[ // $expectedTermKeys
					'Q11/label.fr:kittens love mittens',
					'Q22/label.en:KITTENS should have mittens',
					'Q22/label.sv:kittens should have mittens',
					'Q10/label.en:kittens',
				]
			],
		];
	}

	/**
	 * @dataProvider provideGetMatchingTerms
	 */
	public function testGetMatchingTerms(
		array $entities,
		array $criteria,
		$termTypes,
		$entityTypes,
		array $options,
		array $expectedTermKeys
	) {
		$lookup = $this->getTermIndex();

		foreach ( $entities as $entitiy ) {
			$lookup->saveTermsOfEntity( $entitiy );
		}

		$actual = $lookup->getMatchingTerms( $criteria, $termTypes, $entityTypes, $options );

		$this->assertInternalType( 'array', $actual );

		$actualTermKeys = array_map( [ $this, 'getTermKey' ], $actual );

		if ( !array_key_exists( 'orderByWeight', $options ) || $options['orderByWeight'] === false ) {
			$this->assertArrayEquals( $expectedTermKeys, $actualTermKeys, false );
		} else {
			$this->assertArrayEquals( $expectedTermKeys, $actualTermKeys, true );
		}
	}

	/**
	 * This wraps around provideGetMatchingTerms removing duplicate keys for entityIds that are already expected
	 * @see provideGetMatchingTerms
	 */
	public function provideGetTopMatchingTerms() {
		list( $item0, $item1, $item2 ) = $this->getTestItems();

		return [
			'EXACT MATCH not prefix, case sensitive' => [
				[ // $entities
					$item0, $item1, $item2
				],
				[ // $criteria
					new TermIndexSearchCriteria( [
						'termText' => 'Mittens',
					] ),
				],
				null, // $termTypes
				null, // $entityTypes
				[ // $options
					'prefixSearch' => false,
					'caseSensitive' => true,
				],
				[ // $expectedTermKeys
					'Q11/label.de:Mittens',
				],
			],
			'prefixSearch and not caseSensitive' => [
				[ // $entities
					$item0, $item1, $item2
				],
				[ // $criteria
					new TermIndexSearchCriteria( [
						'termText' => 'KiTTeNS',
					] ),
				],
				null, // $termTypes
				null, // $entityTypes
				[ // $options
					'prefixSearch' => true,
					'caseSensitive' => false,
				],
				[ // $expectedTermKeys
					'Q11/label.fr:kittens love mittens',
					'Q22/label.en:KITTENS should have mittens',
					// If not asking for top terms the below would normally also be expected
					//'Q22/label.sv:kittens should have mittens',
					'Q10/label.en:kittens',
				],
			],
			'prefixSearch and not caseSensitive LIMIT 1' => [
				[ // $entities
					$item0, $item1, $item2
				],
				[ // $criteria
					new TermIndexSearchCriteria( [
						'termText' => 'KiTTeNS',
					] ),
				],
				null, // $termTypes
				null, // $entityTypes
				[ // $options
					'prefixSearch' => true,
					'caseSensitive' => false,
					'LIMIT' => 1,
				],
				[ // $expectedTermKeys
					'Q11/label.fr:kittens love mittens',
				],
			],
		];
	}

	/**
	 * @dataProvider provideGetTopMatchingTerms
	 */
	public function testGetTopMatchingTerms(
		array $entities,
		array $criteria,
		$termTypes,
		$entityTypes,
		array $options,
		array $expectedTermKeys
	) {
		$lookup = $this->getTermIndex();

		foreach ( $entities as $entitiy ) {
			$lookup->saveTermsOfEntity( $entitiy );
		}

		$actual = $lookup->getTopMatchingTerms( $criteria, $termTypes, $entityTypes, $options );

		$this->assertInternalType( 'array', $actual );

		$actualTermKeys = array_map( [ $this, 'getTermKey' ], $actual );
		$this->assertEquals( $expectedTermKeys, $actualTermKeys );
	}

	public function testDeleteTermsForEntity() {
		$lookup = $this->getTermIndex();

		$id = new ItemId( 'Q10' );
		$item = new Item( $id );

		$item->setLabel( 'en', 'abc' );
		$item->setLabel( 'de', 'def' );
		$item->setLabel( 'nl', 'ghi' );
		$item->setDescription( 'en', 'testDeleteTermsForEntity' );
		$item->setAliases( 'fr', [ 'o', '_', 'O' ] );

		$lookup->saveTermsOfEntity( $item );

		$this->assertTermExists( $lookup, 'testDeleteTermsForEntity' );

		$this->assertTrue( $lookup->deleteTermsOfEntity( $item->getId() ) !== false );

		$this->assertNotTermExists( $lookup, 'testDeleteTermsForEntity' );

		$abc = new TermIndexSearchCriteria( [ 'termType' => TermIndexEntry::TYPE_LABEL, 'termText' => 'abc' ] );
		$matchedTerms = $lookup->getMatchingTerms( [ $abc ], [ TermIndexEntry::TYPE_LABEL ], Item::ENTITY_TYPE );
		foreach ( $matchedTerms as $matchedTerm ) {
			if ( $matchedTerm->getEntityId() === $id ) {
				$this->fail( 'Failed to delete term or entity: ' . $id->getSerialization() );
			}
		}
	}

	public function testSaveTermsOfEntity() {
		$lookup = $this->getTermIndex();

		$item = new Item( new ItemId( 'Q568431314' ) );

		$item->setLabel( 'en', 'abc' );
		$item->setLabel( 'de', 'def' );
		$item->setLabel( 'nl', 'ghi' );
		$item->setDescription( 'en', 'testSaveTermsForEntity' );
		$item->setAliases( 'fr', [ 'o', '_', 'O' ] );

		$this->assertTrue( $lookup->saveTermsOfEntity( $item ) );

		$this->assertTermExists( $lookup,
			'testSaveTermsForEntity',
			TermIndexEntry::TYPE_DESCRIPTION,
			'en',
			Item::ENTITY_TYPE
		);

		$this->assertTermExists( $lookup,
			'ghi',
			TermIndexEntry::TYPE_LABEL,
			'nl',
			Item::ENTITY_TYPE
		);

		$this->assertTermExists( $lookup,
			'o',
			TermIndexEntry::TYPE_ALIAS,
			'fr',
			Item::ENTITY_TYPE
		);

		// save again - this should hit an optimized code path
		// that avoids re-saving the terms if they are the same as before.
		$this->assertTrue( $lookup->saveTermsOfEntity( $item ) );

		$this->assertTermExists( $lookup,
			'testSaveTermsForEntity',
			TermIndexEntry::TYPE_DESCRIPTION,
			'en',
			Item::ENTITY_TYPE
		);

		$this->assertTermExists( $lookup,
			'ghi',
			TermIndexEntry::TYPE_LABEL,
			'nl',
			Item::ENTITY_TYPE
		);

		$this->assertTermExists( $lookup,
			'o',
			TermIndexEntry::TYPE_ALIAS,
			'fr',
			Item::ENTITY_TYPE
		);

		// modify and save again - this should NOT skip saving,
		// and make sure the modified term is in the database.
		$item->setLabel( 'nl', 'xyz' );
		$this->assertTrue( $lookup->saveTermsOfEntity( $item ) );

		$this->assertTermExists( $lookup,
			'testSaveTermsForEntity',
			TermIndexEntry::TYPE_DESCRIPTION,
			'en',
			Item::ENTITY_TYPE
		);

		$this->assertTermExists( $lookup,
			'xyz',
			TermIndexEntry::TYPE_LABEL,
			'nl',
			Item::ENTITY_TYPE
		);

		$this->assertTermExists( $lookup,
			'o',
			TermIndexEntry::TYPE_ALIAS,
			'fr',
			Item::ENTITY_TYPE
		);
	}

	public function testUpdateTermsOfEntity() {
		$item = new Item( new ItemId( 'Q568431314' ) );

		// save original set of terms
		$item->setLabel( 'en', 'abc' );
		$item->setLabel( 'de', 'def' );
		$item->setLabel( 'nl', 'ghi' );
		$item->setDescription( 'en', '-abc-' );
		$item->setDescription( 'de', '-def-' );
		$item->setDescription( 'nl', '-ghi-' );
		$item->setAliases( 'en', [ 'ABC', '_', 'X' ] );
		$item->setAliases( 'de', [ 'DEF', '_', 'Y' ] );
		$item->setAliases( 'nl', [ 'GHI', '_', 'Z' ] );

		$lookup = $this->getTermIndex();
		$lookup->saveTermsOfEntity( $item );

		// modify the item and save new set of terms
		$item = new Item( new ItemId( 'Q568431314' ) );
		$item->setLabel( 'en', 'abc' );
		$item->setLabel( 'nl', 'jke' );
		$item->setDescription( 'en', '-abc-' );
		$item->setDescription( 'de', '-def-' );
		$item->setDescription( 'nl', '-ghi-' );
		$item->setDescription( 'it', 'ABC' );
		$item->setAliases( 'en', [ 'ABC', 'X', '_' ] );
		$item->setAliases( 'de', [ 'DEF', 'Y' ] );
		$item->setAliases( 'nl', [ '_', 'Z', 'foo' ] );

		$lookup->saveTermsOfEntity( $item );

		// check that the stored terms are the ones in the modified items
		$expectedTerms = $lookup->getEntityTerms( $item );
		$actualTerms = $lookup->getTermsOfEntity( $item->getId() );

		$missingTerms = array_udiff( $expectedTerms, $actualTerms, [ TermIndexEntry::class, 'compare' ] );
		$extraTerms = array_udiff( $actualTerms, $expectedTerms, [ TermIndexEntry::class, 'compare' ] );

		$this->assertEquals( [], $missingTerms, 'Missing terms' );
		$this->assertEquals( [], $extraTerms, 'Extra terms' );
	}

	private function getTermConflictEntities() {
		$entities = [
			$this->makeTermConflictItem( 'Q1', 'de', 'Foo', 'Bar' ),
			$this->makeTermConflictItem( 'Q2', 'de', 'Bar', 'Foo' ),
			$this->makeTermConflictItem( 'Q3', 'en', 'Foo', 'Bar' ),
			$this->makeTermConflictItem( 'Q4', 'en', 'Bar', 'Foo' ),
			$this->makeTermConflictItem( 'Q5', 'de', 'Foo', 'Quux' )
		];

		$deFooBarP6 = Property::newFromType( 'string' );
		$deFooBarP6->setId( new PropertyId( 'P6' ) );
		$deFooBarP6->setLabel( 'de', 'Foo' );
		$deFooBarP6->setAliases( 'de', [ 'AFoo' ] );
		$deFooBarP6->setDescription( 'de', 'Bar' );

		$entities[] = $deFooBarP6;

		return $entities;
	}

	private function makeTermConflictItem( $id, $languageCode, $label, $description ) {
		$item = new Item( new ItemId( $id ) );
		$item->setLabel( $languageCode, $label );
		$item->setDescription( $languageCode, $description );

		return $item;
	}

	public function labelConflictProvider() {
		$entities = $this->getTermConflictEntities();

		return [
			'conflicting label' => [
				$entities,
				Property::ENTITY_TYPE,
				[ 'de' => 'Foo' ],
				[],
				[ 'P6' ],
			],
			'conflicting label with different case' => [
				$entities,
				Property::ENTITY_TYPE,
				[ 'de' => 'fOO' ],
				[],
				[ 'P6' ],
			],
			'conflict between label and alias' => [
				$entities,
				Property::ENTITY_TYPE,
				[ 'de' => 'AFoo' ],
				[],
				[ 'P6' ],
			],
			'conflict between alias and label' => [
				$entities,
				Property::ENTITY_TYPE,
				[],
				[ 'de' => 'Foo' ],
				[ 'P6' ],
			],
			'conflicting alias' => [
				$entities,
				Property::ENTITY_TYPE,
				[],
				[ 'de' => 'AFoo' ],
				[ 'P6' ],
			],
			'no conflicting label' => [
				$entities,
				Item::ENTITY_TYPE,
				[ 'de' => 'Nope' ],
				[],
				[],
			],
			'conflicts in multiple languages' => [
				$entities,
				Item::ENTITY_TYPE,
				[ 'de' => 'Foo', 'en' => 'Foo' ],
				[],
				[ 'Q1', 'Q3', 'Q5' ],
			],
		];
	}

	/**
	 * @dataProvider labelConflictProvider
	 */
	public function testGetLabelConflicts(
		array $entities,
		$entityType,
		array $labels,
		array $aliases,
		array $expected
	) {
		$termIndex = $this->getTermIndex();
		$termIndex->clear();

		foreach ( $entities as $entity ) {
			$termIndex->saveTermsOfEntity( $entity );
		}

		//TODO: move this test case to LabelConflictFinderContractTester
		$matches = $termIndex->getLabelConflicts( $entityType, $labels, $aliases );
		$actual = $this->getEntityIdStrings( $matches );

		$this->assertArrayEquals( $expected, $actual, false, false );
	}

	public function labelWithDescriptionConflictProvider() {
		$entities = $this->getTermConflictEntities();

		return [
			'by label, empty descriptions' => [
				$entities,
				Item::ENTITY_TYPE,
				[ 'de' => 'Foo' ],
				[],
				[],
			],
			'by label, mismatching description' => [
				$entities,
				Item::ENTITY_TYPE,
				[ 'de' => 'Foo' ],
				[ 'de' => 'XYZ' ],
				[],
			],
			'by label and description' => [
				$entities,
				Item::ENTITY_TYPE,
				[ 'de' => 'Foo' ],
				[ 'de' => 'Bar' ],
				[ 'Q1' ],
			],
			'by label and description, different label capitalization' => [
				$entities,
				Item::ENTITY_TYPE,
				[ 'de' => 'fOO' ],
				[ 'de' => 'Bar' ],
				[ 'Q1' ],
			],
			'by label and description, different description capitalization' => [
				$entities,
				Item::ENTITY_TYPE,
				[ 'de' => 'Foo' ],
				[ 'de' => 'bAR' ],
				[ 'Q1' ],
			],
			'two languages for label and description' => [
				$entities,
				Item::ENTITY_TYPE,
				[ 'de' => 'Foo', 'en' => 'Foo' ],
				[ 'de' => 'Bar', 'en' => 'Bar' ],
				[ 'Q1', 'Q3' ],
			],
		];
	}

	/**
	 * @dataProvider labelWithDescriptionConflictProvider
	 */
	public function testGetLabelWithDescriptionConflicts(
		array $entities,
		$entityType,
		array $labels,
		array $descriptions,
		array $expected
	) {
		$termIndex = $this->getTermIndex();
		$termIndex->clear();

		foreach ( $entities as $entity ) {
			$termIndex->saveTermsOfEntity( $entity );
		}

		// FIXME: This tests the LabelConflictFinder interface!
		$matches = $termIndex->getLabelWithDescriptionConflicts( $entityType, $labels, $descriptions );
		$actual = $this->getEntityIdStrings( $matches );

		$this->assertArrayEquals( $expected, $actual, false, false );
	}

	protected function getEntityIdStrings( array $terms ) {
		return array_map( function( TermIndexEntry $term ) {
			$id = $term->getEntityId();
			return $id->getSerialization();
		}, $terms );
	}

	public function testGetTermsOfEntity() {
		$lookup = $this->getTermIndex();

		$item = new Item( new ItemId( 'Q568234314' ) );

		$item->setLabel( 'en', 'abc' );
		$item->setLabel( 'de', 'def' );
		$item->setLabel( 'nl', 'ghi' );
		$item->setDescription( 'en', 'testGetTermsOfEntity' );
		$item->setAliases( 'fr', [ 'o', '_', 'O' ] );

		$this->assertTrue( $lookup->saveTermsOfEntity( $item ) );

		$labelTerms = $lookup->getTermsOfEntity( $item->getId(), [ 'label' ] );
		$this->assertEquals( 3, count( $labelTerms ), "expected 3 labels" );

		$englishTerms = $lookup->getTermsOfEntity( $item->getId(), null, [ 'en' ] );
		$this->assertEquals( 2, count( $englishTerms ), "expected 2 English terms" );

		$germanLabelTerms = $lookup->getTermsOfEntity( $item->getId(), [ 'label' ], [ 'de' ] );
		$this->assertEquals( 1, count( $germanLabelTerms ), "expected 1 German label" );

		$noTerms = $lookup->getTermsOfEntity( $item->getId(), [ 'label' ], [] );
		$this->assertEmpty( $noTerms, "expected no labels" );

		$noTerms = $lookup->getTermsOfEntity( $item->getId(), [], [ 'de' ] );
		$this->assertEmpty( $noTerms, "expected no labels" );

		$terms = $lookup->getTermsOfEntity( $item->getId() );
		$this->assertEquals( 7, count( $terms ), "expected 7 terms for item" );

		// make list of strings for easy checking
		$term_keys = [];
		foreach ( $terms as $t ) {
			$term_keys[] = $t->getTermType() . '/' .  $t->getLanguage() . '/' . $t->getText();
		}

		$k = TermIndexEntry::TYPE_LABEL . '/en/abc';
		$this->assertContains( $k, $term_keys,
			"expected to find $k in terms for item" );

		$k = TermIndexEntry::TYPE_DESCRIPTION . '/en/testGetTermsOfEntity';
		$this->assertContains( $k, $term_keys,
			"expected to find $k in terms for item" );

		$k = TermIndexEntry::TYPE_ALIAS . '/fr/_';
		$this->assertContains( $k, $term_keys,
			"expected to find $k in terms for item" );
	}

	public function testGetTermsOfEntities() {
		$lookup = $this->getTermIndex();

		$item1 = new Item( new ItemId( 'Q568234314' ) );

		$item1->setLabel( 'en', 'abc' );
		$item1->setLabel( 'de', 'def' );
		$item1->setLabel( 'nl', 'ghi' );
		$item1->setDescription( 'en', 'one description' );
		$item1->setAliases( 'fr', [ 'o', '_', 'O' ] );

		$item2 = new Item( new ItemId( 'Q87236423' ) );

		$item2->setLabel( 'en', 'xyz' );
		$item2->setLabel( 'de', 'uvw' );
		$item2->setLabel( 'nl', 'rst' );
		$item2->setDescription( 'en', 'another description' );
		$item2->setAliases( 'fr', [ 'X', '~', 'x' ] );

		$this->assertTrue( $lookup->saveTermsOfEntity( $item1 ) );
		$this->assertTrue( $lookup->saveTermsOfEntity( $item2 ) );

		$itemIds = [ $item1->getId(), $item2->getId() ];

		$labelTerms = $lookup->getTermsOfEntities( $itemIds, [ TermIndexEntry::TYPE_LABEL ] );
		$this->assertEquals( 6, count( $labelTerms ), "expected 3 labels" );

		$englishTerms = $lookup->getTermsOfEntities( $itemIds, null, [ 'en' ] );
		$this->assertEquals( 4, count( $englishTerms ), "expected 2 English terms" );

		$englishTerms = $lookup->getTermsOfEntities( [ $item1->getId() ], null, [ 'en' ] );
		$this->assertEquals( 2, count( $englishTerms ), "expected 2 English terms" );

		$germanLabelTerms = $lookup->getTermsOfEntities( $itemIds, [ TermIndexEntry::TYPE_LABEL ], [ 'de' ] );
		$this->assertEquals( 2, count( $germanLabelTerms ), "expected 1 German label" );

		$noTerms = $lookup->getTermsOfEntities( $itemIds, [ TermIndexEntry::TYPE_LABEL ], [] );
		$this->assertEmpty( $noTerms, "expected no labels" );

		$noTerms = $lookup->getTermsOfEntities( $itemIds, [], [ 'de' ] );
		$this->assertEmpty( $noTerms, "expected no labels" );

		$terms = $lookup->getTermsOfEntities( $itemIds );
		$this->assertEquals( 14, count( $terms ), "expected 7 terms for item" );

		// make list of strings for easy checking
		$term_keys = [];
		foreach ( $terms as $t ) {
			$term_keys[] = $t->getTermType() . '/' .  $t->getLanguage() . '/' . $t->getText();
		}

		$k = TermIndexEntry::TYPE_LABEL . '/en/abc';
		$this->assertContains( $k, $term_keys,
			"expected to find $k in terms for item" );

		$k = TermIndexEntry::TYPE_LABEL . '/en/xyz';
		$this->assertContains( $k, $term_keys,
			"expected to find $k in terms for item" );

		$k = TermIndexEntry::TYPE_DESCRIPTION . '/en/another description';
		$this->assertContains( $k, $term_keys,
			"expected to find $k in terms for item" );

		$k = TermIndexEntry::TYPE_ALIAS . '/fr/x';
		$this->assertContains( $k, $term_keys,
			"expected to find $k in terms for item" );
	}

	/**
	 * @param TermIndex $termIndex
	 * @param string $text
	 * @param string|null $termType
	 * @param string|null $language
	 * @param string|null $entityType
	 */
	private function assertTermExists(
		TermIndex $termIndex,
		$text,
		$termType = null,
		$language = null,
		$entityType = null
	) {
		$this->assertTrue(
			$this->termExists( $termIndex, $text, $termType, $language, $entityType ),
			"Term \"$text\" should exist"
		);
	}

	/**
	 * @param TermIndex $termIndex
	 * @param string $text
	 */
	private function assertNotTermExists( TermIndex $termIndex, $text ) {
		$this->assertFalse(
			$this->termExists( $termIndex, $text ),
			"Term \"$text\" should not exist"
		);
	}

	/**
	 * @param TermIndex $termIndex
	 * @param string $text
	 * @param string|null $termType
	 * @param string|null $language
	 * @param string|null $entityType
	 *
	 * @return bool
	 */
	private function termExists(
		TermIndex $termIndex,
		$text,
		$termType = null,
		$language = null,
		$entityType = null
	) {
		$criteria = [];
		$criteria['termText'] = $text;

		if ( $language !== null ) {
			$criteria['termLanguage'] = $language;
		}

		$matches = $termIndex->getMatchingTerms(
			[ new TermIndexSearchCriteria( $criteria ) ],
			$termType,
			$entityType
		);
		return !empty( $matches );
	}

}
