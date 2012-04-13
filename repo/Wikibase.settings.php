<?php

/**
 * File defining the settings for the Wikibase extension.
 * More info can be found at https://www.mediawiki.org/wiki/Extension:Wikibase#Settings
 *
 * NOTICE:
 * Changing one of these settings can be done by assigning to $egWBSettings,
 * AFTER the inclusion of the extension itself.
 *
 * @since 0.1
 *
 * @file Wikibase.settings.php
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class WBSettings {

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
			// alternative: application/vnd.php.serialized
			'serializationFormat' => 'application/json',

			// Disables token and post requirements in the API to
			// facilitate testing, do not turn on in production!
			'apiInDebug' => false,
		);
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
	public static function getSettings() {
		static $settings = false;

		if ( $settings === false ) {
			$settings = array_merge(
				self::getDefaultSettings(),
				$GLOBALS['egWBSettings']
			);
		}

		return $settings;
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
	public static function get( $settingName ) {
		$settings = self::getSettings();

		if ( !array_key_exists( $settingName, $settings ) ) {
			throw new MWException( 'Attempt to get non-existing setting "' . $settingName . '"' );
		}

		return $settings[$settingName];
	}

}
