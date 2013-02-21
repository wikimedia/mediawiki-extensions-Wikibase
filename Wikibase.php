<?php

/**
 * Loader for all extensions in the Wikibase git repository.
 *
 * THIS IS NOT the entry point you want to use in production.
 * It is mainly meant to facilitate development and testing.
 * For production setups, inclusion of the entry points of
 * the extensions you want to load according to their respective
 * installation instructions is recommended. See the INSTALL
 * and README file for more information.
 *
 * @file
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */

// @codeCoverageIgnoreStart
//call_user_func( function() {
	$components = array(
		'lib/WikibaseLib',
		'client/WikibaseClient',
		'repo/Wikibase',
	);

	foreach ( $components as $component ) {
		// Load extensions in non-global scope.
		require_once __DIR__ . '/' . $component . '.php';
	}

//} );
// @codeCoverageIgnoreEnd
