<?php

namespace Wikibase\Lib\Test;

use Wikibase\SettingsArray;

/**
 * @covers Wikibase\SettingsArray
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA
 *
 * @file
 * @since 0.4
 *
 * @ingroup Wikibase
 * @ingroup Test
 *
 * @group Wikibase
 * @group WikibaseLib
 * @group SettingsArrayTest
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class SettingsArrayTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider settingProvider
	 */
	public function testGetKnownSetting( array $settings ) {
		$settingsArray = new SettingsArray( $settings );

		foreach ( $settingsArray as $settingName => $settingValue ) {
			$this->assertEquals( $settingValue, $settingsArray->getSetting( $settingName ) );
		}

		$this->assertSameSize( $settings, $settingsArray );
	}

	public function settingProvider() {
		$argLists = array();

		$argLists[] = array( array() );

		$argLists[] = array( array(
			'foo' => 'bar'
		) );

		$argLists[] = array( array(
			'foo' => 'bar',
			'baz' => 'bah',
		) );

		$argLists[] = array( array(
			'foo' => 'bar',
			'baz' => 'bah',
			'blah' => 'bah',
			'nyan' => 1337,
			'onoez' => array( 1, 2, 3 ),
			'spam' => false,
			'hax' => null,
		) );

		return $argLists;
	}

	/**
	 * @dataProvider settingProvider
	 */
	public function testGetUnknownSetting( array $settings ) {
		$settingsArray = new SettingsArray( $settings );

		$this->setExpectedException( 'OutOfBoundsException' );

		$settingsArray->getSetting( 'NyanData ALL the way across the sky' );
	}

	/**
	 * @dataProvider settingProvider
	 */
	public function testHasSetting( array $settings ) {
		$settings = new SettingsArray( $settings );

		foreach ( array_keys( iterator_to_array( $settings ) ) as $settingName ) {
			$this->assertTrue( $settings->hasSetting( $settingName ) );
		}

		$this->assertFalse( $settings->hasSetting( 'I dont think therefore I dont exist' ) );
	}

	/**
	 * @dataProvider settingProvider
	 */
	public function testSetSetting( array $settings ) {
		$settings = new SettingsArray( $settings );

		foreach ( $settings as $settingName => $settingValue ) {
			$settings->setSetting( $settingName, $settingValue );
			$this->assertEquals( $settingValue, $settings->getSetting( $settingName ) );
		}

		foreach ( $settings as $settingName => $settingValue ) {
			$settings->setSetting( $settingName, $settingValue );
			$this->assertEquals( $settingValue, $settings->getSetting( $settingName ) );
		}

		if ( $settings->count() === 0 ) {
			$this->assertTrue( true );
		}
	}

}
