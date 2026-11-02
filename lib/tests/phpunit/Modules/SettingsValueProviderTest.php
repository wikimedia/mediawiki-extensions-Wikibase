<?php

namespace Wikibase\Lib\Tests\Modules;

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

	public function testGetters() {
		$settingsValueProvider = new SettingsValueProvider(
			new SettingsArray( [ 'setting_name' => 'setting value' ] ),
			'jsName',
			'setting_name'
		);

		self::assertSame( 'jsName', $settingsValueProvider->getKey() );
		self::assertSame( 'setting value', $settingsValueProvider->getValue() );
	}

}
