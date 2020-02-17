<?php

namespace Wikibase\Lib\Store;

use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\Term\PropertyTermStoreWriter;
use Wikibase\DataModel\Term\Fingerprint;

/**
 * An adapter to turn a {@link TermIndex} into a {@link PropertyTermStoreWriter}.
 *
 * @license GPL-2.0-or-later
 */
class TermIndexPropertyTermStoreWriter implements PropertyTermStoreWriter {

	/** @var TermIndex */
	private $termIndex;

	public function __construct( TermIndex $termIndex ) {
		$this->termIndex = $termIndex;
	}

	public function storeTerms( PropertyId $propertyId, Fingerprint $terms ) {
		$property = new Property( $propertyId, $terms, 'fake data type' );
		$this->termIndex->saveTermsOfEntity( $property );
	}

	public function deleteTerms( PropertyId $propertyId ) {
		$this->termIndex->deleteTermsOfEntity( $propertyId );
	}

}
