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
		$testDir = dirname( __FILE__ ) . '/tests/phpunit/';

		$files[] = $testDir . 'WikibaseChangesTest.php';
		$files[] = $testDir . 'WikibaseListDiffTest.php';
		$files[] = $testDir . 'WikibaseMapDiffTest.php';

		// changes
		$files[] = $testDir . 'changes/WikibaseAliasChangeTest.php';
		$files[] = $testDir . 'changes/WikibaseSitelinkChangeTest.php';

		return true;
	}

}
