<?php

namespace Wikibase\DataAccess;

use MediaWiki\Services\ServiceContainer;
use Wikibase\DataModel\Services\Entity\EntityPrefetcher;
use Wikibase\DataModel\Services\Lookup\UnknownForeignRepositoryException;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\DataModel\Services\Term\TermBuffer;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Lib\Interactors\TermSearchInteractorFactory;
use Wikibase\Lib\RepositoryDefinitions;
use Wikibase\Lib\Store\EntityInfoBuilderFactory;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\EntityStoreWatcher;
use Wikibase\Lib\Store\PropertyInfoLookup;

/**
 * A factory/locator of services dispatching the action to services configured for the
 * particular input, based on the repository the particular input entity belongs to.
 * Dispatching services provide a way of using entities from multiple repositories.
 *
 * Services are defined by loading wiring arrays, or by using defineService method.
 *
 * @license GPL-2.0+
 */
class MultiRepositoryServices extends ServiceContainer implements DataAccessServices, EntityStoreWatcher {

	/**
	 * @var PerRepositoryServiceContainerFactory
	 */
	private $serviceContainerFactory;

	/**
	 * @var RepositoryDefinitions
	 */
	private $repositoryDefinitions;

	/**
	 * @var PerRepositoryServiceContainer[]
	 */
	private $repositoryServiceContainers = [];

	public function __construct(
		PerRepositoryServiceContainerFactory $serviceContainerFactory,
		RepositoryDefinitions $repositoryDefinitions
	) {
		parent::__construct();

		$this->serviceContainerFactory = $serviceContainerFactory;
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
	 * @return PerRepositoryServiceContainer|null
	 */
	private function getContainerForRepository( $repositoryName ) {
		if ( !array_key_exists( $repositoryName, $this->repositoryServiceContainers ) ) {
			try {
				$this->repositoryServiceContainers[$repositoryName] =
					$this->serviceContainerFactory->newContainer( $repositoryName );
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
	 * @return array[]
	 */
	public function getEntityTypeToRepoMapping() {
		return $this->repositoryDefinitions->getEntityTypeToRepositoryMapping();
	}

	/**
	 * @return EntityInfoBuilderFactory
	 */
	public function getEntityInfoBuilderFactory() {
		return $this->getService( 'EntityInfoBuilderFactory' );
	}

	/**
	 * @return EntityPrefetcher
	 */
	public function getEntityPrefetcher() {
		return $this->getService( 'EntityPrefetcher' );
	}

	/**
	 * @return EntityRevisionLookup
	 */
	public function getEntityRevisionLookup() {
		return $this->getService( 'EntityRevisionLookup' );
	}

	/**
	 * @return PropertyInfoLookup
	 */
	public function getPropertyInfoLookup() {
		return $this->getService( 'PropertyInfoLookup' );
	}

	/**
	 * @return TermBuffer
	 */
	public function getTermBuffer() {
		return $this->getService( 'TermBuffer' );
	}

	/**
	 * @return TermSearchInteractorFactory
	 */
	public function getTermSearchInteractorFactory() {
		return $this->getService( 'TermSearchInteractorFactory' );
	}

}
