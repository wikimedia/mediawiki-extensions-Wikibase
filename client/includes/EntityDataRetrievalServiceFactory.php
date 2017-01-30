<?php

namespace Wikibase\Client;

use Wikibase\DataModel\Services\Term\TermBuffer;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\PropertyInfoLookup;

/**
 * An interface of a factory of data retrieval/lookup services.
 *
 * @license GPL-2.0+
 */
interface EntityDataRetrievalServiceFactory {

	/**
	 * Note: Instance returned is not guaranteed to be a caching decorator.
	 * Callers should take care of caching themselves.
	 *
	 * @return EntityRevisionLookup
	 */
	public function getEntityRevisionLookup();

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

}
