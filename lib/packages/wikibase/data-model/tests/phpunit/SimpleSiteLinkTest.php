<?php

namespace Wikibase\DataModel\Test;

use Wikibase\DataModel\SimpleSiteLink;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;

/**
 * @covers Wikibase\DataModel\SimpleSiteLink
 *
 * @file
 * @since 0.4
 *
 * @ingroup WikibaseDataModel
 * @ingroup Test
 *
 * @group Wikibase
 * @group WikibaseDataModel
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class SimpleSiteLinkTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {
		new SimpleSiteLink( 'enwiki', 'Wikidata' );
		$this->assertTrue( true );
	}

	/**
	 * @dataProvider siteIdProvider
	 */
	public function testGetSiteId( $siteId ) {
		$siteLink = new SimpleSiteLink( $siteId, 'Wikidata' );
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
	 * @dataProvider stuffThatIsNotStringProvider
	 */
	public function testCannotConstructWithNonStringSiteId( $invalidSiteId ) {
		$this->setExpectedException( 'InvalidArgumentException' );
		new SimpleSiteLink( $invalidSiteId, 'Wikidata' );
	}

	public function stuffThatIsNotStringProvider() {
		$argLists = array();

		$argLists[] = array( 42 );
		$argLists[] = array( true );
		$argLists[] = array( array() );
		$argLists[] = array( null );

		return $argLists;
	}

	/**
	 * @dataProvider pageNameProvider
	 */
	public function testGetPageName( $pageName ) {
		$siteLink = new SimpleSiteLink( 'enwiki', $pageName );
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
	 * @dataProvider stuffThatIsNotStringProvider
	 */
	public function testCannotConstructWithNonStringPageName( $invalidPageName ) {
		$this->setExpectedException( 'InvalidArgumentException' );
		new SimpleSiteLink( 'enwiki', $invalidPageName );
	}

	/**
	 * @dataProvider badgesProvider
	 */
	public function testGetBadges( $badges, $expected ) {
		$siteLink = new SimpleSiteLink( 'enwiki', 'Wikidata', $badges );
		$this->assertEquals( $expected, $siteLink->getBadges() );
	}

	public function badgesProvider() {
		$argLists = array();

		$badges = array();
		$expected = array_values( $badges );

		$argLists[] = array( $badges, $expected );


		$badges = array(
			new ItemId( "q149" )
		);
		$expected = array_values( $badges );

		$argLists[] = array( $badges, $expected );


		// removing from the middle of array
		$badges = array(
			new ItemId( "q36" ),
			new ItemId( "q149" ),
			new ItemId( "q7" )
		);

		$key = array_search(
			new ItemId( "q149" ),
			$badges
		);
		unset( $badges[$key] );

		$expected = array_values( $badges );

		$argLists[] = array( $badges, $expected );


		return $argLists;
	}

	/**
	 * @dataProvider stuffThatIsNotArrayProvider
	 */
	public function testCannotConstructWithNonArrayBadges( $invalidBadges ) {
		$this->setExpectedException( 'InvalidArgumentException' );
		new SimpleSiteLink( 'enwiki', 'Wikidata', $invalidBadges );
	}

	public function stuffThatIsNotArrayProvider() {
		$argLists = array();

		$argLists[] = array( 42 );
		$argLists[] = array( true );
		$argLists[] = array( 'nyan nyan' );
		$argLists[] = array( null );

		return $argLists;
	}

	/**
	 * @dataProvider stuffThatIsNotArrayOfItemIdsProvider
	 */
	public function testCannotConstructWithNonArrayOfItemIdsBadges( $invalidBadges ) {
		$this->setExpectedException( 'InvalidArgumentException' );
		new SimpleSiteLink( 'enwiki', 'Wikidata', $invalidBadges );
	}

	public function stuffThatIsNotArrayOfItemIdsProvider() {
		$argLists = array();

		$argLists[] = array( array(
			'nyan',
			42
		) );
		$argLists[] = array( array(
			'nyan',
			array()
		) );
		$argLists[] = array( array(
			new PropertyId( "p2" ),
			new ItemId( "q149" )
		) );
		$argLists[] = array( array(
			new PropertyId( "p2" ),
			new PropertyId( "p3" )
		) );

		return $argLists;
	}
}
