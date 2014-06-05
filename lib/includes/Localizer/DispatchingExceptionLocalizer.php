<?php

namespace Wikibase\Lib\Localizer;

use Exception;

/**
 * ExceptionLocalizer implementing localization of some well known types of exceptions
 * that may occur in the context of the Wikibase exception, as provided in $localizers.
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class DispatchingExceptionLocalizer implements ExceptionLocalizer {

	/**
	 * @var array
	 */
	private $localizers;

	/**
	 * @param ExceptionLocalizer[] $localizers
	 */
	public function __construct( array $localizers ) {
		$this->localizers = $localizers;
	}

	/**
	 * @see ExceptionLocalizer::getExceptionMessage()
	 *
	 * @param Exception $ex
	 *
	 * @return Message
	 */
	public function getExceptionMessage( Exception $ex ) {
		foreach( $this->localizers as $localizer ) {
			if ( $localizer->hasExceptionMessage( $ex ) ) {
				return $localizer->getExceptionMessage( $ex );
			}
		}

		return $this->getGenericExceptionMessage( $ex );
	}

	/**
	 * @param Exception $error
	 *
	 * @return Message
	 */
	protected function getGenericExceptionMessage( Exception $error ) {
		$key = 'wikibase-error-unexpected';
		$params = array( $error->getMessage() );
		$msg = wfMessage( $key )->params( $params );

		return $msg;
	}

	/**
	 * @see ExceptionLocalizer::getExceptionMessage()
	 *
	 * @param Exception $ex
	 *
	 * @return bool Always true, since DispatchingExceptionLocalizer is able to provide
	 *         a Message for any kind of exception.
	 */
	public function hasExceptionMessage( Exception $ex ) {
		return true;
	}
}
