<?php

namespace Wikibase\DataModel\Test;

use Wikibase\DataModel\SimpleSiteLink;

/**
 * @covers Wikibase\DataModel\SimpleSiteLink
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
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

}
