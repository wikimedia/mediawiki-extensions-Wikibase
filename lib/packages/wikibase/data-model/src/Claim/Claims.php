<?php

namespace Wikibase\DataModel\Claim;

use ArrayObject;
use Comparable;
use Hashable;
use InvalidArgumentException;
use Traversable;
use Wikibase\DataModel\ByPropertyIdGrouper;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Statement\Statement;

/**
 * A claim (identified using it's GUID) can only be added once.
 *
 * @deprecated since 1.0, use StatementList and associated classes instead.
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 * @author H. Snater < mediawiki@snater.com >
 */
class Claims extends ArrayObject {

	/**
	 * @see GenericArrayObject::__construct
	 *
	 * @since 0.3
	 *
	 * @param Claim[]|Traversable|null $input
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( $input = null ) {
		parent::__construct( array() );

		if ( $input !== null ) {
			if ( !is_array( $input ) && !( $input instanceof Traversable ) ) {
				throw new InvalidArgumentException( '$input must be an array or an instance of Traversable' );
			}

			foreach ( $input as $claim ) {
				$this[] = $claim;
			}
		}
	}

	/**
	 * @param string $guid
	 *
	 * @throws InvalidArgumentException
	 * @return string
	 */
	private function getGuidKey( $guid ) {
		if ( !is_string( $guid ) ) {
			throw new InvalidArgumentException( '$guid must be a string; got ' . gettype( $guid ) );
		}

		$key = strtoupper( $guid );
		return $key;
	}

	/**
	 * @param Claim $claim
	 *
	 * @throws InvalidArgumentException
	 * @return string
	 */
	private function getClaimKey( Claim $claim ) {
		$guid = $claim->getGuid();

		if ( $guid === null ) {
			throw new InvalidArgumentException( 'Can\'t handle claims with no GUID set!' );
		}

		$key = $this->getGuidKey( $guid );
		return $key;
	}

	/**
	 * @since 0.1
	 *
	 * @param Claim $claim
	 * @param int|null $index
	 *
	 * @throws InvalidArgumentException
	 */
	public function addClaim( Claim $claim, $index = null ) {
		if ( !is_null( $index ) && !is_integer( $index ) ) {
			throw new InvalidArgumentException( '$index must be an integer or null; got ' . gettype( $index ) );
		} elseif ( is_null( $index ) || $index >= count( $this ) ) {
			$this[] = $claim;
		} else {
			$this->insertClaimAtIndex( $claim, $index );
		}
	}

	/**
	 * @param Claim $claim
	 * @param int $index
	 */
	private function insertClaimAtIndex( Claim $claim, $index ) {
		// Determine the claims to shift and remove them from the array:
		$claimsToShift = array_slice( (array)$this, $index );

		foreach ( $claimsToShift as $object ) {
			$this->offsetUnset( $this->getClaimKey( $object ) );
		}

		// Append the new claim and re-append the previously removed claims:
		$this[] = $claim;

		foreach ( $claimsToShift as $object ) {
			$this[] = $object;
		}
	}

	/**
	 * @since 0.1
	 *
	 * @param Claim $claim
	 *
	 * @return bool
	 */
	public function hasClaim( Claim $claim ) {
		$guid = $claim->getGuid();

		if ( $guid === null ) {
			return false;
		}

		$key = $this->getGuidKey( $guid );
		return $this->offsetExists( $key );
	}

	/**
	 * @since 0.3
	 *
	 * @param string $claimGuid
	 *
	 * @return bool
	 */
	public function hasClaimWithGuid( $claimGuid ) {
		return $this->offsetExists( $claimGuid );
	}

	/**
	 * @since 0.3
	 *
	 * @param string $claimGuid
	 */
	public function removeClaimWithGuid( $claimGuid ) {
		if ( $this->offsetExists( $claimGuid ) ) {
			$this->offsetUnset( $claimGuid );
		}
	}

	/**
	 * @since 0.3
	 *
	 * @param string $claimGuid
	 *
	 * @return Claim|null
	 */
	public function getClaimWithGuid( $claimGuid ) {
		if ( $this->offsetExists( $claimGuid ) ) {
			return $this->offsetGet( $claimGuid );
		} else {
			return null;
		}
	}

	/**
	 * @see ArrayAccess::offsetExists
	 *
	 * @param string $guid
	 *
	 * @return bool
	 *
	 * @throws InvalidArgumentException
	 */
	public function offsetExists( $guid ) {
		$key = $this->getGuidKey( $guid );
		return parent::offsetExists( $key );
	}

	/**
	 * @see ArrayAccess::offsetGet
	 *
	 * @param string $guid
	 *
	 * @return Claim
	 *
	 * @throws InvalidArgumentException
	 */
	public function offsetGet( $guid ) {
		$key = $this->getGuidKey( $guid );
		return parent::offsetGet( $key );
	}

	/**
	 * @see ArrayAccess::offsetSet
	 *
	 * @param string $guid
	 * @param Claim $claim
	 *
	 * @throws InvalidArgumentException
	 */
	public function offsetSet( $guid, $claim ) {
		if ( !( $claim instanceof Claim ) ) {
			throw new InvalidArgumentException( '$claim must be an instance of Claim' );
		}

		$claimKey = $this->getClaimKey( $claim );

		if ( $guid !== null ) {
			$guidKey = $this->getGuidKey( $guid );

			if ( $guidKey !== $claimKey ) {
				throw new InvalidArgumentException( 'The key must be the claim\'s GUID.' );
			}
		}

		parent::offsetSet( $claimKey, $claim );
	}

	/**
	 * @see ArrayAccess::offsetUnset
	 *
	 * @param string $guid
	 */
	public function offsetUnset( $guid ) {
		$key = $this->getGuidKey( $guid );
		parent::offsetUnset( $key );
	}

}
