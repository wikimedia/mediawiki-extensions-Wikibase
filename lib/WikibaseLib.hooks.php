<?php

namespace Wikibase;
use DatabaseUpdater;

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
final class LibHooks {

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
				'pollDefaultInterval' => 1,
				'pollDefaultLimit' => 100,
				'pollContinueInterval' => 0,

				'itemPrefix' => 'q',
				'propertyPrefix' => 'p',
				'queryPrefix' => 'y', // TODO: find a more suiting prefix, perhaps use 'q' and use 'i' for items then

				'siteLinkGroup' => 'wikipedia',

				'changeHandlers' => array(
					'wikibase-item~add' => 'Wikibase\EntityCreation',
					'wikibase-property~add' => 'Wikibase\EntityCreation',
					'wikibase-query~add' => 'Wikibase\EntityCreation',

					'wikibase-item~update' => 'Wikibase\EntityUpdate',
					'wikibase-property~update' => 'Wikibase\EntityUpdate',
					'wikibase-query~update' => 'Wikibase\EntityUpdate',

					'wikibase-item~remove' => 'Wikibase\EntityDeletion',
					'wikibase-property~remove' => 'Wikibase\EntityDeletion',
					'wikibase-query~remove' => 'Wikibase\EntityDeletion',

					'wikibase-item~refresh' => 'Wikibase\EntityRefresh',
					'wikibase-property~refresh' => 'Wikibase\EntityRefresh',
					'wikibase-query~refresh' => 'Wikibase\EntityRefresh',
				),
			)
		);

		return true;
	}

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
		$type = $updater->getDB()->getType();

		if ( $type === 'mysql' || $type === 'sqlite' /* || $type === 'postgres' */ ) {
			$extension = $type === 'postgres' ? '.pg.sql' : '.sql';

			$updater->addExtensionTable(
				'wb_changes',
				__DIR__ . '/sql/WikibaseLib' . $extension
			);

			// TODO: move to core
			$updater->addExtensionField(
				'sites',
				'site_source',
				__DIR__ . '/sql/DropSites.sql'
			);

			$updater->addExtensionTable(
				'site_identifiers',
				__DIR__ . '/sql/AddSitesTable.sql'
			);

			$updater->addExtensionUpdate( array( '\Wikibase\Utils::insertDefaultSites' /*, array( $updater, 'output' )*/ ) );
		}
		else {
			wfWarn( "Database type '$type' is not supported by Wikibase Client." );
		}

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
		// @codeCoverageIgnoreStart
		$testFiles = array(
			'changes/DiffChange',
			'changes/EntityCreation',
			'changes/EntityDeletion',
			'changes/EntityRefresh',
			'changes/EntityUpdate',

			'claim/ClaimList',
			'claim/ClaimObject',

			'item/ItemDiff',
			'item/ItemMultilangTexts',
			'item/ItemNewEmpty',
			'item/ItemNewFromArray',
			'item/ItemObject',

			'property/PropertyObject',

			'query/QueryObject',

			'reference/ReferenceList',
			'reference/ReferenceObject',

			'snak/PropertyValueSnak',
			'snak/SnakList',

			'ChangeNotifier',
			'ChangeHandler',
			'ChangesTable',
			'LibHooks',
			'MapValueHasher',
			'SiteLink',
			'StatementObject',
			'Utils',

			'site/MediaWikiSite',
			'site/SiteList',
			'site/SiteObject',
			'site/Sites',

			'store/SiteLinkLookup',
		);

		// Test compat
		if ( !array_key_exists( 'SettingsBase', $GLOBALS['wgAutoloadLocalClasses'] ) ) {
			$testFiles[] = 'SettingsBase';
		}

		foreach ( $testFiles as $file ) {
			$files[] = __DIR__ . '/tests/phpunit/' . $file . 'Test.php';
		}

		return true;
		// @codeCoverageIgnoreEnd
	}

}
