<?php

namespace Wikibase;

use InvalidArgumentException;

/**
 * Class that represents a single Wikibase claim.
 * See https://meta.wikimedia.org/wiki/Wikidata/Data_model#Statements
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
 * @since 0.4 (as 'ClaimObject' and interface 'Claim' since 0.1)
 *
 * @file
 * @ingroup WikibaseDataModel
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class Claim implements \Hashable, \Serializable {

	/**
	 * Rank enum. Higher values are more preferred.
	 *
	 * @since 0.1
	 */
	const RANK_TRUTH = 3;
	const RANK_PREFERRED = 2;
	const RANK_NORMAL = 1;
	const RANK_DEPRECATED = 0;

	/**
	 * @since 0.1
	 *
	 * @var Snak
	 */
	protected $mainSnak;

	/**
	 * The property snaks that are qualifiers for this claim.
	 *
	 * @since 0.1
	 *
	 * @var Snaks
	 */
	protected $qualifiers;

	/**
	 * @since 0.2
	 *
	 * @var string|null
	 */
	protected $guid = null;

	/**
	 * Constructor.
	 *
	 * @since 0.1
	 *
	 * @param Snak $mainSnak
	 * @param null|Snaks $qualifiers
	 */
	public function __construct( Snak $mainSnak, Snaks $qualifiers = null ) {
		$this->mainSnak = $mainSnak;
		$this->qualifiers = $qualifiers === null ? new SnakList() : $qualifiers;
	}

	/**
	 * Returns the value snak.
	 *
	 * @since 0.1
	 *
	 * @return Snak
	 */
	public function getMainSnak() {
		return $this->mainSnak;
	}

	/**
	 * Sets the main snak.
	 *
	 * @since 0.1
	 *
	 * @param Snak $mainSnak
	 */
	public function setMainSnak( Snak $mainSnak ) {
		$this->mainSnak = $mainSnak;
	}

	/**
	 * Gets the property snaks making up the qualifiers for this claim.
	 *
	 * @since 0.1
	 *
	 * @return Snaks
	 */
	public function getQualifiers() {
		return $this->qualifiers;
	}

	/**
	 * Sets the property snaks making up the qualifiers for this claim.
	 *
	 * @since 0.1
	 *
	 * @param Snaks $propertySnaks
	 */
	public function setQualifiers( Snaks $propertySnaks ) {
		$this->qualifiers = $propertySnaks;
	}

	/**
	 * @see Hashable::getHash
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function getHash() {
		return sha1(
			$this->mainSnak->getHash()
				. $this->qualifiers->getHash()
		);
	}

	/**
	 * Returns the id of the property of the main snak.
	 * Short for ->getMainSnak()->getPropertyId()
	 *
	 * @since 0.2
	 *
	 * @return EntityId
	 */
	public function getPropertyId() {
		return $this->getMainSnak()->getPropertyId();
	}

	/**
	 * Returns the GUID of the Claim.
	 *
	 * @since 0.2
	 *
	 * @return string|null
	 */
	public function getGuid() {
		return $this->guid;
	}

	/**
	 * Sets the GUID of the Claim.
	 *
	 * @since 0.2
	 *
	 * @param string|null $guid
	 *
	 * @throws InvalidArgumentException
	 */
	public function setGuid( $guid ) {
		if ( !is_string( $guid ) && $guid !== null ) {
			throw new InvalidArgumentException( 'Can only set the GUID to string values or null' );
		}

		$this->guid = $guid;
	}

	/**
	 * Returns an array representing the claim.
	 * Roundtrips with Claim::newFromArray
	 *
	 * This method can be used for serialization when passing the array to for
	 * instance json_encode which created behavior similar to
	 * @see Serializable::serialize but different in that it uses the
	 * type identifiers rather then class names.
	 *
	 * @since 0.3
	 *
	 * @return array
	 */
	public function toArray() {
		return array(
			'm' => $this->mainSnak->toArray(),
			'q' => $this->qualifiers->toArray(),
			'g' => $this->guid,
		);
	}

	/**
	 * Constructs a new Claim from an array in the same format as Claim::toArray returns.
	 *
	 * @since 0.3
	 *
	 * @param array $data
	 *
	 * @return Claim
	 */
	public static function newFromArray( array $data ) {
		if ( array_key_exists( 'rank', $data ) ) {
			return Statement::newFromArray( $data );
		}

		$mainSnak = SnakObject::newFromArray( $data['m'] );
		$qualifiers = SnakList::newFromArray( $data['q'] );

		$claim = new static( $mainSnak, $qualifiers );
		$claim->setGuid( $data['g'] );

		return $claim;
	}

	/**
	 * @see Serializable::serialize
	 *
	 * @since 0.3
	 *
	 * @return string
	 */
	public function serialize() {
		return json_encode( $this->toArray() );
	}

	/**
	 * @see Serializable::unserialize
	 *
	 * @since 0.3
	 *
	 * @param string $serialization
	 *
	 * @return Claim
	 */
	public function unserialize( $serialization ) {
		$instance = static::newFromArray( json_decode( $serialization, true ) );

		$this->setMainSnak( $instance->getMainSnak() );
		$this->setQualifiers( $instance->getQualifiers() );
		$this->setGuid( $instance->getGuid() );
	}

	/**
	 * Gets the rank of the claim.
	 * The rank is an element of the Claim::RANK_ enum.
	 *
	 * @since 0.1
	 *
	 * @return integer
	 */
	public function getRank() {
		return self::RANK_TRUTH;
	}

}

/**
 * @deprecated since 0.4. Use Claim instead.
 */
class ClaimObject extends Claim {}
