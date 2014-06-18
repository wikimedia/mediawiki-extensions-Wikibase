<?php

/**
 * This file assigns the default values to all Wikibase Repo settings.
 *
 * This file is NOT an entry point the Wikibase extension. Use Wikibase.php.
 * It should furthermore not be included from outside the extension.
 *
 * @since 0.4
 *
 * @licence GNU GPL v2+
 */

return call_user_func( function() {
	global $wgSquidMaxage;

	$defaults = array(
		'idBlacklist' => array(
			1,
			23,
			42,
			1337,
			9001,
			31337,
			720101010,
		),

		// Allow the TermIndex table to work without the term_search_key field,
		// for sites that can not easily roll out schema changes on large tables.
		// This means that all searches will use exact matching
		// (depending on the database's collation).
		'withoutTermSearchKey' => false,

		'entityNamespaces' => array(),

		// Define constraints for multilingual terms (such as labels, descriptions and aliases).
		'multilang-limits' => array(
			'length' => 250, // length constraint
		),

		// Should the page names (titles) be normalized against the external site
		'normalizeItemByTitlePageNames' => false,

		// items allowed to be used as badges
		'badgeItems' => array(),

		// Number of seconds for which data output shall be cached.
		// Note: keep that low, because such caches can not always be purged easily.
		'dataSquidMaxage' => $wgSquidMaxage,

		// Formats that shall be available via SpecialEntityData.
		// The first format will be used as the default.
		// This is a whitelist, some formats may not be supported because when missing
		// optional dependencies (e.g. easyRdf).
		// The formats are given using logical names as used by EntityDataSerializationService.
		'entityDataFormats' => array(
			// using the API
			'json', // default
			'php',
			'xml',

			// using easyRdf
			'rdfxml',
			'n3',
			'turtle',
			'ntriples',

			// hardcoded internal handling
			'html',
		),

		'dataRightsUrl' => function() {
			return $GLOBALS['wgRightsUrl'];
		},

		'dataRightsText' => function() {
			return $GLOBALS['wgRightsText'];
		},

		// Can be used to override the serialization used for storage.
		// Typical value: Wikibase\Lib\Serializers\LegacyInternalEntitySerializer
		'internalEntitySerializerClass' => null,
	);

	return $defaults;
} );
