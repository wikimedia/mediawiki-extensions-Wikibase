<?php

/**
 * Tests for the SiteObject class.
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
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class SiteObjectTest extends ORMRowTest {

	/**
	 * @see ORMRowTest::getRowClass
	 * @since 1.20
	 * @return string
	 */
	protected function getRowClass() {
		return 'SiteObject';
	}

	/**
	 * @see ORMRowTest::getTableInstance
	 * @since 1.20
	 * @return IORMTable
	 */
	protected function getTableInstance() {
		return SitesTable::singleton();
	}

	/**
	 * @see ORMRowTest::constructorTestProvider
	 * @since 1.20
	 * @return array
	 */
	public function constructorTestProvider() {
		$argLists = array();

		$argLists[] = array( 'global_key' => '42' );

		$argLists[] = array( 'global_key' => '42', 'type' => Site::TYPE_MEDIAWIKI );

		$constructorArgs = array();

		foreach ( $argLists as $argList ) {
			$constructorArgs[] = array( $argList, true );
		}

		return $constructorArgs;
	}

	/**
	 * @dataProvider instanceProvider
	 * @param Site $site
	 */
	public function testSetInternalId( Site $site ) {
		$site->setInternalId( 42 );
		$this->assertEquals( 42, $site->getInternalId() );
	}

	/**
	 * @dataProvider instanceProvider
	 * @param Site $site
	 */
	public function testGetInterwikiIds( Site $site ) {
		$this->assertInternalType( 'array', $site->getInterwikiIds() );
	}

	/**
	 * @dataProvider instanceProvider
	 * @param Site $site
	 */
	public function testGetNavigationIds( Site $site ) {
		$this->assertInternalType( 'array', $site->getNavigationIds() );
	}

	/**
	 * @dataProvider instanceProvider
	 * @param Site $site
	 */
	public function testAddNavigationId( Site $site ) {
		$site->addNavigationId( 'foobar' );
		$this->assertTrue( in_array( 'foobar', $site->getNavigationIds(), true ) );
	}

	/**
	 * @dataProvider instanceProvider
	 * @param Site $site
	 */
	public function testAddInterwikiId( Site $site ) {
		$site->addInterwikiId( 'foobar' );
		$this->assertTrue( in_array( 'foobar', $site->getInterwikiIds(), true ) );
	}

	/**
	 * @dataProvider instanceProvider
	 * @param Site $site
	 */
	public function testGetLanguageCode( Site $site ) {
		$this->assertTypeOrFalse( 'string', $site->getLanguageCode() );
	}

	/**
	 * @dataProvider instanceProvider
	 * @param Site $site
	 */
	public function testSetLanguageCode( Site $site ) {
		$site->setLanguageCode( 'en' );
		$this->assertEquals( 'en', $site->getLanguageCode() );
	}

	/**
	 * @dataProvider instanceProvider
	 * @param Site $site
	 */
	public function testNormalizePageName( Site $site ) {
		$this->assertInternalType( 'string', $site->normalizePageName( 'Foobar' ) );
	}

	/**
	 * @dataProvider instanceProvider
	 * @param Site $site
	 */
	public function testGetGlobalId( Site $site ) {
		$this->assertInternalType( 'string', $site->getGlobalId() );
	}

	/**
	 * @dataProvider instanceProvider
	 * @param Site $site
	 */
	public function testSetGlobalId( Site $site ) {
		$site->setGlobalId( 'foobar' );
		$this->assertEquals( 'foobar', $site->getGlobalId() );
	}

	/**
	 * @dataProvider instanceProvider
	 * @param Site $site
	 */
	public function testGetType( Site $site ) {
		$this->assertInternalType( 'string', $site->getType() );
	}

	protected function assertTypeOrFalse( $type, $value ) {
		if ( $value === false ) {
			$this->assertTrue( true );
		}
		else {
			$this->assertInternalType( $type, $value );
		}
	}

}