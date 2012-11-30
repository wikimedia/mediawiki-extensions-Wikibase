<?php

/**
 * Example configuration for the WikibaseClient extension.
 *
 * @author Daniel Kinzler
 */

require_once( "$IP/extensions/Wikibase/lib/WikibaseLib.php");
require_once( "$IP/extensions/Wikibase/client/WikibaseClient.php");

// Base URL for building links to the repository.
// Assumes your wiki is setup as "http://repo.example.org/wiki/"
// This can be protocol relative, such as "//wikidata.org"
$wgWBSettings['repoUrl'] = "http://repo.example.org";

// This setting is optional if you have the same type of setup for your
// repo and client.  It will default to using the client's $wgArticlePath setting,
// and if you do not have $wgArticlePath set anywhere, MediaWiki has a default for it.
$wgWBSettings['repoArticlePath'] = "/wiki/$1";

// Assuming your wiki is setup with such script path as "http://repo.example.org/w/api.php"
// This should be the same as the $wgScriptPath setting if you have it set in your repo
// If $wgScriptPath is not set, then MediaWiki assumes a default.
//
// If your client and repo are setup in the same way, then the below setting is optional
// and will default to what you have $wgScriptPath set in the client.
$wgWBSettings['repoScriptPath'] = "/w";

// The global site ID by which this wiki is known on the repo.
$wgWBSettings['siteGlobalID'] = "mywiki";

// Database name of the repository, for use by the pollForChanges script.
// This requires the given database name to be known to LBFactory, see
// $wgLBFactoryConf below.
$wgWBSettings['changesDatabase'] = "repo";

$wgWBSettings['injectRecentChanges'] = true;
$wgWBSettings['showExternalRecentChanges'] = true;

$wgLBFactoryConf = array(
	// In order to seamlessly access a remote wiki, as the pollForChanges script needs to do,
	// LBFactory_Multi must be used.
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
	'hostsByName' => array(
		'localhost' => '127.0.0.1:3306',
		'local1' => '127.0.2.1',
		'local2' => '127.0.2.2',
		'local3' => '127.0.2.3',
	),

	// Set up as fake master, because there are no slaves.
	'masterTemplateOverrides' => array( 'fakeMaster' => true ),
);

