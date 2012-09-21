<?php

namespace Wikibase\Test;
use Wikibase\TermCache as TermCache;

/**
 * Tests for the Wikibase\TermCache implementing classes.
 *
 * @file
 * @since 0.1
 *
 * @ingroup Wikibase
 * @ingroup Test
 *
 * @group Wikibase
 * @group WikibaseStore
 * @group Database
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class TermCacheTest extends \MediaWikiTestCase {

	public function instanceProvider() {
		$instance = \Wikibase\StoreFactory::getStore( 'sqlstore' )->newTermCache();

		$instances = array( $instance );

		return $this->arrayWrap( $instances );
	}

	/**
	 * @dataProvider instanceProvider
	 */
	public function testGetItemIdsForLabel( TermCache $lookup ) {
		$item0 = \Wikibase\ItemObject::newEmpty();

		$item0->setLabel( 'en', 'foobar' );
		$item0->setLabel( 'de', 'foobar' );
		$item0->setLabel( 'nl', 'baz' );

		$item1 = $item0->copy();
		$item1->setLabel( 'nl', 'o_O' );
		$item1->setDescription( 'en', 'foo bar baz' );

		$content0 = \Wikibase\ItemContent::newEmpty();
		$content0->setItem( $item0 );
		$content0->save( '', null, EDIT_NEW );
		$id0 = $content0->getItem()->getId();

		$content1 = \Wikibase\ItemContent::newEmpty();
		$content1->setItem( $item1 );

		$content1->save( '', null, EDIT_NEW );
		$id1 = $content1->getItem()->getId();

		$ids = $lookup->getItemIdsForLabel( 'foobar' );
		$this->assertInternalType( 'array', $ids );
		$this->assertArrayEquals( array( $id0, $id1 ), $ids );

		$ids = $lookup->getItemIdsForLabel( 'baz', 'nl' );
		$this->assertInternalType( 'array', $ids );
		$this->assertArrayEquals( array( $id0 ), $ids );

		$ids = $lookup->getItemIdsForLabel( 'o_O', 'nl' );
		$this->assertInternalType( 'array', $ids );
		$this->assertArrayEquals( array( $id1 ), $ids );

		// Mysql fails (http://bugs.mysql.com/bug.php?id=10327), so we cannot test this properly when using MySQL.
		if ( !defined( 'MW_PHPUNIT_TEST' )
			|| wfGetDB( DB_MASTER )->getType() !== 'mysql'
			|| get_class( $lookup ) !== 'Wikibase\TermSqlCache' ) {

			$ids = $lookup->getItemIdsForLabel( 'foobar', 'en', 'foo bar baz' );
			$this->assertInternalType( 'array', $ids );
			$this->assertArrayEquals( array( $id1 ), $ids );

			$ids = $lookup->getItemIdsForLabel( 'foobar', null, 'foo bar baz' );
			$this->assertInternalType( 'array', $ids );
			$this->assertArrayEquals( array( $id1 ), $ids );

			$ids = $lookup->getItemIdsForLabel( 'foobar', 'nl', 'foo bar baz' );
			$this->assertInternalType( 'array', $ids );
			$this->assertArrayEquals( array(), $ids );
		}
	}

	/**
	 * @dataProvider instanceProvider
	 */
	public function testTermExists( TermCache $lookup ) {
		$item = \Wikibase\ItemObject::newEmpty();

		$item->setLabel( 'en', 'foobarz' );
		$item->setLabel( 'de', 'foobarz' );
		$item->setLabel( 'nl', 'bazz' );
		$item->setDescription( 'en', 'foobarz' );
		$item->setDescription( 'fr', 'fooz barz bazz' );
		$item->setAliases( 'nl', array( 'a42', 'b42', 'c42' ) );

		$content = \Wikibase\ItemContent::newEmpty();
		$content->setItem( $item );
		$content->save( '', null, EDIT_NEW );

		$this->assertFalse( $lookup->termExists( 'foobarz', 'does-not-exist' ) );
		$this->assertFalse( $lookup->termExists( 'foobarz', null, 'does-not-exist' ) );
		$this->assertFalse( $lookup->termExists( 'foobarz', null, null, 'does-not-exist' ) );

		$this->assertTrue( $lookup->termExists( 'foobarz' ) );
		$this->assertTrue( $lookup->termExists( 'foobarz', TermCache::TERM_TYPE_LABEL ) );
		$this->assertTrue( $lookup->termExists( 'foobarz', TermCache::TERM_TYPE_LABEL, 'en' ) );
		$this->assertTrue( $lookup->termExists( 'foobarz', TermCache::TERM_TYPE_LABEL, 'de' ) );
		$this->assertTrue( $lookup->termExists( 'foobarz', TermCache::TERM_TYPE_LABEL, 'de', $item::ENTITY_TYPE ) );

		$this->assertFalse( $lookup->termExists( 'foobarz', TermCache::TERM_TYPE_LABEL, 'de', \Wikibase\Property::ENTITY_TYPE ) );
		$this->assertFalse( $lookup->termExists( 'foobarz', TermCache::TERM_TYPE_LABEL, 'nl' ) );
		$this->assertFalse( $lookup->termExists( 'foobarz', TermCache::TERM_TYPE_DESCRIPTION, 'de' ) );
		$this->assertFalse( $lookup->termExists( 'foobarz', TermCache::TERM_TYPE_DESCRIPTION, null, \Wikibase\Property::ENTITY_TYPE ) );
		$this->assertFalse( $lookup->termExists( 'dzxfzdtrgfdrtgryfth', TermCache::TERM_TYPE_LABEL ) );

		$this->assertTrue( $lookup->termExists( 'foobarz', TermCache::TERM_TYPE_DESCRIPTION ) );
		$this->assertTrue( $lookup->termExists( 'foobarz', TermCache::TERM_TYPE_DESCRIPTION, 'en' ) );
		$this->assertFalse( $lookup->termExists( 'foobarz', TermCache::TERM_TYPE_DESCRIPTION, 'fr' ) );

		$this->assertFalse( $lookup->termExists( 'a42', TermCache::TERM_TYPE_DESCRIPTION ) );
		$this->assertFalse( $lookup->termExists( 'b42', TermCache::TERM_TYPE_LABEL ) );
		$this->assertTrue( $lookup->termExists( 'a42' ) );
		$this->assertTrue( $lookup->termExists( 'b42' ) );
		$this->assertTrue( $lookup->termExists( 'a42', TermCache::TERM_TYPE_ALIAS ) );
		$this->assertTrue( $lookup->termExists( 'b42', TermCache::TERM_TYPE_ALIAS ) );
		$this->assertFalse( $lookup->termExists( 'b42', TermCache::TERM_TYPE_ALIAS, 'de' ) );
		$this->assertTrue( $lookup->termExists( 'b42', null, 'nl' ) );
	}

	/**
	 * @dataProvider instanceProvider
	 */
	public function testGetMatchingTerms( TermCache $lookup ) {
		$item0 = \Wikibase\ItemObject::newEmpty();
		$item0->setLabel( 'en', 'getmatchingterms-0' );

		$item1 = \Wikibase\ItemObject::newEmpty();
		$item1->setLabel( 'nl', 'getmatchingterms-1' );

		$content0 = \Wikibase\ItemContent::newEmpty();
		$content0->setItem( $item0 );
		$content0->save( '', null, EDIT_NEW );
		$id0 = $content0->getItem()->getId();

		$content1 = \Wikibase\ItemContent::newEmpty();
		$content1->setItem( $item1 );

		$content1->save( '', null, EDIT_NEW );
		$id1 = $content1->getItem()->getId();

		$terms = array(
			$id0 => array(
				'termLanguage' => 'en',
				'termText' => 'getmatchingterms-0',
			),
			$id1 => array(
				'termText' => 'getmatchingterms-1',
			),
		);

		$actual = $lookup->getMatchingTerms( $terms );

		$terms[$id1]['termLanguage'] = 'nl';

		$this->assertInternalType( 'array', $actual );

		foreach ( $actual as $term ) {
			$this->assertInternalType( 'array', $term );

			$this->assertArrayHasKey( 'termLanguage', $term );
			$this->assertArrayHasKey( 'termText', $term );
			$this->assertArrayHasKey( 'termType', $term );
			$this->assertArrayHasKey( 'entityId', $term );
			$this->assertArrayHasKey( 'entityType', $term );

			$this->assertInternalType( 'string', $term['termLanguage'] );
			$this->assertInternalType( 'string', $term['termText'] );
			$this->assertInternalType( 'integer', $term['entityId'] );
			$this->assertInternalType( 'string', $term['entityType'] );

			$this->assertTrue( in_array(
				$term['termType'],
				array(
					TermCache::TERM_TYPE_ALIAS,
					TermCache::TERM_TYPE_DESCRIPTION,
					TermCache::TERM_TYPE_LABEL
				),
				true
			) );

			$id = $term['entityId'];

			$this->assertTrue( in_array( $id, array( $id0, $id1 ), true ) );

			$expected = $terms[$id];

			$this->assertEquals( $expected['termText'], $term['termText'] );
			$this->assertEquals( $expected['termLanguage'], $term['termLanguage'] );
		}
	}

}
