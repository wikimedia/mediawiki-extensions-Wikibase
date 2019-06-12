<?php

use MediaWiki\Logger\LoggerFactory;
use Wikibase\DataAccess\MultiRepositoryServices;
use Wikibase\Lib\Interactors\ByTypeDispatchingTermSearchInteractorFactory;
use Wikibase\Lib\Store\ByRepositoryDispatchingEntityInfoBuilder;
use Wikibase\Lib\Store\ByRepositoryDispatchingEntityPrefetcher;
use Wikibase\Lib\Store\ByRepositoryDispatchingEntityRevisionLookup;
use Wikibase\Lib\Store\ByRepositoryDispatchingPropertyInfoLookup;
use Wikibase\Lib\Store\ByRepositoryDispatchingTermBuffer;
use Wikimedia\Assert\Assert;

/**
 * @license GPL-2.0-or-later
 */

return [

	'EntityInfoBuilder' => function( MultiRepositoryServices $multiRepositoryServices ) {
		return new ByRepositoryDispatchingEntityInfoBuilder(
			$multiRepositoryServices->getServiceMap( 'EntityInfoBuilder' )
		);
	},

	'EntityPrefetcher' => function( MultiRepositoryServices $multiRepositoryServices ) {
		return new ByRepositoryDispatchingEntityPrefetcher(
			$multiRepositoryServices->getServiceMap( 'EntityPrefetcher' )
		);
	},

	'EntityRevisionLookup' => function( MultiRepositoryServices $multiRepositoryServices ) {
		return new ByRepositoryDispatchingEntityRevisionLookup(
			$multiRepositoryServices->getServiceMap( 'EntityRevisionLookup' )
		);
	},

	'PropertyInfoLookup' => function( MultiRepositoryServices $multiRepositoryServices ) {
		return new ByRepositoryDispatchingPropertyInfoLookup(
			$multiRepositoryServices->getServiceMap( 'PropertyInfoLookup' )
		);
	},

	'TermBuffer' => function( MultiRepositoryServices $multiRepositoryServices ) {
		return new ByRepositoryDispatchingTermBuffer(
			$multiRepositoryServices->getServiceMap( 'PrefetchingTermLookup' ),
			LoggerFactory::getInstance( 'Wikibase' )
		);
	},

	'TermSearchInteractorFactory' => function( MultiRepositoryServices $multiRepositoryServices ) {
		$repoSpecificFactories = $multiRepositoryServices->getServiceMap( 'TermSearchInteractorFactory' );
		$entityTypeToRepoMapping = $multiRepositoryServices->getEntityTypeToRepoMapping();

		$factories = [];
		foreach ( $entityTypeToRepoMapping as $entityType => $repositoryNameAndNamespace ) {
			Assert::precondition(
				count( $repositoryNameAndNamespace ) === 1,
				'Expected entities of type: "' . $entityType . '" to only be provided by single repository.'
			);
			list( $repository, ) = $repositoryNameAndNamespace[0];
			$factories[$entityType] = $repoSpecificFactories[$repository];
		}

		return new ByTypeDispatchingTermSearchInteractorFactory( $factories );
	},

];
