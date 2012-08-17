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
class ClaimObject implements  Claim {

	/**
	 * @since 0.1
	 *
	 * @var Snak
	 */
	protected $mainSnak;

	/**
	 * @since 0.1
	 *
	 * @var Snaks (each element being a PropertySnak)
	 */
	protected $qualifiers;

}