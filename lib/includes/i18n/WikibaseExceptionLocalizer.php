<?php

namespace Wikibase\i18n;

use Exception;
use Message;
use MessageException;
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
	 * @see ExceptionLocalizer::getExceptionMessage()
	 *
	 * @param Exception $ex
	 *
	 * @return Message
	 */
	public function getExceptionMessage( Exception $ex ) {
		if ( $ex instanceof MessageException ) {
			return $this->getMessageExceptionMessage( $ex );
		} elseif ( $ex instanceof ParseException ) {
			return $this->getParseExceptionMessage( $ex );
		} else {
			return $this->getGenericExceptionMessage( $ex );
		}
	}

	/**
	 * @param MessageException $messageException
	 *
	 * @return Message
	 */
	protected function getMessageExceptionMessage( MessageException $messageException ) {
		$key = $messageException->getKey();
		$params = $messageException->getParams();
		$msg = wfMessage( $key )->params( $params );

		return $msg;
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
	 * @see ExceptionLocalizer::getExceptionMessage()
	 *
	 * @param Exception $ex
	 *
	 * @return bool Always true, since WikibaseExceptionLocalizer is able to provide
	 *         a Message for any kind of exception.
	 */
	public function hasExceptionMessage( Exception $ex ) {
		return true;
	}
}
