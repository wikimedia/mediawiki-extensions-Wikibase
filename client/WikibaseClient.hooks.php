<?php

use Wikibase\Change as Change;

/**
 * File defining the hook handlers for the Wikibase Client extension.
 *
 * @since 0.1
 *
 * @file WikibaseClient.hooks.php
 * @ingroup WikibaseClient
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
final class WBCHooks {

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
			'wbc_local_items',
			dirname( __FILE__ ) . '/sql/WikibaseClient.sql'
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

		$files[] = $testDir . 'General.php';
		$files[] = $testDir . 'Sorting.php';

		$files[] = $testDir . 'includes/LocalItemTest.php';

		return true;
	}

	/**
	 * When the poll script finds a new change or set of changes, it will fire
	 * this hook for each change, so it can be handled appropriately.
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/WikibasePollHandle
	 *
	 * @since 0.1
	 *
	 * @param Change $change
	 *
	 * @return boolean
	 */
	public static function onWikibasePollHandle( Change $change ) {
		$changeHandlers = array(
			'sitelink' => array( __CLASS__, 'todo' ),
			'alias' => array( __CLASS__, 'todo' ),
		);

		if ( array_key_exists( $change->getType(), $changeHandlers ) ) {
			call_user_func_array( $changeHandlers[$change->getType()], array( $change ) );
		}

		return true;
	}

	public static function todo( Change $change ) {
		// TODO
	}

}
