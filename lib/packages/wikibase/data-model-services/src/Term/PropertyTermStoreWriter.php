<?php

namespace Wikibase\DataModel\Services\Term;

use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Term\Fingerprint;

/**
 * @license GPL-2.0-or-later
 */
interface PropertyTermStoreWriter {

	/**
	 * Updates the stored terms for the specified property.
	 * @throws TermStoreException
	 */
	public function storeTerms( PropertyId $propertyId, Fingerprint $terms );

	/**
	 * @throws TermStoreException
	 */
	public function deleteTerms( PropertyId $propertyId );

}
