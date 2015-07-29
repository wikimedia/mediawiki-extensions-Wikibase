<?php

if ( defined( 'MEDIAWIKI' ) ) {
	$GLOBALS['wgExtensionCredits']['wikibase']['WikibaseInternalSerialization'] = array(
		'path' => __DIR__,
		'name' => 'Wikibase Internal Serialization',
		'version' => '1.5',
		'author' => array(
			'[https://www.mediawiki.org/wiki/User:Jeroen_De_Dauw Jeroen De Dauw]',
		),
		'url' => 'https://github.com/wmde/WikibaseInternalSerialization',
		'description' => 'Serializers and deserializers for the data access layer of Wikibase Repository',
		'license-name' => 'GPL-2.0+'
	);
}
