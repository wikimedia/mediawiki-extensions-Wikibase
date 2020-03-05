<?php

namespace Wikibase\Lib\Modules;

use Wikibase\Lib\SettingsArray;

/**
 * @license GPL-2.0-or-later
 */
class SettingsValueProvider implements MediaWikiConfigValueProvider {

	/**
	 * @var SettingsArray
	 */
	private $settings;

	/**
	 * @var string
	 */
	private $jsSettingName;

	/**
	 * @var string
	 */
	private $phpSettingName;

	/**
	 * @param SettingsArray $settings
	 * @param string $jsSettingName
	 * @param string $phpSettingName
	 */
	public function __construct( SettingsArray $settings, $jsSettingName, $phpSettingName ) {
		$this->settings = $settings;
		$this->jsSettingName = $jsSettingName;
		$this->phpSettingName = $phpSettingName;
	}

	/**
	 * @inheritDoc
	 */
	public function getKey() {
		return $this->jsSettingName;
	}

	/**
	 * @inheritDoc
	 */
	public function getValue() {
		return $this->settings->getSetting( $this->phpSettingName );
	}

}
