<?php

/**
 * @deprecated since 3.0
 */
define( 'WIKIBASE_DATAMODEL_JAVASCRIPT_VERSION', '3.0.0' );

if ( defined( 'MEDIAWIKI' ) && function_exists( 'wfLoadExtension' ) ) {
	wfLoadExtension( 'WikibaseDataModelJavaScript', __DIR__ . '/mediawiki-extension.json' );

	include 'resources.php';
	include 'resources.test.php';
}
