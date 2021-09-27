<?php

namespace Wikibase\DataModel\Services\Term;

use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Term\Fingerprint;

/**
 * @license GPL-2.0-or-later
 */
interface PropertyTermStoreWriter {

	/**
	 * Updates the stored terms for the specified property.
	 * @throws TermStoreException
	 */
	public function storeTerms( NumericPropertyId $propertyId, Fingerprint $terms );

	/**
	 * @throws TermStoreException
	 */
	public function deleteTerms( NumericPropertyId $propertyId );

}
