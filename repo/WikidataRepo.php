<?php
/**
 * @file
 * @ingroup Wikidata
 */

// safeguard ---------------------------------------------------------
if ( !defined( 'MEDIAWIKI' ) ) {
        echo( "This file is an extension to the MediaWiki software and cannot be used standalone.\n" );
        die( 1 );
	die( 'Not an entry point.' );
}

if ( version_compare( $wgVersion, '1.19c', '<' ) ) { // Needs to be 1.19c because version_compare() works in confusing ways.
	die( '<b>Error:</b> WikidataRepo requires MediaWiki 1.19 or above.' );
}

// credits ---------------------------------------------------------
$WikidataRepoVersion = 0.1;

$wgExtensionCredits['other'][] = array(
        'path' => __FILE__,
        'name' => 'WikidataRepo',
        'author' => 'Daniel Kinzler for Hallo Welt Medienwerkstatt',
        'url' => 'http://mediawiki.org/wiki/Extension:WikidataRepo',
        'descriptionmsg' => 'wikidatarepo-desc',
        'version' => $WikidataRepoVersion,
);

// register resources ---------------------------------------------------------
$dir = dirname( __FILE__ ) . '/';
$wgExtensionMessagesFiles['WikidataRepo'] = $dir . 'WikidataRepo.i18n.php';

$wgResourceModules['ext.WikidataRepo'] = array(
        // JavaScript and CSS styles. To combine multiple file, just list them as an array.
        'scripts' => 'WikidataRepo.js',
        'styles' => 'WikidataRepo.css',

        // When your module is loaded, these messages will be available to mediaWiki.msg()
        //'messages' => array( 'myextension-hello-world', 'myextension-goodbye-world' ),

        // If your scripts need code from other modules, list their identifiers as dependencies
        // and ResourceLoader will make sure they're loaded before you.
        // You don't need to manually list 'mediawiki' or 'jquery', which are always loaded.
        //'dependencies' => array( 'jquery.ui.datepicker' ),

        // ResourceLoader needs to know where your files are; specify your
        // subdir relative to "extensions" or $wgExtensionAssetsPath
        'localBasePath' => 'WikidataRepo',
        'remoteExtPath' => 'WikidataRepo',
);

// register classes ---------------------------------------------------------
$wgAutoloadClasses['WikidataContentHandler'] = $dir . 'WikidataContentHandler.php';
$wgAutoloadClasses['WikidataDifferenceEngine'] = $dir . 'WikidataContentHandler.php';
$wgAutoloadClasses['WikidataContent'] = $dir . 'WikidataContent.php';
$wgAutoloadClasses['ApiQueryWikidataProp'] = $dir . 'ApiQueryWikidataProp.php';
$wgAutoloadClasses['WikidataPage'] = $dir . 'WikidataPage.php';

// register hooks and handlers ---------------------------------------------------------
define('CONTENT_MODEL_WIKIDATA', 'wikidata');
$wgContentHandlers[CONTENT_MODEL_WIKIDATA] = 'WikidataContentHandler';
$wgAPIPropModules['wikidata'] = 'ApiQueryWikidataProp';

// configuration defaults ---------------------------------------------------------
$wgWikidataSerialisationFormat = 'application/json'; # alternative: application/vnd.php.serialized
