<?php

namespace Wikibase;

use Hooks;
use MWException;
use OutOfBoundsException;

/**
 * WikibaseSettings is a static access point to Wikibase settings defined as global state
 * (typically in LocalSettings.php).
 *
 * @note WikibaseSettings is intended for internal use by bootstrapping code. Application service
 * logic should have individual settings injected, static entry points to application logic should
 * use top level factory methods such as WikibaseRepo::getSettings() and
 * WikibaseClient::getSettings().
 *
 * @todo Move this to a separate component.
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class WikibaseSettings {

	/**
	 * @return bool True if and only if the Wikibase repository component is enabled on this wiki.
	 */
	public static function isRepoEnabled() {
		return defined( 'WB_VERSION' );
	}

	/**
	 * @note This runs the WikibaseEntityNamespaces hook to allow extensions to modify
	 *       the 'entityNamespaces' setting.
	 *
	 * @throws MWException
	 *
	 * @return SettingsArray
	 */
	public static function getRepoSettings() {
		if ( !self::isRepoEnabled() ) {
			throw new MWException( 'Cannot access repo settings: Wikibase Repository component is not enabled!' );
		}

		$settings = self::getSettings( 'wgWBRepoSettings' );
		$settings->setSetting( 'entityNamespaces', self::buildEntityNamespaceConfigurations( $settings ) );
		return $settings;
	}

	/**
	 * @return bool True if and only if the Wikibase client component is enabled on this wiki.
	 */
	public static function isClientEnabled() {
		return defined( 'WBC_VERSION' );
	}

	/**
	 * @throws MWException
	 *
	 * @return SettingsArray
	 */
	public static function getClientSettings() {
		if ( !self::isClientEnabled() ) {
			throw new MWException( 'Cannot access client settings: Wikibase Client component is not enabled!' );
		}

		$settings = self::getSettings( 'wgWBClientSettings' );

		$entityNamespaces = self::buildEntityNamespaceConfigurations( $settings );
		self::applyEntityNamespacesToSettings( $settings, $entityNamespaces );

		return $settings;
	}

	/**
	 * Returns settings for a wikibase component based on global state.
	 * This is intended to be used to access settings specified in LocalSettings.php.
	 *
	 * @param string $var The name of a global variable.
	 *
	 * @return SettingsArray
	 */
	private static function getSettings( $var ) {
		if ( !isset( $GLOBALS[$var] ) ) {
			throw new OutOfBoundsException( 'No such global configuration variable: ' . $var );
		}

		if ( !is_array( $GLOBALS[$var] ) ) {
			throw new OutOfBoundsException( 'Not a Wikibase configuration array: ' . $var );
		}

		$settings = $GLOBALS[$var];

		return new SettingsArray( $settings );
	}

	/**
	 * @throws MWException in case of a misconfiguration
	 * @return int[] An array mapping entity type identifiers to namespace numbers.
	 */
	private static function buildEntityNamespaceConfigurations( SettingsArray $settings ) {
		if ( !$settings->hasSetting( 'repositories' ) && !$settings->hasSetting( 'entityNamespaces' ) ) {
			throw new MWException( 'Wikibase: Incomplete configuration: '
				. 'The \'entityNamespaces\' setting has to be set to an '
				. 'array mapping entity types to namespace IDs. '
				. 'See Wikibase.example.php for details and examples.' );
		}

		$namespaces = $settings->hasSetting( 'entityNamespaces' )
			? $settings->getSetting( 'entityNamespaces' )
			: self::getEntityNamespacesFromRepositorySettings( $settings->getSetting( 'repositories' ) );

		Hooks::run( 'WikibaseEntityNamespaces', [ &$namespaces ] );
		return $namespaces;
	}

	private static function getEntityNamespacesFromRepositorySettings( array $repositorySettings ) {
		return array_reduce(
			$repositorySettings,
			function ( array $result, array $repoSettings ) {
				return array_merge( $result, $repoSettings['entityNamespaces'] );
			},
			[]
		);
	}

	private static function applyEntityNamespacesToSettings( SettingsArray $settings, array $entityNamespaces ) {
		if ( $settings->hasSetting( 'entityNamespaces' ) ) {
			$settings->setSetting( 'entityNamespaces', $entityNamespaces );
			return;
		}

		$repositorySettings = $settings->getSetting( 'repositories' );
		$namespacesDefinedForRepositories = self::getEntityNamespacesFromRepositorySettings( $repositorySettings );

		$namespacesInNoRepository = array_diff_key( $entityNamespaces, $namespacesDefinedForRepositories );

		if ( $namespacesInNoRepository ) {
			$repositorySettings['']['entityNamespaces'] += $namespacesInNoRepository;
			$settings->setSetting( 'repositories', $repositorySettings );
		}
	}

}
