<?php

namespace Wikibase\Lib\Test;

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
 * @ingroup Settings
 * @ingroup Test
 *
 * @group Settings
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class SettingsArrayTest extends \MediaWikiTestCase {

	public function testGetSetting() {
		$settings = new \Wikibase\SettingsArray();

		foreach ( TestSettingsList::getTestSetSettings() as $settingName => $settingValue ) {
			$this->assertEquals( $settingValue, $settings->getSetting( $settingName ) );
		}
	}

	public function testHasSetting() {
		$settings = $settings = new \Wikibase\SettingsArray();

		foreach ( array_keys( TestSettingsList::getTestSetSettings() ) as $settingName ) {
			$this->assertTrue( $settings->hasSetting( $settingName ) );
		}

		$this->assertFalse( $settings->hasSetting( 'I dont think therefore I dont exist' ) );
	}

	public function testSetSetting() {
		$settings = new \Wikibase\SettingsArray();

		foreach ( TestSettingsList::getTestDefaults() as $settingName => $settingValue ) {
			$settings->setSetting( $settingName, $settingValue );
			$this->assertEquals( $settingValue, TestSettingsList::get( $settingName ) );
		}

		foreach ( TestSettingsList::getTestSetSettings() as $settingName => $settingValue ) {
			$settings->setSetting( $settingName, $settingValue );
			$this->assertEquals( $settingValue, TestSettingsList::get( $settingName ) );
		}
	}

}

class TestSettingsList extends \Wikibase\SettingsArray {

	public static function getTestDefaults() {
		return array(
			'awesome' => null,
			'answer' => 0,
			'amount' => 9001,
			'foo' => 'bar',
		);
	}

	public static function getTestSetSettings() {
		return array(
			'awesome' => true,
			'answer' => 42,
			'amount' => 9001,
		);
	}

	public function getDefaultSettings() {
		return static::getTestDefaults();
	}

	public function getSetSettings() {
		return static::getTestSetSettings();
	}

}

