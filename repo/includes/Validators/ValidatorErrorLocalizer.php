<?php

namespace Wikibase\Validators;

use Message;
use Status;
use ValueFormatters\FormattingException;
use ValueFormatters\ValueFormatter;
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
	 * @var ValueFormatter
	 */
	protected $paramFormatter;

	/**
	 * @param ValueFormatter $paramFormatter A formatter for formatting message parameters.
	 *        Typically some kind of dispatcher. If not provided, naive formatting will be used,
	 *        which will fail on non-primitive parameters.
	 */
	function __construct( ValueFormatter $paramFormatter = null ) {
		$this->paramFormatter = $paramFormatter;
	}

	/**
	 * Returns a Status representing the given validation result.
	 *
	 * @param Result $result
	 * @return Status
	 */
	public function getResultStatus( Result $result ) {
		$status = Status::newGood();
		$status->setResult( $result->isValid() );

		foreach ( $result->getErrors() as $error ) {
			$msg = $this->getErrorMessage( $error );
			$status->fatal( $msg );
		}

		return $status;
	}

	/**
	 * Returns a Message representing the given error.
	 * This can be used for reporting validation failures.
	 *
	 * @param Error $error
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

		foreach ( $params as &$param ) {
			if ( $this->paramFormatter === null ) {
				//naive mode, will fail for objects and arrays!
				$param = "$param";
			} elseif ( !is_string( $param ) ) {
				try {
					$param = $this->paramFormatter->format( $param );
				} catch ( FormattingException $e ) {
					$param = "$param";
				}
			}
		}

		return wfMessage( $key, $params );
	}
}