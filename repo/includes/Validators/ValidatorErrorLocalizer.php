<?php

namespace Wikibase\Repo\Validators;

use Message;
use Status;
use ValueFormatters\FormattingException;
use ValueFormatters\ValueFormatter;
use ValueValidators\Error;
use ValueValidators\Result;

/**
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class ValidatorErrorLocalizer {

	/**
	 * @var ValueFormatter|null Formatter for generating wikitext for message parameters.
	 */
	private $paramFormatter;

	/**
	 * @param ValueFormatter|null $paramFormatter Optional formatter for formatting message
	 * parameters. MUST return wikitext. This is typically some kind of dispatcher. If not
	 * provided, naive formatting will be used, which will fail on non-primitive parameters.
	 */
	public function __construct( ValueFormatter $paramFormatter = null ) {
		$this->paramFormatter = $paramFormatter;
	}

	/**
	 * Returns a Status representing the given validation result.
	 *
	 * @param Result $result
	 *
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
	 *
	 * @return Message
	 */
	public function getErrorMessage( Error $error ) {
		// Messages:
		// wikibase-validator-alias-too-long, wikibase-validator-bad-data-type, wikibase-validator-bad-entity-id,
		// wikibase-validator-bad-entity-type, wikibase-validator-bad-type, wikibase-validator-bad-url,
		// wikibase-validator-bad-url-scheme, wikibase-validator-bad-value, wikibase-validator-bad-value-type,
		// wikibase-validator-description-too-long, wikibase-validator-invalid, wikibase-validator-label-too-long,
		// wikibase-validator-malformed-value, wikibase-validator-missing-field, wikibase-validator-no-such-entity,
		// wikibase-validator-no-such-property, wikibase-validator-no-validators, wikibase-validator-not-allowed,
		// wikibase-validator-too-high, wikibase-validator-too-long, wikibase-validator-too-low,
		// wikibase-validator-too-short, wikibase-validator-unknown-unit, wikibase-validator-url-scheme-missing,
		// wikibase-validator-no-mul-descriptions, wikibase-validator-bad-prefix
		$key = 'wikibase-validator-' . $error->getCode();
		$params = $error->getParameters();

		foreach ( $params as &$param ) {
			if ( !is_string( $param ) ) {
				$param = $this->paramToString( $param );
			}
		}

		return new Message( $key, $params );
	}

	/**
	 * @param mixed $param
	 *
	 * @return string wikitext
	 */
	private function paramToString( $param ) {
		if ( $this->paramFormatter !== null ) {
			try {
				return $this->paramFormatter->format( $param );
			} catch ( FormattingException $ex ) {
				// ok, never mind, use naive version below.
				wfWarn( __METHOD__ . ': Formatting of message parameter fialed: ' . $ex->getMessage() );
			}
		}

		return wfEscapeWikiText( strval( $param ) );
	}

}
