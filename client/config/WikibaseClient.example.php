<?php

/**
 * Example configuration for the Wikibase Client extension.
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
 * @author Daniel Kinzler
 */

if ( !defined( 'WB_CLIENT_EXAMPLE_ENTRY' ) ) {
	die( 'Not an entry point.' );
}

// Base URL for building links to the repository.
// Assumes your wiki is setup as "http://repo.example.org/wiki/"
// This can be protocol relative, such as "//www.wikidata.org"
$wgWBClientSettings['repoUrl'] = "http://repo.example.org";

// This setting is optional if you have the same type of setup for your
// repo and client.  It will default to using the client's $wgArticlePath setting,
// and if you do not have $wgArticlePath set anywhere, MediaWiki has a default for it.
$wgWBClientSettings['repoArticlePath'] = "/wiki/$1";

// Assuming your wiki is setup with such script path as "http://repo.example.org/w/api.php"
// This should be the same as the $wgScriptPath setting if you have it set in your repo
// If $wgScriptPath is not set, then MediaWiki assumes a default.
//
// If your client and repo are setup in the same way, then the below setting is optional
// and will default to what you have $wgScriptPath set in the client.
$wgWBClientSettings['repoScriptPath'] = "/w";

// The global site ID by which this wiki is known on the repo.
$wgWBClientSettings['siteGlobalID'] = "mywiki";

// Database name of the repository, for direct access from the client.
// repoDatabase and changesDatabase will generally be the same.
// This requires the given database name to be known to LBFactory, see
// $wgLBFactoryConf below.
$wgWBClientSettings['repoDatabase'] = "repo";
$wgWBClientSettings['changesDatabase'] = "repo";

$wgWBClientSettings['injectRecentChanges'] = true;
$wgWBClientSettings['showExternalRecentChanges'] = true;

// In order to access a remote repo using a different database server,
// LBFactory_Multi must be used. In that case, enabled the block below.
// If the repo is on the same server, this is not necessary.
// This does not work with database types other than mysql.
if ( false ) {
	$wgLBFactoryConf = array(
		'class' => 'LBFactory_Multi',

		// Connect to all databases using the same credentials.
		'serverTemplate' => array(
			'dbname'      => $wgDBname,
			'user'        => $wgDBuser,
			'password'    => $wgDBpassword,
			'type'        => 'mysql',
			'flags'       => DBO_DEFAULT | DBO_DEBUG,
		),

		// Configure two sections, one for the repo and one for the client.
		// Each section contains only one server.
		'sectionLoads' => array(
			'DEFAULT' => array(
				'localhost' => 1,
			),
			'repo' => array(
				'local1' => 1,
			),
		),

		// Map the wiki database names to sections. Database names must be unique,
		// i.e. may not exist in more than one section.
		'sectionsByDB' => array(
			$wgDBname => 'DEFAULT',
			'repowiki' => 'repo',
		),

		// Map host names to IP addresses to bypass DNS.
		//
		// NOTE: Even if all sections run on the same MySQL server (typical for a test setup),
		// they must use different IP addresses, and MySQL must listen on all of them.
		// The easiest way to do this is to set bind-address = 0.0.0.0 in the MySQL
		// configuration. Beware that this makes MySQL listen on you ethernet port too.
		// Safer alternatives include setting up mysql-proxy or mysqld_multi.
		//
		// For this setup to work a valid user must be set up for each of the addresses you use,
		// that is grant access to the wikiuser for each of them. Failure to do so will make the
		// MySQL server refuse access.
		'hostsByName' => array(
			'localhost' => '127.0.0.1:3306',
			'local1' => '127.0.2.1',
			'local2' => '127.0.2.2',
			'local3' => '127.0.2.3',
		),

		// Set up as fake master, because there are no slaves.
		'masterTemplateOverrides' => array( 'fakeMaster' => true ),
	);

}
