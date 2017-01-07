<?php

namespace Wikibase\Repo\Tests;

use Wikibase\SettingsArray;

/**
 * @group Wikibase
 *
 * @license GPL-2.0+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class DefaultRepoSettingsTest extends \PHPUnit_Framework_TestCase {

	public function testDefaultTransformLegacyFormatOnExportSetting() {
		$defaultSettings = require __DIR__ . '/../../../config/Wikibase.default.php';
		$settings = $this->newSettingsArray( $defaultSettings );

		$this->assertTrue( $settings->getSetting( 'transformLegacyFormatOnExport' ) );
	}

	/**
	 * @param mixed[] $settings
	 *
	 * @return SettingsArray
	 */
	private function newSettingsArray( array $settings ) {
		$settingsArray = new SettingsArray();

		foreach ( $settings as $setting => $value ) {
			$settingsArray->setSetting( $setting, $value );
		}

		return $settingsArray;
	}

}
