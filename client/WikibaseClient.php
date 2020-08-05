<?php

/**
 * Entry point for the Wikibase Client extension.
 *
 * @see README.md
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase_Client
 *
 * @license GPL-2.0-or-later
 */

if ( !defined( 'MEDIAWIKI' ) ) {
	die( "Not an entry point.\n" );
}

// load parts already converted to extension registration
wfLoadExtension( 'WikibaseClient', __DIR__ . '/../extension-client.json' );

call_user_func( function() {
	global $wgExtensionMessagesFiles,
		$wgMessagesDirs;

	// i18n messages, kept for backward compatibility (T256245)
	$wgMessagesDirs['wikibaseclient'] = __DIR__ . '/i18n';
	$wgMessagesDirs['wikibaseclientapi'] = __DIR__ . '/i18n/api';
	$wgMessagesDirs['WikibaseLib'] = __DIR__ . '/../lib/i18n';
	$wgExtensionMessagesFiles['Wikibaseclientalias'] = __DIR__ . '/WikibaseClient.i18n.alias.php';
	$wgExtensionMessagesFiles['wikibaseclientmagic'] = __DIR__ . '/WikibaseClient.i18n.magic.php';
} );
