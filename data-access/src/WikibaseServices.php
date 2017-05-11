<?php

namespace Wikibase\DataAccess;

use Wikibase\DataModel\Services\Entity\EntityPrefetcher;
use Wikibase\DataModel\Services\Term\TermBuffer;
use Wikibase\Lib\Interactors\TermSearchInteractorFactory;
use Wikibase\Lib\Store\EntityInfoBuilderFactory;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\EntityStoreWatcher;
use Wikibase\Lib\Store\PropertyInfoLookup;

/**
 * Interface of the top-level container/factory of data access services.
 *
 * @license GPL-2.0+
 */
interface WikibaseServices {

	/**
	 * @return EntityInfoBuilderFactory
	 */
	public function getEntityInfoBuilderFactory();

	/**
	 * @return EntityPrefetcher
	 */
	public function getEntityPrefetcher();

	/**
	 * Note: Instance returned is not guaranteed to be a caching decorator.
	 * Callers should take care of caching themselves.
	 *
	 * @return EntityRevisionLookup
	 */
	public function getEntityRevisionLookup();

	/**
	 * @return EntityStoreWatcher
	 */
	public function getEntityStoreWatcher();

	/**
	 * Note: Instance returned is not guaranteed to be a caching decorator.
	 * Callers should take care of caching themselves.
	 *
	 * @return PropertyInfoLookup
	 */
	public function getPropertyInfoLookup();

	/**
	 * @return TermBuffer
	 */
	public function getTermBuffer();

	/**
	 * @return TermSearchInteractorFactory
	 */
	public function getTermSearchInteractorFactory();

}
