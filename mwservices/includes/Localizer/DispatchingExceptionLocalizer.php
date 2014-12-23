<?php

namespace Wikibase\Lib\Localizer;

use Exception;
use InvalidArgumentException;
use Message;

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
	 * @var ExceptionLocalizer[]
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
	 * @param Exception $exception
	 *
	 * @return Message
	 * @throws InvalidArgumentException
	 */
	public function getExceptionMessage( Exception $exception ) {
		$localizer = $this->getLocalizerForException( $exception );

		if ( $localizer ) {
			return $localizer->getExceptionMessage( $exception );
		}

		throw new InvalidArgumentException( 'ExceptionLocalizer not registered for exception type.' );
	}

	/**
	 * @see ExceptionLocalizer::getExceptionMessage()
	 *
	 * @param Exception $exception
	 *
	 * @return bool Always true, since DispatchingExceptionLocalizer is able to provide
	 *         a Message for any kind of exception.
	 */
	public function hasExceptionMessage( Exception $exception ) {
		$localizer = $this->getLocalizerForException( $exception );

		return $localizer ? true : false;
	}

	/**
	 * @param Exception $exception
	 *
	 * @return ExceptionLocalizer|null
	 */
	private function getLocalizerForException( Exception $exception ) {
		foreach( $this->localizers as $localizer ) {
			if ( $localizer->hasExceptionMessage( $exception ) ) {
				return $localizer;
			}
		}

		return null;
	}

}
