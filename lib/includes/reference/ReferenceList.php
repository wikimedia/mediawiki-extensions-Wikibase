<?php

namespace Wikibase;

/**
 * Implementation of the References interface.
 * @see References
 *
 * @since 0.1
 *
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ReferenceList extends HashArray implements References {

	/**
	 * @see GenericArrayObject::getObjectType
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function getObjectType() {
		return '\Wikibase\Reference';
	}

	/**
	 * @see References::hasReferenceHash
	 *
	 * @since 0.1
	 *
	 * @param string $referenceHash
	 *
	 * @return boolean
	 */
	public function hasReferenceHash( $referenceHash ) {
		return $this->hasElementHash( $referenceHash );
	}

	/**
	 * @see References::removeReferenceHash
	 *
	 * @since 0.1
	 *
	 * @param string $referenceHash
	 */
	public function removeReferenceHash( $referenceHash ) {
		$this->removeByElementHash( $referenceHash );
	}

	/**
	 * @see References::addReference
	 *
	 * @since 0.1
	 *
	 * @param Reference $reference
	 *
	 * @return boolean Indicates if the reference was added or not.
	 */
	public function addReference( Reference $reference ) {
		return $this->addElement( $reference );
	}

	/**
	 * @see References::hasReference
	 *
	 * @since 0.1
	 *
	 * @param Reference $reference
	 *
	 * @return boolean
	 */
	public function hasReference( Reference $reference ) {
		return $this->hasElementHash( $reference->getHash() );
	}

	/**
	 * @see References::removeReference
	 *
	 * @since 0.1
	 *
	 * @param Reference $reference
	 */
	public function removeReference( Reference $reference ) {
		$this->removeByElementHash( $reference->getHash() );
	}

	/**
	 * @see References::getReference
	 *
	 * @since 0.1
	 *
	 * @param string $referenceHash
	 *
	 * @return Reference|false
	 */
	public function getReference( $referenceHash ) {
		return $this->getByElementHash( $referenceHash );
	}

}
