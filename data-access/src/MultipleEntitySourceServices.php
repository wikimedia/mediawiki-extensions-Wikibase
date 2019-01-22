<?php

namespace Wikibase\DataAccess;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\Lib\Interactors\DispatchingTermSearchInteractorFactory;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Lib\Store\EntityStoreWatcher;

/**
 * @license GPL-2.0-or-later
 */
class MultipleEntitySourceServices implements EntityStoreWatcher {

	/**
	 * @var EntitySourceDefinitions
	 */
	private $entitySourceDefinitions;

	/**
	 * @var SingleEntitySourceServices[]
	 */
	private $singleSourceServices;

	private $entityRevisionLookup = null;

	private $entityInfoBuilder = null;

	private $termSearchInteractorFactory = null;

	private $prefetchingTermLookup = null;

	private $entityPrefetcher = null;

	public function __construct( EntitySourceDefinitions $entitySourceDefinitions, array $singleSourceServices ) {
		$this->entitySourceDefinitions = $entitySourceDefinitions;
		$this->singleSourceServices = $singleSourceServices;
	}

	public function getEntityRevisionLookup() {
		if ( $this->entityRevisionLookup === null ) {
			$lookupsPerType = [];

			/** @var EntitySource $source */
			foreach ( $this->entitySourceDefinitions->getEntityTypeToSourceMapping() as $entityType => $source ) {
				$lookupsPerType[$entityType] = $this->singleSourceServices[$source->getSourceName()]->getEntityRevisionLookup();
			}

			$this->entityRevisionLookup = new ByTypeDispatchingEntityRevisionLookup( $lookupsPerType );
		}

		return $this->entityRevisionLookup;
	}

	public function getEntityInfoBuilder() {
		if ( $this->entityInfoBuilder === null ) {
			$buildersPerType = [];

			/** @var EntitySource $source */
			foreach ( $this->entitySourceDefinitions->getEntityTypeToSourceMapping() as $entityType => $source ) {
				$buildersPerType[$entityType] = $this->singleSourceServices[$source->getSourceName()]->getEntityInfoBuilder();
			}

			$this->entityInfoBuilder = new ByTypeDispatchingEntityInfoBuilder( $buildersPerType );
		}

		return $this->entityInfoBuilder;
	}

	public function getTermSearchInteractorFactory() {
		if ( $this->termSearchInteractorFactory === null ) {
			$factoriesByType = [];

			/** @var EntitySource $source */
			foreach ( $this->entitySourceDefinitions->getEntityTypeToSourceMapping() as $entityType => $source ) {
				$factoriesByType[$entityType] = $this->singleSourceServices[$source->getSourceName()]->getTermSearchInteractorFactory();
			}

			$this->termSearchInteractorFactory = new DispatchingTermSearchInteractorFactory( $factoriesByType );
		}

		return $this->termSearchInteractorFactory;
	}

	public function getPrefetchingTermLookup() {
		if ( $this->prefetchingTermLookup === null ) {
			$lookupsByType = [];

			/** @var EntitySource $source */
			foreach ( $this->entitySourceDefinitions->getEntityTypeToSourceMapping() as $entityType => $source ) {
				$lookupsByType[$entityType] = $this->singleSourceServices[$source->getSourceName()]->getPrefetchingTermLookup();
			}

			$this->prefetchingTermLookup = new ByTypeDispatchingPrefetchingTermLookup( $lookupsByType );
		}

		return $this->prefetchingTermLookup;
	}

	public function getEntityPrefetcher() {
		if ( $this->entityPrefetcher === null ) {
			$prefetchersByType = [];

			/** @var EntitySource $source */
			foreach ( $this->entitySourceDefinitions->getEntityTypeToSourceMapping() as $entityType => $source ) {
				$prefetchersByType[$entityType] = $this->singleSourceServices[$source->getSourceName()]->getEntityPrefetcher();
			}

			$this->entityPrefetcher = new ByTypeDispatchingEntityPrefetcher( $prefetchersByType );
		}

		return $this->entityPrefetcher;
	}

	public function getEntityStoreWatcher() {
		return $this;
	}

	public function entityUpdated( EntityRevision $entityRevision ) {
		$source = $this->entitySourceDefinitions->getSourceForEntityType( $entityRevision->getEntity()->getType() );
		if ( $source !== null ) {
			$this->singleSourceServices[$source->getSourceName()]->entityUpdated( $entityRevision );
		}
	}

	public function redirectUpdated( EntityRedirect $entityRedirect, $revisionId ) {
		$source = $this->entitySourceDefinitions->getSourceForEntityType( $entityRedirect->getEntityId()->getEntityType() );
		if ( $source !== null ) {
			$this->singleSourceServices[$source->getSourceName()]->redirectUpdated( $entityRedirect, $revisionId );
		}
	}

	public function entityDeleted( EntityId $entityId ) {
		$source = $this->entitySourceDefinitions->getSourceForEntityType( $entityId->getEntityType() );
		if ( $source !== null ) {
			$this->singleSourceServices[$source->getSourceName()]->entityDeleted( $entityId );
		}
	}

}
