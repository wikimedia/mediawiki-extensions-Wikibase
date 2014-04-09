<?php

namespace Wikibase\Validators;

use Message;
use Status;
use ValueValidators\Error;
use ValueValidators\Result;

/**
 * Class ValidatorErrorLocalizer
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class ValidatorErrorLocalizer {

	/**
	 * @param Result $result
	 *
	 * @return Status
	 */
	public function getResultStatus( Result $result ) {
		if ( $result->isValid() ) {
			return Status::newGood();
		}

		$status = Status::newGood();
		$status->setResult( false );

		foreach ( $result->getErrors() as $error ) {
			$message = $this->getErrorMessage( $error );
			$status->error( $message );
		}

		return $status;
	}

	/**
	 * @param Error $error
	 *
	 * @return Message
	 */
	public function getErrorMessage( Error $error ) {
		// Messages:
		// wikibase-validator-bad-type, wikibase-validator-too-long, wikibase-validator-too-short,
		// wikibase-validator-too-high, wikibase-validator-too-low,
		// wikibase-validator-malformed-value, wikibase-validator-bad-entity-id,
		// wikibase-validator-bad-entity-type, wikibase-validator-no-such-entity,
		// wikibase-validator-no-such-property, wikibase-validator-bad-value,
		// wikibase-validator-bad-value-type, wikibase-validator-bad-url,
		// wikibase-validator-bad-url-scheme, wikibase-validator-bad-http-url,
		// wikibase-validator-bad-mailto-url, wikibase-validator-unknown-unit
		$key = 'wikibase-validator-' . $error->getCode();
		$params = $error->getParameters();

		//TODO: look for non-string in $params and run them through an appropriate formatter

		return wfMessage( $key, $params );
	}
}