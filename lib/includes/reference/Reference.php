<?php

namespace Wikibase;

/**
 * Interface for objects that represent a single Wikibase reference.
 * See https://meta.wikimedia.org/wiki/Wikidata/Data_model#ReferenceRecords
 *
 * @since 0.1
 *
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
interface Reference extends \Hashable, \Comparable, \Immutable, \Countable {

	/**
	 * Returns the property snaks that make up this reference.
	 * Modification of the snaks should NOT happen through this getter.
	 *
	 * @since 0.1
	 *
	 * @return Snaks
	 */
	public function getSnaks();

}