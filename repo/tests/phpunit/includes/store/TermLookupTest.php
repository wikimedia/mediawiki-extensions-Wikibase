<?php

namespace Wikibase\Test;
use Wikibase\TermLookup as TermLookup;

/**
 * Tests for the Wikibase\TermLookup implementing classes.
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
class TermLookupTest extends \MediaWikiTestCase {

	public function instanceProvider() {
		$instances = array( new \Wikibase\TermSqlLookup() );

		return $this->arrayWrap( $instances );
	}

	/**
	 * @dataProvider instanceProvider
	 */
	public function testGetItemIdsForLabel( TermLookup $lookup ) {
		$item0 = \Wikibase\ItemObject::newEmpty();

		$item0->setLabel( 'en', 'foobar' );
		$item0->setLabel( 'de', 'foobar' );
		$item0->setLabel( 'nl', 'baz' );

		$item1 = $item0->copy();
		$item1->setLabel( 'nl', 'o_O' );
		$item1->setDescription( 'en', 'foo bar baz' );

		$content0 = \Wikibase\ItemContent::newEmpty();
		$content0->setItem( $item0 );
		$content0->save();
		$id0 = $content0->getItem()->getId();

		$content1 = \Wikibase\ItemContent::newEmpty();
		$content1->setItem( $item1 );

		$content1->save();
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
			|| get_class( $lookup ) !== 'Wikibase\TermSqlLookup' ) {

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

}
