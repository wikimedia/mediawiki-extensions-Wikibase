<?php

namespace Wikibase\Lib\Tests\Modules;

use PHPUnit\Framework\MockObject\MockObject;
use Wikibase\Lib\Modules\SettingsValueProvider;
use Wikibase\Lib\SettingsArray;

/**
 * @covers \Wikibase\Lib\Modules\SettingsValueProvider
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class SettingsValueProviderTest extends \PHPUnit\Framework\TestCase {

	public function testGetKeyReturnsJSSettingName() {
		$settingsValueProvider = new SettingsValueProvider(
			$this->createMock( SettingsArray::class ),
			$jsSettingName = 'jsName',
			'does not matter'
		);

		self::assertEquals( $jsSettingName, $settingsValueProvider->getKey() );
	}

	public function testGetValueReturnsSettingWithGivenName() {

		/** @var SettingsArray|MockObject $settings */
		$settings = $this->createMock( SettingsArray::class );

		$settings->method( 'getSetting' )->willReturn( 'setting value' );

		$settingsValueProvider = new SettingsValueProvider(
			$settings,
			'does not matter',
			'setting_name'
		);

		self::assertEquals( 'setting value', $settingsValueProvider->getValue() );
	}

}
