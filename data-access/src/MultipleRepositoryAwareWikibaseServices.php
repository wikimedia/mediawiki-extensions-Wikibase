<?php


namespace Wikibase\DataAccess;

use MediaWiki\Services\ServiceContainer;
use Wikibase\DataModel\Services\Entity\EntityPrefetcher;
use Wikibase\DataModel\Services\Term\TermBuffer;
use Wikibase\Lib\Interactors\TermSearchInteractorFactory;
use Wikibase\Lib\Store\EntityInfoBuilderFactory;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\EntityStoreWatcher;
use Wikibase\Lib\Store\PropertyInfoLookup;

/**
 * Top-level container/factory of data access services making use of the "dispatching" pattern of
 * services aware of multi-repository configuration that delegate their action
 * to service instance configured for a particular repository.
 *
 * @license GPL-2.0+
 */
class MultipleRepositoryAwareWikibaseServices extends ServiceContainer implements WikibaseServices {

	public function __construct( DispatchingServiceFactory $dispatchingServiceContainer ) {
		parent::__construct();

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
			'EntityStoreWatcher' => function() use ( $dispatchingServiceContainer ) {
				return $dispatchingServiceContainer;
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
	 * @return EntityStoreWatcher
	 */
	public function getEntityStoreWatcher() {
		return $this->getService( 'EntityStoreWatcher' );
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
