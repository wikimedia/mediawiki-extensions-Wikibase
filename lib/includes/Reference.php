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
	 * Returns the property snaks that make up this reference.
	 *
	 * @since 0.1
	 *
	 * @return Snaks
	 */
	public function getSnaks();

	/**
	 * Sets the snaks that make up this reference.
	 *
	 * @since 0.1
	 *
	 * @param Snaks $propertySnaks
	 */
	public function setSnaks( Snaks $propertySnaks );

}