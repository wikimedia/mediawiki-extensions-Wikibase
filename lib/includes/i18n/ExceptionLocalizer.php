<?php

namespace Wikibase\i18n;

use Exception;
use InvalidArgumentException;
use Message;

/**
 * Interface for services that provide localized messages for various types of Exceptions.
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
interface ExceptionLocalizer {

	/**
	 * Returns a Message object containing a localized message representing the exception,
	 * in a form appropriate for display to the user.
	 *
	 * The localized message may or may not contain the text returned by $ex->getMessage().
	 *
	 * @param Exception $ex
	 *
	 * @throws InvalidArgumentException If localization of the given exception is not supported.
	 *
	 * @return Message
	 */
	function getExceptionMessage( Exception $ex );

	/**
	 * Whether this localizer can handle the given exception.
	 *
	 * This is intended for use by a dispatcher to determine which localizer
	 * can handle a given exception.
	 *
	 * @param Exception $ex
	 *
	 * @return bool
	 */
	function hasExceptionMessage( Exception $ex );

}
