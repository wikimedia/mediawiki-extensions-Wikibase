<?php

namespace Wikibase\Lib;

/**
 * Globally Unique IDentifier generator.
 *
 * @since 0.3
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
interface GuidGenerator {

	/**
	 * Generates and returns a Globally Unique Identifier.
	 *
	 * @since 0.3
	 *
	 * @return string
	 */
	public function newGuid();

}
