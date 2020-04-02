<?php

namespace Wikibase\Client\DataBridge;

use Wikibase\Lib\Modules\MediaWikiConfigValueProvider;
use Wikibase\Lib\SettingsArray;

/**
 * @license GPL-2.0-or-later
 */
class DataBridgeConfigValueProvider implements MediaWikiConfigValueProvider {

	/** @var SettingsArray */
	private $settings;

	/** @var bool */
	private $usePublish;

	public function __construct( SettingsArray $settings, $usePublish ) {
		$this->settings = $settings;
		$this->usePublish = $usePublish;
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
			'usePublish' => $this->usePublish,
			'issueReportingLink' => $this->settings->getSetting( 'dataBridgeIssueReportingLink' ),
		];
	}

}
