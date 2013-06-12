<?php

namespace Wikibase;

use Diff\Differ;
use Diff\DiffOpAdd;
use Diff\DiffOpChange;
use Diff\DiffOpRemove;
use Diff\MapDiffer;
use InvalidArgumentException;

/**
 * Implementation of the Claims interface.
 * @see Claims
 *
 * A claim (identified using it's GUID) can only be added once.
 *
 * TODO: if it turns out we do not need efficient lookups by guid after all
 * we can switch back to extending the simpler HashableObjectStorage.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @since 0.1
 *
 * @file
 * @ingroup WikibaseDataModel
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 */
class Claims extends HashArray implements ClaimListAccess {

	/**
	 * Maps claim GUIDs to their offsets.
	 *
	 * @since 0.1
	 *
	 * @var array [ element hash (string) => element offset (string|int) ]
	 */
	protected $guidIndex = array();

	/**
	 * Constructor.
	 * @see GenericArrayObject::__construct
	 *
	 * @since 0.3
	 *
	 * @param null|array $input
	 */
	public function __construct( $input = null ) {
		$this->acceptDuplicates = true;
		parent::__construct( $input );
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
	 * @see ClaimListAccess::addClaim
	 *
	 * @since 0.1
	 *
	 * @param Claim $claim
	 */
	public function addClaim( Claim $claim ) {
		$this->append( $claim );
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
		return $this->hasElement( $claim );
	}

	/**
	 * @see ClaimListAccess::removeClaim
	 *
	 * @since 0.1
	 *
	 * @param Claim $claim
	 */
	public function removeClaim( Claim $claim ) {
		$this->removeElement( $claim );
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
		return array_key_exists( $claimGuid, $this->guidIndex );
	}

	/**
	 * @see ClaimListAccess::removeClaimWithGuid
	 *
	 * @since 0.3
	 *
	 * @param string $claimGuid
	 */
	public function removeClaimWithGuid( $claimGuid ) {
		$this->offsetUnset( $this->guidIndex[$claimGuid] );
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
		if ( $this->hasClaimWithGuid( $claimGuid ) ) {
			return $this->offsetGet( $this->guidIndex[$claimGuid] );
		}
		else {
			return null;
		}
	}

	/**
	 * Get array of Claim guids
	 *
	 * @since 0.4
	 *
	 * @return string[]
	 */
	public function getGuids() {
		return array_keys( $this->guidIndex );
	}

	/**
	 * Returns the claims for the given property.
	 *
	 * @since 0.4
	 *
	 * @param int $propertyId
	 *
	 * @throws InvalidArgumentException
	 * @return Claims
	 */
	public function getClaimsForProperty( $propertyId ) {
		if ( !is_int( $propertyId ) ) {
			throw new InvalidArgumentException( '$propertyId must be an integer' );
		}

		$claimsByProp = new ByPropertyIdArray( $this );
		$claimsByProp->buildIndex();

		if ( !( in_array( $propertyId, $claimsByProp->getPropertyIds() ) ) ) {
			return new Claims();
		}

		$claimsForProperty = new Claims( $claimsByProp->getByPropertyId( $propertyId ) );
		return $claimsForProperty;
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
			$snaks[] = $claim->getMainSnak();
		}

		return $snaks;
	}

	/**
	 * @see GenericArrayObject::preSetElement
	 *
	 * @since 0.3
	 *
	 * @param int|string $index
	 * @param Claim $claim
	 *
	 * @return boolean
	 */
	protected function preSetElement( $index, $claim ) {
		$shouldSet = parent::preSetElement( $index, $claim );

		if ( $shouldSet ) {
			$this->guidIndex[$claim->getGuid()] = $index;
		}

		return $shouldSet;
	}

	/**
	 * @see ArrayObject::offsetUnset()
	 *
	 * @since 0.3
	 *
	 * @param mixed $index
	 */
	public function offsetUnset( $index ) {
		if ( $this->offsetExists( $index ) ) {
			/**
			 * @var Claim $claim
			 */
			$claim = $this->offsetGet( $index );

			unset( $this->guidIndex[$claim->getGuid()] );
			parent::offsetUnset( $index );
		}
	}

	/**
	 * @since 0.4
	 *
	 * @param Claims $claims
	 * @param Differ|null $differ
	 *
	 * @return \Diff\Diff
	 * @throws InvalidArgumentException
	 */
	public function getDiff( Claims $claims, Differ $differ = null ) {
		assert( $this->indicesAreUpToDate() );
		assert( $claims->indicesAreUpToDate() );

		if ( $differ === null ) {
			$differ = new MapDiffer();
		}

		$sourceHashes = array();
		$targetHashes = array();

		/**
		 * @var Claim $claim
		 */
		foreach ( $this as $claim ) {
			$sourceHashes[$claim->getGuid()] = $claim->getHash();
		}

		foreach ( $claims as $claim ) {
			$targetHashes[$claim->getGuid()] = $claim->getHash();
		}

		$diff = new \Diff\Diff( array(), true );

		foreach ( $differ->doDiff( $sourceHashes, $targetHashes ) as $diffOp ) {
			if ( $diffOp instanceof DiffOpChange ) {
				$oldClaim = $this->getByElementHash( $diffOp->getOldValue() );
				$newClaim = $claims->getByElementHash( $diffOp->getNewValue() );

				assert( $oldClaim instanceof Claim );
				assert( $newClaim instanceof Claim );
				assert( $oldClaim->getGuid() === $newClaim->getGuid() );

				$diff[$oldClaim->getGuid()] = new DiffOpChange( $oldClaim, $newClaim );
			}
			elseif ( $diffOp instanceof DiffOpAdd ) {
				$claim = $claims->getByElementHash( $diffOp->getNewValue() );
				assert( $claim instanceof Claim );
				$diff[$claim->getGuid()] = new DiffOpAdd( $claim );
			}
			elseif ( $diffOp instanceof DiffOpRemove ) {
				$claim = $this->getByElementHash( $diffOp->getOldValue() );
				assert( $claim instanceof Claim );
				$diff[$claim->getGuid()] = new DiffOpRemove( $claim );
			}
			else {
				throw new InvalidArgumentException( 'Invalid DiffOp type cannot be handled' );
			}
		}

		return $diff;
	}

}
