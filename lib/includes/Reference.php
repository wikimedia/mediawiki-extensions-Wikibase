<?php

namespace Wikibase;

/**
 * Interface for objects that represent a single Wikibase reference.
 * See https://meta.wikimedia.org/wiki/Wikidata/Data_model#ReferenceRecords
 *
 * @since 0.1
 *
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
interface Reference {

	/**
	 * Returns a hash that can be used to identify the reference within a list of references (ie a statement).
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function getHash();

	/**
	 * Returns the snaks that make up this reference.
	 *
	 * @since 0.1
	 *
	 * @return array of PropertySnak
	 */
	public function getSnaks();

	/**
	 * Sets the snaks that make up this reference.
	 *
	 * @since 0.1
	 *
	 * @param $snaks array of PropertySnak
	 */
	public function setSnaks( array $snaks );

	/**
	 * Creates a new reference given the statement it will belong to and the property snaks it will consist of.
	 *
	 * @since 0.1
	 *
	 * @param Statement $statement
	 * @param $propertySnaks array of PropertySnak
	 *
	 * @return Reference
	 */
	public static function newFromSnaks( Statement $statement, array $propertySnaks );

}