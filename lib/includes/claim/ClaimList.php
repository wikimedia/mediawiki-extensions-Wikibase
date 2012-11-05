<?php

namespace Wikibase;

/**
 * Implementation of the Claims interface.
 * @see Claims
 *
 * Note that this implementation is based on SplObjectStorage and
 * is not enforcing the type of objects set via it's native methods.
 * Therefore one can add non-Reference-implementing objects when
 * not sticking to the methods of the Claims interface.
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
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ClaimList extends HashableObjectStorage implements Claims {

	/**
	 * @see References::addClaim
	 *
	 * @since 0.1
	 *
	 * @param Claim $claim
	 */
	public function addClaim( Claim $claim ) {
		$this->attach( $claim );
	}

	/**
	 * @see References::hasClaim
	 *
	 * @since 0.1
	 *
	 * @param Claim $claim
	 *
	 * @return boolean
	 */
	public function hasClaim( Claim $claim ) {
		return $this->contains( $claim );
	}

	/**
	 * @see References::removeClaim
	 *
	 * @since 0.1
	 *
	 * @param Claim $claim
	 */
	public function removeClaim( Claim $claim ) {
		$this->detach( $claim );
	}

}
