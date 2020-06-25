<?php

namespace Wikibase\Lib;

use ExtensionRegistry;

/**
 * @deprecated Each component should manage its own settings,
 *  and such settings should be defined in their own configuration.
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 */
final class Settings extends SettingsArray {

	/**
	 * @deprecated Each component should manage its own settings,
	 *  and such settings should be defined in their own configuration.
	 *
	 * @return self
	 */
	public static function singleton() {
		static $instance = null;

		if ( $instance === null ) {
			$instance = new self();
			$instance->initFromGlobals();
		}

		return $instance;
	}

	/**
	 * Initializes this Settings object from the global configuration variables.
	 * Default settings are loaded from the appropriate files.
	 * The hook WikibaseDefaultSettings can be used to manipulate the defaults.
	 */
	private function initFromGlobals() {
		$settings = [];
		$extensionRegistry = ExtensionRegistry::getInstance();

		//NOTE: Repo overrides client. This is important especially for
		//      settings initialized by WikibaseLib.

		if ( $extensionRegistry->isLoaded( 'WikibaseClient' ) ) {
			$settings = array_merge( $settings, $GLOBALS['wgWBClientSettings'] );
		}

		if ( $extensionRegistry->isLoaded( 'WikibaseRepository' ) ) {
			$settings = array_merge( $settings, $GLOBALS['wgWBRepoSettings'] );
		}

		// store
		foreach ( $settings as $key => $value ) {
			$this[$key] = $value;
		}
	}

}
