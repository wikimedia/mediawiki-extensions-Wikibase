<?php

namespace Wikibase;

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
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
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

}
