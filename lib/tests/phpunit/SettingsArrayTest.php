<?php

namespace Wikibase\Lib\Tests;

use OutOfBoundsException;
use Wikibase\Lib\SettingsArray;

/**
 * @covers \Wikibase\Lib\SettingsArray
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class SettingsArrayTest extends \PHPUnit\Framework\TestCase {

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
		$argLists = [];

		$argLists[] = [ [] ];

		$argLists[] = [ [
			'foo' => 'bar',
		] ];

		$argLists[] = [ [
			'foo' => 'bar',
			'baz' => 'bah',
		] ];

		$argLists[] = [ [
			'foo' => 'bar',
			'baz' => 'bah',
			'blah' => 'bah',
			'nyan' => 1337,
			'onoez' => [ 1, 2, 3 ],
			'spam' => false,
			'hax' => null,
		] ];

		return $argLists;
	}

	/**
	 * @dataProvider settingProvider
	 */
	public function testGetUnknownSetting( array $settings ) {
		$settingsArray = new SettingsArray( $settings );

		$this->expectException( OutOfBoundsException::class );

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
