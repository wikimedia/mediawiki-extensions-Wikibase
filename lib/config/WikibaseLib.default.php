<?php

/**
 * This file assigns the default values to all Wikibase Lib settings.
 *
 * This file is NOT an entry point the WikibaseLib extension. Use WikibaseLib.php.
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
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 */

if ( !defined( 'WBL_VERSION' ) ) {
	die( 'Not an entry point.' );
}

$wgWBLibDefaultSettings = array();

// alternative: application/vnd.php.serialized
$wgWBLibDefaultSettings['serializationFormat'] = CONTENT_FORMAT_JSON;

// whether changes get recorded to wb_changes
$wgWBLibDefaultSettings['useChangesTable'] = true;

$wgWBLibDefaultSettings['entityPrefixes'] = array(
	'q' => \Wikibase\Item::ENTITY_TYPE,
	'p' => \Wikibase\Property::ENTITY_TYPE,
);

$wgWBLibDefaultSettings['siteLinkGroup'] = 'wikipedia';

// local by default. Set to something LBFactory understands.
$wgWBLibDefaultSettings['changesDatabase'] = false;

// JSON is more robust against version differences between repo and client,
// but only once the client can cope with the JSON form of the change.
$wgWBLibDefaultSettings['changesAsJson'] = true;

// list of logical database names of local client wikis.
// may contain mappings from site-id to db-name.
$wgWBLibDefaultSettings['localClientDatabases'] = array();

$wgWBLibDefaultSettings['dispatchBatchChunkFactor'] = 3;
$wgWBLibDefaultSettings['dispatchBatchCacheFactor'] = 3;

$wgWBLibDefaultSettings['changeHandlers'] = array(
	'wikibase-item~add' => 'Wikibase\ItemChange',
	'wikibase-property~add' => 'Wikibase\EntityChange',
	'wikibase-query~add' => 'Wikibase\EntityChange',

	'wikibase-item~update' => 'Wikibase\ItemChange',
	'wikibase-property~update' => 'Wikibase\EntityChange',
	'wikibase-query~update' => 'Wikibase\EntityChange',

	'wikibase-item~remove' => 'Wikibase\ItemChange',
	'wikibase-property~remove' => 'Wikibase\EntityChange',
	'wikibase-query~remove' => 'Wikibase\EntityChange',

	'wikibase-item~refresh' => 'Wikibase\ItemChange',
	'wikibase-property~refresh' => 'Wikibase\EntityChange',
	'wikibase-query~refresh' => 'Wikibase\EntityChange',

	'wikibase-item~restore' => 'Wikibase\ItemChange',
	'wikibase-property~restore' => 'Wikibase\EntityChange',
	'wikibase-query~restore' => 'Wikibase\EntityChange',
);

$wgWBLibDefaultSettings['dataTypes'] = array(
	'wikibase-item',
	'commonsMedia',
	'string',
);

$wgValueFormatters['wikibase-entityid'] = 'Wikibase\Lib\EntityIdFormatter';
