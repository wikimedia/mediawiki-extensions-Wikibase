<?php

/**
 * This file holds registration of experimental features part of the Wikibase Client extension.
 *
 * This file is NOT an entry point the Wikibase Client extension. Use WikibaseClient.php.
 * It should furthermore not be included from outside the extension.
 *
 * @since 0.4
 *
 * @licence GNU GPL v2+
 */

if ( !defined( 'WBC_VERSION' ) || !defined( 'WB_EXPERIMENTAL_FEATURES' ) ) {
	die( 'Not an entry point.' );
}

// Sporious code from a merge conflict, but should probably be left in due to line above
//$wgHooks['ParserFirstCallInit'][] = function( \Parser &$parser ) {
//	$parser->setFunctionHook( 'property', array( '\Wikibase\PropertyParserFunction', 'render' ) );
//
//	return true;
//};
