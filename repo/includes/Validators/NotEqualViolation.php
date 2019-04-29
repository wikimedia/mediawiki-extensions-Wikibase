<?php

namespace Wikibase\Repo\Validators;

use ValueValidators\Error;

/**
 * Represents a violation of a not equal constraint.
 * @license GPL-2.0-or-later
 * @author Greta Doci
 */
class NotEqualViolation extends Error {

	/**
	 * @see Error::__construct()
	 *
	 * @param string $text
	 * @param string $code
	 * @param mixed[] $params
	 */
	public function __construct( $text, $code, array $params ) {
		parent::__construct( $text, Error::SEVERITY_ERROR, null, $code, $params );
	}

}
