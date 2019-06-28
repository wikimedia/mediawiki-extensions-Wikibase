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
			// in this case, the module should never get loaded,
			// but let’s leave a brief comment in the “regexp”
			$hrefRegExp = '(?!)' . // empty negated lookahead will never match anything
				'data bridge config incomplete: dataBridgeHrefRegExp missing';
		}

		return [
			'hrefRegExp' => $hrefRegExp,
		];
	}

}
