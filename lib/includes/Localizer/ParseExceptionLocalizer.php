<?php

namespace Wikibase\Lib\Localizer;

use Exception;
use InvalidArgumentException;
use Message;
use ValueParsers\ParseException;

/**
 * Provides a Message for ParseException for localized errors.
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class ParseExceptionLocalizer implements ExceptionLocalizer {

	/**
	 * @see ExceptionLocalizer::getExceptionMessage()
	 *
	 * @param ParseException $exception
	 *
	 * @return Message
	 */
	public function getExceptionMessage( Exception $exception ) {
		if ( !$this->hasExceptionMessage( $exception ) ) {
			throw new InvalidArgumentException( '$exception is not a ParseException' );
		}

		$baseKey = 'wikibase-parse-error';
		$params = array();
		$msg = null;

		// Messages that can be used here:
		// * wikibase-parse-error
		// * wikibase-parse-error-coordinate
		// * wikibase-parse-error-entity-id
		// * wikibase-parse-error-quantity
		// * wikibase-parse-error-time
		$expectedFormat = $exception->getExpectedFormat();
		if( $expectedFormat !== null ) {
			$msg = new Message( $baseKey . '-' . $expectedFormat, $params );
			if( !$msg->exists() ) {
				$msg = null;
			}
		}

		if( $msg === null ) {
			$msg = new Message( $baseKey, $params );
		}

		return $msg;
	}

	/**
	 * @see ExceptionLocalizer::getExceptionMessage()
	 *
	 * @param Exception $exception
	 *
	 * @return bool
	 */
	public function hasExceptionMessage( Exception $exception ) {
		return $exception instanceof ParseException;
	}
}
