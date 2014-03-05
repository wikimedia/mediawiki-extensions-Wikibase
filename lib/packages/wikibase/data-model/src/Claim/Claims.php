<?php

namespace Wikibase\DataModel\Claim;

use ArrayObject;
use Diff\Diff;
use Diff\Differ;
use Diff\DiffOpAdd;
use Diff\DiffOpChange;
use Diff\DiffOpRemove;
use Diff\MapDiffer;
use Hashable;
use InvalidArgumentException;
use Wikibase\DataModel\ByPropertyIdArray;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\Snak;

/**
 * Implementation of the Claims interface.
 * @see Claims
 *
 * A claim (identified using it's GUID) can only be added once.
 *
 * @since 0.1
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 */
class Claims extends ArrayObject implements ClaimListAccess, Hashable {

	/**
	 * @see GenericArrayObject::__construct
	 *
	 * @since 0.3
	 *
	 * @param null|array $input
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( $input = null ) {
		parent::__construct( array() );

		if ( $input !== null ) {
			if ( !is_array( $input) && !( $input instanceof \Traversable ) ) {
				throw new InvalidArgumentException( '$input must be traversable' );
			}

			foreach ( $input as $claim ) {
				$this->append( $claim );
			}
		}
	}

	/**
	 * @see GenericArrayObject::getObjectType
	 *
	 * @since 0.3
	 *
	 * @return string
	 */
	public function getObjectType() {
		return '\Wikibase\Claim';
	}

	/**
	 * @since 0.5
	 *
	 * @param string $guid
	 *
	 * @throws InvalidArgumentException
	 * @return string
	 */
	protected function getGuidKey( $guid ) {
		if ( !is_string( $guid ) ) {
			throw new InvalidArgumentException( 'Expected a GUID string' );
		}

		$key = strtoupper( $guid );
		return $key;
	}

	/**
	 * @param Claim $claim
	 *
	 * @since 0.5
	 *
	 * @param Claim $claim
	 *
	 * @throws InvalidArgumentException
	 * @return string
	 */
	protected function getClaimKey( Claim $claim ) {
		$guid = $claim->getGuid();

		if ( $guid === null ) {
			throw new InvalidArgumentException( 'Can\'t handle claims with no GUID set!' );
		}

		$key = $this->getGuidKey( $guid );
		return $key;
	}

	/**
	 * @see ClaimListAccess::addClaim
	 *
	 * @since 0.1
	 *
	 * @param Claim $claim
	 * @param int|null $index
	 *
	 * @throws InvalidArgumentException
	 */
	public function addClaim( Claim $claim, $index = null ) {
		if( !is_null( $index ) && !is_integer( $index ) ) {
			throw new InvalidArgumentException( 'Index needs to be null or an integer value' );
		} else if ( is_null( $index ) || $index >= count( $this ) ) {
			$this->append( $claim );
		} else {
			$this->insertClaimAtIndex( $claim, $index );
		}
	}

	/**
	 * @since 0.5
	 *
	 * @param Claim $claim
	 * @param int $index
	 */
	protected function insertClaimAtIndex( Claim $claim, $index ) {
		$claimsToShift = array();
		$i = 0;

		// Determine the claims to shift and remove them from the array:
		foreach( $this as $object ) {
			if( $i++ >= $index ) {
				$claimsToShift[] = $object;
			}
		}

		foreach( $claimsToShift as $object ) {
			$this->offsetUnset( $this->getClaimKey( $object ) );
		}

		// Append the new claim and re-append the previously removed claims:
		$this->append( $claim );

		foreach( $claimsToShift as $object ) {
			$this->append( $object );
		}
	}

	/**
	 * @see ArrayAccess::append
	 *
	 * @since 0.5
	 *
	 * @param Claim $claim
	 *
	 * @throws InvalidArgumentException
	 */
	public function append( $claim ) {
		if ( !( $claim instanceof Claim ) ) {
			throw new InvalidArgumentException( '$claim must be a Claim instances' );
		}

		parent::append( $claim );
	}

	/**
	 * @see ClaimListAccess::hasClaim
	 *
	 * @since 0.1
	 *
	 * @param Claim $claim
	 *
	 * @return boolean
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
	 * @see ClaimListAccess::indexOf
	 *
	 * @since 0.5
	 *
	 * @param Claim $claim
	 *
	 * @return int|boolean
	 */
	public function indexOf( Claim $claim ) {
		$guid = $claim->getGuid();
		$index = 0;

		/**
		 * @var Claim $claimObject
		 */
		foreach( $this as $claimObject ) {
			if( $claimObject->getGuid() === $guid ) {
				return $index;
			}
			$index++;
		}

		return false;
	}

	/**
	 * @see ClaimListAccess::removeClaim
	 *
	 * @since 0.1
	 *
	 * @param Claim $claim
	 */
	public function removeClaim( Claim $claim ) {
		$guid = $claim->getGuid();

		if ( $guid === null ) {
			return;
		}

		$key = $this->getGuidKey( $guid );

		if ( $this->offsetExists( $key ) ) {
			$this->offsetUnset( $key );
		}
	}

	/**
	 * @see ClaimListAccess::hasClaimWithGuid
	 *
	 * @since 0.3
	 *
	 * @param string $claimGuid
	 *
	 * @return boolean
	 */
	public function hasClaimWithGuid( $claimGuid ) {
		return $this->offsetExists( $claimGuid );
	}

	/**
	 * @see ClaimListAccess::removeClaimWithGuid
	 *
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
	 * @see ClaimListAccess::getClaimWithGuid
	 *
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
		if ( !is_object( $claim ) || !( $claim instanceof Claim ) ) {
			throw new InvalidArgumentException( 'Expected a Claim instance' );
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

	/**
	 * Get array of Claim guids
	 *
	 * @since 0.4
	 *
	 * @return string[]
	 */
	public function getGuids() {
		return array_map( function ( Claim $claim ) {
			return $claim->getGuid();
		}, iterator_to_array( $this ) );
	}

	/**
	 * Returns the claims for the given property.
	 *
	 * @since 0.4
	 *
	 * @param PropertyId $propertyId
	 *
	 * @return Claims
	 */
	public function getClaimsForProperty( PropertyId $propertyId ) {
		$claimsByProp = new ByPropertyIdArray( $this );
		$claimsByProp->buildIndex();

		if ( !( in_array( $propertyId, $claimsByProp->getPropertyIds() ) ) ) {
			return new Claims();
		}

		return new Claims( $claimsByProp->getByPropertyId( $propertyId ) );
	}

	/**
	 * Returns the main Snaks of the claims in this list.
	 *
	 * @since 0.4
	 *
	 * @return Snak[]
	 */
	public function getMainSnaks() {
		$snaks = array();

		/* @var Claim $claim */
		foreach ( $this as $claim ) {
			$guid = $claim->getGuid();
			$snaks[$guid] = $claim->getMainSnak();
		}

		return $snaks;
	}

	/**
	 * Returns a map from GUIDs to claim hashes.
	 *
	 * @since 0.4
	 *
	 * @return string[]
	 */
	public function getHashes() {
		$snaks = array();

		/* @var Claim $claim */
		foreach ( $this as $claim ) {
			$guid = $claim->getGuid();
			$snaks[$guid] = $claim->getHash();
		}

		return $snaks;
	}

	/**
	 * @since 0.4
	 *
	 * @param Claims $claims
	 * @param Differ|null $differ for building a diff of two GUID-to-hash maps.
	 *
	 * @return Diff
	 * @throws InvalidArgumentException
	 */
	public function getDiff( Claims $claims, Differ $differ = null ) {
		if ( $differ === null ) {
			$differ = new MapDiffer();
		}

		$sourceHashes = $this->getHashes();
		$targetHashes = $claims->getHashes();

		$diff = new Diff( array(), true );

		foreach ( $differ->doDiff( $sourceHashes, $targetHashes ) as $guid => $diffOp ) {
			if ( $diffOp instanceof DiffOpChange ) {
				$oldClaim = $this->getClaimWithGuid( $guid );
				$newClaim = $claims->getClaimWithGuid( $guid );

				assert( $oldClaim instanceof Claim );
				assert( $newClaim instanceof Claim );
				assert( $oldClaim->getGuid() === $newClaim->getGuid() );

				$diff[$guid] = new DiffOpChange( $oldClaim, $newClaim );
			}
			elseif ( $diffOp instanceof DiffOpAdd ) {
				$claim = $claims->getClaimWithGuid( $guid );
				assert( $claim instanceof Claim );
				$diff[$guid] = new DiffOpAdd( $claim );
			}
			elseif ( $diffOp instanceof DiffOpRemove ) {
				$claim = $this->getClaimWithGuid( $guid );
				assert( $claim instanceof Claim );
				$diff[$guid] = new DiffOpRemove( $claim );
			}
			else {
				throw new InvalidArgumentException( 'Invalid DiffOp type cannot be handled' );
			}
		}

		return $diff;
	}

	/**
	 * Returns a hash based on the value of the object.
	 * The hash is based on the hashes of the claims contained,
	 * with the order of claims considered significant.
	 *
	 * @since 0.5
	 *
	 * @return string
	 */
	public function getHash() {
		$hash = sha1( '' );

		/* @var Claim $claim */
		foreach ( $this as $claim ) {
			$hash = sha1( $hash . $claim->getHash() );
		}

		return $hash;
	}

	/**
	 * Returns true if this list contains no claims
	 */
	public function isEmpty() {
		$iter = $this->getIterator();
		return !$iter->valid();
	}

}
