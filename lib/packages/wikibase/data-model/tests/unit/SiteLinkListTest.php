<?php

namespace Wikibase\DataModel\Tests;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\ItemIdSet;
use Wikibase\DataModel\SiteLink;
use Wikibase\DataModel\SiteLinkList;

/**
 * @covers Wikibase\DataModel\SiteLinkList
 *
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class SiteLinkListTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider notSiteLinksProvider
	 */
	public function testGivenNonSiteLinks_constructorThrowsException( array $notSiteLinks ) {
		$this->setExpectedException( 'InvalidArgumentException' );
		new SiteLinkList( $notSiteLinks );
	}

	public function notSiteLinksProvider() {
		return array(
			array(
				array(
					null
				)
			),

			array(
				array(
					42
				)
			),

			array(
				array(
					new SiteLink( 'foo', 'bar' ),
					42,
					new SiteLink( 'baz', 'bah' ),
				)
			),
		);
	}

	/**
	 * @dataProvider siteLinkArrayProvider
	 */
	public function testInputRoundtripsUsingIteratorToArray( array $siteLinkArray ) {
		$list = new SiteLinkList( $siteLinkArray );
		$this->assertEquals( $siteLinkArray, array_values( iterator_to_array( $list ) ) );
	}

	public function siteLinkArrayProvider() {
		return array(
			array(
				array(
				)
			),

			array(
				array(
					new SiteLink( 'foo', 'bar' )
				)
			),

			array(
				array(
					new SiteLink( 'foo', 'bar' ),
					new SiteLink( 'baz', 'bah' ),
					new SiteLink( 'hax', 'bar' ),
				)
			),
		);
	}

	public function testEmptyCollectionHasZeroSize() {
		$list = new SiteLinkList( array() );
		$this->assertCount( 0, $list );
	}

	/**
	 * @dataProvider siteLinkArrayWithDuplicateSiteIdProvider
	 */
	public function testGivenSiteIdTwice_constructorThrowsException( array $siteLinkArray ) {
		$this->setExpectedException( 'InvalidArgumentException' );
		new SiteLinkList( $siteLinkArray );
	}

	public function siteLinkArrayWithDuplicateSiteIdProvider() {
		return array(
			array(
				array(
					new SiteLink( 'foo', 'bar' ),
					new SiteLink( 'foo', 'bar' ),
				)
			),

			array(
				array(
					new SiteLink( 'foo', 'one' ),
					new SiteLink( 'baz', 'two' ),
					new SiteLink( 'foo', 'tree' ),
				)
			),
		);
	}

	public function testGetIteratorReturnsTraversableWithSiteIdKeys() {
		$list = new SiteLinkList( array(
			new SiteLink( 'first', 'one' ),
			new SiteLink( 'second', 'two' ),
			new SiteLink( 'third', 'tree' ),
		) );

		$this->assertEquals(
			array(
				'first' => new SiteLink( 'first', 'one' ),
				'second' => new SiteLink( 'second', 'two' ),
				'third' => new SiteLink( 'third', 'tree' ),
			),
			iterator_to_array( $list )
		);
	}

	public function testGivenNonString_getBySiteIdThrowsException() {
		$list = new SiteLinkList( array() );

		$this->setExpectedException( 'InvalidArgumentException' );
		$list->getBySiteId( 32202 );
	}

	public function testGivenUnknownSiteId_getBySiteIdThrowsException() {
		$link = new SiteLink( 'first', 'one' );

		$list = new SiteLinkList( array( $link ) );

		$this->setExpectedException( 'OutOfBoundsException' );
		$list->getBySiteId( 'foo' );
	}

	public function testGivenKnownSiteId_getBySiteIdReturnsSiteLink() {
		$link = new SiteLink( 'first', 'one' );

		$list = new SiteLinkList( array( $link ) );

		$this->assertEquals( $link, $list->getBySiteId( 'first' ) );
	}

	/**
	 * @dataProvider siteLinkArrayProvider
	 */
	public function testGivenTheSameSet_equalsReturnsTrue( array $links ) {
		$list = new SiteLinkList( $links );
		$this->assertTrue( $list->equals( $list ) );
		$this->assertTrue( $list->equals( unserialize( serialize( $list ) ) ) );
	}

	public function testGivenNonSiteLinkList_equalsReturnsFalse() {
		$set = new SiteLinkList();
		$this->assertFalse( $set->equals( null ) );
		$this->assertFalse( $set->equals( new \stdClass() ) );
	}

	public function testGivenDifferentList_equalsReturnsFalse() {
		$listOne = new SiteLinkList( array(
			new SiteLink( 'foo', 'spam' ),
			new SiteLink( 'bar', 'hax' ),
		) );

		$listTwo = new SiteLinkList( array(
			new SiteLink( 'foo', 'spam' ),
			new SiteLink( 'bar', 'HAX' ),
		) );

		$this->assertFalse( $listOne->equals( $listTwo ) );
	}

	public function testGivenSetWithDifferentOrder_equalsReturnsTrue() {
		$listOne = new SiteLinkList( array(
			new SiteLink( 'foo', 'spam' ),
			new SiteLink( 'bar', 'hax' ),
		) );

		$listTwo = new SiteLinkList( array(
			new SiteLink( 'bar', 'hax' ),
			new SiteLink( 'foo', 'spam' ),
		) );

		$this->assertTrue( $listOne->equals( $listTwo ) );
	}

	public function testGivenNonSiteId_removeSiteWithIdThrowsException() {
		$list = new SiteLinkList();

		$this->setExpectedException( 'InvalidArgumentException' );
		$list->removeLinkWithSiteId( array() );
	}

	public function testGivenKnownId_removeSiteWithIdRemovesIt() {
		$list = new SiteLinkList( array(
			new SiteLink( 'foo', 'spam' ),
			new SiteLink( 'bar', 'hax' ),
		) );

		$list->removeLinkWithSiteId( 'foo' );

		$this->assertFalse( $list->hasLinkWithSiteId( 'foo' ) );
		$this->assertTrue( $list->hasLinkWithSiteId( 'bar' ) );
	}

	public function testGivenUnknownId_removeSiteWithIdDoesNoOp() {
		$list = new SiteLinkList( array(
			new SiteLink( 'foo', 'spam' ),
			new SiteLink( 'bar', 'hax' ),
		) );

		$expected = clone $list;

		$list->removeLinkWithSiteId( 'baz' );

		$this->assertTrue( $expected->equals( $list ) );
	}

	public function testDifferentInstancesWithSameBadgesAreEqual() {
		$list = new SiteLinkList( array(
			new SiteLink( 'foo', 'spam', new ItemIdSet( array(
				new ItemId( 'Q42' ),
				new ItemId( 'Q1337' )
			) ) ),
		) );

		$otherInstance = unserialize( serialize( $list ) );

		$this->assertTrue( $list->equals( $otherInstance ) );
	}

	public function testAddNewSiteLink() {
		$list = new SiteLinkList();

		$list->addNewSiteLink( 'enwiki', 'cats' );
		$list->addNewSiteLink( 'dewiki', 'katzen', array( new ItemId( 'Q1' ) ) );

		$this->assertTrue( $list->equals( new SiteLinkList( array(
			new SiteLink( 'enwiki', 'cats' ),
			new SiteLink( 'dewiki', 'katzen', array( new ItemId( 'Q1' ) ) ),
		) ) ) );
	}

	public function testAddSiteLink() {
		$list = new SiteLinkList();

		$list->addSiteLink( new SiteLink( 'enwiki', 'cats' ) );
		$list->addSiteLink( new SiteLink( 'dewiki', 'katzen' ) );

		$this->assertTrue( $list->equals( new SiteLinkList( array(
			new SiteLink( 'enwiki', 'cats' ),
			new SiteLink( 'dewiki', 'katzen' ),
		) ) ) );
	}

	public function testToArray() {
		$list = new SiteLinkList();
		$list->addNewSiteLink( 'enwiki', 'foo' );
		$list->addNewSiteLink( 'dewiki', 'bar' );

		$expected = array(
			'enwiki' => new SiteLink( 'enwiki', 'foo' ),
			'dewiki' => new SiteLink( 'dewiki', 'bar' ),
		);

		$this->assertEquals( $expected, $list->toArray() );
	}

	public function testGivenNewSiteLink_setSiteLinkAddsIt() {
		$list = new SiteLinkList();
		$list->setSiteLink( new SiteLink( 'enwiki', 'foo' ) );

		$expectedList = new SiteLinkList();
		$expectedList->addNewSiteLink( 'enwiki', 'foo' );

		$this->assertEquals( $expectedList, $list );
	}

	public function testGivenSiteLinkWithExistingId_setSiteLinkReplacesIt() {
		$list = new SiteLinkList();
		$list->addNewSiteLink( 'enwiki', 'foo' );
		$list->addNewSiteLink( 'dewiki', 'bar' );
		$list->setSiteLink( new SiteLink( 'enwiki', 'HAX' ) );

		$expectedList = new SiteLinkList();
		$expectedList->addNewSiteLink( 'enwiki', 'HAX' );
		$expectedList->addNewSiteLink( 'dewiki', 'bar' );

		$this->assertEquals( $expectedList, $list );
	}

	public function testGivenNewSiteLink_setNewSiteLinkAddsIt() {
		$list = new SiteLinkList();
		$list->setNewSiteLink( 'enwiki', 'foo' );

		$expectedList = new SiteLinkList();
		$expectedList->addNewSiteLink( 'enwiki', 'foo' );

		$this->assertEquals( $expectedList, $list );
	}

	public function testGivenSiteLinkWithExistingId_setNewSiteLinkReplacesIt() {
		$list = new SiteLinkList();
		$list->addNewSiteLink( 'enwiki', 'foo' );
		$list->addNewSiteLink( 'dewiki', 'bar' );
		$list->setNewSiteLink( 'enwiki', 'HAX' );

		$expectedList = new SiteLinkList();
		$expectedList->addNewSiteLink( 'enwiki', 'HAX' );
		$expectedList->addNewSiteLink( 'dewiki', 'bar' );

		$this->assertEquals( $expectedList, $list );
	}

}
