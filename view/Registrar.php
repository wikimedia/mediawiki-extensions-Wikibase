<?php

namespace Wikibase\View;

class Registrar {

	public static function registerExtension() {
		global $wgHooks, $wgMessagesDirs, $wgResourceModules;

		if ( defined( 'WIKIBASE_VIEW_VERSION' ) ) {
			// Do not initialize more than once.
			return 1;
		}

		define( 'WIKIBASE_VIEW_VERSION', '0.1-dev' );

		$wgResourceModules = array_merge(
			$wgResourceModules,
			include __DIR__ . '/resources.php'
		);

		$wgMessagesDirs = array_merge(
			$wgMessagesDirs,
			include __DIR__ . '/lib/i18n.php'
		);

		$wgHooks['UnitTestsList'][] = function( array &$paths ) {
			$paths[] = __DIR__ . '/tests/phpunit';
		};

		/**
		 * Register ResourceLoader modules with dynamic dependencies.
		 *
		 * @param \ResourceLoader $resourceLoader
		 *
		 * @return bool
		 */
		$wgHooks['ResourceLoaderRegisterModules'][] = function( \ResourceLoader $resourceLoader ) {
			preg_match( '+' . preg_quote( DIRECTORY_SEPARATOR ) . '(?:vendor|extensions)'
				. preg_quote( DIRECTORY_SEPARATOR ) . '.*+', __DIR__, $remoteExtPath );

			$moduleTemplate = [
				'localBasePath' => __DIR__,
				'remoteExtPath' => '..' . $remoteExtPath[0],
				'position' => 'top' // reducing the time between DOM construction and JS initialisation
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
					],
			];

			$isUlsLoaded = \ExtensionRegistry::getInstance()->isLoaded( 'UniversalLanguageSelector' );
			if ( $isUlsLoaded ) {
				$modules['jquery.util.getDirectionality']['dependencies'][] = 'ext.uls.mediawiki';
				$modules['wikibase.getLanguageNameByCode']['dependencies'][] = 'ext.uls.mediawiki';
			}

			$resourceLoader->register( $modules );

			return true;
		};

		$wgHooks['ResourceLoaderTestModules'][] = function(
			array &$testModules,
			\ResourceLoader &$resourceLoader
		) {
			$testModules['qunit'] = array_merge(
				$testModules['qunit'],
				include __DIR__ . '/lib/resources.test.php',
				include __DIR__ . '/tests/qunit/resources.php'
			);

			return true;
		};

	}

}