<?php

/**
 * Entry point for the Wikibase DataModel component.
 *
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */

define( 'WIKIBASE_DATAMODEL_VERSION', '6.3.0' );

if ( defined( 'MEDIAWIKI' ) && function_exists( 'wfLoadExtension' ) ) {
	wfLoadExtension( 'WikibaseDataModel', __DIR__ . '/mediawiki-extension.json' );
}
