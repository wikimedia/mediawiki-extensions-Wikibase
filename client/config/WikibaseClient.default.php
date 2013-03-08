<?php

/**
 * This file assigns the default values to all Wikibase Client settings.
 *
 * This file is NOT an entry point the Wikibase Client extension. Use WikibaseClient.php.
 * It should furthermore not be included from outside the extension.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @since 0.4
 *
 * @file
 * @ingroup WikibaseClient
 *
 * @licence GNU GPL v2+
 */

if ( !defined( 'WBC_VERSION' ) ) {
	die( 'Not an entry point.' );
}

global $wgScriptPath, $wgArticlePath, $wgLanguageCode, $wgDBname;

$wgWBClientSettings = array(
	'namespaces' => array(), // by default, include all namespaces; deprecated as of 0.4
	'excludeNamespaces' => array(),
	'repoUrl' => '//wikidata.org',
	'repoScriptPath' => $wgScriptPath,
	'repoArticlePath' => $wgArticlePath,
	'sort' => 'code',
	'sortPrepend' => array(),
	'alwaysSort' => false,
	'siteGlobalID' => $wgDBname,
	// @todo would be great to just get this from the sites stuff
	// but we will need to make sure the caching works good enough
	'siteLocalID' => $wgLanguageCode,
	'siteGroup' => 'wikipedia',
	'injectRecentChanges' => true,
	'showExternalRecentChanges' => true,
	'defaultClientStore' => null,
	'repoDatabase' => null, // note: "false" means "local"!
	// default for repo items in main namespace
	'repoNamespaces' => array(
		'wikibase-item' => '',
		'wikibase-property' => 'Property'
	)
);

/**
 * @todo this is a bit wikimedia-specific and need to find a better place for this stuff,
 * such as mediawiki-config or somewhere.
 *
 * alphabetic and alphabetic revised come from:
 * http://meta.wikimedia.org/w/index.php?title=MediaWiki:Interwiki_config-sorting_order-native-languagename
 * http://meta.wikimedia.org/w/index.php?title=MediaWiki:Interwiki_config-sorting_order-native-languagename-firstword (revised)
 * and from pywikipedia for alphabetic_sr
 */
$wgWBClientSettings['interwikiSortOrders'] = array(
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
    )
);
