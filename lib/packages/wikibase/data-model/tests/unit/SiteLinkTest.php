<?php

namespace Wikibase\DataModel\Tests;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\ItemIdSet;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\SiteLink;

/**
 * @covers Wikibase\DataModel\SiteLink
 *
 * @group Wikibase
 * @group WikibaseDataModel
 *
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Michał Łazowik
 * @author Thiemo Mättig
 */
class SiteLinkTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {
		new SiteLink( 'enwiki', 'Wikidata' );
		$this->assertTrue( true );
	}

	/**
	 * @dataProvider siteIdProvider
	 */
	public function testGetSiteId( $siteId ) {
		$siteLink = new SiteLink( $siteId, 'Wikidata' );
		$this->assertEquals( $siteId, $siteLink->getSiteId() );
	}

	public function siteIdProvider() {
		$argLists = array();

		$argLists[] = array( 'enwiki' );
		$argLists[] = array( 'nlwiki' );
		$argLists[] = array( 'Nyan!' );

		return $argLists;
	}

	/**
	 * @dataProvider invalidStringIdentifierProvider
	 * @expectedException InvalidArgumentException
	 */
	public function testCannotConstructWithNonStringSiteId( $invalidSiteId ) {
		new SiteLink( $invalidSiteId, 'Wikidata' );
	}

	public function invalidStringIdentifierProvider() {
		return array(
			array( null ),
			array( true ),
			array( 42 ),
			array( '' ),
			array( array() ),
		);
	}

	/**
	 * @dataProvider pageNameProvider
	 */
	public function testGetPageName( $pageName ) {
		$siteLink = new SiteLink( 'enwiki', $pageName );
		$this->assertEquals( $pageName, $siteLink->getPageName() );
	}

	public function pageNameProvider() {
		$argLists = array();

		$argLists[] = array( 'Wikidata' );
		$argLists[] = array( 'Nyan_Cat' );
		$argLists[] = array( 'NYAN DATA ALL ACROSS THE SKY ~=[,,_,,]:3' );

		return $argLists;
	}

	/**
	 * @dataProvider invalidStringIdentifierProvider
	 * @expectedException InvalidArgumentException
	 */
	public function testCannotConstructWithNonStringPageName( $invalidPageName ) {
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
		$argLists = array();

		$argLists[] = array( null, array() );

		$badges = array();
		$expected = array_values( $badges );

		$argLists[] = array( $badges, $expected );

		$badges = array(
			new ItemId( 'Q149' )
		);
		$expected = array_values( $badges );

		$argLists[] = array( $badges, $expected );

		// removing from the middle of array
		$badges = array(
			new ItemId( 'Q36' ),
			new ItemId( 'Q149' ),
			new ItemId( 'Q7' )
		);

		$key = array_search(
			new ItemId( 'Q149' ),
			$badges
		);
		unset( $badges[$key] );

		$expected = array_values( $badges );

		$argLists[] = array( $badges, $expected );

		return $argLists;
	}

	/**
	 * @dataProvider invalidBadgesProvider
	 * @expectedException InvalidArgumentException
	 */
	public function testCannotConstructWithInvalidBadges( $invalidBadges ) {
		new SiteLink( 'enwiki', 'Wikidata', $invalidBadges );
	}

	public function invalidBadgesProvider() {
		return array(
			// Stuff that's not an array
			array( true ),
			array( 42 ),
			array( 'nyan nyan' ),
			// Arrays with stuff that's not even an object
			array( array( 'nyan', 42 ) ),
			array( array( 'nyan', array() ) ),
			// Arrays with Entities that aren't Items
			array( array( new PropertyId( 'P2' ), new ItemId( 'Q149' ) ) ),
			array( array( new PropertyId( 'P2' ), new PropertyId( 'P3' ) ) ),
		);
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
		return array(
			array( new SiteLink( 'foo', 'Bar' ) ),
			array( new SiteLink( 'foo', 'Bar', array( new ItemId( 'Q42' ), new ItemId( 'Q9001' ) ) ) ),
			array( new SiteLink( 'foo', 'foo' ) ),
		);
	}

	/**
	 * @dataProvider nonEqualityProvider
	 */
	public function testGivenNonEqualLinks_equalsReturnsFalse( SiteLink $linkOne, SiteLink $linkTwo ) {
		$this->assertFalse( $linkOne->equals( $linkTwo ) );
		$this->assertFalse( $linkTwo->equals( $linkOne ) );
	}

	public function nonEqualityProvider() {
		return array(
			array(
				new SiteLink( 'foo', 'bar' ),
				new SiteLink( 'foo', 'Bar' ),
			),
			array(
				new SiteLink( 'foo', 'bar' ),
				new SiteLink( 'Foo', 'bar' ),
			),
			array(
				new SiteLink( 'foo', 'bar' ),
				new SiteLink( 'foo', 'bar', array( new ItemId( 'Q42' ) ) ),
			),
			array(
				new SiteLink( 'foo', 'bar', array( new ItemId( 'Q42' ) ) ),
				new SiteLink( 'foo', 'bar', array( new ItemId( 'Q42' ), new ItemId( 'Q9001' ) ) ),
			),
		);
	}

	public function testCanConstructWithItemIdSet() {
		$badgesArray = array(
			new ItemId( 'Q36' ),
			new ItemId( 'Q149' ),
		);
		$badges = new ItemIdSet( $badgesArray );

		$siteLink = new SiteLink( 'foo', 'bar', $badges );

		$this->assertEquals( $badgesArray, $siteLink->getBadges() );
	}

	public function testGivenNonItemIdCollectionForBadges_constructorThrowsException() {
		$this->setExpectedException( 'InvalidArgumentException' );
		new SiteLink( 'foo', 'bar', 42 );
	}

}
