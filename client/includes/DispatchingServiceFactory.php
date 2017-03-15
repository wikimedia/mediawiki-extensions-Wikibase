<?php

namespace Wikibase\Client;

use MediaWiki\Services\ServiceContainer;
use Wikibase\Client\Store\RepositoryServiceContainer;
use Wikibase\Client\Store\RepositoryServiceContainerFactory;
use Wikibase\DataModel\Services\Entity\EntityPrefetcher;
use Wikibase\DataModel\Services\Lookup\UnknownForeignRepositoryException;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\DataModel\Services\Term\TermBuffer;
use Wikibase\EntityRevision;
use Wikibase\Lib\Interactors\TermSearchInteractorFactory;
use Wikibase\Lib\RepositoryDefinitions;
use Wikibase\Lib\Store\EntityInfoBuilderFactory;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\EntityStoreWatcher;
use Wikibase\Lib\Store\PropertyInfoLookup;
use Wikibase\Lib\Interactors\DispatchingTermSearchInteractorFactory;
use Wikibase\Lib\Store\DispatchingEntityInfoBuilderFactory;
use Wikibase\Lib\Store\DispatchingEntityPrefetcher;
use Wikibase\Lib\Store\DispatchingEntityRevisionLookup;
use Wikibase\Lib\Store\DispatchingPropertyInfoLookup;
use Wikibase\Lib\Store\DispatchingTermBuffer;

/**
 * A factory/locator of services dispatching the action to services configured for the
 * particular input, based on the repository the particular input entity belongs to.
 * Dispatching services provide a way of using entities from multiple repositories.
 *
 * Services are defined by loading wiring arrays, or by using defineService method.
 *
 * @license GPL-2.0+
 */
class DispatchingServiceFactory implements EntityDataRetrievalServiceFactory, EntityStoreWatcher {

	/**
	 * @var RepositoryServiceContainerFactory
	 */
	private $repositoryServiceContainerFactory;

	/**
	 * @var RepositoryDefinitions
	 */
	private $repositoryDefinitions;

	/**
	 * @var RepositoryServiceContainer[]
	 */
	private $repositoryServiceContainers = [];

	/**
	 * @var ServiceContainer
	 */
	private $container;

	/**
	 * @param RepositoryServiceContainerFactory $repositoryServiceContainerFactory
	 * @param RepositoryDefinitions $repositoryDefinitions
	 */
	public function __construct(
		RepositoryServiceContainerFactory $repositoryServiceContainerFactory,
		RepositoryDefinitions $repositoryDefinitions
	) {
		$this->container = new ServiceContainer();
		$this->container->applyWiring( $this->getWiring() );

		$this->repositoryServiceContainerFactory = $repositoryServiceContainerFactory;
		$this->repositoryDefinitions = $repositoryDefinitions;
	}

	/**
	 * @param string $service
	 * @return array An associative array mapping repository names to service instances configured for the repository
	 */
	public function getServiceMap( $service ) {
		$serviceMap = [];
		foreach ( $this->repositoryDefinitions->getRepositoryNames() as $repositoryName ) {
			$container = $this->getContainerForRepository( $repositoryName );
			if ( $container !== null ) {
				$serviceMap[$repositoryName] = $container->getService( $service );
			}
		}
		return $serviceMap;
	}

	/**
	 * @param string $repositoryName
	 *
	 * @return RepositoryServiceContainer|null
	 */
	private function getContainerForRepository( $repositoryName ) {
		if ( !array_key_exists( $repositoryName, $this->repositoryServiceContainers ) ) {
			try {
				$this->repositoryServiceContainers[$repositoryName] =
					$this->repositoryServiceContainerFactory->newContainer( $repositoryName );
			} catch ( UnknownForeignRepositoryException $exception ) {
				$this->repositoryServiceContainers[$repositoryName] = null;
			}
		}

		return $this->repositoryServiceContainers[$repositoryName];
	}

	/**
	 * @see EntityStoreWatcher::entityUpdated
	 *
	 * @param EntityRevision $entityRevision
	 */
	public function entityUpdated( EntityRevision $entityRevision ) {
		$container = $this->getContainerForRepository(
			$entityRevision->getEntity()->getId()->getRepositoryName()
		);

		if ( $container !== null ) {
			$container->entityUpdated( $entityRevision );
		}
	}

	/**
	 * @see EntityStoreWatcher::entityDeleted
	 *
	 * @param EntityId $entityId
	 */
	public function entityDeleted( EntityId $entityId ) {
		$container = $this->getContainerForRepository( $entityId->getRepositoryName() );

		if ( $container !== null ) {
			$container->entityDeleted( $entityId );
		}
	}

	/**
	 * @see EntityStoreWatcher::redirectUpdated
	 *
	 * @param EntityRedirect $entityRedirect
	 * @param int $revisionId
	 */
	public function redirectUpdated( EntityRedirect $entityRedirect, $revisionId ) {
		$container = $this->getContainerForRepository(
			$entityRedirect->getEntityId()->getRepositoryName()
		);

		if ( $container !== null ) {
			$container->redirectUpdated( $entityRedirect, $revisionId );
		}
	}

	/**
	 * @return string[]
	 */
	public function getEntityTypeToRepoMapping() {
		return $this->repositoryDefinitions->getEntityTypeToRepositoryMapping();
	}

	/**
	 * @return EntityInfoBuilderFactory
	 */
	public function getEntityInfoBuilderFactory() {
		return $this->container->getService( 'EntityInfoBuilderFactory' );
	}

	/**
	 * @return EntityPrefetcher
	 */
	public function getEntityPrefetcher() {
		return $this->container->getService( 'EntityPrefetcher' );
	}

	/**
	 * @return EntityRevisionLookup
	 */
	public function getEntityRevisionLookup() {
		return $this->container->getService( 'EntityRevisionLookup' );
	}

	/**
	 * @return PropertyInfoLookup
	 */
	public function getPropertyInfoLookup() {
		return $this->container->getService( 'PropertyInfoLookup' );
	}

	/**
	 * @return TermBuffer
	 */
	public function getTermBuffer() {
		return $this->container->getService( 'TermBuffer' );
	}

	/**
	 * @return TermSearchInteractorFactory
	 */
	public function getTermSearchInteractorFactory() {
		return $this->container->getService( 'TermSearchInteractorFactory' );
	}

	/**
	 * @return array
	 */
	private function getWiring() {
		return [
			'EntityInfoBuilderFactory' => function () {
				return new DispatchingEntityInfoBuilderFactory(
					$this->getServiceMap( 'EntityInfoBuilderFactory' )
				);
			},

			'EntityPrefetcher' => function () {
				return new DispatchingEntityPrefetcher(
					$this->getServiceMap( 'EntityPrefetcher' )
				);
			},

			'EntityRevisionLookup' => function () {
				return new DispatchingEntityRevisionLookup(
					$this->getServiceMap( 'EntityRevisionLookup' )
				);
			},

			'PropertyInfoLookup' => function () {
				return new DispatchingPropertyInfoLookup(
					$this->getServiceMap( 'PropertyInfoLookup' )
				);
			},

			'TermBuffer' => function () {
				return new DispatchingTermBuffer(
					$this->getServiceMap( 'PrefetchingTermLookup' )
				);
			},

			'TermSearchInteractorFactory' => function () {
				$repoSpecificFactories = $this->getServiceMap( 'TermSearchInteractorFactory' );
				$entityTypeToRepoMapping = $this->getEntityTypeToRepoMapping();

				$factories = [];
				foreach ( $entityTypeToRepoMapping as $entityType => $repositoryName ) {
					$factories[$entityType] = $repoSpecificFactories[$repositoryName];
				}

				return new DispatchingTermSearchInteractorFactory( $factories );
			},

		];
	}

}
