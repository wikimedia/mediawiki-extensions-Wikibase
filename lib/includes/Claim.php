<?php

namespace Wikibase;

/**
 * Interface for objects that represent a single Wikibase claim.
 * See https://meta.wikimedia.org/wiki/Wikidata/Data_model#Statements
 *
 * @since 0.1
 *
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
interface Claim {

	/**
	 * Returns the value snak.
	 *
	 * @since 0.1
	 *
	 * @return Snak
	 */
	public function getMainSnak();

	/**
	 * Returns the main snak.
	 *
	 * @since 0.1
	 *
	 * @param Snak $mainSnak
	 */
	public function setMainSnak( Snak $mainSnak );

	/**
	 * Adds the provided property snak to the list of qualifiers.
	 *
	 * @since 0.1
	 *
	 * @param PropertySnak $snak
	 */
	public function addQualifier( PropertySnak $snak );

	/**
	 * Removes the provided property snak to the list of qualifiers, if it exists.
	 *
	 * @since 0.1
	 *
	 * @param PropertySnak $snak
	 */
	public function removeQualifier( PropertySnak $snak );

	/**
	 * Gets the property snaks making up the qualifiers for this claim.
	 *
	 * @since 0.1
	 *
	 * @return array of PropertySnak
	 */
	public function getQualifiers();

}