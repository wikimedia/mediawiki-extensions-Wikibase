<?php

/**
 * Entry point for the Wikibase DataModel component.
 *
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */

if ( defined( 'WIKIBASE_DATAMODEL_VERSION' ) ) {
	return;
}

define( 'WIKIBASE_DATAMODEL_VERSION', '7.0.0' );

if ( defined( 'MEDIAWIKI' ) && function_exists( 'wfLoadExtension' ) ) {
	wfLoadExtension( 'WikibaseDataModel', __DIR__ . '/mediawiki-extension.json' );
}
