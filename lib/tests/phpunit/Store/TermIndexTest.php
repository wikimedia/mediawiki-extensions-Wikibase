<?php

namespace Wikibase\Lib\Tests\Store;

use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\TermIndex;
use Wikibase\TermIndexEntry;

/**
 * Base class for tests for classes implementing Wikibase\TermIndex.
 *
 * @group Wikibase
 *
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Anja Jentzsch < anja.jentzsch@wikimedia.de >
 * @author Daniel Kinzler
 */
abstract class TermIndexTest extends \MediaWikiTestCase {

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

		if ( $term->getType() !== null ) {
			$key .= $term->getType();
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

		return array( $item0, $item1, $item2 );
	}

	public function provideGetMatchingTerms() {
		list( $item0, $item1, $item2 ) = $this->getTestItems();

		return array(
			'cross-language match' => array(
				array( // $entities
					$item0, $item1
				),
				array( // $queryTerms
					new TermIndexEntry( array(
						'termText' => 'mittens', // should match
					) ),
					new TermIndexEntry( array(
						'termText' => 'Kittens', // case doesn't match
					) ),
					new TermIndexEntry( array(
						'termText' => 'Mitt', // prefix isn't sufficient
					) ),
					new TermIndexEntry( array(
						'termLanguage' => 'en', // language mismatch
						'termText' => 'Mittens',
					) ),
					new TermIndexEntry( array(
						'termType' => 'alias', // type mismatch
						'termText' => 'Mittens',
					) ),
				),
				null, // $termTypes
				null, // $entityTypes
				array(), // $options
				array( // $expectedTermKeys
					'Q11/label.nl:mittens',
				)
			),
			'case insensitive prefix' => array(
				array( // $entities
					$item0, $item1
				),
				array( // $queryTerms
					new TermIndexEntry( array(
						'termLanguage' => 'de', // language mismatch
						'termText' => 'kitt',
					) ),
					new TermIndexEntry( array(
						'termText' => 'mitt', // prefix should match regardless of case
					) ),
				),
				null, // $termTypes
				null, // $entityTypes
				array( // $options
					'caseSensitive' => false,
					'prefixSearch' => true,
				),
				array( // $expectedTermKeys
					'Q11/label.nl:mittens',
					'Q11/label.de:Mittens',
				)
			),
			'restrict by term type' => array(
				array( // $entities
					$item0, $item1
				),
				array( // $queryTerms
					new TermIndexEntry( array(
						'termText' => 'mittens',
					) ),
				),
				TermIndexEntry::TYPE_ALIAS, // $termTypes
				null, // $entityTypes
				array(), // $options
				array(), // $expectedTermKeys
			),
			'allow multiple term type' => array(
				array( // $entities
					$item0, $item1
				),
				array( // $queryTerms
					new TermIndexEntry( array(
						'termText' => 'mittens',
					) ),
				),
				array( TermIndexEntry::TYPE_ALIAS, TermIndexEntry::TYPE_LABEL ), // $termTypes
				null, // $entityTypes
				array(), // $options
				array( // $expectedTermKeys
					'Q11/label.nl:mittens',
				)
			),
			'restrict by entity type' => array(
				array( // $entities
					$item0, $item1
				),
				array( // $queryTerms
					new TermIndexEntry( array(
						'termText' => 'mittens',
					) ),
				),
				null, // $termTypes
				Property::ENTITY_TYPE, // $entityTypes
				array(), // $options
				array(), // $expectedTermKeys
			),
			'allow multiple entity type' => array(
				array( // $entities
					$item0, $item1
				),
				array( // $queryTerms
					new TermIndexEntry( array(
						'termText' => 'mittens',
					) ),
				),
				null, // $termTypes
				array( Property::ENTITY_TYPE, Item::ENTITY_TYPE ), // $entityTypes
				array(), // $options
				array( // $expectedTermKeys
					'Q11/label.nl:mittens',
				)
			),
			'orderByWeight, prefixSearch and caseSensitive options' => array(
				array( // $entities
					$item0, $item1, $item2
				),
				array( // $queryTerms
					new TermIndexEntry( array(
						'termText' => 'KiTTeNS',
					) ),
				),
				null, // $termTypes
				null, // $entityTypes
				array(
					'orderByWeight' => true,
					'prefixSearch' => true,
					'caseSensitive' => false,
				), // $options
				array( // $expectedTermKeys
					'Q11/label.fr:kittens love mittens',
					'Q22/label.en:KITTENS should have mittens',
					'Q22/label.sv:kittens should have mittens',
					'Q10/label.en:kittens',
				)
			),
		);
	}

	/**
	 * @dataProvider provideGetMatchingTerms
	 */
	public function testGetMatchingTerms(
		array $entities,
		array $queryTerms,
		$termTypes,
		$entityTypes,
		array $options,
		array $expectedTermKeys
	) {
		$lookup = $this->getTermIndex();

		foreach ( $entities as $entitiy ) {
			$lookup->saveTermsOfEntity( $entitiy );
		}

		$actual = $lookup->getMatchingTerms( $queryTerms, $termTypes, $entityTypes, $options );

		$this->assertInternalType( 'array', $actual );

		$actualTermKeys = array_map( array( $this, 'getTermKey' ), $actual );

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

		return array(
			'EXACT MATCH not prefix, case sensitive' => array(
				array( // $entities
					$item0, $item1, $item2
				),
				array( // $queryTerms
					new TermIndexEntry( array(
						'termText' => 'Mittens',
					) ),
				),
				null, // $termTypes
				null, // $entityTypes
				array(
					'prefixSearch' => false,
					'caseSensitive' => true,
				), // $options
				array( // $expectedTermKeys
					'Q11/label.de:Mittens',
				),
			),
			'prefixSearch and not caseSensitive' => array(
				array( // $entities
					$item0, $item1, $item2
				),
				array( // $queryTerms
					new TermIndexEntry( array(
						'termText' => 'KiTTeNS',
					) ),
				),
				null, // $termTypes
				null, // $entityTypes
				array(
					'prefixSearch' => true,
					'caseSensitive' => false,
				), // $options
				array( // $expectedTermKeys
					'Q11/label.fr:kittens love mittens',
					'Q22/label.en:KITTENS should have mittens',
					// If not asking for top terms the below would normally also be expected
					//'Q22/label.sv:kittens should have mittens',
					'Q10/label.en:kittens',
				),
			),
			'prefixSearch and not caseSensitive LIMIT 1' => array(
				array( // $entities
					$item0, $item1, $item2
				),
				array( // $queryTerms
					new TermIndexEntry( array(
						'termText' => 'KiTTeNS',
					) ),
				),
				null, // $termTypes
				null, // $entityTypes
				array(
					'prefixSearch' => true,
					'caseSensitive' => false,
					'LIMIT' => 1,
				), // $options
				array( // $expectedTermKeys
					'Q11/label.fr:kittens love mittens',
				),
			),
		);
	}

	/**
	 * @dataProvider provideGetTopMatchingTerms
	 */
	public function testGetTopMatchingTerms(
		array $entities,
		array $queryTerms,
		$termTypes,
		$entityTypes,
		array $options,
		array $expectedTermKeys
	) {
		$lookup = $this->getTermIndex();

		foreach ( $entities as $entitiy ) {
			$lookup->saveTermsOfEntity( $entitiy );
		}

		$actual = $lookup->getTopMatchingTerms( $queryTerms, $termTypes, $entityTypes, $options );

		$this->assertInternalType( 'array', $actual );

		$actualTermKeys = array_map( array( $this, 'getTermKey' ), $actual );
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
		$item->setAliases( 'fr', array( 'o', '_', 'O' ) );

		$lookup->saveTermsOfEntity( $item );

		$this->assertTermExists( $lookup, 'testDeleteTermsForEntity' );

		$this->assertTrue( $lookup->deleteTermsOfEntity( $item->getId() ) !== false );

		$this->assertNotTermExists( $lookup, 'testDeleteTermsForEntity' );

		$abc = new TermIndexEntry( array( 'termType' => TermIndexEntry::TYPE_LABEL, 'termText' => 'abc' ) );
		$matchedTerms = $lookup->getMatchingTerms( array( $abc ), array( TermIndexEntry::TYPE_LABEL ), Item::ENTITY_TYPE );
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
		$item->setDescription( 'en', 'testDeleteTermsForEntity' );
		$item->setAliases( 'fr', array( 'o', '_', 'O' ) );

		$this->assertTrue( $lookup->saveTermsOfEntity( $item ) );

		$this->assertTermExists( $lookup,
			'testDeleteTermsForEntity',
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
			'testDeleteTermsForEntity',
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
			'testDeleteTermsForEntity',
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
		$item->setAliases( 'en', array( 'ABC', '_', 'X' ) );
		$item->setAliases( 'de', array( 'DEF', '_', 'Y' ) );
		$item->setAliases( 'nl', array( 'GHI', '_', 'Z' ) );

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
		$item->setAliases( 'en', array( 'ABC', 'X', '_' ) );
		$item->setAliases( 'de', array( 'DEF', 'Y' ) );
		$item->setAliases( 'nl', array( '_', 'Z', 'foo' ) );

		$lookup->saveTermsOfEntity( $item );

		// check that the stored terms are the ones in the modified items
		$expectedTerms = $lookup->getEntityTerms( $item );
		$actualTerms = $lookup->getTermsOfEntity( $item->getId() );

		$missingTerms = array_udiff( $expectedTerms, $actualTerms, 'Wikibase\TermIndexEntry::compare' );
		$extraTerms = array_udiff( $actualTerms, $expectedTerms, 'Wikibase\TermIndexEntry::compare' );

		$this->assertEquals( array(), $missingTerms, 'Missing terms' );
		$this->assertEquals( array(), $extraTerms, 'Extra terms' );
	}

	private function getTermConflictEntities() {
		$entities = array(
			$this->makeTermConflictItem( 'Q1', 'de', 'Foo', 'Bar' ),
			$this->makeTermConflictItem( 'Q2', 'de', 'Bar', 'Foo' ),
			$this->makeTermConflictItem( 'Q3', 'en', 'Foo', 'Bar' ),
			$this->makeTermConflictItem( 'Q4', 'en', 'Bar', 'Foo' ),
			$this->makeTermConflictItem( 'Q5', 'de', 'Foo', 'Quux' )
		);

		$deFooBarP6 = Property::newFromType( 'string' );
		$deFooBarP6->setId( new PropertyId( 'P6' ) );
		$deFooBarP6->setLabel( 'de', 'Foo' );
		$deFooBarP6->setAliases( 'de', array( 'AFoo' ) );
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

		return array(
			'conflicting label' => array(
				$entities,
				Property::ENTITY_TYPE,
				array( 'de' => 'Foo' ),
				array(),
				array( 'P6' ),
			),
			'conflicting label with different case' => array(
				$entities,
				Property::ENTITY_TYPE,
				array( 'de' => 'fOO' ),
				array(),
				array( 'P6' ),
			),
			'conflict between label and alias' => array(
				$entities,
				Property::ENTITY_TYPE,
				array( 'de' => 'AFoo' ),
				array(),
				array( 'P6' ),
			),
			'conflict between alias and label' => array(
				$entities,
				Property::ENTITY_TYPE,
				array(),
				array( 'de' => 'Foo' ),
				array( 'P6' ),
			),
			'conflicting alias' => array(
				$entities,
				Property::ENTITY_TYPE,
				array(),
				array( 'de' => 'AFoo' ),
				array( 'P6' ),
			),
			'no conflicting label' => array(
				$entities,
				Item::ENTITY_TYPE,
				array( 'de' => 'Nope' ),
				array(),
				array(),
			),
			'conflicts in multiple languages' => array(
				$entities,
				Item::ENTITY_TYPE,
				array( 'de' => 'Foo', 'en' => 'Foo' ),
				array(),
				array( 'Q1', 'Q3', 'Q5' ),
			),
		);
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

		return array(
			'by label, empty descriptions' => array(
				$entities,
				Item::ENTITY_TYPE,
				array( 'de' => 'Foo' ),
				array(),
				array(),
			),
			'by label, mismatching description' => array(
				$entities,
				Item::ENTITY_TYPE,
				array( 'de' => 'Foo' ),
				array( 'de' => 'XYZ' ),
				array(),
			),
			'by label and description' => array(
				$entities,
				Item::ENTITY_TYPE,
				array( 'de' => 'Foo' ),
				array( 'de' => 'Bar' ),
				array( 'Q1' ),
			),
			'by label and description, different label capitalization' => array(
				$entities,
				Item::ENTITY_TYPE,
				array( 'de' => 'fOO' ),
				array( 'de' => 'Bar' ),
				array( 'Q1' ),
			),
			'by label and description, different description capitalization' => array(
				$entities,
				Item::ENTITY_TYPE,
				array( 'de' => 'Foo' ),
				array( 'de' => 'bAR' ),
				array( 'Q1' ),
			),
			'two languages for label and description' => array(
				$entities,
				Item::ENTITY_TYPE,
				array( 'de' => 'Foo', 'en' => 'Foo' ),
				array( 'de' => 'Bar', 'en' => 'Bar' ),
				array( 'Q1', 'Q3' ),
			),
		);
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

	private function getEntityIdStrings( array $terms ) {
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
		$item->setAliases( 'fr', array( 'o', '_', 'O' ) );

		$this->assertTrue( $lookup->saveTermsOfEntity( $item ) );

		$labelTerms = $lookup->getTermsOfEntity( $item->getId(), array( 'label' ) );
		$this->assertEquals( 3, count( $labelTerms ), "expected 3 labels" );

		$englishTerms = $lookup->getTermsOfEntity( $item->getId(), null, array( 'en' ) );
		$this->assertEquals( 2, count( $englishTerms ), "expected 2 English terms" );

		$germanLabelTerms = $lookup->getTermsOfEntity( $item->getId(), array( 'label' ), array( 'de' ) );
		$this->assertEquals( 1, count( $germanLabelTerms ), "expected 1 German label" );

		$noTerms = $lookup->getTermsOfEntity( $item->getId(), array( 'label' ), array() );
		$this->assertEmpty( $noTerms, "expected no labels" );

		$noTerms = $lookup->getTermsOfEntity( $item->getId(), array(), array( 'de' ) );
		$this->assertEmpty( $noTerms, "expected no labels" );

		$terms = $lookup->getTermsOfEntity( $item->getId() );
		$this->assertEquals( 7, count( $terms ), "expected 7 terms for item" );

		// make list of strings for easy checking
		$term_keys = array();
		foreach ( $terms as $t ) {
			$term_keys[] = $t->getType() . '/' .  $t->getLanguage() . '/' . $t->getText();
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
		$item1->setAliases( 'fr', array( 'o', '_', 'O' ) );

		$item2 = new Item( new ItemId( 'Q87236423' ) );

		$item2->setLabel( 'en', 'xyz' );
		$item2->setLabel( 'de', 'uvw' );
		$item2->setLabel( 'nl', 'rst' );
		$item2->setDescription( 'en', 'another description' );
		$item2->setAliases( 'fr', array( 'X', '~', 'x' ) );

		$this->assertTrue( $lookup->saveTermsOfEntity( $item1 ) );
		$this->assertTrue( $lookup->saveTermsOfEntity( $item2 ) );

		$itemIds = array( $item1->getId(), $item2->getId() );

		$labelTerms = $lookup->getTermsOfEntities( $itemIds, array( TermIndexEntry::TYPE_LABEL ) );
		$this->assertEquals( 6, count( $labelTerms ), "expected 3 labels" );

		$englishTerms = $lookup->getTermsOfEntities( $itemIds, null, array( 'en' ) );
		$this->assertEquals( 4, count( $englishTerms ), "expected 2 English terms" );

		$englishTerms = $lookup->getTermsOfEntities( array( $item1->getId() ), null, array( 'en' ) );
		$this->assertEquals( 2, count( $englishTerms ), "expected 2 English terms" );

		$germanLabelTerms = $lookup->getTermsOfEntities( $itemIds, array( TermIndexEntry::TYPE_LABEL ), array( 'de' ) );
		$this->assertEquals( 2, count( $germanLabelTerms ), "expected 1 German label" );

		$noTerms = $lookup->getTermsOfEntities( $itemIds, array( TermIndexEntry::TYPE_LABEL ), array() );
		$this->assertEmpty( $noTerms, "expected no labels" );

		$noTerms = $lookup->getTermsOfEntities( $itemIds, array(), array( 'de' ) );
		$this->assertEmpty( $noTerms, "expected no labels" );

		$terms = $lookup->getTermsOfEntities( $itemIds );
		$this->assertEquals( 14, count( $terms ), "expected 7 terms for item" );

		// make list of strings for easy checking
		$term_keys = array();
		foreach ( $terms as $t ) {
			$term_keys[] = $t->getType() . '/' .  $t->getLanguage() . '/' . $t->getText();
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

	protected function assertTermExists( TermIndex $termIndex, $text, $termType = null, $language = null, $entityType = null ) {
		$this->assertTrue( $this->termExists( $termIndex, $text, $termType, $language, $entityType ) );
	}

	protected function assertNotTermExists( TermIndex $termIndex, $text, $termType = null, $language = null, $entityType = null ) {
		$this->assertFalse( $this->termExists( $termIndex, $text, $termType, $language, $entityType ) );
	}

	private function termExists( TermIndex $termIndex, $text, $termType = null, $language = null, $entityType = null ) {
		$termFields = array();
		$termFields['termText'] = $text;

		if ( $language !== null ) {
			$termFields['termLanguage'] = $language;
		}

		$matches = $termIndex->getMatchingTerms( array( new TermIndexEntry( $termFields ) ), $termType, $entityType );
		return !empty( $matches );
	}

}
