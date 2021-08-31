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

	public const MAIN_SNAK = 0;
	public const QUALIFIER = 1;

	private function __construct() {
	}

}
