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
				'clientPageArgs' => array(
					'action' => 'query',
					'prop' => 'info',
					'redirects' => true,
					'converttitles' => true,
					'format' => 'json',
					//TODO: This should REALLY be handled somehow as without it we could run into lots of trouble
					//'maxage' => 5, // filter down repeated clicks, don't let clicky folks loose to fast
					//'smaxage' => 15, // give the proxy some time, don't let clicky folks loose to fast
					//'maxlag' => 100, // time to wait on a lagging server, hanging on for 100 sec is very aggressive
				),
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

		if ( $type === 'mysql' || $type === 'sqlite' ) {
			$updater->addExtensionTable(
				'wb_changes',
				__DIR__ . '/sql/WikibaseLib.sql'
			);

			// TODO: move to core
			$updater->addExtensionTable(
				'sites',
				__DIR__ . '/sql/AddSitesTable.sql'
			);

			// TODO: move to core
			$updater->addExtensionField(
				'langlinks',
				'll_local',
				__DIR__ . '/sql/AddLocalLanglinksField.sql'
			);

			$updater->addExtensionField(
				'sites',
				'site_link_navigation',
				__DIR__ . '/sql/IndexSitesTable.sql'
			);

			$updater->addExtensionField(
				'sites',
				'site_language',
				__DIR__ . '/sql/MakeSitesTableMoarAwesome.sql'
			);

			$updater->addExtensionUpdate( array( '\Wikibase\Utils::insertDefaultSites' ) );
		}
		elseif ( $type === 'postgres' ) {
			$updater->addExtensionTable(
				'wb_changes',
				__DIR__ . '/sql/WikibaseLib.pg.sql'
			);

			//$updater->addExtensionUpdate( array( '\Wikibase\Utils::insertDefaultSites' ) );
		}
		else {
			// TODO
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

			'entity/ItemDiff', // wtf is this in /entity?

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
			'ClaimObject',
			'LibHooks',
			'MapValueHasher',
			'MediaWikiSite',
			'SiteConfigObject',
			'SiteLink',
			'SiteList',
			'SiteRow',
			'MediaWikiSite',
			'Sites',
			'StatementObject',
			'Utils',
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
