<?php

if ( function_exists( 'wfLoadExtension' ) ) {
	wfLoadExtension( 'Wikibase View', __DIR__ . '/extension.json' );
	wfWarn(
		'Deprecated PHP entry point used for Wikibase View extension. ' .
		'Please use wfLoadExtension instead, ' .
		'see https://www.mediawiki.org/wiki/Extension_registration for more details.'
	);
	return;
} else {
	die( 'This version of the Wikibase View extension requires MediaWiki 1.31+' );
}
