<?php

namespace Wikibase\Lib\Test;

use OutOfBoundsException;
use Wikibase\SettingsArray;

/**
 * @covers Wikibase\SettingsArray
 *
 * @group Wikibase
 * @group WikibaseLib
 * @group SettingsArrayTest
 *
 * @license GPL-2.0+
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

		$this->setExpectedException( OutOfBoundsException::class );

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
