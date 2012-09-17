<?php

/**
 * File defining the settings for the ValueHandler extension.
 * More info can be found at https://www.mediawiki.org/wiki/Extension:ValueHandler#Settings
 *
 * NOTICE:
 * Changing one of these settings can be done by assigning to $egValueHandlerSettings,
 * AFTER the inclusion of the extension itself.
 *
 * @since 0.1
 *
 * @file
 * @ingroup ValueHandler
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
final class ValueHandlerSettings {

	/**
	 * Protected constructor - force singleton usage.
	 * @since 0.1
	 */
	protected function __construct(){}

	/**
	 * @since 0.1
	 * @var ValueHandler|null
	 */
	protected static $instance = null;

	/**
	 * @since 0.1
	 * @var array|null
	 */
	protected $settings = null;

	/**
	 * Returns an instance of ValueHandlerSettings.
	 *
	 * @since 0.1
	 *
	 * @return ValueHandlerSettings
	 */
	public static function singleton() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Returns the default values for the settings.
	 * setting name (string) => setting value (mixed)
	 *
	 * @since 0.1
	 *
	 * @return array
	 */
	protected function getDefaultSettings() {
		return array(
		);
	}

	/**
	 * Builds the settings if needed.
	 * This includes merging the set settings over the default ones.
	 *
	 * @since 0.1
	 */
	protected function buildSettings() {
		if ( is_null( $this->settings ) ) {
			$this->settings = array_merge(
				self::getDefaultSettings(),
				$GLOBALS['egValueHandlerSettings']
			);
		}
	}

	/**
	 * Retruns an array with all settings after making sure they are
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
	 * Returns if a certain setting is set, and can therefor be obtained via getSetting.
	 *
	 * @since 0.1
	 *
	 * @param string $settingName
	 *
	 * @throws MWException
	 * @return mixed
	 */
	public function hasSetting( $settingName ) {
		$this->buildSettings();
		return array_key_exists( $settingName, $this->settings );
	}

	/**
	 * Gets the value of the specified setting.
	 * Shortcut to ValueHandlerSettings::getSetting.
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
	 * Returns if a certain setting is set, and can therefor be obtained via getSetting.
	 * Shortcut to ValueHandlerSettings::hasSetting.
	 *
	 * @since 0.1
	 *
	 * @param string $settingName
	 *
	 * @throws MWException
	 * @return mixed
	 */
	public static function has( $settingName ) {
		return self::singleton()->hasSetting( $settingName );
	}

}
