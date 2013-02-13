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
	'namespaces' => array( NS_MAIN ),
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
