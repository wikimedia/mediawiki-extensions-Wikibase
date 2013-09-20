<?php

namespace Wikibase;

/**
 * Implementation of the Snaks interface.
 * @see Snaks
 *
 * @since 0.1
 *
 * @file
 * @ingroup WikibaseDataModel
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
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
	 * @see Snaks::toArray
	 *
	 * @since 0.3
	 *
	 * @return string
	 */
	public function toArray() {
		$snaks = array();

		/**
		 * @var Snak $snak
		 */
		foreach ( $this as $snak ) {
			$snaks[] = $snak->toArray();
		}

		return $snaks;
	}

	/**
	 * Factory for constructing a SnakList from its array representation.
	 *
	 * @since 0.3
	 *
	 * @param array $data
	 *
	 * @return Snaks
	 */
	public static function newFromArray( array $data ) {
		$snaks = array();

		foreach ( $data as $snak ) {
			$snaks[] = SnakObject::newFromArray( $snak );
		}

		return new static( $snaks );
	}

}
