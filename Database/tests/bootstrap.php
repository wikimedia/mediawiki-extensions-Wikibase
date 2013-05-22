<?php

/**
 * PHPUnit test bootstrap file for the Wikibase Database component.
 *
 * @since 0.1
 *
 * @file
 * @ingroup WikibaseDatabase
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */

$IP = getenv( 'MW_INSTALL_PATH' );

if ( $IP === false ) {
	$IP = dirname( __FILE__ ) . '/../../../..';
}

require_once( $IP . '/maintenance/Maintenance.php' );

class WhyYouNoHasDecentLoadingMechanism extends Maintenance {
	public function execute() {}
}

$maintClass = 'WhyYouNoHasDecentLoadingMechanism';
require_once( DO_MAINTENANCE );

require_once( $IP . '/includes/AutoLoader.php' );


require_once( __DIR__ . '/../Database.php' );

require_once( __DIR__ . '/testLoader.php' );
