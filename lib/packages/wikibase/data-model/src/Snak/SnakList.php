<?php

namespace Wikibase\DataModel\Snak;

use Wikibase\DataModel\HashArray;
use Wikibase\DataModel\Internal\MapValueHasher;

/**
 * List of Snak objects.
 * Indexes the snaks by hash and ensures no more the one snak with the same hash are in the list.
 *
 * @since 0.1
 *
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Addshore
 */
class SnakList extends HashArray {

	/**
	 * @see GenericArrayObject::getObjectType
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function getObjectType() {
		return 'Wikibase\DataModel\Snak\Snak';
	}

	/**
	 * @since 0.1
	 *
	 * @param string $snakHash
	 *
	 * @return boolean
	 */
	public function hasSnakHash( $snakHash ) {
		return $this->hasElementHash( $snakHash );
	}

	/**
	 * @since 0.1
	 *
	 * @param string $snakHash
	 */
	public function removeSnakHash( $snakHash ) {
		$this->removeByElementHash( $snakHash );
	}

	/**
	 * @since 0.1
	 *
	 * @param Snak $snak
	 *
	 * @return boolean Indicates if the snak was added or not.
	 */
	public function addSnak( Snak $snak ) {
		return $this->addElement( $snak );
	}

	/**
	 * @since 0.1
	 *
	 * @param Snak $snak
	 *
	 * @return boolean
	 */
	public function hasSnak( Snak $snak ) {
		return $this->hasElementHash( $snak->getHash() );
	}

	/**
	 * @since 0.1
	 *
	 * @param Snak $snak
	 */
	public function removeSnak( Snak $snak ) {
		$this->removeByElementHash( $snak->getHash() );
	}

	/**
	 * @since 0.1
	 *
	 * @param string $snakHash
	 *
	 * @return Snak|bool
	 */
	public function getSnak( $snakHash ) {
		return $this->getByElementHash( $snakHash );
	}

	/**
	 * @see HashArray::getHash
	 *
	 * @since 0.5
	 *
	 * @return string
	 */
	public function getHash() {
		$hasher = new MapValueHasher();
		return $hasher->hash( $this );
	}

	/**
	 * Orders the snaks in the list grouping them by property.
	 *
	 * @param string[] $order List of serliazed property ids to order by.
	 *
	 * @since 0.5
	 */
	public function orderByProperty( array $order = array() ) {
		$snaksByProperty = $this->getSnaksByProperty();
		$orderedProperties = array_unique( array_merge( $order, array_keys( $snaksByProperty ) ) );

		foreach ( $orderedProperties as $property ) {
			if ( array_key_exists( $property, $snaksByProperty ) ) {
				$snaks = $snaksByProperty[$property];
				$this->moveSnaksToBottom( $snaks );
			}
		}
	}

	/**
	 * @param Snak[] $snaks to remove and re add
	 */
	private function moveSnaksToBottom( array $snaks ) {
		foreach ( $snaks as $snak ) {
			$this->removeSnak( $snak );
			$this->addSnak( $snak );
		}
	}

	/**
	 * Gets the snaks in the current object in an array
	 * grouped by property id
	 *
	 * @return array[]
	 */
	private function getSnaksByProperty() {
		$snaksByProperty = array();

		foreach ( $this as $snak ) {
			/** @var Snak $snak */
			$propertyId = $snak->getPropertyId()->getSerialization();
			if ( !isset( $snaksByProperty[$propertyId] ) ) {
				$snaksByProperty[$propertyId] = array();
			}
			$snaksByProperty[$propertyId][] = $snak;
		}

		return $snaksByProperty;
	}

}
