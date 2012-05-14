<?php

/**
 * File defining the settings for the WikibaseLib extension.
 * More info can be found at https://www.mediawiki.org/wiki/Extension:WikibaseLib#Settings
 *
 * NOTICE:
 * Changing one of these settings can be done by assigning to $egWBLSettings,
 * AFTER the inclusion of the extension itself.
 *
 * @since 0.1
 *
 * @file WikibaseLib.settings.php
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class WBLSettings {

	/**
	 * Build version of the settings.
	 * @since 0.1
	 * @var boolean
	 */
	protected $settings = false;

	/**
	 * Returns an array with all settings after making sure they are
	 * initialized (ie set settings have been merged with the defaults).
	 * setting name (string) => setting value (mixed)
	 *
	 * @since 0.1
	 *
	 * @return array
	 */
	public function getSettings() {
		$this->buildSettings();
		return $this->settings;
	}

	/**
	 * Returns an instance of the settings class.
	 *
	 * @since 0.1
	 *
	 * @return WBCSettings
	 */
	public static function singleton() {
		static $instance = false;

		if ( $instance === false ) {
			$instance = new self();
		}

		return $instance;
	}

	/**
	 * Does a lazy rebuild of the settings.
	 *
	 * @since 0.1
	 */
	public function rebuildSettings() {
		$this->settings = false;
	}

	/**
	 * Builds the settings if needed.
	 * This includes merging the set settings over the default ones.
	 *
	 * @since 0.1
	 */
	protected function buildSettings() {
		if ( $this->settings === false ) {
			$this->settings = array_merge(
				self::getDefaultSettings(),
				$GLOBALS['egWBLSettings']
			);
		}
	}

	/**
	 * Gets the value of the specified setting.
	 *
	 * @since 0.1
	 *
	 * @param string $settingName
	 *
	 * @throws MWException
	 * @return mixed
	 */
	public function getSetting( $settingName ) {
		$this->buildSettings();

		if ( !array_key_exists( $settingName, $this->settings ) ) {
			throw new MWException( 'Attempt to get non-existing setting "' . $settingName . '"' );
		}

		return $this->settings[$settingName];
	}

	/**
	 * Gets the value of the specified setting.
	 * Shortcut to calling getSetting on the singleton instance of the settings class.
	 *
	 * @since 0.1
	 *
	 * @param string $settingName
	 *
	 * @return mixed
	 */
	public static function get( $settingName ) {
		return self::singleton()->getSetting( $settingName );
	}

	/**
	 * Returns the default values for the settings.
	 * setting name (string) => setting value (mixed)
	 *
	 * @since 0.1
	 *
	 * @return array
	 */
	protected static function getDefaultSettings() {
		return array(
			'pollDefaultInterval' => 1000,
			'pollDefaultLimit' => 100,
			'pollContinueInterval' => 0,
		);
	}

}
