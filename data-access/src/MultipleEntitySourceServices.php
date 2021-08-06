<?php

namespace Wikibase\DataAccess;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Services\Entity\EntityPrefetcher;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\EntityStoreWatcher;
use Wikimedia\Assert\Assert;

/**
 * TODO this has been introduced into data-access with a couple of points that still bind to
 * wikibase lib:
 *   - Wikibase\Lib\Store\EntityRevision; (could already be moved to data-access)
 *   - Wikibase\Lib\Store\EntityStoreWatcher; (only binds to EntityRevision within lib)
 *
 * @license GPL-2.0-or-later
 */
class MultipleEntitySourceServices implements WikibaseServices, EntityStoreWatcher {

	/**
	 * @var EntitySourceDefinitions
	 */
	private $entitySourceDefinitions;

	/**
	 * @var SingleEntitySourceServices[] indexed by source name
	 */
	private $singleSourceServices;

	/** @var EntityRevisionLookup|null */
	private $entityRevisionLookup = null;

	/** @var EntityPrefetcher|null */
	private $entityPrefetcher = null;

	/**
	 * @param EntitySourceDefinitions $entitySourceDefinitions
	 * @param SingleEntitySourceServices[] $singleSourceServices indexed by source name
	 */
	public function __construct(
		EntitySourceDefinitions $entitySourceDefinitions,
		array $singleSourceServices
	) {
		Assert::parameterElementType( SingleEntitySourceServices::class, $singleSourceServices, '$singleSourceServices' );
		$this->entitySourceDefinitions = $entitySourceDefinitions;
		$this->singleSourceServices = $singleSourceServices;
	}

	public function getEntityRevisionLookup() {
		if ( $this->entityRevisionLookup === null ) {
			$lookupsPerType = [];

			/** @var EntitySource $source */
			foreach ( $this->entitySourceDefinitions->getEntityTypeToDatabaseSourceMapping() as $entityType => $source ) {
				$lookupsPerType[$entityType] = $this->singleSourceServices[$source->getSourceName()]->getEntityRevisionLookup();
			}

			$this->entityRevisionLookup = new ByTypeDispatchingEntityRevisionLookup( $lookupsPerType );
		}

		return $this->entityRevisionLookup;
	}

	public function getEntityPrefetcher() {
		if ( $this->entityPrefetcher === null ) {
			$prefetchersByType = [];

			/** @var EntitySource $source */
			foreach ( $this->entitySourceDefinitions->getEntityTypeToDatabaseSourceMapping() as $entityType => $source ) {
				$prefetchersByType[$entityType] = $this->singleSourceServices[$source->getSourceName()]->getEntityPrefetcher();
			}

			$this->entityPrefetcher = new ByTypeDispatchingEntityPrefetcher( $prefetchersByType );
		}

		return $this->entityPrefetcher;
	}

	public function getPropertyInfoLookup() {
		$source = $this->entitySourceDefinitions->getDatabaseSourceForEntityType( Property::ENTITY_TYPE );
		if ( $source === null ) {
			throw new \LogicException( 'No entity source provides properties!' );
		}
		return $this->singleSourceServices[$source->getSourceName()]->getPropertyInfoLookup();
	}

	public function getEntityStoreWatcher() {
		return $this;
	}

	public function entityUpdated( EntityRevision $entityRevision ) {
		$source = $this->entitySourceDefinitions->getDatabaseSourceForEntityType( $entityRevision->getEntity()->getType() );
		if ( $source !== null ) {
			$this->singleSourceServices[$source->getSourceName()]->entityUpdated( $entityRevision );
		}
	}

	public function redirectUpdated( EntityRedirect $entityRedirect, $revisionId ) {
		$source = $this->entitySourceDefinitions->getDatabaseSourceForEntityType( $entityRedirect->getEntityId()->getEntityType() );
		if ( $source !== null ) {
			$this->singleSourceServices[$source->getSourceName()]->redirectUpdated( $entityRedirect, $revisionId );
		}
	}

	public function entityDeleted( EntityId $entityId ) {
		$source = $this->entitySourceDefinitions->getDatabaseSourceForEntityType( $entityId->getEntityType() );
		if ( $source !== null ) {
			$this->singleSourceServices[$source->getSourceName()]->entityDeleted( $entityId );
		}
	}

}
