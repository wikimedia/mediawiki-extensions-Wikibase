<?php

namespace Wikibase;

/**
 * @deprecated
 *
 * Each component should manage its own settings,
 * and such settings should be defined in their own configuration.
 *
 * @since 0.1
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 */
class Settings extends SettingsArray {

	/**
	 * @see Settings::singleton
	 *
	 * @since 0.1
	 *
	 * @return Settings
	 */
	public static function singleton() {
		static $instance = null;

		if ( $instance === null ) {
			$instance = new static();
			$instance->initFromGlobals();
		}

		return $instance;
	}

	/**
	 * Initializes this Settings object from the global configuration variables.
	 * Default settings are loaded from the appropriate files.
	 * The hook WikibaseDefaultSettings can be used to manipulate the defaults.
	 *
	 * @since 0.4
	 */
	public function initFromGlobals() {
		$settings = array();

		//NOTE: Repo overrides client. This is important especially for
		//      settings initialized by WikibaseLib.

		if ( defined( 'WBC_VERSION' ) ) {
			$settings = array_merge( $settings, $GLOBALS['wgWBClientSettings'] );
		}

		if ( defined( 'WB_VERSION' ) ) {
			$settings = array_merge( $settings, $GLOBALS['wgWBRepoSettings'] );
		}

		// store
		foreach ( $settings as $key => $value ) {
			$this[$key] = $value;
		}
	}

	/**
	 * Shortcut to ::singleton()->getSetting
	 *
	 * @since 0.1
	 *
	 * @param string $settingName
	 *
	 * @return mixed
	 */
	public static function get( $settingName ) {
		return static::singleton()->getSetting( $settingName );
	}

}
