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
	 * Gets the property snaks making up the qualifiers for this claim.
	 *
	 * @since 0.1
	 *
	 * @return Snaks
	 */
	public function getQualifiers();

	/**
	 * Sets the property snaks making up the qualifiers for this claim.
	 *
	 * @since 0.1
	 *
	 * @param Snaks $propertySnaks
	 */
	public function setQualifiers( Snaks $propertySnaks );

}
