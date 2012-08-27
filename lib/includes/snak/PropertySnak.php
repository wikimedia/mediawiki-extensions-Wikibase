<?php

namespace Wikibase;

/**
 * Interface for objects that represent a single Wikibase snak.
 * See https://meta.wikimedia.org/wiki/Wikidata/Data_model#Snaks
 *
 * @since 0.1
 *
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
interface PropertySnak extends Snak {

	/**
	 * Returns the id of the snaks property.
	 *
	 * @since 0.1
	 *
	 * @return integer
	 */
	public function getPropertyId();

}