<?php

namespace Wikibase\Client\DataBridge;

use Wikibase\Lib\Modules\MediaWikiConfigValueProvider;
use Wikibase\SettingsArray;

/**
 * @license GPL-2.0-or-later
 */
class DataBridgeConfigValueProvider implements MediaWikiConfigValueProvider {

	/** @var SettingsArray */
	private $settings;

	public function __construct( SettingsArray $settings ) {
		$this->settings = $settings;
	}

	public function getKey() {
		return 'wbDataBridgeConfig';
	}

	public function getValue() {
		if ( $this->settings->hasSetting( 'dataBridgeHrefRegExp' ) ) {
			$hrefRegExp = $this->settings->getSetting( 'dataBridgeHrefRegExp' );
		} else {
			// in this case, the module should never get loaded –
			// client-side code checks for null and logs a warning
			$hrefRegExp = null;
		}

		if ( $this->settings->hasSetting( 'dataBridgeEditTags' ) ) {
			$editTags = $this->settings->getSetting( 'dataBridgeEditTags' );
		} else {
			$editTags = [];
		}

		return [
			'hrefRegExp' => $hrefRegExp,
			'editTags' => $editTags,
		];
	}

}
