<?php

namespace Wikibase\DataModel\Test;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\SiteLink;

/**
 * @covers Wikibase\DataModel\SiteLink
 *
 * @group Wikibase
 * @group WikibaseDataModel
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Michał Łazowik
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
	 * @dataProvider stuffThatIsNotStringProvider
	 */
	public function testCannotConstructWithNonStringSiteId( $invalidSiteId ) {
		$this->setExpectedException( 'InvalidArgumentException' );
		new SiteLink( $invalidSiteId, 'Wikidata' );
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
	 * @dataProvider stuffThatIsNotStringProvider
	 */
	public function testCannotConstructWithNonStringPageName( $invalidPageName ) {
		$this->setExpectedException( 'InvalidArgumentException' );
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
		new SiteLink( 'enwiki', 'Wikidata', $invalidBadges );
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
	 * @dataProvider invalidBadgesProvider
	 */
	public function testCannotConstructWithInvalidBadges( $invalidBadges ) {
		$this->setExpectedException( 'InvalidArgumentException' );
		new SiteLink( 'enwiki', 'Wikidata', $invalidBadges );
	}

	public function invalidBadgesProvider() {
		$argLists = array();

		// non ItemIds
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

		// duplicates
		$argLists[] = array( array(
			new ItemId( 'Q42' ),
			new ItemId( 'q149' ),
			new ItemId( 'q42' )
		) );

		return $argLists;
	}

	/**
	 * @dataProvider newFromArrayProvider
	 */
	public function testNewFromArray( $siteLink, $array ) {
		$this->assertEquals( SiteLink::newFromArray( 'enwiki', $array ), $siteLink );
	}

	public function newFromArrayProvider() {
		$argLists = array();

		$siteLink = new SiteLink(
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
		$this->assertEquals( SiteLink::newFromArray( 'enwiki', $siteLink->toArray() ), $siteLink );
	}

	public function siteLinkProvider() {
		$argLists = array();

		$siteLink = new SiteLink(
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

		$siteLink = new SiteLink(
			'enwiki',
			'Nyan Cat'
		);
		$array = array(
			'name' => 'Nyan Cat',
			'badges' => array()
		);
		$argLists[] = array( $siteLink, $array );

		$siteLink = new SiteLink(
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
		$this->assertEquals( SiteLink::newFromArray( 'enwiki', $data ), $siteLink );
	}

	public function legacySiteLinkProvider() {
		$argLists = array();

		$siteLink = new SiteLink(
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
		SiteLink::newFromArray( 'enwiki', $data );
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
