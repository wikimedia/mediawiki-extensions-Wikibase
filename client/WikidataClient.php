<?php
/**
 * WikidataClient extension
 * Pulls data from WikidataRepo into a wiki
 */

if ( !defined( 'MEDIAWIKI' ) ) {
	echo 'This is not a valid entry point for MediaWiki.';
}

$wgWikidataClientNamespaces = array(0);
$wgWikidataClientSort = 'none';
$wgWikidataClientSortPrepend = false;

$wgExtensionCredits['other'][] = array(
	'author' => array( 'Katie Filbert', 'Nikola Smolenski' ),
	'descriptionmsg' => 'wikidataclient-desc',
	'name' => 'WikidataClient',
	'url' => 'http://www.mediawiki.org/wiki/WikidataClient',
	'version' => '0.1',
	'path' => __FILE__
);

$dir = dirname(__FILE__) . '/';

$wgAutoloadClasses['WikidataClientHooks'] = $dir . 'WikidataClientHooks.php';

$wgHooks['ParserBeforeTidy'][] = 'WikidataClientHooks::parserBeforeTidy';
