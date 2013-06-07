<?php

/**
 * PHPUnit test bootstrap file for the Wikibase QueryEngine component.
 *
 * @since 0.1
 *
 * @file
 * @ingroup QueryEngine
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */

if ( !in_array( '--testsuite=QueryEngineIntegration', $GLOBALS['argv'] ) ) {
	require_once( __DIR__ . '/evilMediaWikiBootstrap.php' );
}

require_once( __DIR__ . '/../../../DataValues/DataValues/DataValues.php' );

require_once( __DIR__ . '/../../../Ask/Ask.php' );

require_once( __DIR__ . '/../../DataModel/DataModel.php' );

require_once( __DIR__ . '/../../Database/Database.php' );

require_once( __DIR__ . '/../QueryEngine.php' );

require_once( __DIR__ . '/testLoader.php' );

// If something needs to change here, a reflecting change needs to be added to ../dependencies.txt.