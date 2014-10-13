<?php

/**
 * MediaWiki setup for the Wikibase DataModel component.
 * The component should be included via the main entry point, WikibaseDataModel.php.
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */

if ( !defined( 'WIKIBASE_DATAMODEL_VERSION' ) ) {
	die( 'Not an entry point.' );
}

$GLOBALS['wgExtensionCredits']['wikibase'][] = array(
	'path' => __DIR__,
	'name' => 'Wikibase DataModel',
	'version' => WIKIBASE_DATAMODEL_VERSION,
	'author' => array(
		'[https://www.mediawiki.org/wiki/User:Jeroen_De_Dauw Jeroen De Dauw]',
	),
	'url' => 'https://github.com/wmde/WikibaseDataModel',
	'description' => 'The canonical PHP implementation of the Data Model at the heart of the Wikibase software.',
	'license-name' => 'GPL-2.0+'
);
