<?php

namespace Wikibase\DataModel\Tests;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\ItemIdSet;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\SiteLink;

/**
 * @covers \Wikibase\DataModel\SiteLink
 *
 * @group Wikibase
 * @group WikibaseDataModel
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Michał Łazowik
 * @author Thiemo Kreuz
 */
class SiteLinkTest extends \PHPUnit\Framework\TestCase {

	public function testCanConstruct() {
		new SiteLink( 'enwiki', 'Wikidata' );
		$this->assertTrue( true );
	}

	/**
	 * @dataProvider siteIdProvider
	 */
	public function testGetSiteId( $siteId ) {
		$siteLink = new SiteLink( $siteId, 'Wikidata' );
		$this->assertSame( $siteId, $siteLink->getSiteId() );
	}

	public function siteIdProvider() {
		$argLists = [];

		$argLists[] = [ 'enwiki' ];
		$argLists[] = [ 'nlwiki' ];
		$argLists[] = [ 'Nyan!' ];

		return $argLists;
	}

	/**
	 * @dataProvider invalidStringIdentifierProvider
	 */
	public function testCannotConstructWithNonStringSiteId( $invalidSiteId ) {
		$this->expectException( InvalidArgumentException::class );
		new SiteLink( $invalidSiteId, 'Wikidata' );
	}

	public function invalidStringIdentifierProvider() {
		return [
			[ null ],
			[ true ],
			[ 42 ],
			[ '' ],
			[ [] ],
		];
	}

	/**
	 * @dataProvider pageNameProvider
	 */
	public function testGetPageName( $pageName ) {
		$siteLink = new SiteLink( 'enwiki', $pageName );
		$this->assertSame( $pageName, $siteLink->getPageName() );
	}

	public function pageNameProvider() {
		$argLists = [];

		$argLists[] = [ 'Wikidata' ];
		$argLists[] = [ 'Nyan_Cat' ];
		$argLists[] = [ 'NYAN DATA ALL ACROSS THE SKY ~=[,,_,,]:3' ];

		return $argLists;
	}

	/**
	 * @dataProvider invalidStringIdentifierProvider
	 */
	public function testCannotConstructWithNonStringPageName( $invalidPageName ) {
		$this->expectException( InvalidArgumentException::class );
		new SiteLink( 'enwiki', $invalidPageName );
	}

	/**
	 * @dataProvider badgesProvider
	 */
	public function testGetBadges( $badges, $expected ) {
		$siteLink = new SiteLink( 'enwiki', 'Wikidata', $badges );
		$this->assertEquals( $expected, $siteLink->getBadges() );
	}

	public function badgesProvider() {
		$argLists = [];

		$argLists[] = [ null, [] ];

		$badges = [];
		$expected = array_values( $badges );

		$argLists[] = [ $badges, $expected ];

		$badges = [
			new ItemId( 'Q149' ),
		];
		$expected = array_values( $badges );

		$argLists[] = [ $badges, $expected ];

		// removing from the middle of array
		$badges = [
			new ItemId( 'Q36' ),
			new ItemId( 'Q149' ),
			new ItemId( 'Q7' ),
		];

		$key = array_search(
			new ItemId( 'Q149' ),
			$badges
		);
		unset( $badges[$key] );

		$expected = array_values( $badges );

		$argLists[] = [ $badges, $expected ];

		return $argLists;
	}

	/**
	 * @dataProvider invalidBadgesProvider
	 */
	public function testCannotConstructWithInvalidBadges( $invalidBadges ) {
		$this->expectException( InvalidArgumentException::class );
		new SiteLink( 'enwiki', 'Wikidata', $invalidBadges );
	}

	public function invalidBadgesProvider() {
		return [
			// Stuff that's not an array
			[ true ],
			[ 42 ],
			[ 'nyan nyan' ],
			// Arrays with stuff that's not even an object
			[ [ 'nyan', 42 ] ],
			[ [ 'nyan', [] ] ],
			// Arrays with Entities that aren't Items
			[ [ new NumericPropertyId( 'P2' ), new ItemId( 'Q149' ) ] ],
			[ [ new NumericPropertyId( 'P2' ), new NumericPropertyId( 'P3' ) ] ],
		];
	}

	/**
	 * @dataProvider linkProvider
	 */
	public function testSelfComparisonReturnsTrue( SiteLink $link ) {
		$this->assertTrue( $link->equals( $link ) );

		$linkCopy = unserialize( serialize( $link ) );
		$this->assertTrue( $link->equals( $linkCopy ) );
		$this->assertTrue( $linkCopy->equals( $link ) );
	}

	public function linkProvider() {
		return [
			[ new SiteLink( 'foo', 'Bar' ) ],
			[ new SiteLink( 'foo', 'Bar', [ new ItemId( 'Q42' ), new ItemId( 'Q9001' ) ] ) ],
			[ new SiteLink( 'foo', 'foo' ) ],
		];
	}

	/**
	 * @dataProvider nonEqualityProvider
	 */
	public function testGivenNonEqualLinks_equalsReturnsFalse( SiteLink $linkOne, SiteLink $linkTwo ) {
		$this->assertFalse( $linkOne->equals( $linkTwo ) );
		$this->assertFalse( $linkTwo->equals( $linkOne ) );
	}

	public function nonEqualityProvider() {
		return [
			[
				new SiteLink( 'foo', 'bar' ),
				new SiteLink( 'foo', 'Bar' ),
			],
			[
				new SiteLink( 'foo', 'bar' ),
				new SiteLink( 'Foo', 'bar' ),
			],
			[
				new SiteLink( 'foo', 'bar' ),
				new SiteLink( 'foo', 'bar', [ new ItemId( 'Q42' ) ] ),
			],
			[
				new SiteLink( 'foo', 'bar', [ new ItemId( 'Q42' ) ] ),
				new SiteLink( 'foo', 'bar', [ new ItemId( 'Q42' ), new ItemId( 'Q9001' ) ] ),
			],
		];
	}

	public function testCanConstructWithItemIdSet() {
		$badgesArray = [
			new ItemId( 'Q36' ),
			new ItemId( 'Q149' ),
		];
		$badges = new ItemIdSet( $badgesArray );

		$siteLink = new SiteLink( 'foo', 'bar', $badges );

		$this->assertEquals( $badgesArray, $siteLink->getBadges() );
	}

	public function testGivenNonItemIdCollectionForBadges_constructorThrowsException() {
		$this->expectException( InvalidArgumentException::class );
		new SiteLink( 'foo', 'bar', 42 );
	}

}
