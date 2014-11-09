<?php

namespace Wikibase\DataModel\Claim;

use InvalidArgumentException;
use Traversable;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Snak\SnakList;
use Wikibase\DataModel\Snak\Snaks;

/**
 * Ordered, non-unique, mutable, collection of Claim objects.
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
			throw new InvalidArgumentException( '$claims must be an array; got ' . gettype( $claims ) );
		}

		$this->claims = $claims;
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
