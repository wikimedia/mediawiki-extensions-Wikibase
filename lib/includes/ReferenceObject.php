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
class ReferenceObject implements Reference {

	/**
	 * @since 0.1
	 *
	 * @var array of PropertySnak
	 */
	protected $snaks;

	/**
	 * @see Reference::getHash
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function getHash() {
		// TODO
	}

}
