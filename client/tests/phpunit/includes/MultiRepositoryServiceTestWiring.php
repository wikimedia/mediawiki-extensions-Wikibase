<?php

use Wikibase\DataAccess\MultiRepositoryServices;

/**
 * @license GPL-2.0+
 */

return [
	// Use a custom service instead of standard "dispatching" lookup (for test purpose only)
	'AwesomeService' => function( MultiRepositoryServices $multiRepositoryServices ) {
		$servicesPerRepo = $multiRepositoryServices->getServiceMap( 'AwesomeService' );
		// Pretend it is dispatching all traffic to the local repo's service instance
		return $servicesPerRepo[''];
	}
];
