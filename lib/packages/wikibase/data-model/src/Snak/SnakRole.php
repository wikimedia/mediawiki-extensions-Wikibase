<?php

namespace Wikibase\DataModel\Snak;

/**
 * Enum with snak roles.
 *
 * @since 0.4
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class SnakRole {

	const MAIN_SNAK = 0;
	const QUALIFIER = 1;

	private function __construct() {
	}

}
