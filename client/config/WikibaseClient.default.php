<?php

use Wikibase\Client\WikibaseClient;
use Wikibase\SettingsArray;

/**
 * This file assigns the default values to all Wikibase Client settings.
 *
 * This file is NOT an entry point the Wikibase Client extension. Use WikibaseClient.php.
 * It should furthermore not be included from outside the extension.
 *
 * @since 0.4
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */

return call_user_func( function() {
	global $wgLanguageCode;

	$defaults = [
		'namespaces' => [], // by default, include all namespaces; deprecated as of 0.4
		'excludeNamespaces' => [],
		'sort' => 'code',
		'sortPrepend' => [],
		'alwaysSort' => false,
		// @todo would be great to just get this from the sites stuff
		// but we will need to make sure the caching works good enough
		'siteLocalID' => $wgLanguageCode,
		'languageLinkSiteGroup' => null,
		'injectRecentChanges' => true,
		'showExternalRecentChanges' => true,
		'sendEchoNotification' => false,
		'repoIcon' => false,
		'allowDataTransclusion' => true,
		'propagateChangesToRepo' => true,
		'otherProjectsLinksByDefault' => false,
		'otherProjectsLinksBeta' => false,
		// List of additional CSS class names for site links that have badges,
		// e.g. array( 'Q101' => 'badge-goodarticle' )
		'badgeClassNames' => [],
		// Allow accessing data from other items in the parser functions and via Lua
		'allowArbitraryDataAccess' => true,
		// Maximum number of full entities that can be accessed on a page. This does
		// not include convenience functions like mw.wikibase.label that use TermLookup
		// instead of loading a full entity.
		'entityAccessLimit' => 250,
		// Allow accessing data in the user's language rather than the content language
		// in the parser functions and via Lua.
		// Allows users to split the ParserCache by user language.
		'allowDataAccessInUserLanguage' => false,

		/**
		 * Prefix to use for cache keys that should be shared among a Wikibase Repo instance and all
		 * its clients. This is for things like caching entity blobs in memcached.
		 *
		 * The default here assumes Wikibase Repo + Client installed together on the same wiki. For
		 * a multiwiki / wikifarm setup, to configure shared caches between clients and repo, this
		 * needs to be set to the same value in both client and repo wiki settings.
		 *
		 * For Wikidata production, we set it to 'wikibase-shared/wikidata_1_25wmf24-wikidatawiki',
		 * which is 'wikibase_shared/' + deployment branch name + '-' + repo database name, and have
		 * it set in both $wgWBClientSettings and $wgWBRepoSettings.
		 */
		'sharedCacheKeyPrefix' => 'wikibase_shared/' . rawurlencode( WBL_VERSION ) . '-' . $GLOBALS['wgDBname'],

		/**
		 * The duration of the object cache, in seconds.
		 *
		 * As with sharedCacheKeyPrefix, this is both client and repo setting. On a multiwiki setup,
		 * this should be set to the same value in both the repo and clients. Also note that the
		 * setting value in $wgWBClientSettings overrides the one here.
		 */
		'sharedCacheDuration' => 60 * 60,

		/**
		 * List of data types (by data type id) not enabled on the wiki.
		 * This setting is intended to aid with deployment of new data types
		 * or on new Wikibase installs without items and properties yet.
		 *
		 * This setting should be consistent with the corresponding setting on the repo.
		 *
		 * WARNING: Disabling a data type after it is in use is dangerous
		 * and might break items.
		 */
		'disabledDataTypes' => [],

		// The type of object cache to use. Use CACHE_XXX constants.
		// This is both a repo and client setting, and should be set to the same value in
		// repo and clients for multiwiki setups.
		'sharedCacheType' => $GLOBALS['wgMainCacheType'],

		/**
		 * @todo this is a bit wikimedia-specific and need to find a better place for this stuff,
		 * such as mediawiki-config, mediawiki messages for custom orders, or somewhere.
		 *
		 * alphabetic and alphabetic revised come from:
		 * http://meta.wikimedia.org/w/index.php?title=MediaWiki:Interwiki_config-sorting_order-native-languagename
		 * http://meta.wikimedia.org/w/index.php?title=MediaWiki:Interwiki_config-sorting_order-native-languagename-firstword (revised)
		 * and from pywikipedia for alphabetic_sr
		 */
		'interwikiSortOrders' => [
			'alphabetic' => [
				'ace', 'kbd', 'ady', 'af', 'ak', 'als', 'am', 'ang', 'ab', 'ar', 'an', 'arc',
				'roa-rup', 'frp', 'as', 'ast', 'gn', 'av', 'ay', 'az', 'azb', 'bm', 'bn', 'bjn',
				'zh-min-nan', 'nan', 'map-bms', 'ba', 'be', 'be-x-old', 'bh', 'bcl', 'bi',
				'bg', 'bar', 'bo', 'bs', 'br', 'bxr', 'ca', 'cv', 'ceb', 'cs', 'ch',
				'cbk-zam', 'ny', 'sn', 'tum', 'cho', 'co', 'cy', 'da', 'dk', 'pdc', 'de',
				'dv', 'nv', 'dsb', 'dz', 'mh', 'et', 'el', 'eml', 'en', 'myv', 'es', 'eo',
				'ext', 'eu', 'ee', 'fa', 'hif', 'fo', 'fr', 'fy', 'ff', 'fur', 'ga', 'gv',
				'gag', 'gd', 'gl', 'gan', 'ki', 'glk', 'gu', 'got', 'gom', 'hak', 'xal', 'ko',
				'ha', 'haw', 'hy', 'hi', 'ho', 'hsb', 'hr', 'io', 'ig', 'ilo', 'bpy', 'id', 'ia',
				'ie', 'iu', 'ik', 'os', 'xh', 'zu', 'is', 'it', 'he', 'jv', 'kl', 'kn', 'kr',
				'pam', 'krc', 'ka', 'ks', 'csb', 'kk', 'kw', 'rw', 'rn', 'sw', 'kv', 'kg',
				'ht', 'ku', 'kj', 'ky', 'mrj', 'lad', 'lbe', 'lez', 'lo', 'lrc', 'ltg', 'la',
				'lv', 'lb', 'lt', 'lij', 'li', 'ln', 'jbo', 'lg', 'lmo', 'hu', 'mk', 'mg', 'ml',
				'mt', 'mi', 'mr', 'xmf', 'arz', 'mzn', 'ms', 'min', 'cdo', 'mwl', 'mdf', 'mo',
				'mn', 'mus', 'my', 'nah', 'na', 'fj', 'nl', 'nds-nl', 'cr', 'ne', 'new', 'ja',
				'nap', 'ce', 'frr', 'pih', 'no', 'nb', 'nn', 'nrm', 'nov', 'ii', 'oc', 'mhr',
				'or', 'om', 'ng', 'hz', 'uz', 'pa', 'pi', 'pfl', 'pag', 'pnb', 'pap', 'ps', 'jam',
				'koi', 'km', 'pcd', 'pms', 'tpi', 'nds', 'pl', 'tokipona', 'tp', 'pnt', 'pt',
				'aa', 'kaa', 'crh', 'ty', 'ksh', 'ro', 'rmy', 'rm', 'qu', 'rue', 'ru', 'sah',
				'se', 'sm', 'sa', 'sg', 'sc', 'sco', 'stq', 'st', 'nso', 'tn', 'sq', 'scn',
				'si', 'simple', 'sd', 'ss', 'sk', 'sl', 'cu', 'szl', 'so', 'ckb', 'srn', 'sr',
				'sh', 'su', 'fi', 'sv', 'tl', 'ta', 'shi', 'kab', 'roa-tara', 'tt', 'te', 'tet',
				'th', 'ti', 'tg', 'to', 'chr', 'chy', 've', 'tr', 'tk', 'tw', 'tyv', 'udm', 'bug',
				'uk', 'ur', 'ug', 'za', 'vec', 'vep', 'vi', 'vo', 'fiu-vro', 'wa', 'zh-classical',
				'vls', 'war', 'wo', 'wuu', 'ts', 'yi', 'yo', 'zh-yue', 'diq', 'zea', 'bat-smg',
				'zh', 'zh-tw', 'zh-cn'
			],
			'alphabetic_revised' => [
				'ace', 'ady', 'kbd', 'af', 'ak', 'als', 'am', 'ang', 'ab', 'ar', 'an', 'arc', 'roa-rup',
				'frp', 'as', 'ast', 'gn', 'av', 'ay', 'az', 'azb', 'bjn', 'id', 'ms', 'bm', 'bn',
				'zh-min-nan', 'nan', 'map-bms', 'jv', 'su', 'ba', 'min', 'be', 'be-x-old', 'bh',
				'bcl', 'bi', 'bar', 'bo', 'bs', 'br', 'bug', 'bg', 'bxr', 'ca', 'ceb', 'cv', 'cs',
				'ch', 'cbk-zam', 'ny', 'sn', 'tum', 'cho', 'co', 'cy', 'da', 'dk', 'pdc', 'de',
				'dv', 'nv', 'dsb', 'na', 'dz', 'mh', 'et', 'el', 'eml', 'en', 'myv', 'es', 'eo',
				'ext', 'eu', 'ee', 'fa', 'hif', 'fo', 'fr', 'fy', 'ff', 'fur', 'ga', 'gv', 'sm',
				'gag', 'gd', 'gl', 'gan', 'ki', 'glk', 'gu', 'got', 'gom', 'hak', 'xal', 'ko',
				'ha', 'haw', 'hy', 'hi', 'ho', 'hsb', 'hr', 'io', 'ig', 'ilo', 'bpy', 'ia', 'ie',
				'iu', 'ik', 'os', 'xh', 'zu', 'is', 'it', 'he', 'kl', 'kn', 'kr', 'pam', 'ka',
				'ks', 'csb', 'kk', 'kw', 'rw', 'ky', 'rn', 'mrj', 'sw', 'kv', 'kg', 'ht', 'ku',
				'kj', 'lad', 'lbe', 'lez', 'lo', 'la', 'lrc', 'ltg', 'lv', 'to', 'lb', 'lt', 'lij',
				'li', 'ln', 'jbo', 'lg', 'lmo', 'hu', 'mk', 'mg', 'ml', 'krc', 'mt', 'mi', 'mr',
				'xmf', 'arz', 'mzn', 'cdo', 'mwl', 'koi', 'mdf', 'mo', 'mn', 'mus', 'my', 'nah',
				'fj', 'nl',	'nds-nl', 'cr', 'ne', 'new', 'ja', 'nap', 'ce', 'frr', 'pih', 'no',
				'nb', 'nn', 'nrm', 'nov', 'ii', 'oc', 'mhr', 'or', 'om', 'ng', 'hz', 'uz', 'pa',
				'pi', 'pfl', 'pag', 'pnb', 'pap', 'ps', 'jam', 'km', 'pcd', 'pms', 'nds', 'pl', 'pnt',
				'pt', 'aa', 'kaa', 'crh', 'ty', 'ksh', 'ro', 'rmy', 'rm', 'qu', 'ru', 'rue', 'sah',
				'se', 'sa', 'sg', 'sc', 'sco', 'stq', 'st', 'nso', 'tn', 'sq', 'scn', 'si',
				'simple', 'sd', 'ss', 'sk', 'sl', 'cu', 'szl', 'so', 'ckb', 'srn', 'sr', 'sh',
				'fi', 'sv', 'tl', 'ta', 'shi', 'kab', 'roa-tara', 'tt', 'te', 'tet', 'th', 'vi',
				'ti', 'tg', 'tpi', 'tokipona', 'tp', 'chr', 'chy', 've', 'tr', 'tk', 'tw', 'tyv', 'udm',
				'uk', 'ur', 'ug', 'za', 'vec', 'vep', 'vo', 'fiu-vro', 'wa', 'zh-classical', 'vls',
				'war', 'wo', 'wuu', 'ts', 'yi', 'yo', 'zh-yue', 'diq', 'zea', 'bat-smg', 'zh',
				'zh-tw', 'zh-cn'
			],
			'alphabetic_sr' => [
				'ace', 'ady', 'kbd', 'af', 'ak', 'als', 'am', 'ang', 'ab', 'ar', 'an', 'arc',
				'roa-rup', 'frp', 'arz', 'as', 'ast', 'gn', 'av', 'ay', 'az', 'azb', 'bjn', 'id',
				'ms', 'bg', 'bm', 'zh-min-nan', 'nan', 'map-bms', 'jv', 'su', 'ba', 'be',
				'be-x-old', 'bh', 'bcl', 'bi', 'bn', 'bo', 'bar', 'bs', 'bpy', 'br', 'bug',
				'bxr', 'ca', 'ceb', 'ch', 'cbk-zam', 'sn', 'tum', 'ny', 'cho', 'chr', 'co',
				'cy', 'cv', 'cs', 'da', 'dk', 'pdc', 'de', 'nv', 'dsb', 'na', 'dv', 'dz',
				'mh', 'et', 'el', 'eml', 'en', 'myv', 'es', 'eo', 'ext', 'eu', 'ee', 'fa',
				'hif', 'fo', 'fr', 'fy', 'ff', 'fur', 'ga', 'gv', 'sm', 'gag', 'gd', 'gl',
				'gan', 'ki', 'glk', 'got', 'gom', 'gu', 'ha', 'hak', 'xal', 'haw', 'he',
				'hi', 'ho', 'hsb', 'hr', 'hy', 'io', 'ig', 'ii', 'ilo', 'ia', 'ie', 'iu',
				'ik', 'os', 'xh', 'zu', 'is', 'it', 'ja', 'ka', 'kl', 'kr', 'pam', 'krc',
				'csb', 'kk', 'kw', 'rw', 'ky', 'mrj', 'rn', 'sw', 'km', 'kn', 'ko', 'kv',
				'kg', 'ht', 'ks', 'ku', 'kj', 'lad', 'lbe', 'la', 'lrc', 'ltg', 'lv', 'to',
				'lb', 'lez', 'lt', 'lij', 'li', 'ln', 'lo', 'jbo', 'lg', 'lmo', 'hu', 'mk',
				'mg', 'mt', 'mi', 'min', 'cdo', 'mwl', 'ml', 'mdf', 'mo', 'mn', 'mr', 'mus',
				'my', 'mzn', 'nah', 'fj', 'ne', 'nl', 'nds-nl', 'cr', 'new', 'nap', 'ce',
				'frr', 'pih', 'no', 'nb', 'nn', 'nrm', 'nov', 'oc', 'mhr', 'or', 'om', 'ng',
				'hz', 'uz', 'pa', 'pfl', 'pag', 'pap', 'koi', 'pi', 'pcd', 'pms', 'nds',
				'pnb', 'pl', 'pt', 'pnt', 'ps', 'jam', 'aa', 'kaa', 'crh', 'ty', 'ksh', 'ro', 'rmy',
				'rm', 'qu', 'ru', 'rue', 'sa', 'sah', 'se', 'sg', 'sc', 'sco', 'sd', 'stq',
				'st', 'nso', 'tn', 'sq', 'si', 'scn', 'simple', 'ss', 'sk', 'sl', 'cu', 'szl',
				'so', 'ckb', 'srn', 'sr', 'sh', 'fi', 'sv', 'ta', 'shi', 'tl', 'kab',
				'roa-tara', 'tt', 'te', 'tet', 'th', 'ti', 'vi', 'tg', 'tokipona', 'tp',
				'tpi', 'chy', 've', 'tr', 'tk', 'tw', 'tyv', 'udm', 'uk', 'ur', 'ug', 'za', 'vec',
				'vep', 'vo', 'fiu-vro', 'wa', 'vls', 'war', 'wo', 'wuu', 'ts', 'xmf', 'yi',
				'yo', 'diq', 'zea', 'zh', 'zh-tw', 'zh-cn', 'zh-classical', 'zh-yue', 'bat-smg'
			],
			'alphabetic_fy' => [
				'aa', 'ab', 'ace', 'ady', 'af', 'ay', 'ak', 'als', 'am', 'an', 'ang', 'ar', 'arc',
				'arz', 'as', 'ast', 'av', 'az', 'azb', 'ba', 'bar', 'bat-smg', 'bcl', 'be', 'be-x-old',
				'bg', 'bh', 'bi', 'bjn', 'bm', 'bn', 'bo', 'bpy', 'br', 'bs', 'bug', 'bxr',
				'ca', 'cbk-zam', 'cdo', 'ce', 'ceb', 'ch', 'chy', 'cho', 'chr', 'cy', 'ckb',
				'co', 'cr', 'crh', 'cs', 'csb', 'cu', 'cv', 'da', 'de', 'diq', 'dk', 'dsb', 'dv',
				'dz', 'ee', 'el', 'eml', 'en', 'eo', 'es', 'et', 'eu', 'ext', 'fa', 'ff', 'fi',
				'fy', 'fiu-vro', 'fj', 'fo', 'fr', 'frp', 'frr', 'fur', 'ga', 'gag', 'gan', 'gd',
				'gl', 'glk', 'gn', 'got', 'gom', 'gu', 'gv', 'ha', 'hak', 'haw', 'he', 'hi', 'hy',
				'hif', 'ho', 'hr', 'hsb', 'ht', 'hu', 'hz', 'ia', 'id', 'ie', 'ig', 'ii', 'yi',
				'ik', 'ilo', 'io', 'yo', 'is', 'it', 'iu', 'ja', 'jam', 'jbo', 'jv', 'ka', 'kaa', 'kab',
				'kbd', 'kg', 'ki', 'ky', 'kj', 'kk', 'kl', 'km', 'kn', 'ko', 'koi', 'kr', 'krc',
				'ks', 'ksh', 'ku', 'kv', 'kw', 'la', 'lad', 'lb', 'lbe', 'lez', 'lg', 'li',
				'lij', 'lmo', 'ln', 'lo', 'lrc', 'lt', 'ltg', 'lv', 'map-bms', 'mdf', 'mg', 'mh',
				'mhr', 'mi', 'my', 'min', 'myv', 'mk', 'ml', 'mn', 'mo', 'mr', 'mrj', 'ms', 'mt',
				'mus', 'mwl', 'mzn', 'na', 'nah', 'nan', 'nap', 'nds', 'nds-nl', 'ne', 'new', 'ng',
				'ny', 'nl', 'nn', 'no', 'nov', 'nrm', 'nso', 'nv', 'oc', 'om', 'or', 'os', 'pa',
				'pag', 'pam', 'pap', 'pcd', 'pdc', 'pfl', 'pi', 'pih', 'pl', 'pms', 'pnb', 'pnt',
				'ps', 'pt', 'qu', 'rm', 'rmy', 'rn', 'ro', 'roa-rup', 'roa-tara', 'ru', 'rue',
				'rw', 'sa', 'sah', 'sc', 'scn', 'sco', 'sd', 'se', 'sg', 'sh', 'shi', 'si', 'simple',
				'sk', 'sl', 'sm', 'sn', 'so', 'sq', 'sr', 'srn', 'ss', 'st', 'stq', 'su', 'sv',
				'sw', 'szl', 'ta', 'te', 'tet', 'tg', 'th', 'ti', 'ty', 'tk', 'tl', 'tn', 'to',
				'tokipona', 'tp', 'tpi', 'tr', 'ts', 'tt', 'tum', 'tw', 'tyv', 'udm', 'ug', 'uk', 'ur',
				'uz', 've', 'vec', 'vep', 'vi', 'vls', 'vo', 'wa', 'war', 'wo', 'wuu', 'xal',
				'xh', 'xmf', 'za', 'zea', 'zh', 'zh-classical', 'zh-cn', 'zh-yue', 'zh-min-nan',
				'zh-tw', 'zu'
			],
		],
	];

	// Some defaults depend on information not available at this time.
	// Especially, if the repository may be active on the local wiki, and
	// we need to adjust some defaults accordingly.
	// We use Closures to calculate such settings on the fly, the first time they
	// are used. See SettingsArray::setSetting() for details.

	//NOTE: when this is executed, WB_VERSION may not yet be defined, because
	//      the repo extension has not yet been initialized. We need to defer the
	//      check and do it inside the closures.
	//      We use the pseudo-setting thisWikiIsTheRepo to store this information.
	//      thisWikiIsTheRepo should really never be overwritten, except for testing.

	$defaults['thisWikiIsTheRepo'] = function ( SettingsArray $settings ) {
		// determine whether the repo extension is present
		return defined( 'WB_VERSION' );
	};

	$defaults['repoSiteName'] = function ( SettingsArray $settings ) {
		// This uses $wgSitename if this wiki is the repo.  Otherwise, set this to
		// either an i18n message key and the message will be used, if it exists.
		// If repo site name does not need translation, then set this as a string.
		return $settings->getSetting( 'thisWikiIsTheRepo' ) ? $GLOBALS['wgSitename'] : 'Wikidata';
	};

	$defaults['repoUrl'] = function ( SettingsArray $settings ) {
		// use $wgServer if this wiki is the repo, otherwise default to wikidata.org
		return $settings->getSetting( 'thisWikiIsTheRepo' ) ? $GLOBALS['wgServer'] : '//www.wikidata.org';
	};

	$defaults['repoConceptBaseUri'] = function ( SettingsArray $settings ) {
		return $settings->getSetting( 'repoUrl' ) . '/entity/';
	};

	$defaults['repoArticlePath'] = function ( SettingsArray $settings ) {
		// use $wgArticlePath if this wiki is the repo, otherwise default to /wiki/$1
		return $settings->getSetting( 'thisWikiIsTheRepo' ) ? $GLOBALS['wgArticlePath'] : '/wiki/$1';
	};

	$defaults['repoScriptPath'] = function ( SettingsArray $settings ) {
		// use $wgScriptPath if this wiki is the repo, otherwise default to /w
		return $settings->getSetting( 'thisWikiIsTheRepo' ) ? $GLOBALS['wgScriptPath'] : '/w';
	};

	$defaults['repoDatabase'] = function ( SettingsArray $settings ) {
		// Use false (meaning the local wiki's database) if this wiki is the repo,
		// otherwise default to null (meaning we can't access the repo's DB directly).
		return $settings->getSetting( 'thisWikiIsTheRepo' ) ? false : null;
	};

	$defaults['entityNamespaces'] = function ( SettingsArray $settings ) {
		if ( $settings->getSetting( 'thisWikiIsTheRepo' ) ) {
			// if this is the repo wiki, use the repo setting
			$repoSettings = WikibaseClient::getDefaultInstance()->getRepoSettings();
			return $repoSettings->getSetting( 'entityNamespaces' );
		} else {
			// XXX: Default to having Items in the main namespace, and properties in NS 120.
			// That is the live setup at wikidata.org, it is NOT consistent with the example settings!
			return [
				'item' => 0,
				'property' => 120
			];
		}
	};

	$defaults['repoNamespaces'] = function ( SettingsArray $settings ) {
		if ( $settings->getSetting( 'thisWikiIsTheRepo' ) ) {
			// if this is the repo wiki, look up the namespace names based on the entityNamespaces setting
			$namespaceNames = array_map(
				'MWNamespace::getCanonicalName',
				$settings->getSetting( 'entityNamespaces' )
			);
			return $namespaceNames;
		} else {
			// XXX: Default to having Items in the main namespace, and properties in the 'Property' namespace.
			// That is the live setup at wikidata.org, it is NOT consistent with the example settings!
			return [
				'item' => '',
				'property' => 'Property'
			];
		}
	};

	$defaults['changesDatabase'] = function ( SettingsArray $settings ) {
		// Per default, the database for tracking changes is the repo's database.
		// Note that the value for the repoDatabase setting may be calculated dynamically,
		// see above.
		return $settings->getSetting( 'repoDatabase' );
	};

	$defaults['siteGlobalID'] = function ( SettingsArray $settings ) {
		// The database name is a sane default for the site ID.
		// On Wikimedia sites, this is always correct.
		return $GLOBALS['wgDBname'];
	};

	$defaults['repoSiteId'] = function( SettingsArray $settings ) {
		// If repoDatabase is set, then default is same as repoDatabase
		// otherwise, defaults to siteGlobalID
		return ( $settings->getSetting( 'repoDatabase' ) === false )
			? $settings->getSetting( 'siteGlobalID' )
			: $settings->getSetting( 'repoDatabase' );
	};

	$defaults['siteGroup'] = function ( SettingsArray $settings ) {
		// by default lookup from SiteSQLStore, can override with setting for performance reasons
		return null;
	};

	$defaults['otherProjectsLinks'] = function ( SettingsArray $settings ) {
		$otherProjectsSitesProvider = WikibaseClient::getDefaultInstance()->getOtherProjectsSitesProvider();
		return $otherProjectsSitesProvider->getOtherProjectsSiteIds( $settings->getSetting( 'siteLinkGroups' ) );
	};

	return $defaults;
} );
