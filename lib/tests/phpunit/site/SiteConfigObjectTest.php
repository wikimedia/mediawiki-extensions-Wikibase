<?php

/**
 * Tests for the SiteConfigObject class.
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
class SiteConfigObjectTest extends MediaWikiTestCase {

	/**
	 * @return array
	 */
	public function constructorProvider() {
		$args = array(
			array( 'en', false, false, false ),
			array( 'en', true, true, true ),
			array( 'nl', true, false, true ),
			array( 'nl', true, false, true, array() ),
			array( 'dewiktionary', false, true, false, array( 'foo' => 'bar', 'baz' => 42 ) ),
		);

		foreach ( $args as &$arg ) {
			$arg = array( $arg );
		}

		return $args;
	}

	/**
	 * @param array $args
	 * @return SiteConfigObject
	 */
	protected function createInstance( array $args ) {
		$configObject = new ReflectionClass( 'SiteConfigObject' );
		return $configObject->newInstanceArgs( $args );
	}

	/**
	 * @dataProvider constructorProvider
	 * @param array $args
	 */
	public function testConstructor( array $args ) {
		$configObject = $this->createInstance( $args );

		$this->assertInstanceOf( 'SiteConfig', $configObject );
	}

	/**
	 * @dataProvider constructorProvider
	 * @param array $args
	 */
	public function testGetForward( array $args ) {
		$configObject = $this->createInstance( $args );

		$this->assertInternalType( 'boolean', $configObject->getForward() );
		$this->assertEquals( $args[3], $configObject->getForward() );
	}

	/**
	 * @dataProvider constructorProvider
	 * @param array $args
	 */
	public function testGetExtraInfo( array $args ) {
		$configObject = $this->createInstance( $args );

		$this->assertInternalType( 'array', $configObject->getExtraInfo() );
		$this->assertEquals(
			array_key_exists( 4, $args ) ? $args[4] : array(),
			$configObject->getExtraInfo()
		);
	}

}
