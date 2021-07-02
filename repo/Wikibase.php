<?php

/**
 * Entry point for the Wikibase Repository extension.
 *
 * @see README.md
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase_Repository
 *
 * @license GPL-2.0-or-later
 */

if ( function_exists( 'wfLoadExtension' ) ) {
	wfLoadExtension( 'WikibaseRepository', __DIR__ . '/../extension-repo.json' );
	wfWarn(
		'Deprecated PHP entry point used for Wikibase Repository extension. ' .
		'Please use wfLoadExtension instead, see' .
		'https://www.mediawiki.org/wiki/Extension_registration for more details.'
	);
	return true;
}

die( 'This version of the Wikibase Repository extension requires MediaWiki 1.35+' );
