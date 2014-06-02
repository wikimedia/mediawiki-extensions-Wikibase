<?php

namespace Wikibase\DataModel\Snak;

use Wikibase\DataModel\HashArray;
use Wikibase\DataModel\MapHasher;
use Wikibase\DataModel\MapValueHasher;

/**
 * Implementation of the Snaks interface.
 * @see Snaks
 *
 * @since 0.1
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Adam Shorland
 */
class SnakList extends HashArray implements Snaks {

	/**
	 * @see GenericArrayObject::getObjectType
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function getObjectType() {
		return '\Wikibase\Snak';
	}

	/**
	 * @see Snaks::hasSnakHash
	 *
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
	 * @see Snaks::removeSnakHash
	 *
	 * @since 0.1
	 *
	 * @param string $snakHash
	 */
	public function removeSnakHash( $snakHash ) {
		$this->removeByElementHash( $snakHash );
	}

	/**
	 * @see Snaks::addSnak
	 *
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
	 * @see Snaks::hasSnak
	 *
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
	 * @see Snaks::removeSnak
	 *
	 * @since 0.1
	 *
	 * @param Snak $snak
	 */
	public function removeSnak( Snak $snak ) {
		$this->removeByElementHash( $snak->getHash() );
	}

	/**
	 * @see Snaks::getSnak
	 *
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
	 * @param $order array of serliazed propertyIds to order by.
	 *
	 * @since 0.5
	 */
	public function orderByProperty( $order = array() ) {
		$snaksByProperty = $this->getSnaksByProperty();
		$orderedProperties = array_unique( array_merge( $order, array_keys( $snaksByProperty ) ) );

		foreach( $orderedProperties as $property ){
			$snaks = $snaksByProperty[ $property ];
			$this->moveSnaksToBottom( $snaks );
		}
	}

	/**
	 * @param array $snaks to remove and re add
	 */
	private function moveSnaksToBottom( $snaks ) {
		foreach( $snaks as $snak ) {
			$this->removeSnak( $snak );
			$this->addSnak( $snak );
		}
	}

	/**
	 * Gets the snaks in the current object in an array
	 * grouped by property id
	 *
	 * @return array
	 */
	private function getSnaksByProperty() {
		$snaksByProperty = array();

		foreach( $this as $snak ) {
			/** @var Snak $snak */
			$propertyId = $snak->getPropertyId()->getSerialization();
			if( !isset( $snaksByProperty[$propertyId] ) ) {
				$snaksByProperty[$propertyId] = array();
			}
			$snaksByProperty[$propertyId][] = $snak;
		}

		return $snaksByProperty;
	}

}
