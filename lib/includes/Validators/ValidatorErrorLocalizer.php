<?php

namespace Wikibase\Validators;

use ValueValidators\Error;

/**
 * Class ValidatorErrorLocalizer
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class ValidatorErrorLocalizer {

	public function getErrorMessage( Error $error ) {
		// Give grep a chance to find the usages:
		// wikibase-validator-bad-type, wikibase-validator-too-long, wikibase-validator-too-short,
		// wikibase-validator-malformed-value, wikibase-validator-bad-entity-id,
		// wikibase-validator-bad-entity-type, wikibase-validator-no-such-entity,
		// wikibase-validator-no-such-property, wikibase-validator-bad-value,
		// wikibase-validator-bad-value-type
		$key = 'wikibase-validator-' . $error->getCode();
		$params = $error->getParameters();

		//TODO: look for non-string in $params and run them through an appropriate formatter

		return wfMessage( $key, $params );
	}
}