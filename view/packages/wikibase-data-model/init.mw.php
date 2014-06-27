<?php

$GLOBALS['wgExtensionCredits']['wikibase'][] = array(
	'path' => __FILE__,
	'name' => 'Wikibase DataModel JavaScript',
	'version' => '0.3 alpha',
	'author' => array(
		'[https://www.mediawiki.org/wiki/User:Danwe Daniel Werner]',
		'[http://www.snater.com H. Snater]',
		'Adrian Lang',
		'[https://www.mediawiki.org/wiki/User:Jeroen_De_Dauw Jeroen De Dauw]',
	),
	'url' => 'https://github.com/wmde/WikibaseDataModelJavascript',
	'description' => 'Javascript implementation of the Wikibase data model'
);

include 'resources.mw.php';
include 'resources.test.mw.php';
