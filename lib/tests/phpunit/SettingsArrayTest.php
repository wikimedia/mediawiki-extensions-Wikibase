<?php

namespace Wikibase\Lib\Test;
use Wikibase\SettingsArray;

/**
 * Tests for the SettingsArray class.
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
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class SettingsArrayTest extends \MediaWikiTestCase {

	public function settingArrayProvider() {
		$settingArrays = array();

		$settingArrays[] = new SettingsArray();

		return $this->arrayWrap( $settingArrays );
	}

	/**
	 * @dataProvider settingArrayProvider
	 *
	 * @param SettingsArray $settings
	 */
	public function testGetSetting( SettingsArray $settings ) {
		foreach ( $settings as $settingName => $settingValue ) {
			$this->assertEquals( $settingValue, $settings->getSetting( $settingName ) );
		}

		$unknownSettings = array( 'dzgtxdfgtdsrxstds4ryt', 'sadftsrftszy' );

		foreach ( $unknownSettings as $unknownSetting ) {
			$this->assertException(
				function() use ( $settings, $unknownSetting ) {
					$settings->getSetting( $unknownSetting );
				},
				'MWException'
			);
		}
	}

	/**
	 * @dataProvider settingArrayProvider
	 *
	 * @param SettingsArray $settings
	 */
	public function testHasSetting( SettingsArray $settings ) {
		foreach ( array_keys( iterator_to_array( $settings ) ) as $settingName ) {
			$this->assertTrue( $settings->hasSetting( $settingName ) );
		}

		$this->assertFalse( $settings->hasSetting( 'I dont think therefore I dont exist' ) );
	}

	/**
	 * @dataProvider settingArrayProvider
	 *
	 * @param SettingsArray $settings
	 */
	public function testSetSetting( SettingsArray $settings ) {
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
