<?php

namespace Wikibase;
use MWException;

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
 * @since 0.1
 *
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ClaimObject implements Claim {

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
	 * @see Claim::getMainSnak
	 *
	 * @since 0.1
	 *
	 * @return Snak
	 */
	public function getMainSnak() {
		return $this->mainSnak;
	}

	/**
	 * @see Claim::setMainSnak
	 *
	 * @since 0.1
	 *
	 * @param Snak $mainSnak
	 */
	public function setMainSnak( Snak $mainSnak ) {
		$this->mainSnak = $mainSnak;
	}

	/**
	 * @see Claim::getQualifiers
	 *
	 * @since 0.1
	 *
	 * @return Snaks
	 */
	public function getQualifiers() {
		return $this->qualifiers;
	}

	/**
	 * @see Claim::setQualifiers
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
	 * @see Claim::getPropertyId
	 *
	 * @since 0.2
	 *
	 * @return integer
	 */
	public function getPropertyId() {
		return $this->getMainSnak()->getPropertyId();
	}

	/**
	 * @see Claim::getGuid
	 *
	 * @since 0.2
	 *
	 * @return string
	 */
	public function getGuid() {
		if ( $this->guid === null ) {
			$this->guid = Utils::getGuid();
		}

		return $this->guid;
	}

	/**
	 * @see Claim::setGuid
	 *
	 * @since 0.2
	 *
	 * @param string $guid
	 *
	 * @throws MWException
	 */
	public function setGuid( $guid ) {
		if ( !is_string( $guid ) ) {
			throw new MWException( 'Can only set the GUID to string values' );
		}

		$this->guid = $guid;
	}

	/**
	 * @see Claim::toArray
	 *
	 * @since 0.3
	 *
	 * @return array
	 */
	public function toArray() {
		return array(
			'm' => $this->mainSnak->toArray(),
			'q' => $this->qualifiers->toArray(),
			'g' => $this->getGuid(),
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
		if ( !is_array( $data ) ) {
			$data = json_decode( $data );
			$data = (array)$data;
		}

		if ( array_key_exists( 'rank', $data ) ) {
			return StatementObject::newFromArray( $data );
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

}
