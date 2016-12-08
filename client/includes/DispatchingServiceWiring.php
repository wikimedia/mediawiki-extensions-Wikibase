<?php

use Wikibase\Client\DispatchingServiceFactory;
use Wikibase\Lib\Store\DispatchingEntityRevisionLookup;

/**
 * @license GPL-2.0+
 */

return [

	'EntityRevisionLookup' => function ( DispatchingServiceFactory $dispatchingServiceFactory ) {
		return new DispatchingEntityRevisionLookup(
			$dispatchingServiceFactory->getServiceMap( 'EntityRevisionLookup' )
		);
	},

];
