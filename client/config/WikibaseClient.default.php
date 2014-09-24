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
 * @licence GNU GPL v2+
 *
 * @author Daniel Kinzler
 */

return call_user_func( function() {
	global $wgLanguageCode;

	$defaults = array(
		'namespaces' => array(), // by default, include all namespaces; deprecated as of 0.4
		'excludeNamespaces' => array(),
		'sort' => 'code',
		'sortPrepend' => array(),
		'alwaysSort' => false,
		// @todo would be great to just get this from the sites stuff
		// but we will need to make sure the caching works good enough
		'siteLocalID' => $wgLanguageCode,
		'languageLinkSiteGroup' => null,
		'injectRecentChanges' => true,
		'showExternalRecentChanges' => true,
		// default for repo items in main namespace
		'repoNamespaces' => array(
			'wikibase-item' => '',
			'wikibase-property' => 'Property'
		),
		'allowDataTransclusion' => true,
		'propagateChangesToRepo' => true,
		'otherProjectsLinksByDefault' => false,
		'otherProjectsLinksBeta' => false,
		// List of additional CSS class names for site links that have badges,
		// e.g. array( 'Q101' => 'badge-goodarticle' )
		'badgeClassNames' => array(),
		// Allow accessing data from other items in the parser functions and via Lua
		'allowArbitraryDataAccess' => true,
		// The new usage tracking is not hooked up yet
		'useLegacyUsageIndex' => false,

		/**
		 * @todo this is a bit wikimedia-specific and need to find a better place for this stuff,
		 * such as mediawiki-config, mediawiki messages for custom orders, or somewhere.
		 *
		 * alphabetic and alphabetic revised come from:
		 * http://meta.wikimedia.org/w/index.php?title=MediaWiki:Interwiki_config-sorting_order-native-languagename
		 * http://meta.wikimedia.org/w/index.php?title=MediaWiki:Interwiki_config-sorting_order-native-languagename-firstword (revised)
		 * and from pywikipedia for alphabetic_sr
		 */
		'interwikiSortOrders' => array(
			'alphabetic' => array(
				'ace', 'kbd', 'af', 'ak', 'als', 'am', 'ang', 'ab', 'ar', 'an', 'arc',
				'roa-rup', 'frp', 'as', 'ast', 'gn', 'av', 'ay', 'az', 'bm', 'bn', 'bjn',
				'zh-min-nan', 'nan', 'map-bms', 'ba', 'be', 'be-x-old', 'bh', 'bcl', 'bi',
				'bg', 'bar', 'bo', 'bs', 'br', 'bxr', 'ca', 'cv', 'ceb', 'cs', 'ch',
				'cbk-zam', 'ny', 'sn', 'tum', 'cho', 'co', 'cy', 'da', 'dk', 'pdc', 'de',
				'dv', 'nv', 'dsb', 'dz', 'mh', 'et', 'el', 'eml', 'en', 'myv', 'es', 'eo',
				'ext', 'eu', 'ee', 'fa', 'hif', 'fo', 'fr', 'fy', 'ff', 'fur', 'ga', 'gv',
				'gag', 'gd', 'gl', 'gan', 'ki', 'glk', 'gu', 'got', 'hak', 'xal', 'ko', 'ha',
				'haw', 'hy', 'hi', 'ho', 'hsb', 'hr', 'io', 'ig', 'ilo', 'bpy', 'id', 'ia',
				'ie', 'iu', 'ik', 'os', 'xh', 'zu', 'is', 'it', 'he', 'jv', 'kl', 'kn', 'kr',
				'pam', 'krc', 'ka', 'ks', 'csb', 'kk', 'kw', 'rw', 'rn', 'sw', 'kv', 'kg',
				'ht', 'ku', 'kj', 'ky', 'mrj', 'lad', 'lbe', 'lez', 'lo', 'ltg', 'la', 'lv',
				'lb', 'lt', 'lij', 'li', 'ln', 'jbo', 'lg', 'lmo', 'hu', 'mk', 'mg', 'ml',
				'mt', 'mi', 'mr', 'xmf', 'arz', 'mzn', 'ms', 'min', 'cdo', 'mwl', 'mdf', 'mo',
				'mn', 'mus', 'my', 'nah', 'na', 'fj', 'nl', 'nds-nl', 'cr', 'ne', 'new', 'ja',
				'nap', 'ce', 'frr', 'pih', 'no', 'nb', 'nn', 'nrm', 'nov', 'ii', 'oc', 'mhr',
				'or', 'om', 'ng', 'hz', 'uz', 'pa', 'pi', 'pfl', 'pag', 'pnb', 'pap', 'ps',
				'koi', 'km', 'pcd', 'pms', 'tpi', 'nds', 'pl', 'tokipona', 'tp', 'pnt', 'pt',
				'aa', 'kaa', 'crh', 'ty', 'ksh', 'ro', 'rmy', 'rm', 'qu', 'rue', 'ru', 'sah',
				'se', 'sm', 'sa', 'sg', 'sc', 'sco', 'stq', 'st', 'nso', 'tn', 'sq', 'scn',
				'si', 'simple', 'sd', 'ss', 'sk', 'sl', 'cu', 'szl', 'so', 'ckb', 'srn', 'sr',
				'sh', 'su', 'fi', 'sv', 'tl', 'ta', 'shi', 'kab', 'roa-tara', 'tt', 'te', 'tet',
				'th', 'ti', 'tg', 'to', 'chr', 'chy', 've', 'tr', 'tk', 'tw', 'udm', 'bug',
				'uk', 'ur', 'ug', 'za', 'vec', 'vep', 'vi', 'vo', 'fiu-vro', 'wa', 'zh-classical',
				'vls', 'war', 'wo', 'wuu', 'ts', 'yi', 'yo', 'zh-yue', 'diq', 'zea', 'bat-smg',
				'zh', 'zh-tw', 'zh-cn'
			),
			'alphabetic_revised' => array(
				'ace', 'kbd', 'af', 'ak', 'als', 'am', 'ang', 'ab', 'ar', 'an', 'arc', 'roa-rup',
				'frp', 'as', 'ast', 'gn', 'av', 'ay', 'az', 'bjn', 'id', 'ms', 'bm', 'bn',
				'zh-min-nan', 'nan', 'map-bms', 'jv', 'su', 'ba', 'min', 'be', 'be-x-old', 'bh',
				'bcl', 'bi', 'bar', 'bo', 'bs', 'br', 'bug', 'bg', 'bxr', 'ca', 'ceb', 'cv', 'cs',
				'ch', 'cbk-zam', 'ny', 'sn', 'tum', 'cho', 'co', 'cy', 'da', 'dk', 'pdc', 'de',
				'dv', 'nv', 'dsb', 'na', 'dz', 'mh', 'et', 'el', 'eml', 'en', 'myv', 'es', 'eo',
				'ext', 'eu', 'ee', 'fa', 'hif', 'fo', 'fr', 'fy', 'ff', 'fur', 'ga', 'gv', 'sm',
				'gag', 'gd', 'gl', 'gan', 'ki', 'glk', 'gu', 'got', 'hak', 'xal', 'ko', 'ha', 'haw',
				'hy', 'hi', 'ho', 'hsb', 'hr', 'io', 'ig', 'ilo', 'bpy', 'ia', 'ie', 'iu', 'ik',
				'os', 'xh', 'zu', 'is', 'it', 'he', 'kl', 'kn', 'kr', 'pam', 'ka', 'ks', 'csb',
				'kk', 'kw', 'rw', 'ky', 'rn', 'mrj', 'sw', 'kv', 'kg', 'ht', 'ku', 'kj', 'lad',
				'lbe', 'lez', 'lo', 'la', 'ltg', 'lv', 'to', 'lb', 'lt', 'lij', 'li', 'ln', 'jbo',
				'lg', 'lmo', 'hu', 'mk', 'mg', 'ml', 'krc', 'mt', 'mi', 'mr', 'xmf', 'arz', 'mzn',
				'cdo', 'mwl', 'koi', 'mdf', 'mo', 'mn', 'mus', 'my', 'nah', 'fj', 'nl', 'nds-nl',
				'cr', 'ne', 'new', 'ja', 'nap', 'ce', 'frr', 'pih', 'no', 'nb', 'nn', 'nrm', 'nov',
				'ii', 'oc', 'mhr', 'or', 'om', 'ng', 'hz', 'uz', 'pa', 'pi', 'pfl', 'pag', 'pnb',
				'pap', 'ps', 'km', 'pcd', 'pms', 'nds', 'pl', 'pnt', 'pt', 'aa', 'kaa', 'crh', 'ty',
				'ksh', 'ro', 'rmy', 'rm', 'qu', 'ru', 'rue', 'sah', 'se', 'sa', 'sg', 'sc', 'sco',
				'stq', 'st', 'nso', 'tn', 'sq', 'scn', 'si', 'simple', 'sd', 'ss', 'sk', 'sl',
				'cu', 'szl', 'so', 'ckb', 'srn', 'sr', 'sh', 'fi', 'sv', 'tl', 'ta', 'shi',
				'kab', 'roa-tara', 'tt', 'te', 'tet', 'th', 'vi', 'ti', 'tg', 'tpi', 'tokipona',
				'tp', 'chr', 'chy', 've', 'tr', 'tk', 'tw', 'udm', 'uk', 'ur', 'ug', 'za',
				'vec', 'vep', 'vo', 'fiu-vro', 'wa', 'zh-classical', 'vls', 'war', 'wo', 'wuu',
				'ts', 'yi', 'yo', 'zh-yue', 'diq', 'zea', 'bat-smg', 'zh', 'zh-tw', 'zh-cn'
			),
			'alphabetic_sr' => array(
				'ace', 'kbd', 'af', 'ak', 'als', 'am', 'ang', 'ab', 'ar', 'an', 'arc',
				'roa-rup', 'frp', 'arz', 'as', 'ast', 'gn', 'av', 'ay', 'az', 'bjn', 'id',
				'ms', 'bg', 'bm', 'zh-min-nan', 'nan', 'map-bms', 'jv', 'su', 'ba', 'be',
				'be-x-old', 'bh', 'bcl', 'bi', 'bn', 'bo', 'bar', 'bs', 'bpy', 'br', 'bug',
				'bxr', 'ca', 'ceb', 'ch', 'cbk-zam', 'sn', 'tum', 'ny', 'cho', 'chr', 'co',
				'cy', 'cv', 'cs', 'da', 'dk', 'pdc', 'de', 'nv', 'dsb', 'na', 'dv', 'dz',
				'mh', 'et', 'el', 'eml', 'en', 'myv', 'es', 'eo', 'ext', 'eu', 'ee', 'fa',
				'hif', 'fo', 'fr', 'fy', 'ff', 'fur', 'ga', 'gv', 'sm', 'gag', 'gd', 'gl',
				'gan', 'ki', 'glk', 'got', 'gu', 'ha', 'hak', 'xal', 'haw', 'he', 'hi', 'ho',
				'hsb', 'hr', 'hy', 'io', 'ig', 'ii', 'ilo', 'ia', 'ie', 'iu', 'ik', 'os',
				'xh', 'zu', 'is', 'it', 'ja', 'ka', 'kl', 'kr', 'pam', 'krc', 'csb', 'kk',
				'kw', 'rw', 'ky', 'mrj', 'rn', 'sw', 'km', 'kn', 'ko', 'kv', 'kg', 'ht',
				'ks', 'ku', 'kj', 'lad', 'lbe', 'la', 'ltg', 'lv', 'to', 'lb', 'lez', 'lt',
				'lij', 'li', 'ln', 'lo', 'jbo', 'lg', 'lmo', 'hu', 'mk', 'mg', 'mt', 'mi',
				'min', 'cdo', 'mwl', 'ml', 'mdf', 'mo', 'mn', 'mr', 'mus', 'my', 'mzn', 'nah',
				'fj', 'ne', 'nl', 'nds-nl', 'cr', 'new', 'nap', 'ce', 'frr', 'pih', 'no', 'nb',
				'nn', 'nrm', 'nov', 'oc', 'mhr', 'or', 'om', 'ng', 'hz', 'uz', 'pa', 'pfl',
				'pag', 'pap', 'koi', 'pi', 'pcd', 'pms', 'nds', 'pnb', 'pl', 'pt', 'pnt',
				'ps', 'aa', 'kaa', 'crh', 'ty', 'ksh', 'ro', 'rmy', 'rm', 'qu', 'ru', 'rue',
				'sa', 'sah', 'se', 'sg', 'sc', 'sco', 'sd', 'stq', 'st', 'nso', 'tn', 'sq',
				'si', 'scn', 'simple', 'ss', 'sk', 'sl', 'cu', 'szl', 'so', 'ckb', 'srn',
				'sr', 'sh', 'fi', 'sv', 'ta', 'shi', 'tl', 'kab', 'roa-tara', 'tt', 'te',
				'tet', 'th', 'ti', 'vi', 'tg', 'tokipona', 'tp', 'tpi', 'chy', 've', 'tr',
				'tk', 'tw', 'udm', 'uk', 'ur', 'ug', 'za', 'vec', 'vep', 'vo', 'fiu-vro',
				'wa', 'vls', 'war', 'wo', 'wuu', 'ts', 'xmf', 'yi', 'yo', 'diq', 'zea', 'zh',
				'zh-tw', 'zh-cn', 'zh-classical', 'zh-yue', 'bat-smg'
			),
			'alphabetic_fy' => array(
				'aa', 'ab', 'ace', 'af', 'ay', 'ak', 'als', 'am', 'an', 'ang', 'ar', 'arc',
				'arz', 'as', 'ast', 'av', 'az', 'ba', 'bar', 'bat-smg', 'bcl', 'be', 'be-x-old',
				'bg', 'bh', 'bi', 'bjn', 'bm', 'bn', 'bo', 'bpy', 'br', 'bs', 'bug', 'bxr',
				'ca', 'cbk-zam', 'cdo', 'ce', 'ceb', 'ch', 'chy', 'cho', 'chr', 'cy', 'ckb',
				'co', 'cr', 'crh', 'cs', 'csb', 'cu', 'cv', 'da', 'de', 'diq', 'dk', 'dsb', 'dv',
				'dz', 'ee', 'el', 'eml', 'en', 'eo', 'es', 'et', 'eu', 'ext', 'fa', 'ff', 'fi',
				'fy', 'fiu-vro', 'fj', 'fo', 'fr', 'frp', 'frr', 'fur', 'ga', 'gag', 'gan', 'gd',
				'gl', 'glk', 'gn', 'got', 'gu', 'gv', 'ha', 'hak', 'haw', 'he', 'hi', 'hy',
				'hif', 'ho', 'hr', 'hsb', 'ht', 'hu', 'hz', 'ia', 'id', 'ie', 'ig', 'ii', 'yi',
				'ik', 'ilo', 'io', 'yo', 'is', 'it', 'iu', 'ja', 'jbo', 'jv', 'ka', 'kaa', 'kab',
				'kbd', 'kg', 'ki', 'ky', 'kj', 'kk', 'kl', 'km', 'kn', 'ko', 'koi', 'kr', 'krc',
				'ks', 'ksh', 'ku', 'kv', 'kw', 'la', 'lad', 'lb', 'lbe', 'lez', 'lg', 'li',
				'lij', 'lmo', 'ln', 'lo', 'lt', 'ltg', 'lv', 'map-bms', 'mdf', 'mg', 'mh', 'mhr',
				'mi', 'my', 'min', 'myv', 'mk', 'ml', 'mn', 'mo', 'mr', 'mrj', 'ms', 'mt', 'mus',
				'mwl', 'mzn', 'na', 'nah', 'nan', 'nap', 'nds', 'nds-nl', 'ne', 'new', 'ng', 'ny',
				'nl', 'nn', 'no', 'nov', 'nrm', 'nso', 'nv', 'oc', 'om', 'or', 'os', 'pa', 'pag',
				'pam', 'pap', 'pcd', 'pdc', 'pfl', 'pi', 'pih', 'pl', 'pms', 'pnb', 'pnt', 'ps',
				'pt', 'qu', 'rm', 'rmy', 'rn', 'ro', 'roa-rup', 'roa-tara', 'ru', 'rue', 'rw',
				'sa', 'sah', 'sc', 'scn', 'sco', 'sd', 'se', 'sg', 'sh', 'shi', 'si', 'simple',
				'sk', 'sl', 'sm', 'sn', 'so', 'sq', 'sr', 'srn', 'ss', 'st', 'stq', 'su', 'sv',
				'sw', 'szl', 'ta', 'te', 'tet', 'tg', 'th', 'ti', 'ty', 'tk', 'tl', 'tn', 'to',
				'tokipona', 'tp', 'tpi', 'tr', 'ts', 'tt', 'tum', 'tw', 'udm', 'ug', 'uk', 'ur',
				'uz', 've', 'vec', 'vep', 'vi', 'vls', 'vo', 'wa', 'war', 'wo', 'wuu', 'xal',
				'xh', 'xmf', 'za', 'zea', 'zh', 'zh-classical', 'zh-cn', 'zh-yue', 'zh-min-nan',
				'zh-tw', 'zu'
			),
		),
	);

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

	// Prefix to use for cache keys that should be shared among
	// a wikibase repo and all its clients.
	// In order to share caches between clients (and the repo)
	// the default includes WBL_VERSION and the repo's DB name.
	$defaults['sharedCacheKeyPrefix'] = function ( SettingsArray $settings ) {
		// Per default, the database for tracking changes is the repo's database.
		// Note that the value for the repoDatabase setting may be calculated dynamically,
		// see above.

		//NOTE: keep in sync with the default value for sharedCacheKeyPrefix defined
		//      in WikibaseLib.default.php, since that's the key the repo is using per
		//      default.

		$repoId = $settings->getSetting( 'repoDatabase' );

		if ( $repoId === false ) {
			// false means the local wiki's DB (this is the case when the local wiki is the repo)
			$repoId = $GLOBALS['wgDBname'];
		} elseif ( $repoId === null ) {
			// null means no direct access to the repo's DB, so use the repo's URL instead
			$repoId = 'repoUrl:' . $settings->getSetting( 'repoUrl' );
		}

		return $repoId . ':WBL/' . WBL_VERSION;
	};

	return $defaults;
} );
