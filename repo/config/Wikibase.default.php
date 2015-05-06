<?php

use Wikibase\SettingsArray;

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
		// for sites that cannot easily roll out schema changes on large tables.
		// This means that all searches will use exact matching
		// (depending on the database's collation).
		'withoutTermSearchKey' => false,

		'entityNamespaces' => array(),

		// Define constraints for multilingual terms (such as labels, descriptions and aliases).
		'multilang-limits' => array(
			'length' => 250, // length constraint
		),

		// Items allowed to be used as badges pointing to their CSS class names
		'badgeItems' => array(),

		// Number of seconds for which data output shall be cached.
		// Note: keep that low, because such caches cannot always be purged easily.
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

		// Can be used to override the serialization used for storage.
		// Typical value: Wikibase\Lib\Serializers\LegacyInternalClaimSerializer
		'internalClaimSerializerClass' => null,

		'transformLegacyFormatOnExport' => function( SettingsArray $settings ) {
			// Enabled, unless internalEntitySerializerClass is set.
			return $settings->getSetting( 'internalEntitySerializerClass' ) === null;
		},

		'useRedirectTargetColumn' => true,

		'conceptBaseUri' => function() {
			$uri = $GLOBALS['wgServer'];
			$uri = preg_replace( '!^//!', 'http://', $uri );
			$uri = $uri . '/entity/';

			return $uri;
		},

		// Determines how subscription lookup is handled. Possible values:
		//
		// - 'sitelinks': Use only sitelinks to determine which wiki is subscribed to which entity.
		//                Use this mode if the wb_changes_subscription table does not exist.
		// - 'subscriptions': use explicit subscriptions in the wb_changes_subscription table.
		// - 'subscriptions+sitelinks': use a combination of both.
		//
		// @note: if Wikibase Repo and Client are enabled on the same wiki, this setting
		//        needs to match the useLegacyChangesSubscription value in the client settings.
		'subscriptionLookupMode' => 'subscriptions',

		'allowEntityImport' => false,

		// Prefix to use for cache keys that should be shared among a Wikibase Repo instance
		// and all its clients. This is for things like caching entity blobs in memcached.
		//
		// The default setting assumes Wikibase Repo + Client installed together on the same wiki.
		// For a multiwiki / wikifarm setup, to configure shared caches between clients and repo,
		// this needs to be set to the same value in both client and repo wiki settings.
		//
		// For Wikidata production, we set it to 'wikibase-shared/wikidata_1_25wmf24-wikidatawiki',
		// which is 'wikibase_shared/' + deployment branch name + '-' + repo database name,
		// and have it set in both $wgWBClientSettings and $wgWBRepoSettings.
		//
		// Please note that $wgWBClientSettings overrides settings such as this one in the repo,
		// if client is enabled on the same wiki.
		'sharedCacheKeyPrefix' => 'wikibase_shared/' . WBL_VERSION . '-' . $GLOBALS['wgDBname'],

		// The duration of the object cache, in seconds.
		//
		// As with sharedCacheKeyPrefix, this is both client and repo setting. On a multiwiki
		// setup, this should be set to the same value in both the repo and clients.
		// Also note that the setting value in $wgWBClientSettings overrides the one here.
		'sharedCacheDuration' => 60 * 60,

		// The type of object cache to use. Use CACHE_XXX constants.
		// This is both a repo and client setting, and should be set to the same value in
		// repo and clients for multiwiki setups.
		'sharedCacheType' => $GLOBALS['wgMainCacheType'],
	);

	return $defaults;
} );
