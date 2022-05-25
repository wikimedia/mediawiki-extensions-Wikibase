<?php

namespace Wikibase\Lib;

use ExtensionRegistry;
use MediaWiki\Hook\ExtensionTypesHook;
use MediaWiki\Hook\UnitTestsListHook;
use MediaWiki\ResourceLoader\Hook\ResourceLoaderRegisterModulesHook;
use MediaWiki\ResourceLoader\ResourceLoader;

/**
 * File defining the hook handlers for the WikibaseLib extension.
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Werner < daniel.a.r.werner@gmail.com >
 * @author H. Snater < mediawiki@snater.com >
 */
final class LibHooks implements
	UnitTestsListHook,
	ResourceLoaderRegisterModulesHook,
	ExtensionTypesHook
{

	/**
	 * Callback called after extension registration,
	 * for any work that cannot be done directly in extension.json.
	 */
	public static function onRegistration(): void {
		global $wgResourceModules;

		$wgResourceModules = array_merge(
			$wgResourceModules,
			require __DIR__ . '/../resources/Resources.php'
		);
	}

	/**
	 * Hook to add PHPUnit test cases.
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/UnitTestsList
	 * @param string[] &$paths
	 * @return void
	 */
	public function onUnitTestsList( &$paths ): void {
		$paths[] = __DIR__ . '/../tests/phpunit/';
		$paths[] = __DIR__ . '/../../data-access/tests/phpunit/';
		$paths[] = __DIR__ . '/../packages/wikibase/changes/tests/';
		$paths[] = __DIR__ . '/../packages/wikibase/data-model/tests/';
		$paths[] = __DIR__ . '/../packages/wikibase/data-model-serialization/tests/';
		$paths[] = __DIR__ . '/../packages/wikibase/data-model-services/tests/';
		$paths[] = __DIR__ . '/../packages/wikibase/federated-properties/tests/';
		$paths[] = __DIR__ . '/../packages/wikibase/internal-serialization/tests/';
	}

	/**
	 * Register the wikibase.Site ResourceLoader module with a dynamic dependency on ULS.
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ResourceLoaderRegisterModules
	 * @param ResourceLoader $rl
	 * @return void
	 */
	public function onResourceLoaderRegisterModules( ResourceLoader $rl ): void {
		if ( $rl->isModuleRegistered( 'wikibase.Site' ) ) {
			return;
		}

		$module = [
			'localBasePath' => __DIR__ . '/../',
			'remoteExtPath' => 'Wikibase/lib',
			'scripts' => [
				'resources/wikibase.Site.js',
			],
			'dependencies' => [
				'mediawiki.util',
			],
		];

		$isUlsLoaded = ExtensionRegistry::getInstance()->isLoaded( 'UniversalLanguageSelector' );
		if ( $isUlsLoaded ) {
			$module['dependencies'][] = 'ext.uls.mediawiki';
		}

		$rl->register( 'wikibase.Site', $module );
	}

	/**
	 * Called when generating the extensions credits, use this to change the tables headers.
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ExtensionTypes
	 * @param array &$extensionTypes
	 * @return void
	 */
	public function onExtensionTypes( &$extensionTypes ): void {
		// @codeCoverageIgnoreStart
		$extensionTypes['wikibase'] = wfMessage( 'version-wikibase' )->text();
		// @codeCoverageIgnoreEnd
	}

}
