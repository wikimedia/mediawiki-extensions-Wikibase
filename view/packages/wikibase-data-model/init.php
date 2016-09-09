<?php

/**
 * @deprecated since 3.0
 */
define( 'WIKIBASE_DATAMODEL_JAVASCRIPT_VERSION', '3.0.1' );

if ( defined( 'MEDIAWIKI' ) && function_exists( 'wfLoadExtension' ) ) {
	wfLoadExtension( 'WikibaseDataModelJavaScript', __DIR__ . '/mediawiki-extension.json' );

	include __DIR__ . '/resources.php';
	include __DIR__ . '/resources.test.php';
}
