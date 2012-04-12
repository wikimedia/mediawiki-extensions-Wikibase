<?php

/**
 * File defining the settings for the Wikibase Client extension.
 * More info can be found at https://www.mediawiki.org/wiki/Extension:Wikibase_Client#Settings
 *
 * NOTICE:
 * Changing one of these settings can be done by assigning to $egWBCSettings,
 * AFTER the inclusion of the extension itself.
 *
 * @since 0.1
 *
 * @file WikibaseClient.settings.php
 * @ingroup WikibaseClient
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class WBCSettings {

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
			'namespaces' => array( NS_MAIN ),
			'sort' => 'none',
			'sortPrepend' => false,
			'alwaysSort' => false,
		);
	}

	/**
	 * Returns an array with all settings after making sure they are
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
				$GLOBALS['egWBCSettings']
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
