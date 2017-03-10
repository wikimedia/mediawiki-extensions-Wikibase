<?php

namespace Wikibase;

use Hooks;
use MWException;

/**
 * WikibaseSettings is a static access point to Wikibase settings defined as global state
 * (typically in LocalSettings.php).
 *
 * @note WikibaseSettings is intended for internal use by bootstrapping code. Application service
 * logic should have individual settings injected, static entry points to application logic should
 * use top level factory methods such as WikibaseRepo::getRepoSettings() and
 * WikibaseClient::getClientSettings().
 *
 * @todo Move this to a separate component.
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class WikibaseSettings {

	/**
	 * Returns settings for a wikibase component based on global state.
	 * This is intended to be used to access settings specified in LocalSettings.php.
	 *
	 * @param string $name Logical name of the settings. Well known names include "Client" and "Repo".
	 *
	 * @return SettingsArray|null
	 */
	public static function getSettings( $name ) {
		$var = "wgWB{$name}Settings";

		if ( !isset( $GLOBALS[$var] ) ) {
			return null;
		}

		if ( !is_array( $GLOBALS[$var] ) ) {
			return null;
		}

		$settings = $GLOBALS[$var];

		return new SettingsArray( $settings );
	}

	/**
	 * @throws MWException
	 *
	 * @returns SettingsArray
	 */
	public static function getRepoSettings() {
		if ( !self::isRepoEnabled() ) {
			throw new MWException( 'Cannot access repo settings: Wikibase Repository component is not enabled!' );
		}

		$settings = self::getSettings( 'Repo' );
		$settings->setSetting( 'entityNamespaces', self::buildEntityNamespaceConfigurations( $settings ) );
		return $settings;
	}

	/**
	 * @throws MWException
	 *
	 * @returns SettingsArray
	 */
	public static function getClientSettings() {
		if ( !self::isClientEnabled() ) {
			throw new MWException( 'Cannot access client settings: Wikibase Client component is not enabled!' );
		}

		return self::getSettings( 'Client' );
	}

	/**
	 * @returns true if and only if the Wikibase client component is enabled on this wiki.
	 */
	public static function isClientEnabled() {
		return defined( 'WBC_VERSION' );
	}

	/**
	 * @returns true if and only if the Wikibase repository component is enabled on this wiki.
	 */
	public static function isRepoEnabled() {
		return defined( 'WB_VERSION' );
	}

	/**
	 * @throws MWException in case of a misconfiguration
	 * @return int[] An array mapping entity type identifiers to namespace numbers.
	 */
	private static function buildEntityNamespaceConfigurations( SettingsArray $settings ) {
		if ( !$settings->hasSetting( 'entityNamespaces' ) ) {
			throw new MWException( 'Wikibase: Incomplete configuration: '
				. '$wgWBRepoSettings[\'entityNamespaces\'] has to be set to an '
				. 'array mapping entity types to namespace IDs. '
				. 'See Wikibase.example.php for details and examples.' );
		}

		$namespaces = $settings->getSetting( 'entityNamespaces' );
		Hooks::run( 'WikibaseEntityNamespaces', [ &$namespaces ] );
		return $namespaces;
	}

}
