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
interface Reference extends Hashable {

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