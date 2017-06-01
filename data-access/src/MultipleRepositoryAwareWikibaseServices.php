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

	public function __construct( MultiRepositoryServices $multiRepositoryServices ) {
		parent::__construct();

		$this->applyWiring( [
			'EntityInfoBuilderFactory' => function() use ( $multiRepositoryServices ) {
				return $multiRepositoryServices->getEntityInfoBuilderFactory();
			},
			'EntityPrefetcher' => function() use ( $multiRepositoryServices ) {
				return $multiRepositoryServices->getEntityPrefetcher();
			},
			'EntityRevisionLookup' => function() use ( $multiRepositoryServices ) {
				return $multiRepositoryServices->getEntityRevisionLookup();
			},
			'EntityStoreWatcher' => function() use ( $multiRepositoryServices ) {
				return $multiRepositoryServices;
			},
			'PropertyInfoLookup' => function() use ( $multiRepositoryServices ) {
				return $multiRepositoryServices->getPropertyInfoLookup();
			},
			'TermBuffer' => function() use ( $multiRepositoryServices ) {
				return $multiRepositoryServices->getTermBuffer();
			},
			'TermSearchInteractorFactory' => function() use ( $multiRepositoryServices ) {
				return $multiRepositoryServices->getTermSearchInteractorFactory();
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
