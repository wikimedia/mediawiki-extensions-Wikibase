<?php

/**
 * Lib is not an application and should not have global config state.
 * Therefore you should NOT add new settings here. If both client and repo
 * need them, add them to both.
 *
 * This file assigns the default values to all Wikibase Lib settings.
 *
 * This file is NOT an entry point the WikibaseLib extension. Use WikibaseLib.php.
 * It should furthermore not be included from outside the extension.
 *
 * @license GPL-2.0-or-later
 */
return [
	// whether changes get recorded to wb_changes
	'useChangesTable' => true,

	'siteLinkGroups' => [
		'wikipedia',
	],

	'specialSiteLinkGroups' => [],

	// local by default. Set to something LBFactory understands.
	'changesDatabase' => false,

	'maxSerializedEntitySize' => (int)$GLOBALS['wgMaxArticleSize'],

	/**
	 * @note This config option is primarily added for Wikidata transition use-case and can be
	 * considered temporary. It could be removed in the future with no warning.
	 *
	 * It can be one of these stages:
	 * MIGRATION_OLD, MIGRATION_WRITE_BOTH, MIGRATION_WRITE_NEW, MIGRATION_NEW
	 */
	'tmpPropertyTermsMigrationStage' => MIGRATION_OLD,

	/**
	 * @note This config option is primarily added for Wikidata transition use-case and can be
	 * considered temporary. It could be removed in the future with no warning.
	 *
	 * This is an array from maximum numeric item ID to one of
	 * MIGRATION_OLD, MIGRATION_WRITE_BOTH, MIGRATION_WRITE_NEW, MIGRATION_NEW.
	 * The final entry should use the key 'max' and applies to all other item IDs.
	 */
	'tmpItemTermsMigrationStages' => [ 'max' => MIGRATION_OLD ],

];
