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
 * @author Michał Łazowik
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
			new PropertyId( 'P2' ),
			new ItemId( 'Q149' )
		) );
		$argLists[] = array( array(
			new PropertyId( 'P2' ),
			new PropertyId( 'P3' )
		) );

		return $argLists;
	}

	/**
	 * @dataProvider newFromArrayProvider
	 */
	public function testNewFromArray( $siteLink, $array ) {
		$this->assertEquals( SimpleSiteLink::newFromArray( 'enwiki', $array ), $siteLink );
	}

	public function newFromArrayProvider() {
		$argLists = array();

		$siteLink = new SimpleSiteLink(
			'enwiki',
			'Nyan Cat'
		);
		$array = array(
			'name' => 'Nyan Cat',
		);
		$argLists[] = array( $siteLink, $array );

		return array_merge( $this->siteLinkProvider(), $argLists );
	}

	/**
	 * @dataProvider siteLinkProvider
	 */
	public function testToArrayRoundtrip( $siteLink, $array ) {
		$this->assertEquals( $siteLink->toArray(), $array );
		$this->assertEquals( SimpleSiteLink::newFromArray( 'enwiki', $siteLink->toArray() ), $siteLink );
	}

	public function siteLinkProvider() {
		$argLists = array();

		$siteLink = new SimpleSiteLink(
			'enwiki',
			'Nyan Cat',
			array(
				new ItemId( "Q149" )
			)
		);
		$array = array(
			'name' => 'Nyan Cat',
			'badges' => array(
				"Q149"
			)
		);
		$argLists[] = array( $siteLink, $array );

		$siteLink = new SimpleSiteLink(
			'enwiki',
			'Nyan Cat'
		);
		$array = array(
			'name' => 'Nyan Cat',
			'badges' => array()
		);
		$argLists[] = array( $siteLink, $array );

		$siteLink = new SimpleSiteLink(
			'enwiki',
			'Nyan Cat',
			array(
				new ItemId( "Q149" ),
				new ItemId( "Q3" )
			)
		);
		$array = array(
			'name' => 'Nyan Cat',
			'badges' => array(
				"Q149",
				"Q3"
			)
		);
		$argLists[] = array( $siteLink, $array );

		return $argLists;
	}

	/**
	 * @dataProvider legacySiteLinkProvider
	 */
	public function testLegacyArrayConversion( $siteLink, $data ) {
		$this->assertEquals( SimpleSiteLink::newFromArray( 'enwiki', $data ), $siteLink );
	}

	public function legacySiteLinkProvider() {
		$argLists = array();

		$siteLink = new SimpleSiteLink(
			'enwiki',
			'Nyan Cat'
		);
		$name = 'Nyan Cat';
		$argLists[] = array( $siteLink, $name );


		return $argLists;
	}

	/**
	 * @dataProvider wrongSerializationProvider
	 */
	public function testWrongSerialization( $data ) {
		$this->setExpectedException( 'InvalidArgumentException' );
		SimpleSiteLink::newFromArray( 'enwiki', $data );
	}

	public function wrongSerializationProvider() {
		$argLists = array();

		$argLists[] = array( true );

		$argLists[] = array( 42 );

		$argLists[] = array( array(
			'Nyan Takeover!' => 149,
			'badges' => array()
		) );

		$argLists[] = array( array(
			'name' => "Wikidata",
			'badges' => "not an array"
		) );


		return $argLists;
	}

}
