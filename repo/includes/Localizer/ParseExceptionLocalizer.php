<?php

namespace Wikibase\Repo\Localizer;

use Exception;
use InvalidArgumentException;
use Message;
use ValueParsers\ParseException;

/**
 * Provides a Message for ParseException for localized errors.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class ParseExceptionLocalizer implements ExceptionLocalizer {

	/**
	 * @see ExceptionLocalizer::getExceptionMessage()
	 *
	 * @param Exception $exception
	 *
	 * @return Message
	 * @throws InvalidArgumentException
	 */
	public function getExceptionMessage( Exception $exception ) {
		if ( !$this->hasExceptionMessage( $exception ) ) {
			throw new InvalidArgumentException( '$exception is not a ParseException' );
		}

		$msg = null;

		/** @var ParseException $exception */
		'@phan-var ParseException $exception';
		$expectedFormat = $exception->getExpectedFormat();
		if ( $expectedFormat !== null ) {
			// Messages:
			// wikibase-parse-error-coordinate
			// wikibase-parse-error-entity-id
			// wikibase-parse-error-quantity
			// wikibase-parse-error-time
			$msg = new Message( 'wikibase-parse-error-' . $expectedFormat );
		}

		if ( !( $msg instanceof Message ) || !$msg->exists() ) {
			$msg = new Message( 'wikibase-parse-error' );
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
