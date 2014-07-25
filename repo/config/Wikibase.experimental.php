<?php

/**
 * This file holds registration of experimental features part of the Wikibase Repo extension.
 *
 * This file is NOT an entry point the Wikibase extension. Use Wikibase.php.
 * It should furthermore not be included from outside the extension.
 *
 * @since 0.4
 * @licence GNU GPL v2+
 */

if ( !defined( 'WB_VERSION' ) || !defined( 'WB_EXPERIMENTAL_FEATURES' ) ) {
	die( 'Not an entry point.' );
}

$wgAPIModules['wbcreateredirect'] = 'Wikibase\Api\CreateRedirectModule';
