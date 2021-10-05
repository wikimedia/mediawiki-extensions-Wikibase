<?php

/**
 * Example configuration for the Wikibase Client extension.
 *
 * This file is NOT an entry point the Wikibase Client extension. Use WikibaseClient.php.
 * It should furthermore not be included from outside the extension.
 *
 * @see docs/options.wiki
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */

if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'Not an entry point.' );
}

$wgWBClientSettings['injectRecentChanges'] = true;
$wgWBClientSettings['showExternalRecentChanges'] = true;
