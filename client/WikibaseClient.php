<?php

/**
 * Entry point for the Wikibase Client extension.
 *
 * @see README.md
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase_Client
 *
 * @license GPL-2.0-or-later
 */

if ( function_exists( 'wfLoadExtension' ) ) {
	wfLoadExtension( 'WikibaseClient', __DIR__ . '/../extension-client.json' );
	wfWarn(
		'Deprecated PHP entry point used for Wikibase Client extension. ' .
		'Please use wfLoadExtension instead, see' .
		'https://www.mediawiki.org/wiki/Extension_registration for more details.'
	);
	return true;
}

die( 'This version of the Wikibase client extension requires MediaWiki 1.35+' );
