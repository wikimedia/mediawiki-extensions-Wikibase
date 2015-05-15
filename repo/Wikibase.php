<?php
if ( function_exists( 'wfLoadExtension' ) ) {
	wfLoadExtension( 'Wikibase/repo' );
	// Keep i18n globals so mergeMessageFileList.php doesn't break
	$wgMessagesDirs['Wikibase'] = __DIR__ . '/i18n';
	$wgExtensionMessagesFiles['WikibaseAlias'] = __DIR__ . '/Wikibase.i18n.alias.php';
	$wgExtensionMessagesFiles['WikibaseNS'] = __DIR__ . '/Wikibase.i18n.namespaces.php';
	/* wfWarn(
		'Deprecated PHP entry point used for Wikibase repo extension. Please use wfLoadExtension instead, ' .
		'see https://www.mediawiki.org/wiki/Extension_registration for more details.'
	); */
	return;
} else {
	die( 'This version of the Wikibase repo extension requires MediaWiki 1.25+' );
}

