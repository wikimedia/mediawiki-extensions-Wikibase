<?php

namespace Wikibase;
use MWException, SettingsBase;

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
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class Settings extends SettingsBase {

	/**
	 * @see SettingsBase::getSetSettings
	 *
	 * @since 0.1
	 *
	 * @return array
	 */
	protected function getSetSettings() {
		return $GLOBALS['egWBSettings'];
	}

	/**
	 * @see SettingsBase::getDefaultSettings
	 *
	 * @since 0.1
	 *
	 * @return array
	 */
	protected function getDefaultSettings() {
		$settings = array(
			'pollDefaultInterval' => 1000,
			'pollDefaultLimit' => 100,
			'pollContinueInterval' => 0,

			'itemPrefix' => 'q',
			'propertyPrefix' => 'p',
			'queryPrefix' => 'query',
		);

		// allow extensions that use WikidataLib to register mode defaults
		wfRunHooks( 'WikibaseDefaultSettings', array( &$settings ) );

		return $settings;
	}

}
