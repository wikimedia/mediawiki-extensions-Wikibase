<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Localizer;

use Exception;
use Message;

/**
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class GenericExceptionLocalizer implements ExceptionLocalizer {

	public function getExceptionMessage( Exception $exception ): Message {
		return wfMessage( 'wikibase-error-unexpected', $exception->getMessage() );
	}

	/**
	 * @return bool Always true, since DispatchingExceptionLocalizer is able to provide
	 *         a Message for any kind of exception.
	 */
	public function hasExceptionMessage( Exception $exception ): bool {
		return true;
	}

}
