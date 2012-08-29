<?php

/**
 * Tests for the MediaWikiSite class.
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
 * @since 1.20
 *
 * @ingroup Site
 * @ingroup Test
 *
 * @group Site
 * @group Database
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class MediaWikiSiteTest extends MediaWikiTestCase {

	public function setUp() {
		parent::setUp();

		static $hasSites = false;

		if ( !$hasSites ) {
			\TestSites::insertIntoDb();
			$hasSites = true;
		}
	}

	public function testFactoryConstruction() {
		$this->assertInstanceOf( 'MediaWikiSite', Sites::newSite( array( 'type' => SITE_TYPE_MEDIAWIKI ) ) );
		$this->assertInstanceOf( 'Site', Sites::newSite( array( 'type' => SITE_TYPE_MEDIAWIKI ) ) );
	}

	public function testNormalizePageTitle() {
		$site = Sites::newSite( array( 'type' => SITE_TYPE_MEDIAWIKI ) );

		//NOTE: this does not actually call out to the enwiki site to perform the normalization,
		//      but uses a local Title object to do so. This is hardcoded on SiteLink::normalizePageTitle
		//      for the case that MW_PHPUNIT_TEST is set.
		$this->assertEquals( "Foo", $site->normalizePageName( " foo " ) );
	}


}
