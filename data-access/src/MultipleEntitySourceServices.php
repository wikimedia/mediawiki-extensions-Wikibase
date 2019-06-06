<?php

namespace Wikibase\DataAccess;

use MediaWiki\Services\ServiceContainer;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\DataModel\Entity\Property;
use Wikibase\Lib\Interactors\DispatchingTermSearchInteractorFactory;
use Wikibase\Lib\Store\EntityRevision;
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
class MultipleEntitySourceServices extends ServiceContainer implements WikibaseServices, EntityStoreWatcher {

	/**
	 * @var EntitySourceDefinitions
	 */
	private $entitySourceDefinitions;

	private $genericServices;

	/**
	 * @var SingleEntitySourceServices[] indexed by source name
	 */
	private $singleSourceServices;

	private $entityRevisionLookup = null;

	private $entityInfoBuilder = null;

	private $termSearchInteractorFactory = null;

	private $prefetchingTermLookup = null;

	private $entityPrefetcher = null;

	/**
	 * @param EntitySourceDefinitions $entitySourceDefinitions
	 * @param GenericServices $genericServices
	 * @param SingleEntitySourceServices[] $singleSourceServices indexed by source name
	 */
	public function __construct(
		EntitySourceDefinitions $entitySourceDefinitions,
		GenericServices $genericServices,
		array $singleSourceServices
	) {
		Assert::parameterElementType( SingleEntitySourceServices::class, $singleSourceServices, '$singleSourceServices' );
		$this->entitySourceDefinitions = $entitySourceDefinitions;
		$this->genericServices = $genericServices;
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

	public function getPropertyInfoLookup() {
		$source = $this->entitySourceDefinitions->getSourceForEntityType( Property::ENTITY_TYPE );
		if ( $source === null ) {
			throw new \LogicException( 'No entity source provides properties!' );
		}
		return $this->singleSourceServices[$source->getSourceName()]->getPropertyInfoLookup();
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

	public function getEntityNamespaceLookup() {
		// TODO: entity namespace lookup is actually source-specific service, should not be provided by
		// GenericServices
		return $this->genericServices->getEntityNamespaceLookup();
	}

	public function getFullEntitySerializer() {
		return $this->genericServices->getFullEntitySerializer();
	}

	public function getCompactEntitySerializer() {
		return $this->genericServices->getCompactEntitySerializer();
	}

	public function getStorageEntitySerializer() {
		return $this->genericServices->getStorageEntitySerializer();
	}

	public function getBaseDataModelSerializerFactory() {
		return $this->genericServices->getBaseDataModelSerializerFactory();
	}

	public function getCompactBaseDataModelSerializerFactory() {
		return $this->genericServices->getCompactBaseDataModelSerializerFactory();
	}

	public function getLanguageFallbackChainFactory() {
		return $this->genericServices->getLanguageFallbackChainFactory();
	}

	public function getStringNormalizer() {
		return $this->genericServices->getStringNormalizer();
	}

	public function getTermBuffer() {
		return $this->getPrefetchingTermLookup();
	}

}
