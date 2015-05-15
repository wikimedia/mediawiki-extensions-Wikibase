<?php
if ( function_exists( 'wfLoadExtension' ) ) {
	wfLoadExtension( 'Wikibase/client' );
	// Keep i18n globals so mergeMessageFileList.php doesn't break
	$wgMessagesDirs['wikibaseclient'] = __DIR__ . '/i18n';
	$wgExtensionMessagesFiles['Wikibaseclientalias'] = __DIR__ . '/WikibaseClient.i18n.alias.php';
	$wgExtensionMessagesFiles['wikibaseclientmagic'] = __DIR__ . '/WikibaseClient.i18n.magic.php';
	/* wfWarn(
		'Deprecated PHP entry point used for Wikibase Client extension. Please use wfLoadExtension instead, ' .
		'see https://www.mediawiki.org/wiki/Extension_registration for more details.'
	); */
	return;
} else {
	die( 'This version of the Wikibase Client extension requires MediaWiki 1.25+' );
}

