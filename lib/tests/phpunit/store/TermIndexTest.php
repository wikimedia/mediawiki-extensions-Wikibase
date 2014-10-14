<?php

namespace Wikibase\Test;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Settings;
use Wikibase\Term;
use Wikibase\TermIndex;

/**
 * Base class for tests for calsses implementing Wikibase\TermIndex.
 *
 * @group Wikibase
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Anja Jentzsch < anja.jentzsch@wikimedia.de >
 * @author Daniel Kinzler
 */
abstract class TermIndexTest extends \MediaWikiTestCase {

	/**
	 * @return TermIndex
	 */
	public abstract function getTermIndex();

	public function testGetEntityIdsForLabel() {
		$lookup = $this->getTermIndex();

		$item0 = Item::newEmpty();
		$id0 = new ItemId( 'Q10' );
		$item0->setId( $id0 );

		$item0->setLabel( 'en', 'foobar' );
		$item0->setLabel( 'de', 'foobar' );
		$item0->setLabel( 'nl', 'baz' );
		$lookup->saveTermsOfEntity( $item0 );

		$item1 = $item0->copy();
		$id1 = new ItemId( 'Q11' );
		$item1->setId( $id1 );

		$item1->setLabel( 'nl', 'o_O' );
		$item1->setDescription( 'en', 'foo bar baz' );
		$lookup->saveTermsOfEntity( $item1 );

		$ids = $lookup->getEntityIdsForLabel( 'foobar' );
		$this->assertInternalType( 'array', $ids );
		$this->assertContainsOnlyInstancesOf( '\Wikibase\DataModel\Entity\ItemId', $ids );
		$this->assertArrayEquals( array( $id0, $id1 ), $ids );

		$ids = $lookup->getEntityIdsForLabel( 'baz', 'nl' );
		$this->assertInternalType( 'array', $ids );
		$this->assertContainsOnlyInstancesOf( '\Wikibase\DataModel\Entity\ItemId', $ids );
		$this->assertArrayEquals( array( $id0 ), $ids );

		$ids = $lookup->getEntityIdsForLabel( 'o_O', 'nl' );
		$this->assertInternalType( 'array', $ids );
		$this->assertContainsOnlyInstancesOf( '\Wikibase\DataModel\Entity\ItemId', $ids );
		$this->assertArrayEquals( array( $id1 ), $ids );
	}

	public function testTermExists() {
		$lookup = $this->getTermIndex();

		$item = Item::newEmpty();
		$item->setId( new ItemId( 'Q1234' )  );

		$item->setLabel( 'en', 'foobarz' );
		$item->setLabel( 'de', 'foobarz' );
		$item->setLabel( 'nl', 'bazz' );
		$item->setDescription( 'en', 'foobarz' );
		$item->setDescription( 'fr', 'fooz barz bazz' );
		$item->setAliases( 'nl', array( 'a42', 'b42', 'c42' ) );

		$lookup->saveTermsOfEntity( $item );

		$this->assertFalse( $lookup->termExists( 'foobarz', 'does-not-exist' ) );
		$this->assertFalse( $lookup->termExists( 'foobarz', null, 'does-not-exist' ) );
		$this->assertFalse( $lookup->termExists( 'foobarz', null, null, 'does-not-exist' ) );

		$this->assertTrue( $lookup->termExists( 'foobarz' ) );
		$this->assertTrue( $lookup->termExists( 'foobarz', Term::TYPE_LABEL ) );
		$this->assertTrue( $lookup->termExists( 'foobarz', Term::TYPE_LABEL, 'en' ) );
		$this->assertTrue( $lookup->termExists( 'foobarz', Term::TYPE_LABEL, 'de' ) );
		$this->assertTrue( $lookup->termExists( 'foobarz', Term::TYPE_LABEL, 'de', $item::ENTITY_TYPE ) );

		$this->assertFalse( $lookup->termExists( 'foobarz', Term::TYPE_LABEL, 'de', Property::ENTITY_TYPE ) );
		$this->assertFalse( $lookup->termExists( 'foobarz', Term::TYPE_LABEL, 'nl' ) );
		$this->assertFalse( $lookup->termExists( 'foobarz', Term::TYPE_DESCRIPTION, 'de' ) );
		$this->assertFalse( $lookup->termExists( 'foobarz', Term::TYPE_DESCRIPTION, null, Property::ENTITY_TYPE ) );
		$this->assertFalse( $lookup->termExists( 'dzxfzdtrgfdrtgryfth', Term::TYPE_LABEL ) );

		$this->assertTrue( $lookup->termExists( 'foobarz', Term::TYPE_DESCRIPTION ) );
		$this->assertTrue( $lookup->termExists( 'foobarz', Term::TYPE_DESCRIPTION, 'en' ) );
		$this->assertFalse( $lookup->termExists( 'foobarz', Term::TYPE_DESCRIPTION, 'fr' ) );

		$this->assertFalse( $lookup->termExists( 'a42', Term::TYPE_DESCRIPTION ) );
		$this->assertFalse( $lookup->termExists( 'b42', Term::TYPE_LABEL ) );
		$this->assertTrue( $lookup->termExists( 'a42' ) );
		$this->assertTrue( $lookup->termExists( 'b42' ) );
		$this->assertTrue( $lookup->termExists( 'a42', Term::TYPE_ALIAS ) );
		$this->assertTrue( $lookup->termExists( 'b42', Term::TYPE_ALIAS ) );
		$this->assertFalse( $lookup->termExists( 'b42', Term::TYPE_ALIAS, 'de' ) );
		$this->assertTrue( $lookup->termExists( 'b42', null, 'nl' ) );
	}

	/**
	 * @fixme: this test is broken (and has been for a long time); will be fixed in a follow up.
	 */
	public function testGetMatchingTerms() {
		$lookup = $this->getTermIndex();

		$item0 = Item::newEmpty();
		$item0->setId( new ItemId( 'Q10' ) );
		$id0 = $item0->getId()->getSerialization();

		$item0->setLabel( 'en', 'getmatchingterms-0' );
		$lookup->saveTermsOfEntity( $item0 );

		$item1 = Item::newEmpty();
		$item1->setId( new ItemId( 'Q11' )  );
		$id1 = $item1->getId()->getSerialization();

		$item1->setLabel( 'nl', 'getmatchingterms-1' );
		$item1->setLabel( 'de', 'GeTMAtchingterms-2' );
		$lookup->saveTermsOfEntity( $item1 );

		$terms = array(
			$id0 => new Term( array(
				'termLanguage' => 'en',
				'termText' => 'getmatchingterms-0',
			) ),
			$id1 => new Term( array(
				'termText' => 'getmatchingterms-1',
			) ),
			new Term( array(
				'termText' => 'getmatchingterms-2',
			) ),
		);

		$actual = $lookup->getMatchingTerms( $terms );

		$this->assertInternalType( 'array', $actual );
		$this->assertCount( 2, $actual );
		
		/**
		 * @var Term $term
		 * @var Term $expected
		 */
		foreach ( $actual as $term ) {
			$id = $term->getEntityId()->getSerialization();

			$this->assertContains( $id, array( $id0, $id1 ) );

			$expected = $terms[$id];

			if ( $expected->getText() !== null ) {
				$this->assertEquals( $expected->getText(), $term->getText() );
			}

			if ( $expected->getLanguage() !== null ) {
				$this->assertEquals( $expected->getLanguage(), $term->getLanguage() );
			}
		}
	}

	public function testGetMatchingPrefixTerms() {
		$lookup = $this->getTermIndex();

		$item0 = Item::newEmpty();
		$item0->setLabel( 'en', 'prefix' );
		$item0->setId( new ItemId( 'Q10' ) );
		$id0 = $item0->getId()->getSerialization();
		$lookup->saveTermsOfEntity( $item0 );

		$item1 = Item::newEmpty();
		$item1->setLabel( 'nl', 'postfix' );
		$item1->setId( new ItemId( 'Q11' ) );
		$id1 = $item1->getId()->getSerialization();
		$lookup->saveTermsOfEntity( $item1 );

		/** @var Term[] $terms */
		$terms = array(
			$id0 => new Term( array(
				'termLanguage' => 'en',
				'termText' => 'preF',
			) ),
			$id1 => new Term( array(
				'termText' => 'post',
			) ),
		);

		/** @var Term[] $expectedTerms */
		$expectedTerms = array();

		if ( ! Settings::get( 'withoutTermSearchKey' ) ) {
			// case insensitive match is (probably) only found if SearchKey can be used.
			// See comment in TermSqlIndex::termsToConditions
			$expectedTerms[$id0] = new Term( array(
				'termLanguage' => 'en',
				'termText' => 'prefix',
			) );
		}

		$expectedTerms[$id1] = new Term( array(
			'termText' => 'postfix',
		) );

		$options = array(
			'caseSensitive' => Settings::get( 'withoutTermSearchKey' ),
			'prefixSearch' => true,
		);

		$actual = $lookup->getMatchingTerms( $terms, null, null, $options );

		$terms[$id1]->setLanguage( 'nl' );
		$expectedTerms[$id1]->setLanguage( 'nl' );

		$this->assertInternalType( 'array', $actual );
		$this->assertEquals( count( $expectedTerms ), count( $actual ) );

		/**
		 * @var Term $term
		 * @var Term $expected
		 */
		foreach ( $actual as $term ) {
			$id = $term->getEntityId()->getSerialization();

			$this->assertContains( $id, array( $id0, $id1 ) );

			$expected = $expectedTerms[$id];

			$this->assertEquals( $expected->getText(), $term->getText() );
			$this->assertEquals( $expected->getLanguage(), $term->getLanguage() );
		}
	}

	public function testDeleteTermsForEntity() {
		$lookup = $this->getTermIndex();

		$item = Item::newEmpty();

		$item->setLabel( 'en', 'abc' );
		$item->setLabel( 'de', 'def' );
		$item->setLabel( 'nl', 'ghi' );
		$item->setDescription( 'en', 'testDeleteTermsForEntity' );
		$item->setAliases( 'fr', array( 'o', '_', 'O' ) );

		$id = new ItemId( 'Q10' );
		$item->setId( $id );
		$lookup->saveTermsOfEntity( $item );

		$this->assertTrue( $lookup->termExists( 'testDeleteTermsForEntity' ) );

		$this->assertTrue( $lookup->deleteTermsOfEntity( $item->getId() ) !== false );

		$this->assertFalse( $lookup->termExists( 'testDeleteTermsForEntity' ) );

		$ids = $lookup->getEntityIdsForLabel( 'abc' );

		$this->assertNotContains( $id, $ids );
	}

	public function testSaveTermsOfEntity() {
		$lookup = $this->getTermIndex();

		$item = Item::newEmpty();
		$item->setId( 568431314 );

		$item->setLabel( 'en', 'abc' );
		$item->setLabel( 'de', 'def' );
		$item->setLabel( 'nl', 'ghi' );
		$item->setDescription( 'en', 'testDeleteTermsForEntity' );
		$item->setAliases( 'fr', array( 'o', '_', 'O' ) );

		$this->assertTrue( $lookup->saveTermsOfEntity( $item ) );

		$this->assertTrue( $lookup->termExists(
			'testDeleteTermsForEntity',
			Term::TYPE_DESCRIPTION,
			'en',
			Item::ENTITY_TYPE
		) );

		$this->assertTrue( $lookup->termExists(
			'ghi',
			Term::TYPE_LABEL,
			'nl',
			Item::ENTITY_TYPE
		) );

		$this->assertTrue( $lookup->termExists(
			'o',
			Term::TYPE_ALIAS,
			'fr',
			Item::ENTITY_TYPE
		) );

		// save again - this should hit an optimized code path
		// that avoids re-saving the terms if they are the same as before.
		$this->assertTrue( $lookup->saveTermsOfEntity( $item ) );

		$this->assertTrue( $lookup->termExists(
			'testDeleteTermsForEntity',
			Term::TYPE_DESCRIPTION,
			'en',
			Item::ENTITY_TYPE
		) );

		$this->assertTrue( $lookup->termExists(
			'ghi',
			Term::TYPE_LABEL,
			'nl',
			Item::ENTITY_TYPE
		) );

		$this->assertTrue( $lookup->termExists(
			'o',
			Term::TYPE_ALIAS,
			'fr',
			Item::ENTITY_TYPE
		) );

		// modify and save again - this should NOT skip saving,
		// and make sure the modified term is in the database.
		$item->setLabel( 'nl', 'xyz' );
		$this->assertTrue( $lookup->saveTermsOfEntity( $item ) );

		$this->assertTrue( $lookup->termExists(
			'testDeleteTermsForEntity',
			Term::TYPE_DESCRIPTION,
			'en',
			Item::ENTITY_TYPE
		) );

		$this->assertTrue( $lookup->termExists(
			'xyz',
			Term::TYPE_LABEL,
			'nl',
			Item::ENTITY_TYPE
		) );

		$this->assertTrue( $lookup->termExists(
			'o',
			Term::TYPE_ALIAS,
			'fr',
			Item::ENTITY_TYPE
		) );
	}

	public function testUpdateTermsOfEntity() {
		$item = Item::newEmpty();
		$item->setId( new ItemId( 'Q568431314' ) );

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
		$item->setLabel( 'en', 'abc' );
		$item->removeLabel( 'de' );
		$item->setLabel( 'nl', 'jke' );
		$item->setDescription( 'it', '-xyz-' );
		$item->setAliases( 'en', array( 'ABC', 'X', '_' ) );
		$item->setAliases( 'de', array( 'DEF', 'Y' ) );
		$item->setAliases( 'nl', array( '_', 'Z', 'foo' ) );
		$item->setDescription( 'it', 'ABC' );
		$lookup->saveTermsOfEntity( $item );

		// check that the stored terms are the ones in the modified items
		$expectedTerms = $lookup->getEntityTerms( $item );
		$actualTerms = $lookup->getTermsOfEntity( $item->getId() );

		$missingTerms = array_udiff( $expectedTerms, $actualTerms, 'Wikibase\Term::compare' );
		$extraTerms =   array_udiff( $actualTerms, $expectedTerms, 'Wikibase\Term::compare' );

		$this->assertEmpty( $missingTerms, 'Missing terms' );
		$this->assertEmpty( $extraTerms, 'Extra terms' );
	}

	private function getTermConflictEntities() {
		$deFooBar1 = Item::newEmpty();
		$deFooBar1->setId( new ItemId( 'Q1' ) );
		$deFooBar1->setLabel( 'de', 'Foo' );
		$deFooBar1->setDescription( 'de', 'Bar' );

		$deBarFoo2 = Item::newEmpty();
		$deBarFoo2->setId( new ItemId( 'Q2' ) );
		$deBarFoo2->setLabel( 'de', 'Bar' );
		$deBarFoo2->setDescription( 'de', 'Foo' );

		$enFooBar3 = Item::newEmpty();
		$enFooBar3->setId( new ItemId( 'Q3' ) );
		$enFooBar3->setLabel( 'en', 'Foo' );
		$enFooBar3->setDescription( 'en', 'Bar' );

		$enBarFoo4 = Item::newEmpty();
		$enBarFoo4->setId( new ItemId( 'Q4' ) );
		$enBarFoo4->setLabel( 'en', 'Bar' );
		$enBarFoo4->setDescription( 'en', 'Foo' );

		$deFooQuux5 = Item::newEmpty();
		$deFooQuux5->setId( new ItemId( 'Q5' ) );
		$deFooQuux5->setLabel( 'de', 'Foo' );
		$deFooQuux5->setDescription( 'de', 'Quux' );

		$deFooBarP6 = Property::newFromType( 'string' );
		$deFooBarP6->setId( new PropertyId( 'P6' ) );
		$deFooBarP6->setLabel( 'de', 'Foo' );
		$deFooBarP6->setDescription( 'de', 'Bar' );

		$entities = array(
			$deFooBar1,
			$deBarFoo2,
			$enFooBar3,
			$enBarFoo4,
			$deFooQuux5,
			$deFooBarP6,
		);

		return $entities;
	}

	public function labelConflictProvider() {
		$entities = $this->getTermConflictEntities();

		return array(
			'by label' => array(
				$entities,
				Property::ENTITY_TYPE,
				array( 'de' => 'Foo' ),
				array( 'P6' ),
			),
			'by label mismatch' => array(
				$entities,
				Item::ENTITY_TYPE,
				array( 'de' => 'Nope' ),
				array(),
			),
			'two languages for label' => array(
				$entities,
				Item::ENTITY_TYPE,
				array( 'de' => 'Foo', 'en' => 'Foo' ),
				array( 'Q1', 'Q3', 'Q5' ),
			),
		);
	}

	/**
	 * @dataProvider labelConflictProvider
	 */
	public function testGetLabelConflicts( $entities, $entityType, $labels, $expected ) {
		$termIndex = $this->getTermIndex();
		$termIndex->clear();

		foreach ( $entities as $entity ) {
			$termIndex->saveTermsOfEntity( $entity );
		}

		$matches = $termIndex->getLabelConflicts( $entityType, $labels );
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
	public function testGetLabelWithDescriptionConflicts( $entities, $entityType, $labels, $descriptions, $expected ) {
		$termIndex = $this->getTermIndex();
		$termIndex->clear();

		foreach ( $entities as $entity ) {
			$termIndex->saveTermsOfEntity( $entity );
		}

		$matches = $termIndex->getLabelWithDescriptionConflicts( $entityType, $labels, $descriptions );
		$actual = $this->getEntityIdStrings( $matches );

		$this->assertArrayEquals( $expected, $actual, false, false );
	}

	private function getEntityIdStrings( array $terms ) {
		return array_map( function( Term $term ) {
			$id = $term->getEntityId();
			return $id->getSerialization();
		}, $terms );
	}

	public function testGetTermsOfEntity() {
		$lookup = $this->getTermIndex();

		$item = Item::newEmpty();
		$item->setId( 568234314 );

		$item->setLabel( 'en', 'abc' );
		$item->setLabel( 'de', 'def' );
		$item->setLabel( 'nl', 'ghi' );
		$item->setDescription( 'en', 'testGetTermsOfEntity' );
		$item->setAliases( 'fr', array( 'o', '_', 'O' ) );

		$this->assertTrue( $lookup->saveTermsOfEntity( $item ) );

		$terms = $lookup->getTermsOfEntity( $item->getId() );

		$this->assertEquals( 7, count( $terms ), "expected 5 terms for item" );

		// make list of strings for easy checking
		$term_keys = array();
		foreach ( $terms as $t ) {
			$term_keys[] = $t->getType() . '/' .  $t->getLanguage() . '/' . $t->getText();
		}

		$k = Term::TYPE_LABEL . '/en/abc';
		$this->assertContains( $k, $term_keys,
			"expected to find $k in terms for item" );

		$k = Term::TYPE_DESCRIPTION . '/en/testGetTermsOfEntity';
		$this->assertContains( $k, $term_keys,
			"expected to find $k in terms for item" );

		$k = Term::TYPE_ALIAS . '/fr/_';
		$this->assertContains( $k, $term_keys,
			"expected to find $k in terms for item" );
	}

}
