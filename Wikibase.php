<?php

/**
 * TESTING entry point. DO NOT USE FOR REAL SETUPS!
 *
 * This entry point is meant to facilitate development and testing.
 * THIS IS NOT the entry point you want to use in production.
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

define( 'WB_EXPERIMENTAL_FEATURES', true );

require_once __DIR__ . '/Query/ExampleSettings.php';

require_once __DIR__ . '/DataModel/DataModel.php';
require_once __DIR__ . '/lib/WikibaseLib.php';
require_once __DIR__ . '/repo/Wikibase.php';
require_once __DIR__ . '/Query/WikibaseQuery.php';
//require_once __DIR__ . '/client/WikibaseClient.php';

//require_once __DIR__ . '/client/ExampleSettings.php';
require_once __DIR__ . '/repo/ExampleSettings.php';




require_once __DIR__ . '/lib/maintenance/populateSitesTable.php';


$wgExtensionFunctions[] = function() {
	$evilStuff = new PopulateSitesTable();
	$evilStuff->execute();
};