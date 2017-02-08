<?php

namespace Wikibase\Repo;

use MediaWiki\Services\ServiceContainer;
use ObjectCache;
use Wikibase\Client\EntityDataRetrievalServiceFactory;
use Wikibase\Client\Store\RepositoryServiceContainer;
use Wikibase\Client\Store\RepositoryServiceContainerFactory;
use Wikibase\DataModel\Services\Entity\EntityPrefetcher;
use Wikibase\DataModel\Services\Lookup\UnknownForeignRepositoryException;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\DataModel\Services\Term\TermBuffer;
use Wikibase\EntityRevision;
use Wikibase\Lib\EntityIdComposer;
use Wikibase\Lib\Store\CachingPropertyInfoLookup;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\EntityStoreWatcher;
use Wikibase\Lib\Store\PrefetchingTermLookup;
use Wikibase\Lib\Store\PropertyInfoLookup;
use Wikibase\Lib\Store\Sql\PropertyInfoTable;
use Wikibase\Store;
use Wikibase\Store\BufferingTermLookup;

/**
 * A factory/locator of services dispatching the action to services configured for the
 * particular input, based on the repository the particular input entity belongs to.
 * Dispatching services provide a way of using entities from multiple repositories.
 *
 * Services are defined by loading wiring arrays, or by using defineService method.
 *
 * @license GPL-2.0+
 */
class StandaloneDataRetrievalServiceFactory
	implements EntityDataRetrievalServiceFactory, EntityStoreWatcher
{

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @var PrefetchingTermLookup
	 */
	private $termLookup;

	/**
	 * @var PropertyInfoLookup
	 */
	private $propertyInfoLookup;

	/**
	 * @var string
	 */
	private $cacheKeyPrefix;

	/**
	 * @var int
	 */
	private $cacheType;

	/**
	 * @var int
	 */
	private $cacheDuration;

	/**
	 * @var EntityIdComposer
	 */
	private $entityIdComposer;

	/**
	 * @var PropertyInfoTable
	 */
	private $propertyInfoTable;

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
	 * @see Store::getPropertyInfoLookup
	 *
	 * @return PropertyInfoLookup
	 */
	public function getPropertyInfoLookup() {
		if ( !$this->propertyInfoLookup ) {
			$this->propertyInfoLookup = $this->newPropertyInfoLookup();
		}

		return $this->propertyInfoLookup;
	}

	/**
	 * Creates a new PropertyInfoLookup instance
	 * Note: cache key used by the lookup should be the same as the cache key used
	 * by CachedPropertyInfoStore.
	 *
	 * @return PropertyInfoLookup
	 */
	private function newPropertyInfoLookup() {
		$table = $this->getPropertyInfoTable();

		$cacheKey = $this->cacheKeyPrefix . ':CacheAwarePropertyInfoStore';

		return new CachingPropertyInfoLookup(
			$table,
			ObjectCache::getInstance( $this->cacheType ),
			$this->cacheDuration,
			$cacheKey
		);
	}

	/**
	 * @return PropertyInfoTable
	 */
	private function getPropertyInfoTable() {
		if ( $this->propertyInfoTable === null ) {
			$this->propertyInfoTable = new PropertyInfoTable( $this->entityIdComposer );
		}
		return $this->propertyInfoTable;
	}

	/**
	 * @return TermBuffer
	 */
	public function getTermBuffer() {
		return $this->getPrefetchingTermLookup();
	}

	/**
	 * @return PrefetchingTermLookup
	 */
	private function getPrefetchingTermLookup() {
		if ( !$this->termLookup ) {
			$this->termLookup = $this->newPrefetchingTermLookup();
		}

		return $this->termLookup;
	}

	/**
	 * @return PrefetchingTermLookup
	 */
	private function newPrefetchingTermLookup() {
		// FIXME: move getTrrmIndex into inEntityDataRetrievalServiceFactory
		return new BufferingTermLookup(
			$this->store->getTermIndex(),
			1000 // @todo: configure buffer size
		);
	}

}
