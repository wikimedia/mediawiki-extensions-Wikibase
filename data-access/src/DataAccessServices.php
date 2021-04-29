<?php

namespace Wikibase\DataAccess;

use Wikibase\DataModel\Services\Entity\EntityPrefetcher;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\PropertyInfoLookup;

/**
 * An interface of a factory of data retrieval/lookup services.
 *
 * @license GPL-2.0-or-later
 */
interface DataAccessServices {

	/**
	 * @return EntityPrefetcher
	 */
	public function getEntityPrefetcher();

	/**
	 * @note Instance returned is not guaranteed to be a caching decorator.
	 * Callers should take care of caching themselves.
	 *
	 * @return EntityRevisionLookup
	 */
	public function getEntityRevisionLookup();

	/**
	 * @note Instance returned is not guaranteed to be a caching decorator.
	 * Callers should take care of caching themselves.
	 *
	 * @return PropertyInfoLookup
	 */
	public function getPropertyInfoLookup();

}
