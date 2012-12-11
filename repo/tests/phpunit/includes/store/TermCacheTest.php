<?php

namespace Wikibase\Test;
use Wikibase\TermCache;
use Wikibase\ItemContent;
use Wikibase\Item;
use Wikibase\Term;

/**
 * Tests for the Wikibase\TermCache implementing classes.
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
 * @group Wikibase
 * @group WikibaseStore
 * @group Database
 * @group TermCacheTest
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Anja Jentzsch < anja.jentzsch@wikimedia.de >
 */
class TermCacheTest extends \MediaWikiTestCase {

	public function instanceProvider() {
		$instance = \Wikibase\StoreFactory::getStore( 'sqlstore' )->newTermCache();

		$instances = array( $instance );

		return $this->arrayWrap( $instances );
	}

	/**
	 * @dataProvider instanceProvider
	 *
	 * @param TermCache $lookup
	 */
	public function testGetEntityIdsForLabel( TermCache $lookup ) {
		$item0 = Item::newEmpty();

		$item0->setLabel( 'en', 'foobar' );
		$item0->setLabel( 'de', 'foobar' );
		$item0->setLabel( 'nl', 'baz' );

		$item1 = $item0->copy();
		$item1->setLabel( 'nl', 'o_O' );
		$item1->setDescription( 'en', 'foo bar baz' );

		$content0 = ItemContent::newEmpty();
		$content0->setItem( $item0 );
		$content0->save( '', null, EDIT_NEW );
		$id0 = $content0->getItem()->getId()->getNumericId();

		$content1 = ItemContent::newEmpty();
		$content1->setItem( $item1 );

		$content1->save( '', null, EDIT_NEW );
		$id1 = $content1->getItem()->getId()->getNumericId();

		$ids = $lookup->getEntityIdsForLabel( 'foobar' );
		$this->assertInternalType( 'array', $ids );
		$ids = array_map( function( $id ) { return $id[1]; }, $ids );
		$this->assertArrayEquals( array( $id0, $id1 ), $ids );

		$ids = $lookup->getEntityIdsForLabel( 'baz', 'nl' );
		$this->assertInternalType( 'array', $ids );
		$ids = array_map( function( $id ) { return $id[1]; }, $ids );
		$this->assertArrayEquals( array( $id0 ), $ids );

		$ids = $lookup->getEntityIdsForLabel( 'o_O', 'nl' );
		$this->assertInternalType( 'array', $ids );
		$ids = array_map( function( $id ) { return $id[1]; }, $ids );
		$this->assertArrayEquals( array( $id1 ), $ids );

		// Mysql fails (http://bugs.mysql.com/bug.php?id=10327), so we cannot test this properly when using MySQL.
		if ( !defined( 'MW_PHPUNIT_TEST' )
			|| wfGetDB( DB_MASTER )->getType() !== 'mysql'
			|| get_class( $lookup ) !== 'Wikibase\TermSqlCache' ) {

			$ids = $lookup->getEntityIdsForLabel( 'foobar', 'en', 'foo bar baz' );
			$this->assertInternalType( 'array', $ids );
			$ids = array_map( function( $id ) { return $id[1]; }, $ids );
			$this->assertArrayEquals( array( $id1 ), $ids );

			$ids = $lookup->getEntityIdsForLabel( 'foobar', null, 'foo bar baz' );
			$this->assertInternalType( 'array', $ids );
			$ids = array_map( function( $id ) { return $id[1]; }, $ids );
			$this->assertArrayEquals( array( $id1 ), $ids );

			$ids = $lookup->getEntityIdsForLabel( 'foobar', 'nl', 'foo bar baz' );
			$this->assertInternalType( 'array', $ids );
			$ids = array_map( function( $id ) { return $id[1]; }, $ids );
			$this->assertArrayEquals( array(), $ids );
		}
	}

	/**
	 * @dataProvider instanceProvider
	 *
	 * @param TermCache $lookup
	 */
	public function testTermExists( TermCache $lookup ) {
		$item = Item::newEmpty();

		$item->setLabel( 'en', 'foobarz' );
		$item->setLabel( 'de', 'foobarz' );
		$item->setLabel( 'nl', 'bazz' );
		$item->setDescription( 'en', 'foobarz' );
		$item->setDescription( 'fr', 'fooz barz bazz' );
		$item->setAliases( 'nl', array( 'a42', 'b42', 'c42' ) );

		$content = ItemContent::newEmpty();
		$content->setItem( $item );
		$content->save( '', null, EDIT_NEW );

		$this->assertFalse( $lookup->termExists( 'foobarz', 'does-not-exist' ) );
		$this->assertFalse( $lookup->termExists( 'foobarz', null, 'does-not-exist' ) );
		$this->assertFalse( $lookup->termExists( 'foobarz', null, null, 'does-not-exist' ) );

		$this->assertTrue( $lookup->termExists( 'foobarz' ) );
		$this->assertTrue( $lookup->termExists( 'foobarz', Term::TYPE_LABEL ) );
		$this->assertTrue( $lookup->termExists( 'foobarz', Term::TYPE_LABEL, 'en' ) );
		$this->assertTrue( $lookup->termExists( 'foobarz', Term::TYPE_LABEL, 'de' ) );
		$this->assertTrue( $lookup->termExists( 'foobarz', Term::TYPE_LABEL, 'de', $item::ENTITY_TYPE ) );

		$this->assertFalse( $lookup->termExists( 'foobarz', Term::TYPE_LABEL, 'de', \Wikibase\Property::ENTITY_TYPE ) );
		$this->assertFalse( $lookup->termExists( 'foobarz', Term::TYPE_LABEL, 'nl' ) );
		$this->assertFalse( $lookup->termExists( 'foobarz', Term::TYPE_DESCRIPTION, 'de' ) );
		$this->assertFalse( $lookup->termExists( 'foobarz', Term::TYPE_DESCRIPTION, null, \Wikibase\Property::ENTITY_TYPE ) );
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
	 * @dataProvider instanceProvider
	 *
	 * @param TermCache $lookup
	 */
	public function testGetMatchingTerms( TermCache $lookup ) {
		$item0 = Item::newEmpty();
		$item0->setLabel( 'en', 'getmatchingterms-0' );

		$item1 = Item::newEmpty();
		$item1->setLabel( 'nl', 'getmatchingterms-1' );

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
		);

		$actual = $lookup->getMatchingTerms( $terms );

		$terms[$id1]->setLanguage( 'nl' );

		$this->assertInternalType( 'array', $actual );

		/**
		 * @var Term $term
		 * @var Term $expected
		 */
		foreach ( $actual as $term ) {
			$id = $term->getEntityId();

			$this->assertTrue( in_array( $id, array( $id0, $id1 ), true ) );

			$expected = $terms[$id];

			$this->assertEquals( $expected->getText(), $term->getText() );
			$this->assertEquals( $expected->getLanguage(), $term->getLanguage() );
		}
	}

	/**
	 * @dataProvider instanceProvider
	 *
	 * @param TermCache $lookup
	 */
	public function testGetMatchingPrefixTerms( TermCache $lookup ) {
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

	/**
	 * @dataProvider instanceProvider
	 *
	 * @param TermCache $lookup
	 */
	public function testDeleteTermsForEntity( TermCache $lookup ) {
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

	/**
	 * @dataProvider instanceProvider
	 *
	 * @param TermCache $lookup
	 */
	public function testSaveTermsOfEntity( TermCache $lookup ) {
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

	/**
	 * @dataProvider instanceProvider
	 *
	 * @param TermCache $lookup
	 */
	public function testGetMatchingTermCombination( TermCache $lookup ) {
		if ( defined( 'MW_PHPUNIT_TEST' )
			&& wfGetDB( DB_MASTER )->getType() === 'mysql'
			&& get_class( $lookup ) === 'Wikibase\TermSqlCache' ) {
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

		$actual = $lookup->getMatchingTermCombination( $terms, null, null, $id0, Item::ENTITY_TYPE );
		$this->assertTrue( $actual === array() );
	}

	/**
	 * @dataProvider instanceProvider
	 *
	 * @param TermCache $lookup
	 */
	public function testGetTermsOfEntity( TermCache $lookup ) {
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
