<?php

namespace Wikibase;

/**
 * Implementation of the Snaks interface.
 * @see Snaks
 *
 * @since 0.1
 *
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class SnakList extends \GenericArrayObject implements Snaks {

	/**
	 * No, these ain't drugs.
	 *
	 * @since 0.1
	 *
	 * @var array [ snak hash (string) => snak offset (string|int) ]
	 */
	protected $snakHashes = array();

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
	 * @see GenericArrayObject::preSetElement
	 *
	 * @since 0.1
	 *
	 * @param int|string $index
	 * @param mixed $snak
	 *
	 * @return boolean
	 */
	protected function preSetElement( $index, $snak ) {
		/**
		 * @var Snak $snak
		 */
		if ( $this->hasSnak( $snak ) ) {
			return false;
		}
		else {
			$this->snakHashes[$snak->getHash()] = $index;
			return true;
		}
	}

	/**
	 * @see ArrayObject::offsetUnset
	 *
	 * @since 0.1
	 *
	 * @param mixed $index
	 */
	public function offsetUnset( $index ) {
		$snak = $this->offsetGet( $index );

		if ( $snak !== false ) {
			/**
			 * @var Snak $snak
			 */
			unset( $this->snakHashes[$snak->getHash()] );

			parent::offsetUnset( $index );
		}
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
		return array_key_exists( $snakHash, $this->snakHashes );
	}

	/**
	 * @see Snaks::removeSnakHash
	 *
	 * @since 0.1
	 *
	 * @param string $snakHash
	 */
	public function removeSnakHash( $snakHash ) {
		if ( $this->hasSnakHash( $snakHash ) ) {
			$this->offsetUnset( $this->snakHashes[$snakHash] );
		}
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
		$this->append( $snak );
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
		$this->hasSnakHash( $snak->getHash() );
	}

	/**
	 * @see Snaks::removeSnak
	 *
	 * @since 0.1
	 *
	 * @param Snak $snak
	 */
	public function removeSnak( Snak $snak ) {
		$this->removeSnakHash( $snak->getHash() );
	}

	/**
	 * @see Snaks::getSnak
	 *
	 * @since 0.1
	 *
	 * @param string $snakHash
	 *
	 * @return Snak|false
	 */
	public function getSnak( $snakHash ) {
		if ( $this->hasSnakHash( $snakHash ) ) {
			return $this->offsetGet( $this->snakHashes[$snakHash] );
		}
		else {
			return false;
		}
	}

	/**
	 * @see Snaks::getHash
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function getHash() {
		return md5( array_reduce(
			$this,
			function( $concaternation, Snak $snak ) {
				$concaternation .= $snak->getHash();
			},
			''
		) );
	}

}
