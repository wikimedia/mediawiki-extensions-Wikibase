<?php

use Wikibase\DataAccess\DispatchingServiceFactory;
use Wikibase\Lib\Interactors\DispatchingTermSearchInteractorFactory;
use Wikibase\Lib\Store\DispatchingEntityInfoBuilderFactory;
use Wikibase\Lib\Store\DispatchingEntityPrefetcher;
use Wikibase\Lib\Store\DispatchingEntityRevisionLookup;
use Wikibase\Lib\Store\DispatchingPropertyInfoLookup;
use Wikibase\Lib\Store\DispatchingTermBuffer;

/**
 * @license GPL-2.0+
 */

return [

	'EntityInfoBuilderFactory' => function( DispatchingServiceFactory $dispatchingServiceFactory ) {
		return new DispatchingEntityInfoBuilderFactory(
			$dispatchingServiceFactory->getServiceMap( 'EntityInfoBuilderFactory' )
		);
	},

	'EntityPrefetcher' => function( DispatchingServiceFactory $dispatchingServiceFactory ) {
		return new DispatchingEntityPrefetcher(
			$dispatchingServiceFactory->getServiceMap( 'EntityPrefetcher' )
		);
	},

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
		return new DispatchingTermBuffer(
			$dispatchingServiceFactory->getServiceMap( 'PrefetchingTermLookup' )
		);
	},

	'TermSearchInteractorFactory' => function( DispatchingServiceFactory $dispatchingServiceFactory ) {
		$repoSpecificFactories = $dispatchingServiceFactory->getServiceMap( 'TermSearchInteractorFactory' );
		$entityTypeToRepoMapping = $dispatchingServiceFactory->getEntityTypeToRepoMapping();

		$factories = [];
		foreach ( $entityTypeToRepoMapping as $entityType => $repositoryName ) {
			$factories[$entityType] = $repoSpecificFactories[$repositoryName];
		}

		return new DispatchingTermSearchInteractorFactory( $factories );
	},

];
