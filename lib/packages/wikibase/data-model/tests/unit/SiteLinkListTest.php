<?php

namespace Wikibase\DataModel\Tests;

use InvalidArgumentException;
use OutOfBoundsException;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\ItemIdSet;
use Wikibase\DataModel\SiteLink;
use Wikibase\DataModel\SiteLinkList;

/**
 * @covers \Wikibase\DataModel\SiteLinkList
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class SiteLinkListTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @dataProvider notSiteLinksProvider
	 */
	public function testGivenNonSiteLinks_constructorThrowsException( array $notSiteLinks ) {
		$this->expectException( InvalidArgumentException::class );
		new SiteLinkList( $notSiteLinks );
	}

	public function notSiteLinksProvider() {
		return [
			[
				[
					null,
				],
			],

			[
				[
					42,
				],
			],

			[
				[
					new SiteLink( 'foo', 'bar' ),
					42,
					new SiteLink( 'baz', 'bah' ),
				],
			],
		];
	}

	/**
	 * @dataProvider siteLinkArrayProvider
	 */
	public function testInputRoundtripsUsingIteratorToArray( array $siteLinkArray ) {
		$list = new SiteLinkList( $siteLinkArray );
		$this->assertEquals( $siteLinkArray, array_values( iterator_to_array( $list ) ) );
	}

	public function siteLinkArrayProvider() {
		return [
			[
				[
				],
			],

			[
				[
					new SiteLink( 'foo', 'bar' ),
				],
			],

			[
				[
					new SiteLink( 'foo', 'bar' ),
					new SiteLink( 'baz', 'bah' ),
					new SiteLink( 'hax', 'bar' ),
				],
			],
		];
	}

	public function testEmptyCollectionHasZeroSize() {
		$list = new SiteLinkList( [] );
		$this->assertCount( 0, $list );
	}

	/**
	 * @dataProvider siteLinkArrayWithDuplicateSiteIdProvider
	 */
	public function testGivenSiteIdTwice_constructorThrowsException( array $siteLinkArray ) {
		$this->expectException( InvalidArgumentException::class );
		new SiteLinkList( $siteLinkArray );
	}

	public function siteLinkArrayWithDuplicateSiteIdProvider() {
		return [
			[
				[
					new SiteLink( 'foo', 'bar' ),
					new SiteLink( 'foo', 'bar' ),
				],
			],

			[
				[
					new SiteLink( 'foo', 'one' ),
					new SiteLink( 'baz', 'two' ),
					new SiteLink( 'foo', 'tree' ),
				],
			],
		];
	}

	public function testGetIteratorReturnsTraversableWithSiteIdKeys() {
		$list = new SiteLinkList( [
			new SiteLink( 'first', 'one' ),
			new SiteLink( 'second', 'two' ),
			new SiteLink( 'third', 'tree' ),
		] );

		$this->assertEquals(
			[
				'first' => new SiteLink( 'first', 'one' ),
				'second' => new SiteLink( 'second', 'two' ),
				'third' => new SiteLink( 'third', 'tree' ),
			],
			iterator_to_array( $list )
		);
	}

	public function testGivenNonString_getBySiteIdThrowsException() {
		$list = new SiteLinkList( [] );

		$this->expectException( InvalidArgumentException::class );
		$list->getBySiteId( 32202 );
	}

	public function testGivenUnknownSiteId_getBySiteIdThrowsException() {
		$link = new SiteLink( 'first', 'one' );

		$list = new SiteLinkList( [ $link ] );

		$this->expectException( OutOfBoundsException::class );
		$list->getBySiteId( 'foo' );
	}

	public function testGivenKnownSiteId_getBySiteIdReturnsSiteLink() {
		$link = new SiteLink( 'first', 'one' );

		$list = new SiteLinkList( [ $link ] );

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
		$listOne = new SiteLinkList( [
			new SiteLink( 'foo', 'spam' ),
			new SiteLink( 'bar', 'hax' ),
		] );

		$listTwo = new SiteLinkList( [
			new SiteLink( 'foo', 'spam' ),
			new SiteLink( 'bar', 'HAX' ),
		] );

		$this->assertFalse( $listOne->equals( $listTwo ) );
	}

	public function testGivenSetWithDifferentOrder_equalsReturnsTrue() {
		$listOne = new SiteLinkList( [
			new SiteLink( 'foo', 'spam' ),
			new SiteLink( 'bar', 'hax' ),
		] );

		$listTwo = new SiteLinkList( [
			new SiteLink( 'bar', 'hax' ),
			new SiteLink( 'foo', 'spam' ),
		] );

		$this->assertTrue( $listOne->equals( $listTwo ) );
	}

	public function testGivenNonSiteId_removeSiteWithIdThrowsException() {
		$list = new SiteLinkList();

		$this->expectException( InvalidArgumentException::class );
		$list->removeLinkWithSiteId( [] );
	}

	public function testGivenKnownId_removeSiteWithIdRemovesIt() {
		$list = new SiteLinkList( [
			new SiteLink( 'foo', 'spam' ),
			new SiteLink( 'bar', 'hax' ),
		] );

		$list->removeLinkWithSiteId( 'foo' );

		$this->assertFalse( $list->hasLinkWithSiteId( 'foo' ) );
		$this->assertTrue( $list->hasLinkWithSiteId( 'bar' ) );
	}

	public function testGivenUnknownId_removeSiteWithIdDoesNoOp() {
		$list = new SiteLinkList( [
			new SiteLink( 'foo', 'spam' ),
			new SiteLink( 'bar', 'hax' ),
		] );

		$expected = clone $list;

		$list->removeLinkWithSiteId( 'baz' );

		$this->assertTrue( $expected->equals( $list ) );
	}

	public function testDifferentInstancesWithSameBadgesAreEqual() {
		$list = new SiteLinkList( [
			new SiteLink( 'foo', 'spam', new ItemIdSet( [
				new ItemId( 'Q42' ),
				new ItemId( 'Q1337' ),
			] ) ),
		] );

		$otherInstance = unserialize( serialize( $list ) );

		$this->assertTrue( $list->equals( $otherInstance ) );
	}

	public function testAddNewSiteLink() {
		$list = new SiteLinkList();

		$list->addNewSiteLink( 'enwiki', 'cats' );
		$list->addNewSiteLink( 'dewiki', 'katzen', [ new ItemId( 'Q1' ) ] );

		$this->assertTrue( $list->equals( new SiteLinkList( [
			new SiteLink( 'enwiki', 'cats' ),
			new SiteLink( 'dewiki', 'katzen', [ new ItemId( 'Q1' ) ] ),
		] ) ) );
	}

	public function testAddSiteLink() {
		$list = new SiteLinkList();

		$list->addSiteLink( new SiteLink( 'enwiki', 'cats' ) );
		$list->addSiteLink( new SiteLink( 'dewiki', 'katzen' ) );

		$this->assertTrue( $list->equals( new SiteLinkList( [
			new SiteLink( 'enwiki', 'cats' ),
			new SiteLink( 'dewiki', 'katzen' ),
		] ) ) );
	}

	public function testToArray() {
		$list = new SiteLinkList();
		$list->addNewSiteLink( 'enwiki', 'foo' );
		$list->addNewSiteLink( 'dewiki', 'bar' );

		$expected = [
			'enwiki' => new SiteLink( 'enwiki', 'foo' ),
			'dewiki' => new SiteLink( 'dewiki', 'bar' ),
		];

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

	public function testEmptyListHasCountZero() {
		$this->assertSame( 0, ( new SiteLinkList() )->count() );
	}

	public function testListWithElementsHasCorrectCount() {
		$list = new SiteLinkList();
		$list->addNewSiteLink( 'enwiki', 'foo' );
		$list->addNewSiteLink( 'dewiki', 'bar' );
		$list->setNewSiteLink( 'nlwiki', 'baz' );

		$this->assertSame( 3, $list->count() );
	}

	public function testCanConstructWithIterable() {
		$links = [ new SiteLink( 'enwiki', 'foo' ) ];

		$this->assertEquals(
			new SiteLinkList( $links ),
			new SiteLinkList( new SiteLinkList( $links ) )
		);
	}

	public function testWhenProvidingNonIterable_constructorThrowsException() {
		$this->expectException( InvalidArgumentException::class );
		new SiteLinkList( new SiteLink( 'enwiki', 'foo' ) );
	}

}
