<?php

namespace Wikibase;

/**
 * Interface for objects that represent a single Wikibase reference.
 * See https://meta.wikimedia.org/wiki/Wikidata/Data_model#ReferenceRecords
 *
 * @since 0.1, instantiable since 0.4
 *
 * @file
 * @ingroup WikibaseDataModel
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class Reference implements \Hashable, \Comparable, \Immutable, \Countable {

	/**
	 * The property snaks that make up this reference.
	 *
	 * @since 0.1
	 *
	 * @var Snaks
	 */
	protected $snaks;

	/**
	 * @since 0.1
	 *
	 * @param Snaks|null $snaks
	 */
	public function __construct( Snaks $snaks = null ) {
		$this->snaks = $snaks === null ? new SnakList() : $snaks;
	}

	/**
	 * Returns the property snaks that make up this reference.
	 * Modification of the snaks should NOT happen through this getter.
	 *
	 * @since 0.1
	 *
	 * @return Snaks
	 */
	public function getSnaks() {
		return $this->snaks;
	}

	/**
	 * @see Countable::count
	 *
	 * @since 0.3
	 *
	 * @return integer
	 */
	public function count() {
		return count( $this->snaks );
	}

	/**
	 * @see Hashable::getHash
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function getHash() {
		return $this->snaks->getHash();
	}

	/**
	 * @see Comparable::equals
	 *
	 * The comparison is done purely value based, ignoring the order of the snaks.
	 *
	 * @since 0.3
	 *
	 * @param mixed $mixed
	 *
	 * @return boolean
	 */
	public function equals( $mixed ) {
		return is_object( $mixed )
			&& $mixed instanceof Reference
			&& $this->getSnaks()->equals( $mixed->getSnaks() );
	}

}
