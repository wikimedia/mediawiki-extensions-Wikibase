<?php

namespace Wikibase\View;

use ExtensionRegistry;
use MediaWiki\Hook\UnitTestsListHook;
use MediaWiki\ResourceLoader\Hook\ResourceLoaderRegisterModulesHook;
use MediaWiki\ResourceLoader\ResourceLoader;

/**
 * File defining the hook handlers for the WikibaseView extension.
 *
 * @license GPL-2.0-or-later
 */
final class ViewHooks implements UnitTestsListHook, ResourceLoaderRegisterModulesHook {

	/**
	 * Callback called after extension registration,
	 * for any work that cannot be done directly in extension.json.
	 */
	public static function onRegistration(): void {
		global $wgResourceModules;

		$wgResourceModules = array_merge(
			$wgResourceModules,
			require __DIR__ . '/../resources.php'
		);
	}

	/**
	 * Register ResourceLoader modules with dynamic dependencies.
	 *
	 * @param ResourceLoader $rl
	 */
	public function onResourceLoaderRegisterModules( ResourceLoader $rl ): void {
		$moduleTemplate = [
			'localBasePath' => __DIR__ . '/..',
			'remoteExtPath' => 'Wikibase/view',
		];

		$modules = [
			'jquery.util.getDirectionality' => $moduleTemplate + [
				'scripts' => [
					'resources/jquery/jquery.util.getDirectionality.js',
				],
			],
			'wikibase.getLanguageNameByCode' => $moduleTemplate + [
				'scripts' => [
					'resources/wikibase/wikibase.getLanguageNameByCode.js',
				],
				'dependencies' => [
					'wikibase',
				],
				'targets' => [ 'desktop', 'mobile' ]
			],
		];

		$isUlsLoaded = ExtensionRegistry::getInstance()->isLoaded( 'UniversalLanguageSelector' );
		if ( $isUlsLoaded ) {
			$modules['jquery.util.getDirectionality']['dependencies'][] = 'ext.uls.mediawiki';
			$modules['wikibase.getLanguageNameByCode']['dependencies'][] = 'ext.uls.mediawiki';
		}

		$rl->register( $modules );
	}

	/**
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/UnitTestsList
	 *
	 * @param string[] &$paths
	 */
	public function onUnitTestsList( &$paths ): void {
		$paths[] = __DIR__ . '/../tests/phpunit';
	}

}
