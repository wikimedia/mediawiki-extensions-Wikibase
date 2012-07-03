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

		$updater->addExtensionField(
			'wbc_local_items',
			'li_page_title',
			dirname( __FILE__ ) . '/sql/LocalItemTitleField.sql'
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
		$testFiles = array(
			'General',
			'Sorting',

			'includes/LocalItemsTable',
			'includes/LocalItem',
		);

		foreach ( $testFiles as $file ) {
			$files[] = dirname( __FILE__ ) . '/tests/phpunit/' . $file . 'Test.php';
		}

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
		switch ( $change->getType() ) {
			case 'item-add': case 'item-update':
				$localItem = LocalItem::newFromItem( $change->getItem() );
				$localItem->save();
				break;
			case 'item-remove':
				$localItem = LocalItem::newFromItem( $change->getItem() );
				$localItem->remove();
				break;
		}

		return true;
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
