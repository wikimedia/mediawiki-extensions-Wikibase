<?php

namespace Wikibase\Test;
use Wikibase\TermIndex;
use Wikibase\ItemContent;
use Wikibase\Item;
use Wikibase\Entity;
use Wikibase\Term;

/**
 * Base class for tests for calsses implementing Wikibase\TermIndex.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 * @since 0.1
 *
 * @ingroup WikibaseRepoTest
 * @ingroup Test
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Anja Jentzsch < anja.jentzsch@wikimedia.de >
 * @author Daniel Kinzler
 */
abstract class TermIndexTest extends \MediaWikiTestCase {

	/**
	 * @return \Wikibase\TermIndex
	 */
	public abstract function getTermIndex();

	/**
	 * @return Entity[]
	 */
	protected function getTestEntities() {
		static $entities = null;

		if ( $entities !== null ) {
			return $entities;
		}

		// foobar -----
		$item = Item::newEmpty();

		$item->setLabel( 'en', 'foobar' );
		$item->setLabel( 'de', 'foobar' );
		$item->setLabel( 'nl', 'baz' );
		$entities['foobar'] = $item;

		// o_O -----
		$item = $item->copy();
		$item->setLabel( 'nl', 'o_O' );
		$item->setDescription( 'en', 'foo bar baz' );
		$entities['o_O'] = $item;

		// foobarz ---
		$item = Item::newEmpty();

		$item->setLabel( 'en', 'foobarz' );
		$item->setLabel( 'de', 'foobarz' );
		$item->setLabel( 'nl', 'bazz' );
		$item->setDescription( 'en', 'foobarz' );
		$item->setDescription( 'fr', 'fooz barz bazz' );
		$item->setAliases( 'nl', array( 'a42', 'b42', 'c42' ) );

		$entities['foobarz'] = $item;

		return $entities;
	}

	/**
	 * @param \Wikibase\TermIndex $index
	 *
	 * @return Entity[]
	 */
	protected function fillTestIndex( TermIndex $index ) {
		$entities = $this->getTestEntities();
		$ids = array();

		$i = 1;

		foreach ( $entities as $handle => $entity ) {
			if ( $entity->getId() === null ) {
				$entity->setId( $i++ );
			}

			$index->saveTermsOfEntity( $entity );
			$ids[$handle] = $entity->getId()->getPrefixedId();
		}

		return $ids;
	}

	public static function provideGetEntityIdsForLabel() {
		return array(
			array( // #0
				'foobar', // label
				null, // languageCode
				null, // description
				null, // entityType
				false, // fuzzy
				array( 'foobar', 'o_O' )
			),
			array( // #1
				'baz', // label
				'nl', // languageCode
				null, // description
				Item::ENTITY_TYPE, // entityType
				false, // fuzzy
				array( 'foobar' )
			),
			array( // #2
				'o_O', // label
				'nl', // languageCode
				null, // description
				null, // entityType
				false, // fuzzy
				array( 'o_O' )
			),
			array( // #3
				'o_O', // label
				'fr', // languageCode
				null, // description
				null, // entityType
				false, // fuzzy
				array()
			),
			array( // #4: mismatch because sensitive
				'FooBar', // label
				null, // languageCode
				null, // description
				null, // entityType
				false, // fuzzy
				array()
			),
			array( // #5: match because insensitive
				'FooBar', // label
				'en', // languageCode
				null, // description
				null, // entityType
				true, // fuzzy
				array( 'foobar' )
			),
			array( // #6: match because prefix
				'foo', // label
				'en', // languageCode
				null, // description
				null, // entityType
				true, // fuzzy
				array( 'foobar' )
			),
			array( // #7: mismatch because entity type
				'foobar', // label
				null, // languageCode
				null, // description
				\Wikibase\Property::ENTITY_TYPE, // entityType
				false, // fuzzy
				array()
			),
			array( // #8: match description
				'foobar', // label
				'en', // languageCode
				'foo bar baz', // description
				null, // entityType
				false, // fuzzy
				array( 'foobar' )
			),
			array( // #9: match description
				'foobar', // label
				null, // languageCode
				'foo bar baz', // description
				null, // entityType
				false, // fuzzy
				array( 'foobar' )
			),
			array( // #10: match description fails
				'foobar', // label
				'nl', // languageCode
				'foo bar baz', // description
				null, // entityType
				false, // fuzzy
				array()
			),
		);
	}

	/**
	 * @dataProvider provideGetEntityIdsForLabel
	 */
	public function testGetEntityIdsForLabel( $label, $languageCode, $description,
										$entityType, $fuzzySearch,
										$expected ) {

		if ( $fuzzySearch && \Wikibase\Settings::get( 'withoutTermSearchKey' ) ) {
			$this->markTestSkipped( "skipping test for fuzzy search because withoutTermSearchKey is off" );
		}

		$lookup = $this->getTermIndex();
		$idsByHandle = $this->fillTestIndex( $lookup );

		// Mysql fails (http://bugs.mysql.com/bug.php?id=10327), so we cannot test this properly when using MySQL.
		if ( $description !== null
			&& wfGetDB( DB_MASTER )->getType() === 'mysql'
			&& get_class( $lookup ) === 'Wikibase\TermSqlIndex' ) {

			$this->markTestSkipped( "skipping test that requires a self-join" );
		}

		$result = $lookup->getEntityIdsForLabel( $label, $languageCode, $description,
					$entityType, $fuzzySearch );

		$this->assertInternalType( 'array', $result );
		$ids = array_map( function( $id ) {
			$id = new \Wikibase\EntityId( $id[0], $id[1] );
			return $id->getPrefixedId();
		}, $result );

		$expectedIds = array_intersect_key( $idsByHandle, array_flip( $expected ) );
		$this->assertArrayEquals( $expectedIds, $ids );
	}

	public static function provideTermExists() {
		return array(
			array( 'foobarz', 'does-not-exist', null, null, false ),
			array( 'foobarz', null, 'does-not-exist', null, false ),
			array( 'foobarz', null, null, 'does-not-exist', false ),

			array( 'foobarz', null, null, null, true ),
			array( 'foobarz', Term::TYPE_LABEL, null, null, true ),
			array( 'foobarz', Term::TYPE_LABEL, 'en', null, true ),
			array( 'foobarz', Term::TYPE_LABEL, 'de', null, true ),
			array( 'foobarz', Term::TYPE_LABEL, 'de', Item::ENTITY_TYPE, true ),

			array( 'foobarz', Term::TYPE_LABEL, 'de', \Wikibase\Property::ENTITY_TYPE, false ),
			array( 'foobarz', Term::TYPE_LABEL, 'nl', null, false ),
			array( 'foobarz', Term::TYPE_DESCRIPTION, 'de', null, false ),
			array( 'foobarz', Term::TYPE_DESCRIPTION, null, \Wikibase\Property::ENTITY_TYPE, false ),
			array( 'dzxfzdtrgfdrtgryfth', Term::TYPE_LABEL, null, null, false ),

			array( 'foobarz', Term::TYPE_DESCRIPTION, null, null, true ),
			array( 'foobarz', Term::TYPE_DESCRIPTION, 'en', null, true ),
			array( 'foobarz', Term::TYPE_DESCRIPTION, 'fr', null, false ),

			array( 'a42', Term::TYPE_DESCRIPTION, null, null, false ),
			array( 'b42', Term::TYPE_LABEL, null, null, false ),
			array( 'a42', null, null, null, true ),
			array( 'b42', null, null, null, true ),
			array( 'a42', Term::TYPE_ALIAS, null, null, true ),
			array( 'b42', Term::TYPE_ALIAS, null, null, true ),
			array( 'b42', Term::TYPE_ALIAS, 'de', null, false ),
			array( 'b42', null, 'nl', null, true ),
		);
	}

	/**
	 * @dataProvider provideTermExists
	 */
	public function testTermExists( $termValue, $termType, $termLanguage, $entityType, $expected ) {
		$lookup = $this->getTermIndex();
		$this->fillTestIndex( $lookup );

		$res = $lookup->termExists( $termValue, $termType, $termLanguage, $entityType );
		$this->assertEquals( $expected, $res );
	}

	/**
	 * @fixme: this test is broken (and has been for a long time); will be fixed in a follow up.
	 */
	public function testGetMatchingTerms() {
		$lookup = $this->getTermIndex();

		$item0 = Item::newEmpty();
		$item0->setLabel( 'en', 'getmatchingterms-0' );

		$item1 = Item::newEmpty();
		$item1->setLabel( 'nl', 'getmatchingterms-1' );
		$item1->setLabel( 'de', 'GeTMAtchingterms-2' );

		$content0 = ItemContent::newEmpty();
		$content0->setItem( $item0 );
		$content0->save( '', null, EDIT_NEW );
		$id0 = $content0->getItem()->getId()->getNumericId();

		$content1 = ItemContent::newEmpty();
		$content1->setItem( $item1 );

		$content1->save( '', null, EDIT_NEW );
		$id1 = $content1->getItem()->getId()->getNumericId();

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
		$this->assertEquals( 2, count( $actual ) );
		
		/**
		 * @var Term $term
		 * @var Term $expected
		 */
		foreach ( $actual as $term ) {
			$id = $term->getEntityId();

			$this->assertTrue( in_array( $id, array( $id0, $id1 ), true ) );

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

		$item1 = Item::newEmpty();
		$item1->setLabel( 'nl', 'postfix' );

		$content0 = ItemContent::newEmpty();
		$content0->setItem( $item0 );
		$content0->save( '', null, EDIT_NEW );
		$id0 = $content0->getItem()->getId()->getNumericId();

		$content1 = ItemContent::newEmpty();
		$content1->setItem( $item1 );

		$content1->save( '', null, EDIT_NEW );
		$id1 = $content1->getItem()->getId()->getNumericId();

		$terms = array(
			$id0 => new Term( array(
				'termLanguage' => 'en',
				'termText' => 'preF',
			) ),
			$id1 => new Term( array(
				'termText' => 'post',
			) ),
		);

		$expectedTerms = array();

		if ( !\Wikibase\Settings::get( 'withoutTermSearchKey' ) ) {
			// case insensitive match is only found if SearchKey can be used.
			$expectedTerms[$id0] = new Term( array(
				'termLanguage' => 'en',
				'termText' => 'prefix',
			) );
		}

		$expectedTerms[$id1] = new Term( array(
			'termText' => 'postfix',
		) );

		$options = array(
			'caseSensitive' => false,
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
			$id = $term->getEntityId();

			$this->assertTrue( in_array( $id, array( $id0, $id1 ), true ) );

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

		$content = ItemContent::newEmpty();
		$content->setItem( $item );
		$content->save( '', null, EDIT_NEW );

		$this->assertTrue( $lookup->termExists( 'testDeleteTermsForEntity' ) );

		$this->assertTrue( $lookup->deleteTermsOfEntity( $item ) !== false );

		$this->assertFalse( $lookup->termExists( 'testDeleteTermsForEntity' ) );

		$ids = $lookup->getEntityIdsForLabel( 'abc' );
		$ids = array_map( function( $id ) { return $id[1]; }, $ids );

		$this->assertTrue( !in_array( $content->getItem()->getId()->getNumericId(), $ids, true ) );
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

	public function testGetMatchingTermCombination() {
		$lookup = $this->getTermIndex();

		if ( defined( 'MW_PHPUNIT_TEST' )
			&& wfGetDB( DB_MASTER )->getType() === 'mysql'
			&& get_class( $lookup ) === 'Wikibase\TermSqlIndex' ) {
			// Mysql fails (http://bugs.mysql.com/bug.php?id=10327), so we cannot test this properly when using MySQL.
			$this->assertTrue( true );
			return;
		}

		$item0 = Item::newEmpty();
		$item0->setLabel( 'en', 'joinedterms-0' );
		$item0->setDescription( 'de', 'joinedterms-d0' );

		$content0 = ItemContent::newEmpty();
		$content0->setItem( $item0 );
		$content0->save( '', null, EDIT_NEW );
		$id0 = $content0->getItem()->getId()->getNumericId();

		$terms = array(
			$id0 => array(
				new Term( array(
					'termLanguage' => 'en',
					'termText' => 'joinedterms-0',
				) ),
				new Term( array(
					'termLanguage' => 'de',
					'termText' => 'joinedterms-d0',
					'termType' => Term::TYPE_DESCRIPTION,
				) )
			),
		);

		$actual = $lookup->getMatchingTermCombination( $terms );

		$this->assertInternalType( 'array', $actual );

		/**
		 * @var Term $term
		 * @var Term $expected
		 */
		foreach ( $actual as $term ) {
			$id = $term->getEntityId();

			$this->assertEquals( $id0, $id );

			$isFirstElement = $term->getText() === 'joinedterms-0';
			$expected = $terms[$id][$isFirstElement ? 0 : 1];

			$this->assertEquals( $expected->getText(), $term->getText() );
			$this->assertEquals( $expected->getLanguage(), $term->getLanguage() );
		}

		$actual = $lookup->getMatchingTermCombination( $terms, null, null, $content0->getItem()->getId(), Item::ENTITY_TYPE );
		$this->assertTrue( $actual === array() );
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
		$this->assertTrue( in_array( $k, $term_keys ),
			"expected to find $k in terms for item" );

		$k = Term::TYPE_DESCRIPTION . '/en/testGetTermsOfEntity';
		$this->assertTrue( in_array( $k, $term_keys ),
			"expected to find $k in terms for item" );

		$k = Term::TYPE_ALIAS . '/fr/_';
		$this->assertTrue( in_array( $k, $term_keys ),
			"expected to find $k in terms for item" );
	}

}
