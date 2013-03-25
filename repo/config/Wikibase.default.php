<?php

/**
 * This file assigns the default values to all Wikibase Repo settings.
 *
 * This file is NOT an entry point the Wikibase extension. Use Wikibase.php.
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
 * @ingroup WikibaseRepo
 *
 * @licence GNU GPL v2+
 */

if ( !defined( 'WB_VERSION' ) ) {
	die( 'Not an entry point.' );
}

$dir = __DIR__ . '/';

$wgWBRepoSettings = array();

// Set API in debug mode
// do not turn on in production!
$wgWBRepoSettings['apiInDebug'] = false;

// Additional settings for API when debugging is on to
// facilitate testing.
$wgWBRepoSettings['apiDebugWithPost'] = false;
$wgWBRepoSettings['apiDebugWithRights'] = false;
$wgWBRepoSettings['apiDebugWithTokens'] = false;

$wgWBRepoSettings['defaultStore'] = 'sqlstore';

$wgWBRepoSettings['idBlacklist'] = array(
	1,
	23,
	42,
	1337,
	9001,
	31337,
	720101010,
);

// Allow the TermIndex table to work without the term_search_key field,
// for sites that can not easily roll out schema changes on large tables.
// This means that all searches will use exact matching
// (depending on the database's collation).
$wgWBRepoSettings['withoutTermSearchKey'] = false;

$wgWBRepoSettings['entityNamespaces'] = array();

// These are used for multilanguage strings that should have a soft length constraint
$wgWBRepoSettings['multilang-limits'] = array(
	'length' => 250,
);

$wgWBRepoSettings['multilang-truncate-length'] = 32;

// Should the page names (titles) be normalized against the external site
$wgWBRepoSettings['normalizeItemByTitlePageNames'] = false;
