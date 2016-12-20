<?php

use Wikibase\Client\DispatchingServiceFactory;
use Wikibase\Lib\Store\DispatchingEntityRevisionLookup;
use Wikibase\Lib\Store\DispatchingPropertyInfoLookup;
use Wikibase\Lib\Store\DispatchingTermBuffer;
use Wikibase\Store\BufferingTermLookup;
use Wikibase\TermIndex;

/**
 * @license GPL-2.0+
 */

return [

	'EntityRevisionLookup' => function( DispatchingServiceFactory $dispatchingServiceFactory ) {
		return new DispatchingEntityRevisionLookup(
			$dispatchingServiceFactory->getServiceMap( 'EntityRevisionLookup' )
		);
	},

	'PropertyInfoLookup' => function( DispatchingServiceFactory $dispatchingServiceFactory ) {
		return new DispatchingPropertyInfoLookup(
			$dispatchingServiceFactory->getServiceMap( 'PropertyInfoLookup' )
		);
	},

	'TermBuffer' => function( DispatchingServiceFactory $dispatchingServiceFactory ) {
		$termIndexes = $dispatchingServiceFactory->getServiceMap( 'TermIndex' );

		$buffers = array_map(
			function( TermIndex $termIndex ) {
				return new BufferingTermLookup( $termIndex, 1000 ); // TODO: customize buffer sizes?
			},
			$termIndexes
		);

		return new DispatchingTermBuffer( $buffers );
	}

];
