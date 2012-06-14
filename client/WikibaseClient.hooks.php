<?php

namespace Wikibase;


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
final class ClientHooks {

	/**
	 * Schema update to set up the needed database tables.
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/LoadExtensionSchemaUpdates
	 *
	 * @since 0.1
	 *
	 * @param \DatabaseUpdater $updater
	 *
	 * @return boolean
	 */
	public static function onSchemaUpdate( \DatabaseUpdater $updater ) {
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
			'item' => array( __CLASS__, 'onWikibaseItemChange' ),
			'query' => array( __CLASS__, 'onWikibaseQueryChange' ),
		);

		if ( array_key_exists( $change->getType(), $changeHandlers ) ) {
			call_user_func_array( $changeHandlers[$change->getType()], array( $change ) );
		}

		return true;
	}

	/**
	 * Some temporary code to handle changes to items in a very simple fashion:
	 * The changes now contain the complete new version of the item, which
	 * on every detected change gets updated in the local items table.
	 *
	 * This is inefficient because:
	 * * Multiple changes can be created for a single update to an item (ie sitelink and alias changes)
	 * * The whole item is stored in each change on top of the actual change diff
	 *
	 * TODO: further handle item changes according to their types, including page cache invalidation
	 *
	 * @since 0.1
	 *
	 * @param ItemChange $change
	 */
	public static function onWikibaseItemChange( ItemChange $change ) {
		$localItem = LocalItem::newFromItem( $change->getItem() );
		$localItem->save();
	}

	public static function onWikibaseQueryChange( $change ) {
		// TODO
	}

	/**
	 * Adds default settings.
	 * Setting name (string) => setting value (mixed)
	 *
	 * @param array &$settings
	 *
	 * @since 0.1
	 *
	 * @return boolean
	 */
	public static function onWikibaseDefaultSettings( array &$settings ) {
		$settings = array_merge(
			$settings,
			array(
				'namespaces' => array( NS_MAIN ),
				'source' => array( 'dir' => dirname(__FILE__) . '/tests' ),
				'editURL' => '',
				'sort' => 'none',
				'sortPrepend' => false,
				'alwaysSort' => false,
			)
		);

		return true;
	}

}
