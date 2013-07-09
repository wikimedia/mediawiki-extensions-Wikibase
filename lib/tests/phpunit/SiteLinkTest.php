<?php

namespace Wikibase\Test;

use Site;
use Wikibase\SiteLink;

/**
 * @covers Wikibase\SiteLink
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
 * @since 0.1
 *
 * @ingroup WikibaseLib
 * @ingroup Test
 *
 * @group WikibaseLib
 * @group Wikibase
 * @group WikibaseDataModel
 * @group SiteLink
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Michał Łazowik
 */
class SiteLinkTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider constructorProvider
	 */
	public function testConstructor( Site $site, $pageName ) {
		$link = new SiteLink( $site, $pageName );

		$this->assertEquals( $site, $link->getSite() );
		$this->assertEquals( $pageName, $link->getPage() );
	}

	public function constructorProvider() {
		$argLists = array();

		foreach ( $this->getSites() as $site ) {
			foreach ( array( 'Nyan', 'Nyan!', 'Main_Page' ) as $pageName ) {
				$argLists[] = array( $site, $pageName );
			}
		}

		return $argLists;
	}

	protected function getSites() {
		$sites = array();

		$enWiki = new \MediaWikiSite();
		$enWiki->setGlobalId( 'enwiki' );

		$sites[] = $enWiki;


		$nlWiki = new \MediaWikiSite();
		$nlWiki->setGlobalId( 'nlwiki' );

		$sites[] = $nlWiki;


		$fooWiki = new \Site();
		$fooWiki->setGlobalId( 'foobarbaz' );

		$sites[] = $fooWiki;

		return $sites;
	}

	/**
	 * @dataProvider constructorProvider
	 */
	public function testToString( Site $site, $pageName ) {
		$link = new SiteLink( $site, $pageName );

		$this->assertInternalType( 'string', (string)$link );

		$parts = explode( ':', (string)$link );
		$this->assertCount( 2, $parts, 'string representation should contain one colon' );

		$this->assertEquals(
			$site->getGlobalId(),
			substr( $parts[0], 2 ),
			'The first part of the string representation should be [[$globalSiteId'
		);

		$this->assertEquals(
			$pageName,
			substr( $parts[1], 0, strlen( $parts[1] ) - 2 ),
			'The second part of the string representation should be $pageName]]'
		);
	}

	/**
	 * @dataProvider constructorProvider
	 */
	public function testGetUrl( Site $site, $pageName ) {
		$link = new SiteLink( $site, $pageName );

		$this->assertEquals(
			$site->getPageUrl( $pageName ),
			$link->getUrl()
		);
	}

	/**
	 * @dataProvider siteProvider
	 */
	public function testConstructorWithNullPageName( Site $site ) {
		$this->setExpectedException( 'InvalidArgumentException' );
		new SiteLink( $site, null );
	}

	public function siteProvider() {
		$argLists = array();

		foreach ( $this->getSites() as $site ) {
			$argLists[] = array( $site );
		}

		return $argLists;
	}
	
	/**
	 * @dataProvider stuffThatIsNotArrayProvider
	 */
	public function testCannotConstructWithNonArrayBadges( $invalidBadges ) {
		$site = new \MediaWikiSite();
		$site->setGlobalId( 'enwiki' );
		
		$this->setExpectedException( 'InvalidArgumentException' );
		new SiteLink( $site, 'Wikidata', $invalidBadges );
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
		$site = new \MediaWikiSite();
		$site->setGlobalId( 'enwiki' );
		
		$this->setExpectedException( 'InvalidArgumentException' );
		new SiteLink( $site, 'Wikidata', $invalidBadges );
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
