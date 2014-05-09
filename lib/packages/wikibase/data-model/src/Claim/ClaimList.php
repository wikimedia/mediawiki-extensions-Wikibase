<?php

namespace Wikibase\DataModel\Claim;

use Traversable;
use Wikibase\DataModel\Entity\PropertyId;

/**
 * @since 1.0
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ClaimList implements \IteratorAggregate {

	/**
	 * @var Claim[]
	 */
	private $claims;

	/**
	 * @param Claim[] $claims
	 */
	public function __construct( array $claims = array() ) {
		$this->claims = array_values( $claims );
	}

	/**
	 * Returns the best claims.
	 * The best claims are those with the highest rank for a particular property.
	 * Deprecated ranks are never included.
	 *
	 * Caution: the ranking is done per property, not globally, as in the Claims class.
	 *
	 * @return self
	 */
	public function getBestClaims() {
		$claimList = new self();

		foreach ( $this->getPropertyIds() as $propertyId ) {
			$claims = new Claims( $this->claims );
			$claimList->addClaims( $claims->getClaimsForProperty( $propertyId )->getBestClaims() );
		}

		return $claimList;
	}

	/**
	 * @return PropertyId[]
	 */
	public function getPropertyIds() {
		$propertyIds = array();

		foreach ( $this->claims as $claim ) {
			$propertyIds[$claim->getPropertyId()->getSerialization()] = $claim->getPropertyId();
		}

		return array_values( $propertyIds );
	}

	private function addClaims( Claims $claims ) {
		foreach ( $claims as $claim ) {
			$this->addClaim( $claim );
		}
	}

	private function addClaim( Claim $claim ) {
		$this->claims[] = $claim;
	}

	/**
	 * Claims that have a main snak already in the list are filtered out.
	 * The last occurrences are retained.
	 *
	 * @return self
	 */
	public function getWithUniqueMainSnaks() {
		$claims = array();

		foreach ( $this->claims as $claim ) {
			$claims[$claim->getMainSnak()->getHash()] = $claim;
		}

		return new self( $claims );
	}

	/**
	 * @return Traversable
	 */
	public function getIterator() {
		return new \ArrayIterator( $this->claims );
	}

	/**
	 * @return Claim[]
	 */
	public function toArray() {
		return $this->claims;
	}

}