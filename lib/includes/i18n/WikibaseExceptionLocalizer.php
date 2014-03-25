<?php

namespace Wikibase\i18n;

use Exception;
use InvalidArgumentException;
use Message;
use ValueParsers\ParseException;

/**
 * ExceptionLocalizer implementing localization of some well known types of exceptions
 * that may occur in the context of the Wikibase exception.
 *
 * This hardcodes knowledge about different kinds of exceptions. A more generic approach
 * based on a dispatcher can be implemented later if needed.
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class WikibaseExceptionLocalizer implements ExceptionLocalizer {

	/**
	 * @param Exception $ex
	 *
	 * @throws InvalidArgumentException If localization of the given exception is not supported.
	 *
	 * @return Message
	 */
	public function getExceptionMessage( Exception $ex ) {
		if ( $ex instanceof ParseException ) {
			return $this->getParseExceptionMessage( $ex );
		} else {
			return $this->getGenericExceptionMessage( $ex );
		}
	}

	/**
	 * @param ParseException $parseError
	 *
	 * @return Message
	 */
	protected function getParseExceptionMessage( ParseException $parseError ) {
		$key = 'wikibase-parse-error';
		$params = array();
		$msg = wfMessage( $key )->params( $params );

		return $msg;
	}

	/**
	 * @param Exception $error
	 *
	 * @return Message
	 */
	protected function getGenericExceptionMessage( Exception $error ) {
		$key = 'wikibase-unexpected-error';
		$params = array( $error->getMessage() );
		$msg = wfMessage( $key )->params( $params );

		return $msg;
	}

	/**
	 * Whether this localizer can handle the given exception.
	 *
	 * @param Exception $ex
	 *
	 * @return bool
	 */
	public function hasExceptionMessage( Exception $ex ) {
		return true;
	}

}
