<?php

use Wikibase\DataAccess\MultiRepositoryServices;
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

	'EntityInfoBuilderFactory' => function( MultiRepositoryServices $multiRepositoryServices ) {
		return new DispatchingEntityInfoBuilderFactory(
			$multiRepositoryServices->getServiceMap( 'EntityInfoBuilderFactory' )
		);
	},

	'EntityPrefetcher' => function( MultiRepositoryServices $multiRepositoryServices ) {
		return new DispatchingEntityPrefetcher(
			$multiRepositoryServices->getServiceMap( 'EntityPrefetcher' )
		);
	},

	'EntityRevisionLookup' => function( MultiRepositoryServices $multiRepositoryServices ) {
		return new DispatchingEntityRevisionLookup(
			$multiRepositoryServices->getServiceMap( 'EntityRevisionLookup' )
		);
	},

	'PropertyInfoLookup' => function( MultiRepositoryServices $multiRepositoryServices ) {
		return new DispatchingPropertyInfoLookup(
			$multiRepositoryServices->getServiceMap( 'PropertyInfoLookup' )
		);
	},

	'TermBuffer' => function( MultiRepositoryServices $multiRepositoryServices ) {
		return new DispatchingTermBuffer(
			$multiRepositoryServices->getServiceMap( 'PrefetchingTermLookup' )
		);
	},

	'TermSearchInteractorFactory' => function( MultiRepositoryServices $multiRepositoryServices ) {
		$repoSpecificFactories = $multiRepositoryServices->getServiceMap( 'TermSearchInteractorFactory' );
		$entityTypeToRepoMapping = $multiRepositoryServices->getEntityTypeToRepoMapping();

		$factories = [];
		foreach ( $entityTypeToRepoMapping as $entityType => $repositoryName ) {
			$factories[$entityType] = $repoSpecificFactories[$repositoryName];
		}

		return new DispatchingTermSearchInteractorFactory( $factories );
	},

];
