<?php

namespace Wikibase\View;

use ExtensionRegistry;
use MediaWiki\Hook\UnitTestsListHook;
use MediaWiki\Languages\LanguageNameUtils;
use MediaWiki\MediaWikiServices;
use MediaWiki\ResourceLoader\Hook\ResourceLoaderRegisterModulesHook;
use MediaWiki\ResourceLoader\ResourceLoader;
use ResourceLoaderContext;

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

		$isUlsLoaded = ExtensionRegistry::getInstance()->isLoaded( 'UniversalLanguageSelector' );

		$modules = [
			'jquery.util.getDirectionality' => $moduleTemplate + [
				'scripts' => [
					'resources/jquery/jquery.util.getDirectionality.js',
				],
			],
			'wikibase.getLanguageNameByCode' => $moduleTemplate + [
				'packageFiles' => [
					'resources/wikibase/wikibase.getLanguageNameByCode.js',
					[
						'name' => 'resources/wikibase/languageNames.json',
						'callback' => static function (
							ResourceLoaderContext $context
						) use ( $isUlsLoaded ) {
							$languageNameUtils = MediaWikiServices::getInstance()->getLanguageNameUtils();
							$languageNames = $languageNameUtils->getLanguageNames(
								$context->getLanguage(),
								LanguageNameUtils::ALL
							);

							if ( $isUlsLoaded ) {
								// remove all the supported languages, ULS already sends those
								$supportedLanguageNames = $languageNameUtils->getLanguageNames(
									$context->getLanguage(),
									LanguageNameUtils::SUPPORTED
								);
								$languageNames = array_diff_key( $languageNames, $supportedLanguageNames );
							}

							return $languageNames;
						},
					],
				],
				'dependencies' => [
					'wikibase',
				],
				'targets' => [ 'desktop', 'mobile' ],
			],
		];

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
