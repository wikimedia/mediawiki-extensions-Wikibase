<?php

namespace Wikibase\DataModel\Claim;

use InvalidArgumentException;
use Traversable;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Snak\SnakList;
use Wikibase\DataModel\Snak\Snaks;

/**
 * Ordered, non-unique, collection of Claim objects.
 * Provides various filter operations though does not do any indexing by default.
 *
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
	 * @param Claim[]|Traversable $claims
	 * @throws InvalidArgumentException
	 */
	public function __construct( $claims = array() ) {
		if ( $claims instanceof Traversable ) {
			$claims = iterator_to_array( $claims );
		}

		if ( !is_array( $claims ) ) {
			throw new InvalidArgumentException( '$claims should be an array' );
		}

		$this->claims = $claims;
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
	 * Returns the property ids used by the claims.
	 * The keys of the returned array hold the serializations of the property ids.
	 *
	 * @return PropertyId[]
	 */
	public function getPropertyIds() {
		$propertyIds = array();

		foreach ( $this->claims as $claim ) {
			$propertyIds[$claim->getPropertyId()->getSerialization()] = $claim->getPropertyId();
		}

		return $propertyIds;
	}

	private function addClaims( Claims $claims ) {
		foreach ( $claims as $claim ) {
			$this->addClaim( $claim );
		}
	}

	public function addClaim( Claim $claim ) {
		$this->claims[] = $claim;
	}

	/**
	 * @param Snak $mainSnak
	 * @param Snak[]|Snaks|null $qualifiers
	 * @param string|null $guid
	 */
	public function addNewClaim( Snak $mainSnak, $qualifiers = null, $guid = null ) {
		$qualifiers = is_array( $qualifiers ) ? new SnakList( $qualifiers ) : $qualifiers;

		$claim = new Claim( $mainSnak, $qualifiers );
		$claim->setGuid( $guid );

		$this->addClaim( $claim );
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