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
	 * @return EntityRevisionLookup
	 */
	public function getEntityRevisionLookup();

	/**
	 * @return PropertyInfoLookup
	 */
	public function getPropertyInfoLookup();

	/**
	 * @return TermBuffer
	 */
	public function getTermBuffer();

}
