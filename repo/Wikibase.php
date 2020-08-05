<?php

/**
 * Entry point for the Wikibase Repository extension.
 *
 * @see README.md
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase_Repository
 *
 * @license GPL-2.0-or-later
 */

if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'Not an entry point.' );
}

// load parts already converted to extension registration
wfLoadExtension( 'WikibaseRepository', __DIR__ . '/../extension-repo.json' );

call_user_func( function() {
	global $wgExtensionMessagesFiles,
		$wgMessagesDirs;

	// i18n messages, kept for backward compatibility (T257442)
	$wgMessagesDirs['Wikibase'] = __DIR__ . '/i18n';
	$wgMessagesDirs['WikibaseApi'] = __DIR__ . '/i18n/api';
	$wgMessagesDirs['WikibaseLib'] = __DIR__ . '/../lib/i18n';
	$wgMessagesDirs['WikibaseView'] = __DIR__ . '/../view/lib/wikibase-data-values-value-view/i18n';
	$wgExtensionMessagesFiles['WikibaseAlias'] = __DIR__ . '/Wikibase.i18n.alias.php';
	$wgExtensionMessagesFiles['WikibaseNS'] = __DIR__ . '/Wikibase.i18n.namespaces.php';
	$wgExtensionMessagesFiles['wikibaserepomagic'] = __DIR__ . '/WikibaseRepo.i18n.magic.php';
} );
