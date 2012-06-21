<?php

/**
 * File defining the hook handlers for the WikibaseLib extension.
 *
 * @since 0.1
 *
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
final class WikibaseLibHooks {

	/**
	 * Schema update to set up the needed database tables.
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/LoadExtensionSchemaUpdates
	 *
	 * @since 0.1
	 *
	 * @param DatabaseUpdater $updater
	 *
	 * @return boolean
	 */
	public static function onSchemaUpdate( DatabaseUpdater $updater ) {
		$updater->addExtensionTable(
			'wb_changes',
			dirname( __FILE__ ) . '/sql/WikibaseLib.sql'
		);

		// TODO: move to core
		$updater->addExtensionTable(
			'sites',
			dirname( __FILE__ ) . '/sql/AddSitesTable.sql'
		);

		// TODO: move to core
		$updater->addExtensionField(
			'langlinks',
			'll_local',
			dirname( __FILE__ ) . '/sql/AddLocalLanglinksField.sql'
		);

		$updater->addExtensionField(
			'sites',
			'site_link_navigation',
			dirname( __FILE__ ) . '/sql/IndexSitesTable.sql'
		);

		// TODO: enable && move to core
//		$updater->dropExtensionTable(
//			'interwiki',
//			dirname( __FILE__ ) . '/sql/DropInterwiki.sql'
//		);

		$updater->addExtensionUpdate( array( '\Wikibase\Utils::insertDefaultSites' ) );

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
	 * @return boolean
	 */
	public static function registerUnitTests( array &$files ) {
		$testFiles = array(
			'Changes',
			'ItemMultilangTexts',
			'ItemNewEmpty',
			'ItemNewFromArray',
			'Item',
			'SiteRow',
			'Sites',
			'Utils',

			'changes/DiffChange',
			'changes/ItemChange',

			'diff/Diff',
			'diff/ListDiff',
			'diff/MapDiff',
		);

		foreach ( $testFiles as $file ) {
			$files[] = dirname( __FILE__ ) . '/tests/phpunit/' . $file . 'Test.php';
		}

		return true;
	}

}
