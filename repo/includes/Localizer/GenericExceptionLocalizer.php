<?php

namespace Wikibase\Repo\Localizer;

use Exception;
use Message;

/**
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class GenericExceptionLocalizer implements ExceptionLocalizer {

	/**
	 * @see ExceptionLocalizer::getExceptionMessage()
	 *
	 * @param Exception $exception
	 *
	 * @return Message
	 */
	public function getExceptionMessage( Exception $exception ) {
		return wfMessage( 'wikibase-error-unexpected', $exception->getMessage() );
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
		return true;
	}

}
