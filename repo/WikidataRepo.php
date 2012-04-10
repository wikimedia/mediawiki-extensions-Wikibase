<?php

/**
 * Initialization file for the WikidataRepo extension.
 * Note: the name WikidataRepo is still suceptive to change, so it and links using it might later be changed.
 *
 * Documentation:	 		https://www.mediawiki.org/wiki/Extension:WikidataRepo
 * Support					https://www.mediawiki.org/wiki/Extension_talk:WikidataRepo
 * Source code:				https://gerrit.wikimedia.org/r/gitweb?p=mediawiki/extensions/WikidataRepo.git
 *
 * @file WikidataRepo.php
 * @ingroup WikidataRepo
 *
 * @licence GNU GPL v2+
 */

/**
 * This documentation group collects source code files belonging to WikidataRepo.
 *
 * @defgroup WikidataRepo WikidataRepo
 */

if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'Not an entry point.' );
}

if ( version_compare( $wgVersion, '1.20c', '<' ) ) { // Needs to be 1.20c because version_compare() works in confusing ways.
	die( '<b>Error:</b> WikidataRepo requires MediaWiki 1.20 or above.' );
}

define( 'WDR_VERSION', '0.1 alpha' );

$wgExtensionCredits['other'][] = array(
	'path' => __FILE__,
	'name' => 'Wikidata Repo',
	'version' => WDR_VERSION,
	'author' => array(
		'The Wikidata team', // TODO: link?
	),
	'url' => 'https://www.mediawiki.org/wiki/Extension:Wikidata_Repo',
	'descriptionmsg' => 'wikidatarepo-desc'
);

$dir = dirname( __FILE__ ) . '/';



// i18n
$wgExtensionMessagesFiles['WikidataRepo'] 		= $dir . 'WikidataRepo.i18n.php';



// Autoloading
$wgAutoloadClasses['WDRSettings'] 				= $dir . 'WikidataRepo.settings.php';

// api
$wgAutoloadClasses['ApiQueryWikidataProp'] 		= $dir . 'api/ApiQueryWikidataProp.php';

// includes
$wgAutoloadClasses['WikidataContentHandler'] 	= $dir . 'includes/WikidataContentHandler.php';
$wgAutoloadClasses['WikidataDifferenceEngine'] 	= $dir . 'includes/WikidataDifferenceEngine.php';
$wgAutoloadClasses['WikidataContent'] 			= $dir . 'includes/WikidataContent.php';
$wgAutoloadClasses['WikidataPage'] 				= $dir . 'includes/WikidataPage.php';



// API module registration
$wgAPIPropModules['wikidata'] = 'ApiQueryWikidataProp';



// Resource loader modules
$moduleTemplate = array(
	'localBasePath' => dirname( __FILE__ ) . '/resources',
	'remoteExtPath' => 'WikidataRepo/resources',
);

$wgResourceModules['wdr.exampleName'] = $moduleTemplate + array(
	'scripts' => array(
		'wdr.exampleName.js',
	),
	'styles' => array(
		'wdr.exampleName.css',
	),
	'dependencies' => array(
	),
);

unset( $moduleTemplate );



// register hooks and handlers
define( 'CONTENT_MODEL_WIKIDATA', 'wikidata' );
$wgContentHandlers[CONTENT_MODEL_WIKIDATA] = 'WikidataContentHandler';


$egWDRSettings = array();
