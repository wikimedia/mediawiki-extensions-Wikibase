<?php

$GLOBALS['wgExtensionCredits']['wikibase'][] = array(
	'path' => __FILE__,
	'name' => 'Wikibase DataModel JavaScript',
	'version' => WIKIBASE_DATAMODEL_JAVASCRIPT_VERSION,
	'author' => array(
		'[https://www.mediawiki.org/wiki/User:Danwe Daniel Werner]',
		'[http://www.snater.com H. Snater]',
	),
	'url' => 'https://github.com/wmde/WikibaseDataModelJavascript',
	'description' => 'Javascript implementation of the Wikibase data model'
);

include 'resources.mw.php';
include 'resources.test.mw.php';
