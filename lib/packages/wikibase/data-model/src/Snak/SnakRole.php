<?php

namespace Wikibase\DataModel\Snak;

/**
 * Enum with snak roles.
 *
 * @since 0.4
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class SnakRole {

	const MAIN_SNAK = 0;
	const QUALIFIER = 1;

	private function __construct() {
	}

}
