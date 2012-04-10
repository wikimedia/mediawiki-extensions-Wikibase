<?php

/**
 * Initialization file for the Wikibase extension.
 *
 * Documentation:	 		https://www.mediawiki.org/wiki/Extension:Wikibase
 * Support					https://www.mediawiki.org/wiki/Extension_talk:Wikibase
 * Source code:				https://gerrit.wikimedia.org/r/gitweb?p=mediawiki/extensions/WikidataRepo.git
 *
 * @file Wikibase.php
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 */

/**
 * This documentation group collects source code files belonging to Wikibase.
 *
 * @defgroup Wikibase Wikibase
 */

if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'Not an entry point.' );
}

if ( version_compare( $wgVersion, '1.19c', '<' ) ) { // Needs to be 1.19c because version_compare() works in confusing ways.
	die( '<b>Error:</b> Wikibase requires MediaWiki 1.19 or above.' );
}

define( 'WDR_VERSION', '0.1 alpha' );

$wgExtensionCredits['other'][] = array(
	'path' => __FILE__,
	'name' => 'Wikibase',
	'version' => WDR_VERSION,
	'author' => array(
		'The Wikidata team', // TODO: link?
	),
	'url' => 'https://www.mediawiki.org/wiki/Extension:Wikibase',
	'descriptionmsg' => 'wikibase-desc'
);

$dir = dirname( __FILE__ ) . '/';



// i18n
$wgExtensionMessagesFiles['Wikibase'] 			= $dir . 'Wikibase.i18n.php';



// Autoloading
$wgAutoloadClasses['WBSettings'] 				= $dir . 'Wikibase.settings.php';

// api
$wgAutoloadClasses['ApiQueryWikibaseProp'] 		= $dir . 'api/ApiQueryWikibaseProp.php'; // TODO
$wgAutoloadClasses['ApiWikibaseGetItem'] 		= $dir . 'api/ApiWikibaseGetItem.php';

// includes
$wgAutoloadClasses['WikibaseContentHandler'] 	= $dir . 'includes/WikibaseContentHandler.php';
$wgAutoloadClasses['WikibaseDifferenceEngine'] 	= $dir . 'includes/WikibaseDifferenceEngine.php';
$wgAutoloadClasses['WikibaseContent'] 			= $dir . 'includes/WikibaseContent.php';
$wgAutoloadClasses['WikibasePage'] 				= $dir . 'includes/WikibasePage.php';



// API module registration
$wgAPIPropModules['wikidata'] = 'ApiQueryWikibaseProp'; // TODO
$wgAPIPropModules['wbgetitem'] = 'ApiWikibaseGetItem';



// Resource loader modules
$moduleTemplate = array(
	'localBasePath' => dirname( __FILE__ ) . '/resources',
	'remoteExtPath' => 'Wikibase/resources',
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
$wgContentHandlers[CONTENT_MODEL_WIKIDATA] = 'WikibaseContentHandler';


$egWDSettings = array();
