<?php

namespace Wikibase;

/**
 * Class that represents a single Wikibase claim.
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
class ClaimObject implements Claim {

	/**
	 * @since 0.1
	 *
	 * @var Snak
	 */
	protected $mainSnak;

	/**
	 * The property snaks that are qualifiers for this claim.
	 *
	 * @since 0.1
	 *
	 * @var Snaks
	 */
	protected $qualifiers;

	/**
	 * Constructor.
	 *
	 * @since 0.1
	 *
	 * @param Snak $mainSnak
	 * @param null|Snaks $qualifiers All elements in the Snaks object must be PropertySnak objects
	 */
	public function __construct( Snak $mainSnak, Snaks $qualifiers = null ) {
		$this->mainSnak = $mainSnak;
		$this->qualifiers = $qualifiers === null ? new SnakList() : $qualifiers;
	}

	/**
	 * @see Claim::getMainSnak
	 *
	 * @since 0.1
	 *
	 * @return Snak
	 */
	public function getMainSnak() {
		return $this->mainSnak;
	}

	/**
	 * @see Claim::setMainSnak
	 *
	 * @since 0.1
	 *
	 * @param Snak $mainSnak
	 */
	public function setMainSnak( Snak $mainSnak ) {
		$this->mainSnak = $mainSnak;
	}

	/**
	 * @see Claim::getQualifiers
	 *
	 * @since 0.1
	 *
	 * @return Snaks
	 */
	public function getQualifiers() {
		return $this->qualifiers;
	}

	/**
	 * @see Claim::setQualifiers
	 *
	 * @since 0.1
	 *
	 * @param Snaks $propertySnaks
	 */
	public function setQualifiers( Snaks $propertySnaks ) {
		$this->qualifiers = $propertySnaks;
	}

}
