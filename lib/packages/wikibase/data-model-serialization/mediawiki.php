<?php

if ( defined( 'MEDIAWIKI' ) ) {
	$GLOBALS['wgExtensionCredits']['wikibase']['WikibaseDataModelSerialization'] = array(
		'path' => __DIR__,
		'name' => 'Wikibase DataModel Serialization',
		'version' => '1.4.0',
		'author' => array(
			'[https://www.mediawiki.org/wiki/User:Jeroen_De_Dauw Jeroen De Dauw]',
		),
		'url' => 'https://github.com/wmde/WikibaseDataModelSerialization',
		'description' => 'Serializers and deserializers for the Wikibase DataModel',
		'license-name' => 'GPL-2.0+'
	);
}
