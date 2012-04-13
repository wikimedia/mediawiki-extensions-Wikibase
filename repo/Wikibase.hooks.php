<?php

/**
 * File defining the hook handlers for the Wikibase Client extension.
 *
 * @since 0.1
 *
 * @file Wikibase.hooks.php
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
final class WikibaseHooks {

	/**
	 * Schema update to set up the needed database tables.
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/LoadExtensionSchemaUpdates
	 *
	 * @since 0.1
	 *
	 * @param DatabaseUpdater $updater
	 *
	 * @return true
	 */
	public static function onSchemaUpdate( DatabaseUpdater $updater ) {
		$updater->addExtensionTable(
			'wb_items_per_site',
			dirname( __FILE__ ) . '/sql/Wikibase.sql'
		);

		return true;
	}

	/**
	 * Hook to add PHPUnit test cases.
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/UnitTestsList
	 *
	 * @since 0.1
	 *
	 * @param array $files
	 *
	 * @return true
	 */
	public static function registerUnitTests( array &$files ) {
		$testDir = dirname( __FILE__ ) . '/test/';

		//$files[] = $testDir . '.php';

		return true;
	}

	/**
	 * In Wikidata namespace, page content language is the same as the current user language.
	 * @author	Nikola Smolenski
	 */
	public static function onPageContentLanguage( $title, &$pageLang, $wgLang ) {
		global $wgNamespaceContentModels;
		if( $wgNamespaceContentModels[$title->getNamespace()] === CONTENT_MODEL_WIKIBASE) {
			$pageLang = $wgLang;
		}
		return true;
	}

}
