<?php

/**
 * Lib is not an application and should not have global config state.
 * Therefore you should NOT add new settings here. If both client and repo
 * need them, add them to both.
 *
 * This file assigns the default values to all Wikibase Lib settings.
 *
 * This file is NOT an entry point to the Wikibase extension.
 * It should not be included from outside the extension.
 *
 * @license GPL-2.0-or-later
 */
return [
	// whether changes get recorded to wb_changes
	'useChangesTable' => true,

	'siteLinkGroups' => [],

	'specialSiteLinkGroups' => [],

	'maxSerializedEntitySize' => (int)$GLOBALS['wgMaxArticleSize'],
];
