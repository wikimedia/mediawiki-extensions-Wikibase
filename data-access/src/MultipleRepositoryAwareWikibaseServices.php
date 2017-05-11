<?php


namespace Wikibase\DataAccess;

use MediaWiki\Services\ServiceContainer;
use Wikibase\DataAccess\Store\PropertyInfoLookup;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\DataModel\Services\Entity\EntityPrefetcher;
use Wikibase\DataModel\Services\Term\TermBuffer;
use Wikibase\EntityRevision;
use Wikibase\Lib\Interactors\TermSearchInteractorFactory;
use Wikibase\Lib\Store\EntityInfoBuilderFactory;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\EntityStoreWatcher;

/**
 * Top-level container/factory of data access services making use of the "dispatching" pattern of
 * services aware of multi-repository configuration that delegate their action
 * to service instance configured for a particular repository.
 *
 * @license GPL-2.0+
 */
class MultipleRepositoryAwareWikibaseServices extends ServiceContainer implements WikibaseServices, EntityStoreWatcher {

	/**
	 * @var EntityStoreWatcher
	 */
	private $entityStoreWatcher;

	public function __construct(
		DispatchingDataAccessServices $dispatchingServiceContainer,
		EntityStoreWatcher $entityStoreWatcher
	) {
		parent::__construct();

		$this->entityStoreWatcher = $dispatchingServiceContainer;

		$this->applyWiring( [
			'EntityInfoBuilderFactory' => function() use ( $dispatchingServiceContainer ) {
				return $dispatchingServiceContainer->getEntityInfoBuilderFactory();
			},
			'EntityPrefetcher' => function() use ( $dispatchingServiceContainer ) {
				return $dispatchingServiceContainer->getEntityPrefetcher();
			},
			'EntityRevisionLookup' => function() use ( $dispatchingServiceContainer ) {
				return $dispatchingServiceContainer->getEntityRevisionLookup();
			},
			'PropertyInfoLookup' => function() use ( $dispatchingServiceContainer ) {
				return $dispatchingServiceContainer->getPropertyInfoLookup();
			},
			'TermBuffer' => function() use ( $dispatchingServiceContainer ) {
				return $dispatchingServiceContainer->getTermBuffer();
			},
			'TermSearchInteractorFactory' => function() use ( $dispatchingServiceContainer ) {
				return $dispatchingServiceContainer->getTermSearchInteractorFactory();
			},
		] );
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

	/**
	 * @see EntityStoreWatcher::entityUpdated
	 *
	 * @param EntityRevision $entityRevision
	 */
	public function entityUpdated( EntityRevision $entityRevision ) {
		$this->entityStoreWatcher->entityUpdated( $entityRevision );
	}

	/**
	 * @see EntityStoreWatcher::entityDeleted
	 *
	 * @param EntityId $entityId
	 */
	public function entityDeleted( EntityId $entityId ) {
		$this->entityStoreWatcher->entityDeleted( $entityId );
	}

	/**
	 * @see EntityStoreWatcher::redirectUpdated
	 *
	 * @param EntityRedirect $entityRedirect
	 * @param int $revisionId
	 */
	public function redirectUpdated( EntityRedirect $entityRedirect, $revisionId ) {
		$this->entityStoreWatcher->redirectUpdated( $entityRedirect, $revisionId );
	}

}
