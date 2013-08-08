<?php

namespace Wikibase\DataModel\Test;

use Wikibase\DataModel\SimpleSiteLink;

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


		$badges = array( 'Nyan Certified' );
		$expected = array_values( $badges );

		$argLists[] = array( $badges, $expected );


		// removing from the middle of array
		$badges = array( 'FA', 'Nyan Certified', 'stub' );

		$key = array_search( 'Nyan Certified', $badges );
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
	 * @dataProvider stuffThatIsNotArrayOfStringsProvider
	 */
	public function testCannotConstructWithNonArrayOfStringsBadges( $invalidBadges ) {
		$this->setExpectedException( 'InvalidArgumentException' );
		new SimpleSiteLink( 'enwiki', 'Wikidata', $invalidBadges );
	}

	public function stuffThatIsNotArrayOfStringsProvider() {
		$argLists = array();

		$argLists[] = array( array( 'nyan', 42 ) );
		$argLists[] = array( array( 'nyan', true ) );
		$argLists[] = array( array( 'nyan', array() ) );
		$argLists[] = array( array( 'nyan', null ) );

		return $argLists;
	}
}
