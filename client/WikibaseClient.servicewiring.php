<?php

/**
 * Defines location of wiring files defining entity data retrieval services.
 *
 * This will be merged into wgWBClientServiceWiring.
 *
 * TODO: move out of client subdir
 * TODO: global should not be hinting about Client once this becomes not only client-related
 */

return [
	'repositoryServiceWiringFiles' => [ __DIR__ . '/includes/Store/RepositoryServiceWiring.php' ],
	'dispatchingServiceWiringFiles' => [ __DIR__ . '/includes/DispatchingServiceWiring.php' ],
];
